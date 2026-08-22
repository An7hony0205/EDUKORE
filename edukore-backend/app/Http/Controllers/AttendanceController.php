<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'course_assignment_id' => 'required|uuid',
            'date' => 'nullable|date',
        ]);

        $query = Attendance::with('enrollment.student')
            ->where('course_assignment_id', $request->course_assignment_id);

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        return response()->json($query->get());
    }

    public function storeBulk(Request $request)
    {
        $request->validate([
            'course_assignment_id' => 'required|uuid',
            'date' => 'required|date|before_or_equal:today',
            'attendances' => 'required|array',
            'attendances.*.enrollment_id' => 'required|uuid',
            'attendances.*.status' => 'required|string|in:Presente,Ausente,Tardanza,Justificado',
        ]);

        // Validate enrollments belong to the course_assignment_id
        $enrollmentIds = collect($request->attendances)->pluck('enrollment_id');
        $courseAssignment = \App\Models\CourseAssignment::findOrFail($request->course_assignment_id);
        $validEnrollments = Enrollment::whereIn('id', $enrollmentIds)
            ->where('section_id', $courseAssignment->section_id)
            ->pluck('id');

        if ($validEnrollments->count() !== $enrollmentIds->count()) {
            return response()->json(['message' => 'Invalid enrollments for this course assignment.'], 422);
        }

        $records = [];
        foreach ($request->attendances as $attendanceData) {
            $records[] = Attendance::updateOrCreate(
                [
                    'course_assignment_id' => $request->course_assignment_id,
                    'enrollment_id' => $attendanceData['enrollment_id'],
                    'date' => $request->date,
                ],
                [
                    'status' => $attendanceData['status'],
                ]
            );
        }

        return response()->json([
            'message' => 'Attendance saved successfully.',
            'data' => $records
        ]);
    }
}
