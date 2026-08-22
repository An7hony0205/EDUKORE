<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use Illuminate\Http\Request;

class AcademicPeriodController extends Controller
{
    public function index(Request $request)
    {
        $query = AcademicPeriod::query();

        if ($request->has('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        return response()->json($query->orderBy('start_date')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;

        $period = AcademicPeriod::create($validated);

        return response()->json($period, 201);
    }

    public function show(AcademicPeriod $academicPeriod)
    {
        return response()->json($academicPeriod);
    }

    public function update(Request $request, AcademicPeriod $academicPeriod)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
        ]);

        $academicPeriod->update($validated);

        return response()->json($academicPeriod);
    }

    public function toggleLock(AcademicPeriod $academicPeriod)
    {
        $academicPeriod->update(['is_locked' => !$academicPeriod->is_locked]);
        return response()->json($academicPeriod);
    }

    public function destroy(AcademicPeriod $academicPeriod)
    {
        if ($academicPeriod->is_locked) {
            return response()->json(['message' => 'Cannot delete a locked period.'], 403);
        }
        $academicPeriod->delete();
        return response()->json(null, 204);
    }
}
