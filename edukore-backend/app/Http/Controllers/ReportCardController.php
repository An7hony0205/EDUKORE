<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Course;
use App\Models\CompetencyEvaluation;
use App\Models\AcademicPeriod;
use App\Models\SectionAttendance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class ReportCardController extends Controller
{
    public function progressReport(Request $request, $studentId)
    {
        try {
            $student = Student::with(['user', 'section.tutor', 'section.grade.academicLevel'])->findOrFail($studentId);

            // Obtener sección activa o la más reciente
            $section = $student->section ?? $student->enrollments()->latest()->first()?->section;

            // Obtener datos del tenant con fallbacks seguros
            $institutionName = config('app.name', 'I.E. FE Y ALEGRIA');
            $dre = 'DRE ICA';
            $ugel = 'UGEL PISCO';
            $modularCode = '1393586-0';

            // Cursos y competencias con relaciones seguras
            $courses = Course::with('competencies')->get();

            // Inyectar competencias por defecto oficiales si no existen
            foreach ($courses as $course) {
                if ($course->competencies->isEmpty()) {
                    $nameLower = strtolower($course->name);
                    if (str_contains($nameLower, 'matemática')) {
                        $defaultComps = [
                            (object)['id' => 'mat1', 'name' => 'Resuelve problemas de cantidad'],
                            (object)['id' => 'mat2', 'name' => 'Resuelve problemas de regularidad, equivalencia y cambio'],
                            (object)['id' => 'mat3', 'name' => 'Resuelve problemas de forma, movimiento y localización'],
                            (object)['id' => 'mat4', 'name' => 'Resuelve problemas de gestión de datos e incertidumbre'],
                        ];
                    } elseif (str_contains($nameLower, 'comunicación') || str_contains($nameLower, 'comunicacion')) {
                        $defaultComps = [
                            (object)['id' => 'com1', 'name' => 'Se comunica oralmente en su lengua materna'],
                            (object)['id' => 'com2', 'name' => 'Lee diversos tipos de textos escritos'],
                            (object)['id' => 'com3', 'name' => 'Escribe diversos tipos de textos'],
                        ];
                    } elseif (str_contains($nameLower, 'ciencia') || str_contains($nameLower, 'tecnolog')) {
                        $defaultComps = [
                            (object)['id' => 'cyt1', 'name' => 'Indaga mediante métodos científicos'],
                            (object)['id' => 'cyt2', 'name' => 'Explica el mundo físico basándose en conocimientos'],
                            (object)['id' => 'cyt3', 'name' => 'Diseña y construye soluciones tecnológicas'],
                        ];
                    } elseif (str_contains($nameLower, 'personal') || str_contains($nameLower, 'social')) {
                        $defaultComps = [
                            (object)['id' => 'ps1', 'name' => 'Construye su identidad'],
                            (object)['id' => 'ps2', 'name' => 'Convive y participa democráticamente'],
                            (object)['id' => 'ps3', 'name' => 'Construye interpretaciones históricas'],
                        ];
                    } else {
                        // Genérico CNEB
                        $defaultComps = [
                            (object)['id' => 'gen1', 'name' => 'Desarrolla habilidades cognitivas'],
                            (object)['id' => 'gen2', 'name' => 'Aplica conocimientos en su entorno'],
                        ];
                    }
                    $course->setRelation('competencies', collect($defaultComps));
                }
            }

            // Calificaciones existentes o array vacío por defecto
            $evaluations = CompetencyEvaluation::where('student_id', $student->id)->get();

            // Periodos académicos (Bimestres)
            $terms = AcademicPeriod::orderBy('start_date', 'asc')->get();

            // Asistencias agrupadas por bimestre
            $attendances = SectionAttendance::where('student_id', $student->id)->get();
            $attendanceStats = [];
            foreach ($terms->take(4) as $idx => $term) {
                $termAttendances = $attendances->filter(function($att) use ($term) {
                    return $att->date >= $term->start_date && $att->date <= $term->end_date;
                });
                
                $attendanceStats["b".($idx+1)] = [
                    'justified_absences' => $termAttendances->where('status', 'justified')->count(),
                    'unjustified_absences' => $termAttendances->where('status', 'absent')->count(),
                    'justified_tardies' => 0, // El modelo no diferencia explícitamente, asumimos 0 para este ejemplo
                    'unjustified_tardies' => $termAttendances->where('status', 'late')->count(),
                ];
            }
            // Llenar faltantes si hay menos de 4 periodos configurados
            for ($i = count($terms); $i < 4; $i++) {
                 $attendanceStats["b".($i+1)] = [
                    'justified_absences' => 0,
                    'unjustified_absences' => 0,
                    'justified_tardies' => 0,
                    'unjustified_tardies' => 0,
                ];
            }

            $pdf = Pdf::loadView('reports.progress_report', compact(
                'student', 'section', 'institutionName', 'dre', 'ugel', 'modularCode', 'courses', 'evaluations', 'attendanceStats', 'terms'
            ))->setPaper('a4', 'portrait');

            $pdfBase64 = base64_encode($pdf->output());

            return response()->json([
                'success' => true,
                'filename' => "Libreta_{$student->id}.pdf",
                'pdf_base64' => $pdfBase64
            ]);
        } catch (\Throwable $e) {
            Log::error("Error generando libreta: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
