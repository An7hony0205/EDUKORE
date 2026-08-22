<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ParentProfile;

class ParentPortalController extends Controller
{
    public function myChildren(Request $request)
    {
        $user = $request->user();
        
        $students = \App\Models\Student::whereHas('families.members', function ($q) use ($user) {
            $q->where('user_id', $user->id)->where('can_view_info', true);
        })->with('user')->get();
        
        return response()->json([
            'children' => $students
        ]);
    }

    public function childDetail(Request $request, $studentId)
    {
        $user = $request->user();
        
        // Ensure student belongs to parent's family
        $student = \App\Models\Student::where('id', $studentId)
            ->whereHas('families.members', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('can_view_info', true);
            })->firstOrFail();

        $student->load([
            'enrollments.section.courseAssignments.course',
            'enrollments.attendance.courseAssignment.course',
            'enrollments.grades' => function ($query) {
                $query->whereHas('evaluation', function ($q) {
                    $q->where('is_published', true);
                })->with('evaluation');
            }
        ]);

        $grades = collect();
        $attendances = collect();
        
        foreach ($student->enrollments as $enrollment) {
            foreach ($enrollment->grades as $grade) {
                $gradeData = $grade->toArray();
                $gradeData['course'] = $enrollment->section->courseAssignments->first()->course ?? null;
                $grades->push($gradeData);
            }
            foreach ($enrollment->attendance as $att) {
                $attData = $att->toArray();
                $attData['course'] = $att->courseAssignment->course ?? null;
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
