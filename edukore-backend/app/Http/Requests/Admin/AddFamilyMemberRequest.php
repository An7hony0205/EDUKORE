<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida la adición de un miembro (apoderado/guardian) a una familia.
 *
 * REGLA DE SEGURIDAD CRÍTICA: El user_id se valida contra la tabla users
 * filtrando por tenant_id del admin autenticado. Esto impide que un admin
 * de tenant A añada a su familia un usuario que pertenece a tenant B (IDOR).
 */
class AddFamilyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'user_id' => [
                'required',
                'uuid',
                // Aislamiento multi-tenant: el usuario debe pertenecer al mismo colegio.
                Rule::exists('users', 'id')->where('tenant_id', $tenantId),
                // No puede ser ya miembro de esta misma familia.
                Rule::unique('family_members')->where(function ($query) {
                    return $query->where('family_id', $this->route('family')->id);
                }),
            ],
            'relation_type'      => 'required|string|max:100',
            'is_primary_contact' => 'boolean',
            'can_view_info'      => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required'      => 'El usuario es obligatorio.',
            'user_id.exists'        => 'El usuario no existe en este colegio.',
            'user_id.unique'        => 'Este usuario ya es miembro de esta familia.',
            'relation_type.required' => 'El tipo de relación es obligatorio.',
        ];
    }
}
