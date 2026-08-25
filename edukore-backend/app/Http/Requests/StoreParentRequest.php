<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Middleware de rol ya filtra acceso
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            // ─── Datos del User ───────────────────────────────────────────────────
            'name'            => ['required', 'string', 'max:255'],
            'last_name'       => ['required', 'string', 'max:255'],
            'email'           => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],

            // ─── DNI: único en la tabla parents por tenant ────────────────────────
            'document_number' => [
                'required',
                'string',
                'max:30',
                // Unicidad del DNI dentro del tenant via join
                Rule::unique('parents', 'document_number'),
            ],

            'phone'           => ['nullable', 'string', 'max:30'],
            'address'         => ['nullable', 'string', 'max:255'],
            'occupation'      => ['nullable', 'string', 'max:100'],

            // ─── Hijos: array obligatorio, cada ID debe ser UUID del mismo tenant ─
            'student_ids'     => ['required', 'array', 'min:1'],
            'student_ids.*'   => [
                'required',
                'uuid',
                // El estudiante debe existir Y pertenecer al mismo tenant que el admin
                Rule::exists('students', 'id')->where(function ($query) use ($tenantId) {
                    // Los estudiantes se vinculan al tenant a través de su user
                    $query->whereIn('user_id', function ($q) use ($tenantId) {
                        $q->select('id')
                          ->from('users')
                          ->where('tenant_id', $tenantId);
                    });
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'            => 'El nombre es obligatorio.',
            'last_name.required'       => 'El apellido es obligatorio.',
            'email.required'           => 'El correo electrónico es obligatorio.',
            'email.unique'             => 'Ya existe un usuario con este correo.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.unique'   => 'Ya existe un padre registrado con este DNI.',
            'student_ids.required'     => 'Debe vincular al menos un estudiante.',
            'student_ids.*.uuid'       => 'El ID del estudiante no tiene un formato válido.',
            'student_ids.*.exists'     => 'Uno o más estudiantes no existen o no pertenecen a su institución.',
        ];
    }
}
