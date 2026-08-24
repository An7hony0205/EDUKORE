<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\JsonResponse;

class AcademicStructureController extends Controller
{
    /**
     * Get the full academic structure tree for the tenant.
     */
    public function index(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        // Eager load the entire tree: Year -> Level -> GradeLevel -> Section
        $tree = AcademicYear::where('tenant_id', $tenantId)
            ->with(['levels.gradeLevels.sections' => function($query) {
                // We could order them here if needed
                $query->orderBy('name', 'asc');
            }])
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json($tree);
    }
}
