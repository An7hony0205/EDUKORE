<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\DB;
use App\Contracts\PaymentProviderInterface;
use App\Services\Payments\ManualPaymentProvider;

class PaymentController extends Controller
{
    protected PaymentProviderInterface $paymentProvider;

    public function __construct()
    {
        // En el futuro, esto se inyectaría via el Service Container dependiendo de la pasarela configurada
        $this->paymentProvider = new ManualPaymentProvider();
    }

    public function index(Request $request)
    {
        $payments = Payment::with(['fee', 'user'])
            ->where('tenant_id', auth()->user()->tenant_id)
            ->latest()
            ->paginate(15);
            
        return response()->json($payments);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fee_id' => 'required|exists:fees,id',
            'amount_paid' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'reference' => 'nullable|string',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $fee = Fee::where('tenant_id', $tenantId)->findOrFail($request->fee_id);

        if (in_array($fee->status, ['paid', 'cancelled'])) {
            return response()->json(['message' => 'Esta deuda ya está pagada o cancelada.'], 400);
        }

        try {
            DB::beginTransaction();

            // 1. Cobro en la pasarela / proveedor
            $providerResponse = $this->paymentProvider->charge(
                $request->amount_paid,
                $fee->currency,
                [
                    'type' => $request->payment_method,
                    'reference' => $request->reference
                ],
                ['fee_id' => $fee->id, 'student_id' => $fee->student_id]
            );

            // 2. Registrar el Pago (Inmutable)
            $payment = Payment::create([
                'tenant_id' => $tenantId,
                'fee_id' => $fee->id,
                'user_id' => auth()->id(),
                'amount_paid' => $request->amount_paid,
                'payment_date' => now(),
                'payment_method' => $request->payment_method,
                'transaction_id' => $providerResponse['transaction_id'],
                'metadata' => $providerResponse['metadata'],
                'status' => 'completed'
            ]);

            // 3. Registrar el Movimiento en el Ledger (Entrada de dinero)
            $transaction = FinancialTransaction::create([
                'tenant_id' => $tenantId,
                'type' => 'credit',
                'amount' => $request->amount_paid,
                'currency' => $fee->currency,
                'description' => "Pago recibido para deuda #{$fee->id}",
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'created_by' => auth()->id()
            ]);

            // 4. Actualizar Estado de la Deuda (Fee)
            // Calcular el total esperado (Monto + Impuesto + Mora - Descuento)
            $expectedTotal = $fee->amount + $fee->tax_amount + $fee->penalty_amount - $fee->discount_amount;
            
            // Sumar todos los pagos exitosos anteriores + este
            $totalPaidSoFar = Payment::where('fee_id', $fee->id)
                                     ->where('status', 'completed')
                                     ->sum('amount_paid');

            if ($totalPaidSoFar >= $expectedTotal) {
                $fee->status = 'paid';
            } else {
                $fee->status = 'partial';
            }
            $fee->save();

            DB::commit();

            return response()->json(['message' => 'Pago procesado exitosamente', 'payment' => $payment], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error($e);
            return response()->json(['message' => 'Error procesando el pago.', 'error' => $e->getMessage()], 500);
        }
    }

    public function voidPayment(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $payment = Payment::where('tenant_id', $tenantId)->findOrFail($id);

        if ($payment->status === 'voided') {
            return response()->json(['message' => 'Este pago ya ha sido anulado.'], 400);
        }

        try {
            DB::beginTransaction();

            // 1. Reversar en el proveedor
            $refundResponse = $this->paymentProvider->refund($payment->transaction_id, $payment->amount_paid, 'Anulación solicitada por usuario');

            // 2. Marcar pago como anulado
            $payment->status = 'voided';
            $payment->metadata = array_merge($payment->metadata ?? [], ['voided_at' => now(), 'refund_tx' => $refundResponse['transaction_id']]);
            $payment->save();

            // 3. Crear movimiento de contrapartida (Debit) en el Ledger
            FinancialTransaction::create([
                'tenant_id' => $tenantId,
                'type' => 'debit',
                'amount' => $payment->amount_paid,
                'currency' => $payment->fee->currency,
                'description' => "Anulación de pago #{$payment->id}",
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'created_by' => auth()->id()
            ]);

            // 4. Recalcular la deuda
            $fee = $payment->fee;
            $expectedTotal = $fee->amount + $fee->tax_amount + $fee->penalty_amount - $fee->discount_amount;
            $totalPaidSoFar = Payment::where('fee_id', $fee->id)
                                     ->where('status', 'completed')
                                     ->sum('amount_paid');

            if ($totalPaidSoFar == 0) {
                // If it's overdue according to date, mark overdue, else pending
                $fee->status = (now()->startOfDay() > $fee->due_date) ? 'overdue' : 'pending';
            } else if ($totalPaidSoFar < $expectedTotal) {
                $fee->status = 'partial';
            } else {
                $fee->status = 'paid';
            }
            $fee->save();

            DB::commit();

            return response()->json(['message' => 'Pago anulado exitosamente.', 'payment' => $payment]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al anular el pago.', 'error' => $e->getMessage()], 500);
        }
    }
}
