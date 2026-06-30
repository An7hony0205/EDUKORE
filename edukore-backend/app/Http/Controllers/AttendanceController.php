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
            ->whereHas('enrollment', function ($q) use ($request) {
                $q->where('course_assignment_id', $request->course_assignment_id);
            });

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        return response()->json($query->get());
    }

    public function storeBulk(Request $request)
    {
        $request->validate([
            'course_assignment_id' => 'required|uuid',
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.enrollment_id' => 'required|uuid',
            'attendances.*.status' => 'required|string',
            'attendances.*.remarks' => 'nullable|string',
        ]);

        // Validate enrollments belong to the course_assignment_id
        $enrollmentIds = collect($request->attendances)->pluck('enrollment_id');
        $validEnrollments = Enrollment::whereIn('id', $enrollmentIds)
            ->where('course_assignment_id', $request->course_assignment_id)
            ->pluck('id');

        if ($validEnrollments->count() !== $enrollmentIds->count()) {
            return response()->json(['message' => 'Invalid enrollments for this course assignment.'], 422);
        }

        $records = [];
        foreach ($request->attendances as $attendanceData) {
            $records[] = Attendance::updateOrCreate(
                [
                    'enrollment_id' => $attendanceData['enrollment_id'],
                    'date' => $request->date,
                ],
                [
                    'status' => $attendanceData['status'],
                    'remarks' => $attendanceData['remarks'] ?? null,
                ]
            );
        }

        return response()->json([
            'message' => 'Attendance saved successfully.',
            'data' => $records
        ]);
    }
}
