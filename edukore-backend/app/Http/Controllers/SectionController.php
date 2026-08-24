<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $sections = Section::with('gradeLevel.level.academicYear')
            ->whereHas('gradeLevel.level.academicYear', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->get();
        return response()->json($sections);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $validated = $request->validate([
            'grade_level_id' => 'required|uuid|exists:grade_levels,id',
            'name'           => 'required|string|max:255',
            'capacity'       => 'nullable|integer|min:1',
        ]);
        $section = Section::create($validated);
        return response()->json($section->load('gradeLevel.level.academicYear'), 201);
    }

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

    public function destroy(string $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $section = Section::whereHas('gradeLevel.level.academicYear', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->findOrFail($id);
        $section->delete();
        return response()->json(null, 204);
    }

    public function details(string $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $section = Section::with([
            'gradeLevel.level.academicYear',
            'enrollments.student.user',
            'courseAssignments.course',
            'courseAssignments.teacher'
        ])
        ->whereHas('gradeLevel.level.academicYear', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })
        ->findOrFail($id);

        $studentsCount = $section->enrollments->where('status', 'matriculado')->count();
        if ($studentsCount === 0) {
            $studentsCount = $section->enrollments->count();
        }
        $coursesCount = $section->courseAssignments->unique('course_id')->count();
        $teachersCount = $section->courseAssignments->unique('teacher_id')->count();

        // format students
        $students = $section->enrollments->map(function($enrollment) {
            return [
                'id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'name' => $enrollment->student->user->name ?? 'N/A',
                'email' => $enrollment->student->user->email ?? 'N/A',
                'enrollment_number' => $enrollment->student->enrollment_number,
                'status' => $enrollment->status
            ];
        });

        // format teachers & courses
        $teachersMap = [];
        $coursesList = [];

        foreach($section->courseAssignments as $assignment) {
            $teacherId = $assignment->teacher_id;
            $courseId = $assignment->course_id;

            if ($assignment->teacher) {
                if (!isset($teachersMap[$teacherId])) {
                    $teachersMap[$teacherId] = [
                        'id' => $teacherId,
                        'name' => $assignment->teacher->name,
                        'email' => $assignment->teacher->email,
                        'courses' => []
                    ];
                }
                $teachersMap[$teacherId]['courses'][] = $assignment->course->name;
            }

            if ($assignment->course) {
                $coursesList[] = [
                    'id' => $assignment->course->id,
                    'name' => $assignment->course->name,
                    'teacher_name' => $assignment->teacher->name ?? 'Sin asignar'
                ];
            }
        }

        return response()->json([
            'section' => [
                'id' => $section->id,
                'name' => $section->name,
                'grade_level' => $section->gradeLevel->name,
                'level' => $section->gradeLevel->level->name,
                'academic_year' => $section->gradeLevel->level->academicYear->year_name,
                'academic_year_id' => $section->gradeLevel->level->academicYear->id,
            ],
            'stats' => [
                'students_count' => $studentsCount,
                'courses_count' => $coursesCount,
                'teachers_count' => $teachersCount,
            ],
            'lists' => [
                'students' => $students,
                'teachers' => array_values($teachersMap),
                'courses' => $coursesList
            ]
        ]);
    }
}
