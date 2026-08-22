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
            return response()->json(['message' => 'No se pueden modificar notas en un periodo académico bloqueado.'], 403);
        }

        if ($evaluation->status === 'CLOSED') {
            return response()->json(['message' => 'No se pueden modificar notas de una evaluación CERRADA.'], 403);
        }
        
        $courseAssignment = \App\Models\CourseAssignment::findOrFail($evaluation->course_assignment_id);
        $enrollmentIds = collect($request->grades)->pluck('enrollment_id');
        $validEnrollments = Enrollment::whereIn('id', $enrollmentIds)
            ->where('section_id', $courseAssignment->section_id)
            ->pluck('id');

        if ($validEnrollments->count() !== $enrollmentIds->count()) {
            return response()->json(['message' => 'Invalid enrollments for this evaluation.'], 422);
        }

        $records = [];
        foreach ($request->grades as $gradeData) {
            $existing = Grade::where('evaluation_id', $request->evaluation_id)
                ->where('enrollment_id', $gradeData['enrollment_id'])
                ->first();

            $oldScore = $existing ? $existing->score : null;

            if ($evaluation->status === 'PUBLISHED' && $oldScore !== null && $oldScore != $gradeData['score']) {
                if (empty($gradeData['reason'])) {
                    return response()->json(['message' => 'Se requiere un motivo para modificar una calificación publicada.'], 422);
                }
            }

            $grade = Grade::updateOrCreate(
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

            // Audit
            if ($oldScore !== null && $oldScore != $gradeData['score']) {
                \App\Models\GradeAudit::create([
                    'grade_id' => $grade->id,
                    'user_id' => $request->user()->id,
                    'old_score' => $oldScore,
                    'new_score' => $gradeData['score'],
                    'reason' => $gradeData['reason'] ?? 'Actualización en borrador'
                ]);
            }

            $records[] = $grade;
        }

        return response()->json([
            'message' => 'Grades saved successfully.',
            'data' => $records
        ]);
    }
}
