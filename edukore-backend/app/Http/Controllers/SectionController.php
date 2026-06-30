<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\GradeLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    /**
     * List all sections for the authenticated user's tenant,
     * eager-loading the full academic hierarchy.
     */
    public function index(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        // Sections are linked to tenant via gradeLevel → level → academicYear → tenant_id
        $sections = Section::with('gradeLevel.level.academicYear')
            ->whereHas('gradeLevel.level.academicYear', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->orderBy('name')
            ->get();

        return response()->json($sections);
    }

    /**
     * Create a new section under a grade level.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'grade_level_id' => 'required|uuid|exists:grade_levels,id',
            'name'           => 'required|string|max:255',
            'capacity'       => 'nullable|integer|min:1',
        ]);

        // Ensure the grade_level belongs to the authenticated tenant
        $tenantId = auth()->user()->tenant_id;
        $gradeLevel = GradeLevel::whereHas('level.academicYear', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->findOrFail($validated['grade_level_id']);

        $section = Section::create([
            'id' => \Illuminate\Support\Str::uuid(),
            ...$validated,
        ]);

        return response()->json($section->load('gradeLevel.level.academicYear'), 201);
    }

    /**
     * Show a single section with full academic hierarchy.
     */
    public function show(string $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $section = Section::with('gradeLevel.level.academicYear', 'enrollments', 'courseAssignments.course')
            ->whereHas('gradeLevel.level.academicYear', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->findOrFail($id);

        return response()->json($section);
    }

    /**
     * Update a section.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $section = Section::whereHas('gradeLevel.level.academicYear', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->findOrFail($id);

        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'capacity' => 'sometimes|nullable|integer|min:1',
        ]);

        $section->update($validated);

        return response()->json($section->load('gradeLevel.level.academicYear'));
    }

    /**
     * Delete a section.
     */
    public function destroy(string $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $section = Section::whereHas('gradeLevel.level.academicYear', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->findOrFail($id);

        $section->delete();

        return response()->json(null, 204);
    }
}
