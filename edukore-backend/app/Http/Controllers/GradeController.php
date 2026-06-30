<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Evaluation;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'evaluation_id' => 'required|uuid',
        ]);

        $grades = Grade::with('enrollment.student')
            ->where('evaluation_id', $request->evaluation_id)
            ->get();

        return response()->json($grades);
    }

    public function storeBulk(Request $request)
    {
        $request->validate([
            'evaluation_id' => 'required|uuid|exists:evaluations,id',
            'grades' => 'required|array',
            'grades.*.enrollment_id' => 'required|uuid',
            'grades.*.score' => 'required|numeric',
            'grades.*.rubric_results' => 'nullable|array',
            'grades.*.feedback' => 'nullable|string',
        ]);

        $evaluation = Evaluation::with('academicPeriod')->findOrFail($request->evaluation_id);
        
        if ($evaluation->academicPeriod && $evaluation->academicPeriod->is_locked) {
            return response()->json(['message' => 'Cannot modify grades for a locked academic period.'], 403);
        }
        
        $enrollmentIds = collect($request->grades)->pluck('enrollment_id');
        $validEnrollments = Enrollment::whereIn('id', $enrollmentIds)
            ->where('course_assignment_id', $evaluation->course_assignment_id)
            ->pluck('id');

        if ($validEnrollments->count() !== $enrollmentIds->count()) {
            return response()->json(['message' => 'Invalid enrollments for this evaluation.'], 422);
        }

        $records = [];
        foreach ($request->grades as $gradeData) {
            $records[] = Grade::updateOrCreate(
                [
                    'evaluation_id' => $request->evaluation_id,
                    'enrollment_id' => $gradeData['enrollment_id'],
                ],
                [
                    'score' => $gradeData['score'],
                    'rubric_results' => isset($gradeData['rubric_results']) ? $gradeData['rubric_results'] : null,
                    'feedback' => $gradeData['feedback'] ?? null,
                ]
            );
        }

        return response()->json([
            'message' => 'Grades saved successfully.',
            'data' => $records
        ]);
    }
}
