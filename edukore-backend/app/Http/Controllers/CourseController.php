<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * List all courses for the authenticated user's tenant.
     */
    public function index(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $courses = Course::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        return response()->json($courses);
    }

    /**
     * Create a new course.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $course = Course::create([
            'id'        => \Illuminate\Support\Str::uuid(),
            'tenant_id' => auth()->user()->tenant_id,
            ...$validated,
        ]);

        return response()->json($course, 201);
    }

    /**
     * Show a single course (tenant-scoped).
     */
    public function show(string $id): JsonResponse
    {
        $course = Course::where('tenant_id', auth()->user()->tenant_id)
            ->with('courseAssignments.section', 'courseAssignments.teacher')
            ->findOrFail($id);

        return response()->json($course);
    }

    /**
     * Update an existing course.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $course = Course::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'code'        => 'sometimes|nullable|string|max:50',
            'description' => 'sometimes|nullable|string',
        ]);

        $course->update($validated);

        return response()->json($course);
    }

    /**
     * Delete a course.
     */
    public function destroy(string $id): JsonResponse
    {
        $course = Course::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id);

        $course->delete();

        return response()->json(null, 204);
    }
}
