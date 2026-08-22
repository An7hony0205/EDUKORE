<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Section;
use App\Services\GradeCalculationService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    protected GradeCalculationService $gradeService;

    public function __construct(GradeCalculationService $gradeService)
    {
        $this->gradeService = $gradeService;
    }

    public function studentReportCard(Request $request, $studentId)
    {
        $student = Student::with([
            'user',
            'enrollments.section.gradeLevel.level',
            'enrollments.section.courseAssignments.course',
            'enrollments.attendance'
        ])->findOrFail($studentId);

        $periodId = $request->query('academic_period_id');

        $reportData = [
            'student_name' => $student->user->name,
            'student_email' => $student->user->email,
            'courses' => []
        ];

        foreach ($student->enrollments as $enrollment) {
            $courseAssignment = $enrollment->section->courseAssignments->first();
            $courseName = $courseAssignment ? $courseAssignment->course->name : 'N/A';
            
            $breakdown = $this->gradeService->getStudentBreakdown($enrollment->id, $periodId);
            
            // Calculate attendance stats
            $attendances = $enrollment->attendance;
            $present = $attendances->where('status', 'Presente')->count();
            $absent = $attendances->where('status', 'Ausente')->count();
            $late = $attendances->where('status', 'Tardanza')->count();

            $reportData['courses'][] = [
                'course_name' => $courseName,
                'average' => $breakdown['average'],
                'attendance' => [
                    'present' => $present,
                    'absent' => $absent,
                    'late' => $late
                ]
            ];
        }

        return response()->json($reportData);
    }

    public function generateSectionPdfs(Request $request, $sectionId)
    {
        $section = Section::with(['enrollments.student.user'])->findOrFail($sectionId);
        $periodId = $request->query('academic_period_id');

        // Note: For a real production system with hundreds of students, 
        // this should be pushed to a Background Job and zipped. 
        // For demonstration, we will generate a single PDF with page breaks.

        $studentsData = [];

        foreach ($section->enrollments as $enrollment) {
            $student = $enrollment->student;
            
            $reportData = [
                'student_name' => $student->user->name,
                'courses' => []
            ];

            // Re-fetch individual student enrollments for cross-course data if needed
            $allEnrollments = $student->enrollments()->with([
                'section.courseAssignments.course',
                'attendance'
            ])->get();

            foreach ($allEnrollments as $stuEnroll) {
                $ca = $stuEnroll->section->courseAssignments->first();
                $breakdown = $this->gradeService->getStudentBreakdown($stuEnroll->id, $periodId);
                
                $reportData['courses'][] = [
                    'course_name' => $ca ? $ca->course->name : 'N/A',
                    'average' => $breakdown['average']
                ];
            }
            $studentsData[] = $reportData;
        }

        $pdf = Pdf::loadView('reports.report_card_bulk', ['studentsData' => $studentsData]);
        
        return $pdf->download('report_cards_section_'.$section->name.'.pdf');
    }

    
    public function exportReportCardPdf(Request $request, $studentId)
    {
        $tenantId = auth()->user()->tenant_id;
        $student = Student::whereHas('user', function($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->with([
            'user',
            'enrollments.section.gradeLevel.level',
            'enrollments.section.courseAssignments.course',
            'enrollments.attendance'
        ])->findOrFail($studentId);

        $periodId = $request->query('academic_period_id');

        $reportData = [
            'student_name' => $student->user->name,
            'student_email' => $student->user->email,
            'courses' => []
        ];

        foreach ($student->enrollments as $enrollment) {
            $courseAssignment = $enrollment->section->courseAssignments->first();
            $courseName = $courseAssignment ? $courseAssignment->course->name : 'N/A';
            
            $breakdown = $this->gradeService->getStudentBreakdown($enrollment->id, $periodId);
            
            $attendances = $enrollment->attendance;
            $present = $attendances->where('status', 'Presente')->count();
            $absent = $attendances->where('status', 'Ausente')->count();
            $late = $attendances->where('status', 'Tardanza')->count();

            $reportData['courses'][] = [
                'course_name' => $courseName,
                'average' => $breakdown['average'],
                'attendance' => [
                    'present' => $present,
                    'absent' => $absent,
                    'late' => $late
                ]
            ];
        }

        $pdf = Pdf::loadView('reports.report_card', ['data' => $reportData]);
        
        $base64 = base64_encode($pdf->output());

        return response()->json([
            'filename' => "boletin_{$student->id}.pdf",
            'file_data' => $base64
        ]);
    }

    public function exportEnrollmentsCsv()
    {
        $tenantId = auth()->user()->tenant_id;
        $enrollments = \App\Models\Enrollment::whereHas('student.user', function($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->with(['student.user', 'section.gradeLevel.level'])->get();

        $csvData = "\xEF\xBB\xBFID;Nombre;Email;Seccion;Grado;Nivel;Estado\n";
        
        foreach ($enrollments as $e) {
            $name = $e->student->user->name ?? '';
            $email = $e->student->user->email ?? '';
            $section = $e->section->name ?? '';
            $grade = $e->section->gradeLevel->name ?? '';
            $level = $e->section->gradeLevel->level->name ?? '';
            $status = $e->status ?? '';
            
            $csvData .= "{$e->id};\"{$name}\";\"{$email}\";\"{$section}\";\"{$grade}\";\"{$level}\";\"{$status}\"\n";
        }

        $base64 = base64_encode($csvData);

        return response()->json([
            'filename' => 'matriculas_export.csv',
            'file_data' => $base64
        ]);
    }
}