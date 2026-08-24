<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;

class ParentPortalController extends Controller
{
    /**
     * TENANT ISOLATION — Análisis de la cadena de propiedad:
     *
     * La tabla `students` NO tiene tenant_id propio. El aislamiento se garantiza con 3 barreras:
     *
     * Barrera 1 (User-Tenant): El Student tiene un User (user_id). El User tiene tenant_id.
     *   Filtramos whereHas('user') con el tenant_id del solicitante.
     *
     * Barrera 2 (Family-Tenant): La tabla `families` SÍ tiene tenant_id.
     *   Filtramos que la familia pertenezca al mismo tenant del solicitante.
     *
     * Barrera 3 (Ownership): El padre solicitante debe estar en family_members
     *   con can_view_info = true para esa familia específica.
     *
     * Las tres barreras deben cumplirse simultáneamente.
     * Falla silenciosa con 404 — no revela la existencia del recurso al atacante.
     */
    public function myChildren(Request $request)
    {
        $user = $request->user();

        $students = Student::whereHas('user', function ($q) use ($user) {
                $q->where('tenant_id', $user->tenant_id);  // Barrera 1
            })
            ->whereHas('families', function ($q) use ($user) {
                $q->where('tenant_id', $user->tenant_id)   // Barrera 2
                  ->whereHas('members', function ($q2) use ($user) {
                      $q2->where('user_id', $user->id)     // Barrera 3
                         ->where('can_view_info', true);
                  });
            })
            ->with('user')
            ->get();

        return response()->json([
            'children' => $students
        ]);
    }

    public function childDetail(Request $request, $studentId)
    {
        $user = $request->user();

        $student = Student::where('id', $studentId)
            ->whereHas('user', function ($q) use ($user) {
                $q->where('tenant_id', $user->tenant_id);  // Barrera 1
            })
            ->whereHas('families', function ($q) use ($user) {
                $q->where('tenant_id', $user->tenant_id)   // Barrera 2
                  ->whereHas('members', function ($q2) use ($user) {
                      $q2->where('user_id', $user->id)     // Barrera 3
                         ->where('can_view_info', true);
                  });
            })
            ->firstOrFail(); // 404 silencioso — no revela existencia en otro tenant

        $student->load([
            'enrollments.section.courseAssignments.course',
            'enrollments.attendance.courseAssignment.course',
            'enrollments.grades' => function ($query) {
                $query->whereHas('evaluation', function ($q) {
                    $q->where('status', 'PUBLISHED'); // Solo notas publicadas para padres
                })->with('evaluation');
            }
        ]);

        $grades = collect();
        $attendances = collect();

        foreach ($student->enrollments as $enrollment) {
            foreach ($enrollment->grades as $grade) {
                $gradeData = $grade->toArray();
                $gradeData['course'] = $enrollment->section->courseAssignments->first()?->course ?? null;
                $grades->push($gradeData);
            }
            foreach ($enrollment->attendance as $att) {
                $attData = $att->toArray();
                $attData['course'] = $att->courseAssignment?->course ?? null;
                $attendances->push($attData);
            }
        }

        $studentData = $student->toArray();
        $studentData['grades'] = $grades;
        $studentData['attendance'] = $attendances;

        return response()->json([
            'student' => $studentData
        ]);
    }
}
