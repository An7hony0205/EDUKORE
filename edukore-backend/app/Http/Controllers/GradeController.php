<?php

namespace App\Http\Controllers;

use App\Models\CourseAssignment;
use App\Models\EvaluationCriterion;
use App\Models\Grade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GradeController extends Controller
{
    /**
     * GET /api/grades/sheet
     * Retorna los criterios y estudiantes con sus notas para una asignación y periodo.
     */
    public function sheet(Request $request): JsonResponse
    {
        $request->validate([
            'course_assignment_id' => 'required|uuid|exists:course_assignments,id',
            'term_id'              => 'required|uuid|exists:academic_terms,id',
        ]);

        $assignmentId = $request->course_assignment_id;
        $termId       = $request->term_id;

        // Criterios de evaluación para este curso y periodo
        $criteria = EvaluationCriterion::where('course_assignment_id', $assignmentId)
            ->where('term_id', $termId)
            ->orderBy('order_index')
            ->get();

        // Si no hay criterios, creamos 3 por defecto
        if ($criteria->isEmpty()) {
            $defaultCriteria = [
                ['name' => 'Evaluación Continua', 'weight' => 0.4, 'order_index' => 1],
                ['name' => 'Tareas / Actividades', 'weight' => 0.3, 'order_index' => 2],
                ['name' => 'Examen de Periodo', 'weight' => 0.3, 'order_index' => 3],
            ];

            foreach ($defaultCriteria as $c) {
                EvaluationCriterion::create([
                    'id'                   => (string) Str::uuid(),
                    'course_assignment_id' => $assignmentId,
                    'term_id'              => $termId,
                    'name'                 => $c['name'],
                    'weight'               => $c['weight'],
                    'order_index'          => $c['order_index'],
                ]);
            }

            $criteria = EvaluationCriterion::where('course_assignment_id', $assignmentId)
                ->where('term_id', $termId)
                ->orderBy('order_index')
                ->get();
        }

        // Obtener estudiantes matriculados en la sección del curso
        $assignment = CourseAssignment::with('section.students')->findOrFail($assignmentId);
        $students = $assignment->section->students;

        // Obtener notas existentes para estos criterios y estudiantes
        $grades = Grade::whereIn('evaluation_criterion_id', $criteria->pluck('id'))
            ->whereIn('student_id', $students->pluck('id'))
            ->get();

        // Armar el payload
        $studentsData = $students->map(function ($student) use ($criteria, $grades) {
            $studentGrades = $criteria->map(function ($criterion) use ($student, $grades) {
                $grade = $grades->where('student_id', $student->id)
                    ->where('evaluation_criterion_id', $criterion->id)
                    ->first();

                return [
                    'criterion_id' => $criterion->id,
                    'score'        => $grade ? $grade->score : null,
                ];
            });

            // Cálculo promedio servidor
            $totalWeight = 0;
            $sum = 0;
            foreach ($studentGrades as $sg) {
                if ($sg['score'] !== null) {
                    $weight = $criteria->firstWhere('id', $sg['criterion_id'])->weight;
                    $sum += $sg['score'] * $weight;
                    $totalWeight += $weight;
                }
            }
            $average = $totalWeight > 0 ? round($sum / $totalWeight, 2) : null;

            return [
                'id'                => $student->id,
                'name'              => $student->name,
                'enrollment_number' => $student->enrollment_number,
                'grades'            => $studentGrades,
                'average'           => $average,
            ];
        });

        return response()->json([
            'criteria' => $criteria,
            'students' => $studentsData,
        ]);
    }

    /**
     * POST /api/grades/bulk-sync
     */
    public function bulkSync(Request $request): JsonResponse
    {
        $request->validate([
            'course_assignment_id' => 'required|uuid|exists:course_assignments,id',
            'term_id'              => 'required|uuid|exists:academic_terms,id',
            'criteria'             => 'required|array',
            'grades'               => 'required|array',
        ]);

        $assignmentId = $request->course_assignment_id;
        $termId       = $request->term_id;
        $criteria     = $request->criteria;
        $grades       = $request->grades;

        DB::transaction(function () use ($assignmentId, $termId, $criteria, $grades) {
            // Sincronizar Criterios
            $existingCriteriaIds = collect($criteria)->pluck('id')->filter()->toArray();
            
            // Eliminar criterios que ya no están en la lista (y en cascada se van sus notas)
            EvaluationCriterion::where('course_assignment_id', $assignmentId)
                ->where('term_id', $termId)
                ->whereNotIn('id', $existingCriteriaIds)
                ->delete();

            $criteriaMap = []; // map temporal_id -> real UUID
            
            foreach ($criteria as $index => $c) {
                $uuid = $c['id'] ?? (string) Str::uuid();
                if (isset($c['id']) && Str::isUuid($c['id'])) {
                    EvaluationCriterion::where('id', $uuid)->update([
                        'name'        => $c['name'],
                        'weight'      => $c['weight'],
                        'order_index' => $index + 1,
                    ]);
                } else {
                    EvaluationCriterion::create([
                        'id'                   => $uuid,
                        'course_assignment_id' => $assignmentId,
                        'term_id'              => $termId,
                        'name'                 => $c['name'],
                        'weight'               => $c['weight'],
                        'order_index'          => $index + 1,
                    ]);
                    $criteriaMap[$c['id']] = $uuid; // Por si el front envió un ID temporal (ej. 'new-1')
                }
            }

            // Sincronizar Notas
            foreach ($grades as $g) {
                // Si el criterio era nuevo, usamos el UUID real
                $criterionId = $criteriaMap[$g['criterion_id']] ?? $g['criterion_id'];

                if ($g['score'] === null || $g['score'] === '') {
                    Grade::where('evaluation_criterion_id', $criterionId)
                        ->where('student_id', $g['student_id'])
                        ->delete();
                } else {
                    // HasUuids maneja la creación de UUIDs
                    Grade::updateOrCreate(
                        [
                            'evaluation_criterion_id' => $criterionId,
                            'student_id'              => $g['student_id'],
                        ],
                        [
                            'score' => $g['score'],
                        ]
                    );
                }
            }
        });

        return response()->json(['message' => 'Calificaciones guardadas exitosamente.']);
    }
}
