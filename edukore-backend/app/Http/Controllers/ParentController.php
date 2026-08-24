<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParentRequest;
use App\Models\ParentProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ParentController extends Controller
{
    /**
     * GET /parents
     * Lista todos los padres del tenant en sesión, con sus hijos vinculados.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $parents = ParentProfile::with(['user', 'students.user'])
            ->whereHas('user', fn($q) => $q->where('tenant_id', $tenantId))
            ->when($request->search, function ($query, $search) {
                $query->where('document_number', 'like', "%{$search}%")
                      ->orWhereHas('user', fn($q) =>
                          $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                      );
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($parents);
    }

    /**
     * POST /parents
     * Registra un padre/apoderado nuevo y lo vincula a sus hijos.
     * Todo ocurre dentro de una transacción atómica: si cualquier paso falla,
     * se hace rollback completo (no quedan users huérfanos ni pivotes corruptos).
     */
    public function store(StoreParentRequest $request): JsonResponse
    {
        $parentProfile = DB::transaction(function () use ($request) {
            $tenantId = $request->user()->tenant_id;

            // ─── PASO 1: Crear el User ──────────────────────────────────────────
            // UUID generado por Str::uuid() — nunca auto-incremental.
            // Contraseña temporal = document_number hasheado.
            $user = User::create([
                'id'        => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'name'      => trim($request->name . ' ' . $request->last_name),
                'email'     => $request->email,
                'password'  => Hash::make($request->document_number),
                'is_active' => true,
            ]);

            // Asignar rol "parent" usando Spatie (el rol debe existir en la BD)
            $user->assignRole('parent');

            // ─── PASO 2: Crear el ParentProfile ────────────────────────────────
            // El UUID del perfil también se genera aquí — independiente del user.
            $parentProfile = ParentProfile::create([
                'id'              => (string) Str::uuid(),
                'user_id'         => $user->id,
                'document_number' => $request->document_number,
                'occupation'      => $request->occupation,
                'phone'           => $request->phone,
            ]);

            // ─── PASO 3: Vincular hijos vía tabla pivote student_parents ───────
            // attach() inserta en la pivote student_parents con relationship_type
            // predeterminado. El array de IDs ya fue validado como UUIDs del tenant.
            $syncData = collect($request->student_ids)->mapWithKeys(fn($id) => [
                $id => ['relationship_type' => 'Apoderado'],
            ])->all();

            $parentProfile->students()->attach($syncData);

            return $parentProfile->load(['user', 'students.user']);
        });

        return response()->json([
            'message' => 'Padre/Apoderado registrado exitosamente.',
            'data'    => $parentProfile,
        ], 201);
    }

    /**
     * GET /parents/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $parent = ParentProfile::with(['user', 'students.user', 'students.enrollments'])
            ->whereHas('user', fn($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);

        return response()->json($parent);
    }

    /**
     * DELETE /parents/{id}
     * Elimina el perfil de padre y su User (en cascada), y desvincula sus hijos.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        DB::transaction(function () use ($id, $tenantId) {
            $parent = ParentProfile::whereHas('user', fn($q) => $q->where('tenant_id', $tenantId))
                ->findOrFail($id);

            // Desvincula todos los hijos antes de borrar
            $parent->students()->detach();

            // Borrar el User (cascade elimina el ParentProfile también)
            $parent->user->delete();
        });

        return response()->json(['message' => 'Padre eliminado correctamente.']);
    }
}
