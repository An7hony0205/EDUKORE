<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Attendance;

class StudentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $student = Student::with('user')->where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json(['message' => 'Student profile not found'], 404);
        }

        // Obtener matrículas (con status activo o matriculado)
        $enrollments = Enrollment::with([
            'section.courseAssignments.course', 
            'section.courseAssignments.teacher',
            'section.gradeLevel.level'
        ])
            ->where('student_id', $student->id)
            ->whereIn('status', ['ACTIVE', 'matriculado'])
            ->get();

        $enrollmentIds = $enrollments->pluck('id');

        $courses = [];
        $currentSection = null;

        foreach ($enrollments as $enrollment) {
            if ($enrollment->section && !$currentSection) {
                $level = $enrollment->section->gradeLevel->level->name ?? '';
                $grade = $enrollment->section->gradeLevel->name ?? '';
                $section = $enrollment->section->name ?? '';
                $currentSection = "$grade $level — Sección $section";
            }
            if ($enrollment->section && $enrollment->section->courseAssignments) {
                foreach ($enrollment->section->courseAssignments as $ca) {
                    $courses[] = $ca;
                }
            }
        }

        $grades = Grade::with(['evaluation.courseAssignment.course'])
            ->whereIn('enrollment_id', $enrollmentIds)
            ->whereHas('evaluation', function($q) {
                $q->where('status', 'PUBLISHED');
            })
            ->latest('created_at')
            ->take(10)
            ->get();

        $attendances = Attendance::with(['courseAssignment.course'])
            ->whereIn('enrollment_id', $enrollmentIds)
            ->latest('date')
            ->take(5)
            ->get();

        return response()->json([
            'student' => $student,
            'current_section' => $currentSection,
            'courses' => $courses,
            'recent_grades' => $grades,
            'recent_attendance' => $attendances
        ]);
    }
}
