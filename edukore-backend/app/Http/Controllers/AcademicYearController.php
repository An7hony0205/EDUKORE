<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    /**
     * List all academic years for the authenticated user's tenant.
     */
    public function index(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $academicYears = AcademicYear::where('tenant_id', $tenantId)
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json($academicYears);
    }

    /**
     * Create a new academic year.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
            'is_active'  => 'boolean',
        ]);

        $academicYear = AcademicYear::create([
            'id'         => \Illuminate\Support\Str::uuid(),
            'tenant_id'  => auth()->user()->tenant_id,
            ...$validated,
        ]);

        return response()->json($academicYear, 201);
    }

    /**
     * Show a single academic year (tenant-scoped).
     */
    public function show(string $id): JsonResponse
    {
        $academicYear = AcademicYear::where('tenant_id', auth()->user()->tenant_id)
            ->with('levels.gradeLevels')
            ->findOrFail($id);

        return response()->json($academicYear);
    }

    /**
     * Update an existing academic year.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $academicYear = AcademicYear::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name'       => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date'   => 'sometimes|date|after:start_date',
            'is_active'  => 'sometimes|boolean',
        ]);

        $academicYear->update($validated);

        return response()->json($academicYear);
    }

    /**
     * Delete an academic year.
     */
    public function destroy(string $id): JsonResponse
    {
        $academicYear = AcademicYear::where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id);

        $academicYear->delete();

        return response()->json(null, 204);
    }
}
