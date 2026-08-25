<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\CourseAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ScheduleController extends Controller
{
    /**
     * GET /api/schedules?section_id={uuid}
     * Devuelve los bloques horarios de la sección agrupados por día (1-5).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'section_id' => 'required|uuid|exists:academic_sections,id',
        ]);

        $blocks = Schedule::with([
            'courseAssignment.course:id,name',
            'courseAssignment.teacher:id,name',
        ])
            ->where('section_id', $request->section_id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(fn($b) => $this->formatBlock($b));

        return response()->json(['data' => $blocks]);
    }

    /**
     * POST /api/schedules
     * Crea un bloque horario con validación de solapamiento de docente.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'section_id'            => 'required|uuid|exists:academic_sections,id',
            'course_assignment_id'  => 'nullable|uuid|exists:course_assignments,id',
            'day_of_week'           => 'required|integer|min:1|max:5',
            'start_time'            => 'required|date_format:H:i',
            'end_time'              => 'required|date_format:H:i|after:start_time',
            'room'                  => 'nullable|string|max:60',
            'type'                  => 'nullable|string|in:academic,break,assembly',
        ]);

        // ── Validación de solapamiento de docente ─────────────────────────────
        if ($request->course_assignment_id) {
            $assignment = CourseAssignment::findOrFail($request->course_assignment_id);
            $teacherId  = $assignment->teacher_id;

            // Buscar si el docente ya tiene un bloque solapado en la misma franja
            $conflict = Schedule::where('day_of_week', $request->day_of_week)
                ->where('type', 'academic')
                ->where('id', '!=', '') // placeholder; real logic below
                ->whereHas('courseAssignment', fn($q) => $q->where('teacher_id', $teacherId))
                ->where(function ($q) use ($request) {
                    // Solapamiento: el bloque existente comienza antes de que termine el nuevo
                    // y termina después de que comience el nuevo
                    $q->where('start_time', '<', $request->end_time)
                      ->where('end_time', '>', $request->start_time);
                })
                ->with(['courseAssignment.section:id,name', 'courseAssignment.section.grade:id,name'])
                ->first();

            if ($conflict) {
                $otherSection = optional($conflict->courseAssignment?->section)->name ?? 'otra sección';
                return response()->json([
                    'message' => "El docente {$assignment->teacher->name} ya tiene clase en la Sección {$otherSection} en este horario.",
                    'errors'  => ['course_assignment_id' => ["Conflicto de horario con Sección {$otherSection}"]],
                ], 422);
            }

            // Solapamiento en la misma sección (mismo slot ocupado)
            $sectionConflict = Schedule::where('section_id', $request->section_id)
                ->where('day_of_week', $request->day_of_week)
                ->where(function ($q) use ($request) {
                    $q->where('start_time', '<', $request->end_time)
                      ->where('end_time', '>', $request->start_time);
                })
                ->exists();

            if ($sectionConflict) {
                return response()->json([
                    'message' => 'Ya existe un bloque en esta sección para ese horario.',
                    'errors'  => ['start_time' => ['Horario ocupado en esta sección.']],
                ], 422);
            }
        }

        $block = Schedule::create([
            'id'                   => (string) Str::uuid(),
            'section_id'           => $request->section_id,
            'course_assignment_id' => $request->course_assignment_id,
            'day_of_week'          => $request->day_of_week,
            'start_time'           => $request->start_time,
            'end_time'             => $request->end_time,
            'room'                 => $request->room,
            'type'                 => $request->type ?? 'academic',
        ]);

        $block->load(['courseAssignment.course:id,name', 'courseAssignment.teacher:id,name']);

        return response()->json([
            'message' => 'Bloque añadido correctamente.',
            'data'    => $this->formatBlock($block),
        ], 201);
    }

    /**
     * DELETE /api/schedules/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $block = Schedule::findOrFail($id);
        $block->delete();

        return response()->json(['message' => 'Bloque eliminado.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private function formatBlock(Schedule $b): array
    {
        return [
            'id'                   => $b->id,
            'section_id'           => $b->section_id,
            'course_assignment_id' => $b->course_assignment_id,
            'day_of_week'          => $b->day_of_week,
            'start_time'           => substr($b->start_time, 0, 5),
            'end_time'             => substr($b->end_time, 0, 5),
            'room'                 => $b->room,
            'type'                 => $b->type,
            'course_name'          => $b->courseAssignment?->course?->name,
            'teacher_name'         => $b->courseAssignment?->teacher?->name,
        ];
    }
}
