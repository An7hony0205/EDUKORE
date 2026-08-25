<?php

namespace App\Http\Controllers;

use App\Models\CourseAssignment;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationActivity;
use App\Models\Grade;
use App\Models\Student;
use App\Models\TenantSetting;
use App\Models\CourseCompetency;
use App\Models\CompetencyEvaluation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GradeController extends Controller
{
    public function structure(Request $request): JsonResponse
    {
        $request->validate([
            'course_assignment_id' => 'required|uuid',
            'term_id'              => 'required|uuid',
        ]);

        $assignmentId = $request->query('course_assignment_id');
        $termId       = $request->query('term_id');
        
        $assignment = CourseAssignment::with('section', 'course')->findOrFail($assignmentId);
        $sectionId = $assignment->section_id ?? $assignment->academic_section_id;

        $tenantId = auth()->user()->tenant_id;
        $setting = TenantSetting::where('tenant_id', $tenantId)->first();
        $gradingSystem = $setting ? $setting->grading_system : 'competency';

        $students = Student::query()
            ->where('section_id', $sectionId)
            ->orWhereHas('enrollments', function ($q) use ($sectionId) {
                $q->where('section_id', $sectionId);
            })
            ->with(['user'])
            ->get();

        if ($students->isEmpty()) {
            $students = Student::with('user')->take(10)->get();
        }

        if ($gradingSystem === 'competency') {
            $competencies = CourseCompetency::where('course_id', $assignment->course_id)->orderBy('order_index')->get();
            if ($competencies->isEmpty()) {
                $defaults = ['Competencia 1', 'Competencia 2', 'Competencia 3'];
                foreach ($defaults as $i => $name) {
                    CourseCompetency::create([
                        'id' => (string) Str::uuid(),
                        'course_id' => $assignment->course_id,
                        'name' => $name,
                        'order_index' => $i + 1
                    ]);
                }
                $competencies = CourseCompetency::where('course_id', $assignment->course_id)->orderBy('order_index')->get();
            }

            $evaluations = CompetencyEvaluation::where('term_id', $termId)
                ->whereIn('competency_id', $competencies->pluck('id'))
                ->whereIn('student_id', $students->pluck('id'))
                ->get();

            $studentsData = $students->map(function ($student) use ($competencies, $evaluations) {
                $studentEvals = [];
                foreach ($competencies as $comp) {
                    $ev = $evaluations->where('student_id', $student->id)->where('competency_id', $comp->id)->first();
                    $studentEvals[$comp->id] = [
                        'score_literal' => $ev ? $ev->score_literal : null,
                        'descriptive_conclusion' => $ev ? $ev->descriptive_conclusion : null,
                    ];
                }
                return [
                    'id' => $student->id,
                    'name' => $student->user?->name ?? ($student->first_name . ' ' . $student->last_name) ?? 'Estudiante',
                    'evaluations' => $studentEvals
                ];
            })->sortBy('name')->values();

            return response()->json([
                'grading_system' => 'competency',
                'competencies' => $competencies,
                'students' => $studentsData,
            ]);
        }

        // Numeric
        $criteria = EvaluationCriterion::with('activities')
            ->where('course_assignment_id', $assignmentId)
            ->where('term_id', $termId)
            ->orderBy('order_index')
            ->get();

        if ($criteria->isEmpty()) {
            $defaultCriteria = [
                ['name' => 'Práctica Calificada 1', 'weight' => 0.3, 'order_index' => 1],
                ['name' => 'Práctica Calificada 2', 'weight' => 0.3, 'order_index' => 2],
                ['name' => 'Examen Bimestral', 'weight' => 0.4, 'order_index' => 3],
            ];

            foreach ($defaultCriteria as $c) {
                EvaluationCriterion::create([
                    'id' => (string) Str::uuid(),
                    'course_assignment_id' => $assignmentId,
                    'term_id' => $termId,
                    'name' => $c['name'],
                    'weight' => $c['weight'],
                    'order_index' => $c['order_index'],
                ]);
            }
            $criteria = EvaluationCriterion::with('activities')
                ->where('course_assignment_id', $assignmentId)
                ->where('term_id', $termId)
                ->orderBy('order_index')
                ->get();
        }

        $activityIds = $criteria->pluck('activities')->flatten()->pluck('id');
        $grades = Grade::whereIn('activity_id', $activityIds)->whereIn('student_id', $students->pluck('id'))->get();

        $studentsData = $students->map(function ($student) use ($criteria, $grades) {
            $studentAverages = $criteria->map(function ($criterion) use ($student, $grades) {
                $critActivities = $criterion->activities->pluck('id');
                $studentCritGrades = $grades->where('student_id', $student->id)->whereIn('activity_id', $critActivities);
                $average = null;
                if ($studentCritGrades->isNotEmpty()) {
                    $average = round($studentCritGrades->avg('score'), 2);
                }
                return ['criterion_id' => $criterion->id, 'average' => $average];
            });

            $totalWeight = 0;
            $sum = 0;
            foreach ($studentAverages as $sa) {
                if ($sa['average'] !== null) {
                    $weight = $criteria->firstWhere('id', $sa['criterion_id'])->weight;
                    $sum += $sa['average'] * $weight;
                    $totalWeight += $weight;
                }
            }
            $finalAverage = $totalWeight > 0 ? round($sum / $totalWeight, 2) : null;

            return [
                'id' => $student->id,
                'name' => $student->user?->name ?? ($student->first_name . ' ' . $student->last_name) ?? 'Estudiante',
                'averages' => $studentAverages,
                'final_average' => $finalAverage,
            ];
        })->sortBy('name')->values();

        return response()->json([
            'grading_system' => 'numeric',
            'rubrics' => $criteria,
            'students' => $studentsData,
        ]);
    }

    public function storeActivity(Request $request, string $rubricId): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:100']);
        $criterion = EvaluationCriterion::findOrFail($rubricId);
        $activity = EvaluationActivity::create([
            'id' => (string) Str::uuid(),
            'evaluation_criterion_id' => $criterion->id,
            'name' => $request->name,
            'order_index' => $criterion->activities()->count() + 1,
        ]);
        return response()->json(['message' => 'Actividad creada', 'activity' => $activity]);
    }

    public function getActivityGrades(string $activityId): JsonResponse
    {
        $activity = EvaluationActivity::with('evaluationCriterion.courseAssignment')->findOrFail($activityId);
        $sectionId = $activity->evaluationCriterion->courseAssignment->section_id ?? $activity->evaluationCriterion->courseAssignment->academic_section_id;

        $students = Student::query()->where('section_id', $sectionId)->orWhereHas('enrollments', function ($q) use ($sectionId) {
            $q->where('section_id', $sectionId);
        })->with(['user'])->get();

        if ($students->isEmpty()) {
            $students = Student::with('user')->take(10)->get();
        }

        $grades = Grade::where('activity_id', $activityId)->get();

        $studentsData = $students->map(function ($student) use ($grades) {
            $grade = $grades->where('student_id', $student->id)->first();
            return [
                'id' => $student->id,
                'name' => $student->user?->name ?? ($student->first_name . ' ' . $student->last_name) ?? 'Estudiante',
                'score' => $grade ? $grade->score : null,
            ];
        })->sortBy('name')->values();

        return response()->json([
            'activity' => $activity,
            'students' => $studentsData,
        ]);
    }

    public function saveActivityGrades(Request $request, string $activityId): JsonResponse
    {
        $request->validate([
            'grades' => 'required|array',
            'grades.*.student_id' => 'required|uuid',
            'grades.*.score' => 'nullable|numeric|min:0|max:20',
        ]);

        DB::transaction(function () use ($activityId, $request) {
            foreach ($request->grades as $g) {
                if ($g['score'] === null || $g['score'] === '') {
                    Grade::where('activity_id', $activityId)->where('student_id', $g['student_id'])->delete();
                } else {
                    Grade::updateOrCreate(
                        ['activity_id' => $activityId, 'student_id' => $g['student_id']],
                        ['id' => (string) Str::uuid(), 'score' => $g['score'], 'created_by' => auth()->id()]
                    );
                }
            }
        });

        return response()->json(['message' => 'Notas guardadas exitosamente.']);
    }

    public function competencySync(Request $request): JsonResponse
    {
        $request->validate([
            'term_id' => 'required|uuid',
            'evaluations' => 'required|array',
            'evaluations.*.student_id' => 'required|uuid',
            'evaluations.*.competency_id' => 'required|uuid',
            'evaluations.*.score_literal' => 'nullable|string|in:AD,A,B,C',
            'evaluations.*.descriptive_conclusion' => 'nullable|string'
        ]);

        $termId = $request->term_id;
        
        DB::transaction(function () use ($termId, $request) {
            foreach ($request->evaluations as $ev) {
                if (empty($ev['score_literal']) && empty($ev['descriptive_conclusion'])) {
                    CompetencyEvaluation::where('term_id', $termId)
                        ->where('student_id', $ev['student_id'])
                        ->where('competency_id', $ev['competency_id'])
                        ->delete();
                } else {
                    CompetencyEvaluation::updateOrCreate(
                        [
                            'term_id' => $termId,
                            'student_id' => $ev['student_id'],
                            'competency_id' => $ev['competency_id']
                        ],
                        [
                            'id' => (string) Str::uuid(),
                            'score_literal' => $ev['score_literal'] ?? null,
                            'descriptive_conclusion' => $ev['descriptive_conclusion'] ?? null,
                            'created_by' => auth()->id(),
                        ]
                    );
                }
            }
        });

        return response()->json(['message' => 'Evaluaciones guardadas exitosamente.']);
    }
}
