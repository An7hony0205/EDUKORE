# -*- coding: utf-8 -*-
import sys

with open('app/Http/Controllers/SectionController.php', 'r', encoding='utf-8') as f:
    content = f.read()

details_method = """
    /**
     * Get aggregated details for a section.
     */
    public function details(string $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $section = Section::with([
            'gradeLevel.level.academicYear',
            'enrollments',
            'courseAssignments.course',
            'courseAssignments.teacher'
        ])
        ->whereHas('gradeLevel.level.academicYear', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })
        ->findOrFail($id);

        $studentsCount = $section->enrollments->where('status', 'matriculado')->count();
        if ($studentsCount === 0) {
            $studentsCount = $section->enrollments->count(); // fallback if status is empty
        }
        $coursesCount = $section->courseAssignments->unique('course_id')->count();
        $teachersCount = $section->courseAssignments->unique('teacher_id')->count();

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
            ]
        ]);
    }
}
"""

content = content.replace("}\n", details_method)

with open('app/Http/Controllers/SectionController.php', 'w', encoding='utf-8') as f:
    f.write(content)
