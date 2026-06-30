<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;

class StudentFinancesController extends Controller
{
    public function show(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Ensure student exists and belongs to tenant
        $student = Student::where('tenant_id', $tenantId)->findOrFail($id);

        $fees = Fee::with(['feeType', 'discounts', 'payments' => function ($query) {
                // Return all payments including voided to maintain audit trail in UI
                $query->orderBy('created_at', 'desc');
            }])
            ->where('tenant_id', $tenantId)
            ->where('student_id', $id)
            ->orderBy('due_date', 'desc')
            ->get();

        // Calcular resumen financiero
        $totalDebt = 0;
        $totalPaid = 0;
        $totalOverdue = 0;

        foreach ($fees as $fee) {
            $expected = $fee->amount + $fee->tax_amount + $fee->penalty_amount - $fee->discount_amount;
            $paid = $fee->payments->where('status', 'completed')->sum('amount_paid');
            
            $totalDebt += $expected;
            $totalPaid += $paid;
            
            if ($fee->status === 'overdue' || (now()->startOfDay() > $fee->due_date && $fee->status !== 'paid' && $fee->status !== 'cancelled')) {
                $totalOverdue += ($expected - $paid);
            }
        }

        return response()->json([
            'summary' => [
                'total_expected' => round($totalDebt, 2),
                'total_paid' => round($totalPaid, 2),
                'balance_due' => round($totalDebt - $totalPaid, 2),
                'total_overdue' => round($totalOverdue, 2),
            ],
            'fees' => $fees
        ]);
    }
}
