<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFamilyRequest;
use App\Http\Requests\Admin\AddFamilyMemberRequest;
use App\Http\Requests\Admin\AddFamilyStudentRequest;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyStudent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FamilyController extends Controller
{
    // ── Utilidad privada ──────────────────────────────────────────────────────

    /**
     * Retorna la familia si pertenece al tenant del usuario autenticado.
     * Lanza 404 si no existe o pertenece a otro tenant (aislamiento multi-tenant).
     */
    private function findForTenant(string $id): Family
    {
        return Family::where('tenant_id', auth()->user()->tenant_id)
            ->with(['members.user', 'students'])
            ->findOrFail($id);
    }

    // ── CRUD de Familia ───────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $families = Family::where('tenant_id', auth()->user()->tenant_id)
            ->withCount(['members', 'students'])
            ->orderBy('name')
            ->get();

        return response()->json($families);
    }

    public function store(StoreFamilyRequest $request): JsonResponse
    {
        $family = Family::create([
            'id'        => Str::uuid(),
            'tenant_id' => auth()->user()->tenant_id,
            'name'      => $request->validated()['name'],
        ]);

        return response()->json($family, 201);
    }

    public function show(string $id): JsonResponse
    {
        $family = $this->findForTenant($id);

        return response()->json($family);
    }

    public function update(StoreFamilyRequest $request, string $id): JsonResponse
    {
        $family = $this->findForTenant($id);
        $family->update($request->validated());

        return response()->json($family);
    }

    public function destroy(string $id): JsonResponse
    {
        $family = $this->findForTenant($id);
        $family->delete();

        return response()->json(['message' => 'Familia eliminada correctamente.']);
    }

    // ── Gestión de Miembros (Apoderados) ──────────────────────────────────────

    /**
     * Agrega un apoderado/guardian como miembro de la familia.
     * No permite duplicados (validado en AddFamilyMemberRequest).
     */
    public function addMember(AddFamilyMemberRequest $request, string $familyId): JsonResponse
    {
        // Valida que la familia pertenece al tenant (sin cargar relaciones extra)
        $family = Family::where('tenant_id', auth()->user()->tenant_id)->findOrFail($familyId);

        $data = $request->validated();

        $member = FamilyMember::create([
            'family_id'          => $family->id,
            'user_id'            => $data['user_id'],
            'relation_type'      => $data['relation_type'],
            'is_primary_contact' => $data['is_primary_contact'] ?? false,
            'can_view_info'      => $data['can_view_info'] ?? false,
        ]);

        return response()->json($member->load('user'), 201);
    }

    /**
     * Elimina un miembro de la familia.
     */
    public function removeMember(string $familyId, int $memberId): JsonResponse
    {
        $family = Family::where('tenant_id', auth()->user()->tenant_id)->findOrFail($familyId);

        $member = FamilyMember::where('family_id', $family->id)->findOrFail($memberId);
        $member->delete();

        return response()->json(['message' => 'Miembro eliminado de la familia.']);
    }

    /**
     * Actualiza el flag can_view_info de un miembro (toggle de acceso al portal).
     */
    public function toggleMemberAccess(string $familyId, int $memberId): JsonResponse
    {
        $family = Family::where('tenant_id', auth()->user()->tenant_id)->findOrFail($familyId);

        $member = FamilyMember::where('family_id', $family->id)->findOrFail($memberId);
        $member->update(['can_view_info' => !$member->can_view_info]);

        return response()->json($member);
    }

    // ── Gestión de Estudiantes Vinculados ─────────────────────────────────────

    /**
     * Vincula un estudiante a la familia.
     *
     * LÓGICA CRÍTICA — Familia Principal:
     * Si is_primary = true, se abre una transacción que:
     *   1. Pone en false TODAS las filas de family_students para ese student_id
     *      (elimina cualquier familia principal anterior).
     *   2. Inserta o actualiza la nueva vinculación con is_primary = true.
     * El índice único parcial de PostgreSQL es la segunda línea de defensa.
     */
    public function addStudent(AddFamilyStudentRequest $request, string $familyId): JsonResponse
    {
        $family = Family::where('tenant_id', auth()->user()->tenant_id)->findOrFail($familyId);

        $data      = $request->validated();
        $isPrimary = (bool) ($data['is_primary'] ?? false);

        $record = DB::transaction(function () use ($family, $data, $isPrimary) {
            if ($isPrimary) {
                // Paso 1: Revocar el rol de familia principal a cualquier otra
                // vinculación existente para este estudiante dentro del tenant.
                // (El índice parcial de PG también lo rechazaría, pero el UPDATE
                //  previo garantiza que nunca lleguemos a violarlo.)
                FamilyStudent::where('student_id', $data['student_id'])
                    ->whereHas('family', function ($q) {
                        $q->where('tenant_id', auth()->user()->tenant_id);
                    })
                    ->update(['is_primary' => false]);
            }

            // Paso 2: Crear o actualizar la vinculación
            return FamilyStudent::updateOrCreate(
                [
                    'family_id'  => $family->id,
                    'student_id' => $data['student_id'],
                ],
                [
                    'relation_description' => $data['relation_description'] ?? null,
                    'is_primary'           => $isPrimary,
                ]
            );
        });

        return response()->json($record->load('student.user'), 201);
    }

    /**
     * Desvincula un estudiante de la familia.
     * Si la vinculación era la familia principal, el estudiante queda sin
     * familia principal hasta que el admin asigne una nueva.
     */
    public function removeStudent(string $familyId, string $studentId): JsonResponse
    {
        $family = Family::where('tenant_id', auth()->user()->tenant_id)->findOrFail($familyId);

        FamilyStudent::where('family_id', $family->id)
            ->where('student_id', $studentId)
            ->firstOrFail()
            ->delete();

        return response()->json(['message' => 'Estudiante desvinculado de la familia.']);
    }
}
