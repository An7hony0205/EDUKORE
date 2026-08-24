<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    /**
     * La autorización se delega al middleware de rol (role:admin|super_admin).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas para crear un nuevo curso.
     * La unicidad se restringe al tenant activo del usuario autenticado.
     */
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('courses')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('courses')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del curso es obligatorio.',
            'name.unique'   => 'Ya existe un curso con ese nombre en este colegio.',
            'code.unique'   => 'Ya existe un curso con ese código en este colegio.',
        ];
    }
}
