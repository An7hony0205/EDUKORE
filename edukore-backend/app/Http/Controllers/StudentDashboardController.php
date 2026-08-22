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
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json(['message' => 'Student profile not found'], 404);
        }

        // Obtener matrículas actuales
        $enrollments = Enrollment::with(['section.courseAssignments.course', 'section.courseAssignments.teacher'])
            ->where('student_id', $student->id)
            ->where('status', 'ACTIVE')
            ->get();

        $enrollmentIds = $enrollments->pluck('id');

        // Extraer los cursos de las secciones matriculadas
        $courses = [];
        foreach ($enrollments as $enrollment) {
            if ($enrollment->section && $enrollment->section->courseAssignments) {
                foreach ($enrollment->section->courseAssignments as $ca) {
                    $courses[] = $ca;
                }
            }
        }

        // Obtener notas de evaluaciones PUBLICADAS
        $grades = Grade::with(['evaluation.courseAssignment.course'])
            ->whereIn('enrollment_id', $enrollmentIds)
            ->whereHas('evaluation', function($q) {
                $q->where('status', 'PUBLISHED');
            })
            ->latest('created_at')
            ->take(10)
            ->get();

        // Obtener últimas faltas/asistencias
        $attendances = Attendance::with(['courseAssignment.course'])
            ->whereIn('enrollment_id', $enrollmentIds)
            ->latest('date')
            ->take(5)
            ->get();

        return response()->json([
            'student' => $student,
            'courses' => $courses,
            'recent_grades' => $grades,
            'recent_attendance' => $attendances
        ]);
    }
}
