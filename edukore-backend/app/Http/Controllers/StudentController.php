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

        $query = Student::with('user');

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
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
            'student.name' => 'required|string|max:255',
            'student.email' => 'required|email|unique:users,email',
            'student.enrollment_number' => 'required|string',
            'parents' => 'required|array',
            'parents.*.name' => 'required|string|max:255',
            'parents.*.email' => 'required|email',
            'parents.*.phone' => 'required|string',
            'parents.*.relationship' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            $tenantId = auth()->user()->tenant_id;

            // 1. Create Student User
            $studentUser = User::create([
                'id' => Str::uuid(),
                'tenant_id' => $tenantId,
                'name' => $request->input('student.name'),
                'email' => $request->input('student.email'),
                'password' => Hash::make('password123'), // Default password
            ]);
            $studentUser->assignRole('student');

            // 2. Create Student Profile
            $student = Student::create([
                'id' => Str::uuid(),
                'user_id' => $studentUser->id,
                'enrollment_number' => $request->input('student.enrollment_number'),
                'date_of_birth' => $request->input('student.date_of_birth'),
                'status' => 'activo'
            ]);

            // 3. Process Parents
            $parentIds = [];
            foreach ($request->input('parents') as $parentData) {
                // Check if parent user already exists
                $parentUser = User::where('email', $parentData['email'])->where('tenant_id', $tenantId)->first();
                
                if (!$parentUser) {
                    $parentUser = User::create([
                        'id' => Str::uuid(),
                        'tenant_id' => $tenantId,
                        'name' => $parentData['name'],
                        'email' => $parentData['email'],
                        'password' => Hash::make('password123'),
                    ]);
                    $parentUser->assignRole('parent');
                }

                $parentProfile = ParentProfile::firstOrCreate(
                    ['user_id' => $parentUser->id],
                    ['id' => Str::uuid(), 'phone' => $parentData['phone'], 'address' => $parentData['address'] ?? null]
                );

                $parentIds[$parentProfile->id] = ['relationship' => $parentData['relationship']];
            }

            // 4. Attach Parents to Student
            $student->parents()->sync($parentIds);

            DB::commit();

            $student->load(['user', 'parents.user']);

            return response()->json([
                'message' => 'Student and parents registered successfully',
                'data' => $student
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Registration failed', 'error' => $e->getMessage()], 500);
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
}
