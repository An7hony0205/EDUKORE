<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ParentProfile;

class ParentPortalController extends Controller
{
    public function myChildren(Request $request)
    {
        $parent = ParentProfile::where('user_id', $request->user()->id)->firstOrFail();
        
        $parent->load([
            'students.user'
        ]);
        
        return response()->json([
            'children' => $parent->students
        ]);
    }

    public function childDetail(Request $request, $studentId)
    {
        $parent = ParentProfile::where('user_id', $request->user()->id)->firstOrFail();
        
        // Ensure student belongs to parent
        $student = $parent->students()->where('students.id', $studentId)->firstOrFail();

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
