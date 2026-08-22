<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Enrollment;
use Illuminate\Http\JsonResponse;

class StudentAcademicController extends Controller
{
    /**
     * Get the full academic history (enrollments) of a student.
     */
    public function getHistory(string $studentId): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        // Verify the student belongs to the tenant
        $student = Student::whereHas('user', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->findOrFail($studentId);

        $enrollments = Enrollment::with([
            'section.gradeLevel.level.academicYear'
        ])
        ->where('student_id', $student->id)
        ->orderByDesc('created_at')
        ->get();

        return response()->json([
            'data' => $enrollments
        ]);
    }
}
