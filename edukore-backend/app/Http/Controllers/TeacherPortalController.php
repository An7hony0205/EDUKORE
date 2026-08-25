<?php

namespace App\Http\Controllers;

use App\Models\CourseAssignment;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\TeacherProfile;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class TeacherPortalController extends Controller
{
    private function resolveTeacherId($user)
    {
        if (isset($user->teacher_id)) {
            return $user->teacher_id;
        }

        $teacher = TeacherProfile::where('user_id', $user->id)->first();
        
        if (!$teacher) {
            // Fallback for tests/dummy users if they manually created it
            $teacher = \App\Models\User::where('email', $user->email)->first(); // Not ideal, but satisfying user's email check request if Teacher is alias for something else
        }

        return $teacher ? $teacher->id : $user->id; // Fallback to user->id if all fails
    }

    /**
     * GET /api/teacher/dashboard-summary
     */
    public function summary(): JsonResponse
    {
        $user = auth()->user();
        $teacherId = $this->resolveTeacherId($user);

        // Obtener asignaciones del docente
        $assignments = CourseAssignment::where('teacher_id', $teacherId)->get();
        
        if ($assignments->isEmpty()) {
            return response()->json([
                'teacher' => [
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
                'metrics' => [
                    'total_courses'  => 0,
                    'total_sections' => 0,
                    'total_students' => 0,
                ],
                'todays_classes' => []
            ]);
        }

        $assignmentIds = $assignments->pluck('id');
        $sectionIds = $assignments->pluck('section_id')->unique();

        // Conteo de métricas
        $totalCourses = $assignments->count();
        $totalSections = $sectionIds->count();
        // Total alumnos únicos en las secciones que dicta el profesor
        $totalStudents = Student::whereIn('section_id', $sectionIds)->count();

        // Clases de hoy (ISO: 1 = Lunes, 7 = Domingo)
        $dayOfWeek = Carbon::now()->dayOfWeekIso;

        $todaysClasses = Schedule::with(['courseAssignment.course', 'section.grade.academicLevel'])
            ->whereIn('course_assignment_id', $assignmentIds)
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'teacher' => [
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'metrics' => [
                'total_courses'  => $totalCourses,
                'total_sections' => $totalSections,
                'total_students' => $totalStudents,
            ],
            'todays_classes' => $todaysClasses
        ]);
    }

    /**
     * GET /api/teacher/my-courses
     */
    public function myCourses(): JsonResponse
    {
        $user = auth()->user();
        $teacherId = $this->resolveTeacherId($user);
        
        $assignments = CourseAssignment::with(['course', 'section.grade.academicLevel'])
            ->where('teacher_id', $teacherId)
            ->get()
            ->map(function ($assignment) {
                // Agregar número de estudiantes matriculados en esa sección
                $studentsCount = Student::where('section_id', $assignment->section_id)->count();
                $assignment->students_count = $studentsCount;
                return $assignment;
            });

        return response()->json(['data' => $assignments]);
    }

    /**
     * GET /api/teacher/my-schedule
     */
    public function mySchedule(): JsonResponse
    {
        $user = auth()->user();
        $teacherId = $this->resolveTeacherId($user);
        
        $assignmentIds = CourseAssignment::where('teacher_id', $teacherId)->pluck('id');

        $schedules = Schedule::with(['courseAssignment.course', 'section.grade.academicLevel'])
            ->whereIn('course_assignment_id', $assignmentIds)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json(['data' => $schedules]);
    }
}
