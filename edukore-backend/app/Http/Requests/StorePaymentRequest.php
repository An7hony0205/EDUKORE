<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    /**
     * La autorización se delega al middleware de rol definido en la ruta
     * (role:admin|super_admin). No se necesita lógica adicional aquí.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para registrar un nuevo pago.
     */
    public function rules(): array
    {
        return [
            // ── PARCHE CROSS-TENANT ────────────────────────────────────────────
            // 'exists:fees,id' sin tenant_id permitiría que un admin de tenant A
            // registre un pago contra una deuda (fee) del tenant B.
            // Rule::exists con ->where('tenant_id') ata la validación al tenant
            // del usuario autenticado, cerrando el vector de fuga de datos.
            'fee_id' => [
                'required',
                'uuid',
                Rule::exists('fees', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
            'amount_paid'    => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,transfer,card,check,other',
            'reference'      => 'nullable|string|max:255',
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
    public function messages(): array
    {
        return [
            'fee_id.required'         => 'La deuda (fee_id) es obligatoria.',
            'fee_id.exists'           => 'La deuda especificada no existe.',
            'amount_paid.required'    => 'El monto del pago es obligatorio.',
            'amount_paid.min'         => 'El monto debe ser mayor a cero.',
            'payment_method.required' => 'El método de pago es obligatorio.',
            'payment_method.in'       => 'Método de pago no válido. Opciones: cash, transfer, card, check, other.',
        ];
    }
}
