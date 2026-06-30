<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    /**
     * List all enrollments for the authenticated user's tenant.
     */
    public function index(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $enrollments = Enrollment::with('student.user', 'section.gradeLevel.level.academicYear')
            ->whereHas('section.gradeLevel.level.academicYear', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->orderBy('enrolled_at', 'desc')
            ->get();

        return response()->json($enrollments);
    }

    /**
     * Enroll a student in a section.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id'       => 'required|uuid|exists:students,id',
            'section_id'       => 'required|uuid|exists:sections,id',
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
            'status'           => 'nullable|string|in:preinscrito,pendiente_documentacion,matriculado,suspendido,retirado,finalizado',
        ]);

        $tenantId = auth()->user()->tenant_id;

        // Verify academic year is open
        $academicYear = \App\Models\AcademicYear::where('tenant_id', $tenantId)->findOrFail($validated['academic_year_id']);
        if ($academicYear->status === 'cerrado') {
            return response()->json(['message' => 'El año académico está cerrado.'], 422);
        }

        // Verify that the section belongs to this tenant and is active
        $section = Section::whereHas('gradeLevel.level.academicYear', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->findOrFail($validated['section_id']);

        if ($section->status === 'inactivo') {
            return response()->json(['message' => 'La sección está inactiva.'], 422);
        }

        // Check Capacity
        $currentEnrollments = Enrollment::where('section_id', $section->id)->whereIn('status', ['matriculado', 'preinscrito'])->count();
        if ($currentEnrollments >= $section->capacity) {
            return response()->json(['message' => 'La sección ha superado su capacidad máxima.'], 422);
        }

        // Prevent duplicate enrollments in the same section for the same academic year
        $exists = Enrollment::where('student_id', $validated['student_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'El estudiante ya está matriculado en este año académico.',
            ], 422);
        }

        $enrollment = Enrollment::create([
            'id'          => \Illuminate\Support\Str::uuid(),
            'tenant_id'   => $tenantId,
            'status'      => $validated['status'] ?? 'preinscrito',
            'enrolled_at' => now(),
            ...$validated,
        ]);

        return response()->json(
            $enrollment->load('student.user', 'section.gradeLevel.level.academicYear'),
            201
        );
    }

    /**
     * Show a single enrollment (tenant-scoped).
     */
    public function show(string $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $enrollment = Enrollment::with(
            'student.user',
            'section.gradeLevel.level.academicYear',
            'grades.evaluation',
            'attendance'
        )
            ->whereHas('section.gradeLevel.level.academicYear', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->findOrFail($id);

        return response()->json($enrollment);
    }

    /**
     * Update an enrollment (e.g., change status).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $enrollment = Enrollment::whereHas('section.gradeLevel.level.academicYear', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|string|in:preinscrito,pendiente_documentacion,matriculado,suspendido,retirado,finalizado',
        ]);

        $enrollment->update($validated);

        return response()->json($enrollment->load('student.user', 'section'));
    }

    /**
     * Remove an enrollment.
     */
    public function destroy(string $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $enrollment = Enrollment::whereHas('section.gradeLevel.level.academicYear', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->findOrFail($id);

        $enrollment->delete();

        return response()->json(null, 204);
    }
}
