<?php

namespace App\Http\Controllers;

use App\Models\CourseAssignment;
use App\Models\Section;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CourseAssignmentController extends Controller
{
    public function index(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $assignments = CourseAssignment::with(['course', 'section.gradeLevel.level.academicYear', 'teacher'])
            ->whereHas('course', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->get();

        return response()->json(['data' => $assignments]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|uuid|exists:courses,id',
            'section_id' => 'required|uuid|exists:sections,id',
            'teacher_id' => 'required|uuid|exists:users,id',
            'schedule' => 'nullable|string',
            'room' => 'nullable|string',
            'weekly_hours' => 'nullable|integer',
            'is_substitute' => 'nullable|boolean',
        ]);

        $tenantId = auth()->user()->tenant_id;

        // Verify Course belongs to tenant
        Course::where('tenant_id', $tenantId)->findOrFail($validated['course_id']);

        // Verify Section belongs to tenant
        Section::whereHas('gradeLevel.level.academicYear', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->findOrFail($validated['section_id']);

        // Verify Teacher belongs to tenant
        User::where('tenant_id', $tenantId)->findOrFail($validated['teacher_id']);

        // Prevent exact duplicate assignment (same course, same section)
        $exists = CourseAssignment::where('course_id', $validated['course_id'])
            ->where('section_id', $validated['section_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Ya existe una asignación para este curso en esta sección.'], 422);
        }

        $assignment = CourseAssignment::create([
            'id' => \Illuminate\Support\Str::uuid(),
            ...$validated,
            'is_substitute' => $validated['is_substitute'] ?? false,
            'weekly_hours' => $validated['weekly_hours'] ?? 0,
        ]);

        return response()->json(['data' => $assignment->load(['course', 'section', 'teacher'])], 201);
    }

    public function show(string $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $assignment = CourseAssignment::with(['course', 'section.gradeLevel.level.academicYear', 'teacher'])
            ->whereHas('course', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->findOrFail($id);

        return response()->json(['data' => $assignment]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $assignment = CourseAssignment::whereHas('course', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->findOrFail($id);

        $validated = $request->validate([
            'teacher_id' => 'sometimes|uuid|exists:users,id',
            'schedule' => 'nullable|string',
            'room' => 'nullable|string',
            'weekly_hours' => 'nullable|integer',
            'is_substitute' => 'nullable|boolean',
        ]);

        if (isset($validated['teacher_id'])) {
            User::where('tenant_id', $tenantId)->findOrFail($validated['teacher_id']);
        }

        $assignment->update($validated);

        return response()->json(['data' => $assignment->load(['course', 'section', 'teacher'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $assignment = CourseAssignment::whereHas('course', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->findOrFail($id);

        $assignment->delete();

        return response()->json(null, 204);
    }
}
