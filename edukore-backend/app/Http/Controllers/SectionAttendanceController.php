<?php

namespace App\Http\Controllers;

use App\Models\AcademicSection;
use App\Models\SectionAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SectionAttendanceController extends Controller
{
    /**
     * GET /api/section-attendance
     * Devuelve la lista de alumnos de la sección con su asistencia para la fecha dada.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'section_id' => 'required|uuid|exists:academic_sections,id',
            'date'       => 'required|date',
        ]);

        $section = AcademicSection::with([
            'students.user:id,name',
        ])->findOrFail($request->section_id);

        // Asistencias ya grabadas para esa fecha
        $existing = SectionAttendance::where('section_id', $request->section_id)
            ->where('date', $request->date)
            ->get()
            ->keyBy('student_id');

        $rows = $section->students->map(function ($student) use ($existing) {
            $att = $existing->get($student->id);
            return [
                'student_id'       => $student->id,
                'enrollment_number'=> $student->enrollment_number,
                'name'             => $student->user?->name ?? '—',
                'status'           => $att?->status ?? 'present',
                'remarks'          => $att?->remarks ?? '',
                'saved'            => (bool) $att,
            ];
        })->sortBy('name')->values();

        return response()->json([
            'section' => [
                'id'   => $section->id,
                'name' => $section->name,
            ],
            'date'     => $request->date,
            'students' => $rows,
        ]);
    }

    /**
     * POST /api/section-attendance/bulk
     * Guarda / actualiza en lote la asistencia de una sección para una fecha.
     */
    public function bulk(Request $request): JsonResponse
    {
        $request->validate([
            'section_id'              => 'required|uuid|exists:academic_sections,id',
            'date'                    => 'required|date|before_or_equal:today',
            'attendances'             => 'required|array|min:1',
            'attendances.*.student_id'=> 'required|uuid|exists:students,id',
            'attendances.*.status'    => 'required|string|in:present,late,absent,justified',
            'attendances.*.remarks'   => 'nullable|string|max:500',
        ]);

        $createdBy = auth()->id();
        $now       = now();

        DB::beginTransaction();
        try {
            foreach ($request->attendances as $item) {
                SectionAttendance::updateOrCreate(
                    [
                        'section_id' => $request->section_id,
                        'student_id' => $item['student_id'],
                        'date'       => $request->date,
                    ],
                    [
                        'id'         => (string) Str::uuid(),
                        'status'     => $item['status'],
                        'remarks'    => $item['remarks'] ?? null,
                        'created_by' => $createdBy,
                        'updated_at' => $now,
                    ]
                );
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al guardar asistencia.', 'error' => $e->getMessage()], 500);
        }

        // Métricas rápidas
        $saved = collect($request->attendances);
        $metrics = [
            'present'   => $saved->where('status', 'present')->count(),
            'late'      => $saved->where('status', 'late')->count(),
            'absent'    => $saved->where('status', 'absent')->count(),
            'justified' => $saved->where('status', 'justified')->count(),
            'total'     => $saved->count(),
        ];

        return response()->json([
            'message' => 'Asistencia guardada correctamente.',
            'metrics' => $metrics,
        ]);
    }
}
