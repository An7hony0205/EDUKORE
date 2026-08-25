<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use App\Models\ParentProfile;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $search = $request->query('search', '');
        $sort = $request->query('sort', 'recent');

        $query = Student::with('user');

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $students = $query->paginate($perPage);

        return response()->json([
            'data' => $students->items(),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total()
            ]
        ]);
    }

    public function show($id)
    {
        $student = Student::with(['user', 'parents.user'])->findOrFail($id);
        
        return response()->json([
            'data' => $student
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'student.name'           => 'required|string|max:255',
            'student.document_number'=> 'required|string|max:30',
            'student.email'          => 'nullable|email|unique:users,email',
            'student.date_of_birth'  => 'nullable|date',
            'student.section_id'     => 'nullable|uuid|exists:academic_sections,id',
            'parents'                => 'required|array|min:1',
            'parents.*.name'         => 'required|string|max:255',
            'parents.*.document_number' => 'required|string|max:30',
            'parents.*.email'        => 'nullable|email',
            'parents.*.phone'        => 'nullable|string|max:30',
            'parents.*.address'      => 'nullable|string|max:255',
            'parents.*.relationship' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $tenantId = auth()->user()->tenant_id;

            // ── Autogenerar matrícula única ─────────────────────────────────
            do {
                $enrollmentNumber = date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            } while (Student::where('enrollment_number', $enrollmentNumber)->exists());

            // ── Fallback de correo del alumno ───────────────────────────────
            $studentEmail = $request->input('student.email')
                ?: strtolower(Str::slug($request->input('student.document_number'))) . '@demo.edu';

            // ── 1. Crear User del Estudiante ────────────────────────────────
            $studentUser = User::create([
                'id'        => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'name'      => $request->input('student.name'),
                'email'     => $studentEmail,
                'password'  => Hash::make($request->input('student.document_number')),
                'is_active' => true,
            ]);
            $studentUser->assignRole('student');

            // ── 2. Crear Perfil del Estudiante ──────────────────────────────
            $student = Student::create([
                'id'               => (string) Str::uuid(),
                'user_id'          => $studentUser->id,
                'enrollment_number'=> $enrollmentNumber,
                'date_of_birth'    => $request->input('student.date_of_birth'),
                'section_id'       => $request->input('student.section_id') ?: null,
                'status'           => 'activo',
            ]);

            // ── 3. Procesar Apoderados ──────────────────────────────────────
            $parentIds = [];
            foreach ($request->input('parents') as $parentData) {
                // Fallback de correo del apoderado
                $parentEmail = !empty($parentData['email'])
                    ? $parentData['email']
                    : strtolower(Str::slug($parentData['document_number'])) . '@demo.edu';

                // Reusar si ya existe en el tenant (por correo)
                $parentUser = User::where('email', $parentEmail)
                                  ->where('tenant_id', $tenantId)
                                  ->first();

                if (!$parentUser) {
                    $parentUser = User::create([
                        'id'        => (string) Str::uuid(),
                        'tenant_id' => $tenantId,
                        'name'      => $parentData['name'],
                        'email'     => $parentEmail,
                        'password'  => Hash::make($parentData['document_number']),
                        'is_active' => true,
                    ]);
                    $parentUser->assignRole('parent');
                }

                $parentProfile = ParentProfile::firstOrCreate(
                    ['user_id' => $parentUser->id],
                    [
                        'id'              => (string) Str::uuid(),
                        'document_number' => $parentData['document_number'],
                        'phone'           => $parentData['phone'] ?? null,
                        'address'         => $parentData['address'] ?? null,
                    ]
                );

                $parentIds[(string) $parentProfile->id] = [
                    'relationship_type' => $parentData['relationship'],
                ];
            }

            // ── 4. Vincular apoderados al estudiante ────────────────────────
            $student->parents()->sync($parentIds);

            DB::commit();

            $student->load(['user', 'parents.user']);

            return response()->json([
                'message' => 'Estudiante y apoderados registrados exitosamente.',
                'data'    => $student,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'El registro falló.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:activo,inactivo,egresado,retirado'
        ]);

        $student = Student::findOrFail($id);
        $student->status = $request->status;
        $student->save();

        if (in_array($request->status, ['egresado', 'retirado'])) {
            $student->delete(); // Soft delete
        } else {
            if ($student->trashed()) {
                $student->restore();
            }
        }

        return response()->json([
            'message' => 'Status updated',
            'data' => $student
        ]);
    }

    public function attendance($id)
    {
        $student = Student::findOrFail($id);
        
        $attendance = \App\Models\Attendance::whereHas('enrollment', function ($q) use ($id) {
            $q->where('student_id', $id);
        })->with('courseAssignment.course')->orderBy('date', 'desc')->get();

        return response()->json(['data' => $attendance]);
    }


    public function audit($id)
    {
        $student = Student::findOrFail($id);
        
        // Get activity logs for the student model
        $logs = \Spatie\Activitylog\Models\Activity::with('causer')
            ->where('subject_type', Student::class)
            ->where('subject_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $logs]);
    }

}