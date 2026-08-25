<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TeacherProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class TeacherController extends Controller
{
    public function __construct()
    {
        // Solo Admin puede gestionar docentes
        // Instead of relying on alias, doing manual check below
    }

    public function index(Request $request): JsonResponse
    {
        if (!auth()->user()->hasRole('admin')) abort(403);
        $tenantId = auth()->user()->tenant_id;
        
        $query = User::with(['teacherProfile.creator'])
            ->when($request->active_only, fn($q) => $q->where('is_active', true))
            ->where('tenant_id', $tenantId)
            ->role('teacher');
            
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                  ->orWhereHas('teacherProfile', function($sq) use ($search) {
                      $sq->where('dni', 'ilike', '%' . $search . '%');
                  });
            });
        }
        
        $sort = $request->get('sort', 'recent');
        if ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        if ($request->limit === 'all') {
            $teachers = ['data' => $query->get()];
        } else {
            $teachers = $query->paginate(15);
        }
        
        return response()->json($teachers);
    }

    public function store(Request $request): JsonResponse
    {
        if (!auth()->user()->hasRole('admin')) abort(403);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'dni' => 'required|string|max:50|unique:teacher_profiles,dni',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
        ]);

        $tenantId = auth()->user()->tenant_id;
        
        $user = User::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(10)), // Se genera clave temporal
            'is_active' => true,
        ]);
        
        $user->assignRole('teacher');
        
        TeacherProfile::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'dni' => $validated['dni'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Docente registrado correctamente.',
            'data' => $user->load('teacherProfile.creator')
        ], 201);
    }

    public function show($id): JsonResponse
    {
        if (!auth()->user()->hasRole('admin')) abort(403);
        $tenantId = auth()->user()->tenant_id;
        
        $teacher = User::with(['teacherProfile.creator', 'roles'])
            ->where('tenant_id', $tenantId)
            ->role('teacher')
            ->findOrFail($id);
            
        return response()->json(['data' => $teacher]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        if (!auth()->user()->hasRole('admin')) abort(403);
        $tenantId = auth()->user()->tenant_id;
        
        $teacher = User::where('tenant_id', $tenantId)
            ->role('teacher')
            ->findOrFail($id);
            
        $profileId = $teacher->teacherProfile ? $teacher->teacherProfile->id : null;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'dni' => 'required|string|max:50|unique:teacher_profiles,dni,' . $profileId,
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
        ]);
        
        $teacher->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);
        
        if ($teacher->teacherProfile) {
            $teacher->teacherProfile->update([
                'dni' => $validated['dni'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
            ]);
        } else {
            TeacherProfile::create([
                'id' => (string) Str::uuid(),
                'user_id' => $teacher->id,
                'dni' => $validated['dni'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'created_by' => auth()->id(),
            ]);
        }

        return response()->json([
            'message' => 'Información del docente actualizada correctamente.',
            'data' => $teacher->load('teacherProfile.creator')
        ]);
    }

    public function toggleStatus($id): JsonResponse
    {
        if (!auth()->user()->hasRole('admin')) abort(403);
        $tenantId = auth()->user()->tenant_id;
        
        $teacher = User::where('tenant_id', $tenantId)
            ->role('teacher')
            ->findOrFail($id);
            
        $teacher->is_active = !$teacher->is_active;
        $teacher->save();
        
        $msg = $teacher->is_active ? 'Docente activado correctamente.' : 'Docente desactivado correctamente.';
        return response()->json(['message' => $msg]);
    }
}
