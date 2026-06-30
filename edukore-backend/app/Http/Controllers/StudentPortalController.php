<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;

class StudentPortalController extends Controller
{
    public function myGrades(Request $request)
    {
        $student = Student::where('user_id', $request->user()->id)->firstOrFail();
        
        $student->load([
            'enrollments.section.courseAssignments.course',
            'enrollments.grades' => function ($query) {
                $query->whereHas('evaluation', function ($q) {
                    $q->where('is_published', true);
                })->with('evaluation');
            }
        ]);
        
        $grades = collect();
        foreach ($student->enrollments as $enrollment) {
            foreach ($enrollment->grades as $grade) {
                // Flatten the response for the frontend
                $gradeData = $grade->toArray();
                $gradeData['course'] = $enrollment->section->courseAssignments->first()->course ?? null;
                $grades->push($gradeData);
            }
        }
        
        return response()->json($grades);
    }

    public function myAttendance(Request $request)
    {
        $student = Student::where('user_id', $request->user()->id)->firstOrFail();
        
        $student->load([
            'enrollments.attendance.courseAssignment.course',
            'enrollments.section'
        ]);
        
        $attendances = collect();
        foreach ($student->enrollments as $enrollment) {
            foreach ($enrollment->attendance as $att) {
                $attData = $att->toArray();
                $attData['course'] = $att->courseAssignment->course ?? null;
                $attendances->push($attData);
            }
        }
        
        return response()->json($attendances);
    }
}
