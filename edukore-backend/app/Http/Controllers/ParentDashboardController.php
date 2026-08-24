<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Fee;
use App\Models\Obligation;
use App\Models\EventParticipant;

class ParentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // 1. Obtener los estudiantes del apoderado
        $students = Student::whereHas('families', function($q) use ($user) {
            $q->whereHas('members', function($q2) use ($user) {
                $q2->where('user_id', $user->id);
            });
        })->with(['user', 'enrollments' => function($q) {
            $q->whereIn('status', ['ACTIVE', 'matriculado'])->with('section.gradeLevel.level');
        }])->get();

        $studentIds = $students->pluck('id');

        $students = $students->map(function($student) {
            $currentSection = null;
            if ($student->enrollments->count() > 0) {
                $enrollment = $student->enrollments->first();
                if ($enrollment->section) {
                    $level = $enrollment->section->gradeLevel->level->name ?? '';
                    $grade = $enrollment->section->gradeLevel->name ?? '';
                    $sectionName = $enrollment->section->name ?? '';
                    $currentSection = "$grade $level — Sección $sectionName";
                }
            }

            // appending the calculated field dynamically for the API response
            $studentArray = $student->toArray();
            $studentArray['current_section'] = $currentSection;
            return $studentArray;
        });

        // 2. Obtener deudas económicas (Pensiones)
        $fees = Fee::whereIn('student_id', $studentIds)
            ->where('status', '!=', 'paid')
            ->orderBy('due_date', 'asc')
            ->get();

        // 3. Obtener obligaciones cívicas (Faenas, multas)
        $obligations = Obligation::where('user_id', $user->id)
            ->where('status', 'PENDING')
            ->latest()
            ->get();

        // 4. Obtener próximos eventos
        $events = EventParticipant::with('event')
            ->where('user_id', $user->id)
            ->whereHas('event', function($q) {
                $q->whereIn('status', ['SCHEDULED', 'IN_PROGRESS']);
            })
            ->get();

        return response()->json([
            'students' => $students,
            'fees' => $fees,
            'obligations' => $obligations,
            'upcoming_events' => $events
        ]);
    }
}
