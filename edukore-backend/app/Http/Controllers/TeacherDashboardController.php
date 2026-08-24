<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CourseAssignment;
use App\Models\Evaluation;

class TeacherDashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $teacherId = auth()->id();

        $assignments = CourseAssignment::with(['course', 'section.gradeLevel.level.academicYear'])
            ->whereHas('course', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->where('teacher_id', $teacherId)
            ->get();

        // Append students count for each assignment's section
        foreach ($assignments as $assignment) {
            $assignment->students_count = $assignment->section->enrollments()->count();
        }

        return response()->json([
            'assignments' => $assignments
        ]);
    }

    public function gradebook(Request $request, $id)
    {
        $teacherId = auth()->id();

        $assignment = CourseAssignment::with([
                'course', 
                'section.enrollments.student.user',
                'section.enrollments.grades.audits'
            ])
            ->where('id', $id)
            ->where('teacher_id', $teacherId)
            ->firstOrFail();

        $evaluations = Evaluation::with('academicPeriod')
            ->where('course_assignment_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'assignment' => $assignment,
            'evaluations' => $evaluations
        ]);
    }
}
