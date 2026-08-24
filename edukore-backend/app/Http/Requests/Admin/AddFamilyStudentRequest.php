<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida la vinculación de un estudiante a una familia.
 *
 * REGLA DE SEGURIDAD CRÍTICA: El student_id se valida contra la tabla students
 * unida a users, filtrando por tenant_id. Esto evita que un admin vincule
 * un estudiante de otro colegio a una familia (IDOR cross-tenant).
 */
class AddFamilyStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'student_id' => [
                'required',
                'uuid',
                // Aislamiento multi-tenant: el estudiante debe ser del mismo colegio.
                // Se verifica a través de la relación students → users (tenant_id).
                Rule::exists('students', 'id')->where(function ($query) use ($tenantId) {
                    $query->whereIn('user_id', function ($subQuery) use ($tenantId) {
                        $subQuery->select('id')
                            ->from('users')
                            ->where('tenant_id', $tenantId);
                    });
                }),
                // No puede estar ya vinculado a esta familia.
                Rule::unique('family_students')->where(function ($query) {
                    return $query->where('family_id', $this->route('family')->id);
                }),
            ],
            'relation_description' => 'nullable|string|max:255',
            'is_primary'           => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'El estudiante es obligatorio.',
            'student_id.exists'   => 'El estudiante no existe en este colegio.',
            'student_id.unique'   => 'Este estudiante ya está vinculado a esta familia.',
        ];
    }
}
