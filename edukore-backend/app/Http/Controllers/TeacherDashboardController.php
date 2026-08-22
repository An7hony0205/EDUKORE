<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CourseAssignment;
use App\Models\Evaluation;

class TeacherDashboardController extends Controller
{
    /**
     * Get the dashboard overview for the authenticated teacher.
     */
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $teacherId = auth()->id();

        // Get courses assigned to this teacher
        $assignments = CourseAssignment::with(['course', 'section.gradeLevel.level.academicYear'])
            ->whereHas('course', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->where('teacher_id', $teacherId)
            ->get();

        return response()->json([
            'assignments' => $assignments
        ]);
    }

    /**
     * Get the full gradebook for a specific course assignment
     * Crosses Students x Evaluations
     */
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
