<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Http\Requests\StoreCourseRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $query = Course::where('tenant_id', $tenantId);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                  ->orWhere('code', 'ilike', '%' . $search . '%');
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // Sorting
        $sort = $request->input('sort', 'name_asc');
        switch ($sort) {
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'recent':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name_asc':
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        return response()->json($query->get());
    }

    /**
     * Store a newly created course.
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        // La validación (incluyendo unicidad por tenant) fue ejecutada por StoreCourseRequest.
        $validated = $request->validated();

        $course = Course::create([
            'id'          => Str::uuid(),
            'tenant_id'   => $tenantId,
            'name'        => $validated['name'],
            'code'        => $validated['code'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'] ?? true,
        ]);

        return response()->json($course, 201);
    }

    /**
     * Display the specified course.
     */
    public function show($id): JsonResponse
    {
        $course = Course::where('tenant_id', auth()->user()->tenant_id)
            ->with(['courseAssignments' => function ($query) {
                $query->with(['teacher', 'section.gradeLevel.level']);
            }])
            ->findOrFail($id);

        return response()->json($course);
    }

    /**
     * Update the specified course.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $course = Course::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('courses')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })->ignore($course->id)
            ],
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('courses')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })->ignore($course->id)
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $course->update($validated);

        return response()->json($course);
    }

    /**
     * Remove the specified course (soft deactivate logic per plan).
     */
    public function destroy($id): JsonResponse
    {
        $course = Course::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        
        // We deactivate instead of physical delete
        $course->update(['is_active' => false]);
        
        return response()->json(['message' => 'Curso desactivado correctamente']);
    }
}
