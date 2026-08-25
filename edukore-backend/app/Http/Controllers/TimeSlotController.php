<?php

namespace App\Http\Controllers;

use App\Models\TimeSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TimeSlotController extends Controller
{
    /**
     * GET /api/time-slots?level_id={uuid}
     */
    public function index(Request $request): JsonResponse
    {
        // En este caso permitiremos nullable level_id para traer slots generales
        // Si el frontend envía level_id lo filtramos, sino traemos todos
        $query = TimeSlot::query();
        
        if ($request->filled('level_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('level_id', $request->level_id)
                  ->orWhereNull('level_id');
            });
        }
        
        $slots = $query->orderBy('order_index')
                       ->orderBy('start_time')
                       ->get()
                       ->map(fn($s) => $this->formatSlot($s));
                       
        // Si no hay ninguno, podemos retornar una plantilla por defecto y el frontend la maneja, o crearla
        if ($slots->isEmpty()) {
            $slots = $this->createDefaultTemplate($request->level_id);
        }
        
        return response()->json(['data' => $slots]);
    }

    /**
     * POST /api/time-slots
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'level_id'    => 'nullable|uuid|exists:academic_levels,id',
            'name'        => 'required|string|max:100',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'type'        => 'required|string|in:academic,break,assembly',
            'order_index' => 'integer'
        ]);

        $slot = TimeSlot::create([
            'id'          => (string) Str::uuid(),
            'level_id'    => $request->level_id,
            'name'        => $request->name,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'type'        => $request->type,
            'order_index' => $request->order_index ?? 0,
        ]);

        return response()->json([
            'message' => 'Bloque creado.',
            'data'    => $this->formatSlot($slot)
        ], 201);
    }

    /**
     * PUT /api/time-slots/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $slot = TimeSlot::findOrFail($id);
        
        $request->validate([
            'name'        => 'required|string|max:100',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'type'        => 'required|string|in:academic,break,assembly',
            'order_index' => 'integer'
        ]);

        $slot->update($request->only(['name', 'start_time', 'end_time', 'type', 'order_index']));

        return response()->json([
            'message' => 'Bloque actualizado.',
            'data'    => $this->formatSlot($slot)
        ]);
    }

    /**
     * DELETE /api/time-slots/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        TimeSlot::findOrFail($id)->delete();
        return response()->json(['message' => 'Bloque eliminado.']);
    }

    private function formatSlot(TimeSlot $s): array
    {
        return [
            'id'          => $s->id,
            'level_id'    => $s->level_id,
            'name'        => $s->name,
            'start_time'  => substr($s->start_time, 0, 5),
            'end_time'    => substr($s->end_time, 0, 5),
            'type'        => $s->type,
            'order_index' => $s->order_index,
            'isBreak'     => $s->type === 'break'
        ];
    }

    /**
     * POST /api/time-slots/sync
     * Sincronización masiva de los bloques horarios de un nivel
     */
    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'level_id'           => 'nullable|uuid|exists:academic_levels,id',
            'slots'              => 'required|array',
            'slots.*.name'       => 'required|string|max:100',
            'slots.*.start_time' => 'required|date_format:H:i,H:i:s',
            'slots.*.end_time'   => 'required|date_format:H:i,H:i:s|after:slots.*.start_time',
            'slots.*.type'       => 'nullable|string|in:academic,break,assembly',
        ]);

        $levelId = $request->level_id;
        $slotsData = $request->slots;

        \Illuminate\Support\Facades\DB::transaction(function () use ($levelId, $slotsData) {
            // Elimina los slots previos (si level_id es null, filtra por los globales)
            if ($levelId) {
                TimeSlot::where('level_id', $levelId)->delete();
            } else {
                TimeSlot::whereNull('level_id')->delete();
            }

            foreach ($slotsData as $index => $slot) {
                TimeSlot::create([
                    'id'          => (string) Str::uuid(),
                    'level_id'    => $levelId,
                    'name'        => $slot['name'],
                    'start_time'  => $slot['start_time'],
                    'end_time'    => $slot['end_time'],
                    'type'        => $slot['type'] ?? 'academic',
                    'order_index' => $index + 1,
                ]);
            }
        });

        // Retornar los nuevos actualizados
        $query = TimeSlot::query();
        if ($levelId) {
            $query->where('level_id', $levelId);
        } else {
            $query->whereNull('level_id');
        }
        $updatedSlots = $query->orderBy('order_index')->get()->map(fn($s) => $this->formatSlot($s));

        return response()->json([
            'message' => 'Configuración de bloques guardada exitosamente.',
            'data'    => $updatedSlots
        ]);
    }

    private function createDefaultTemplate($levelId)
    {
        $defaults = [
            ['name' => '1° Hora', 'start_time' => '08:00', 'end_time' => '08:45', 'type' => 'academic', 'order_index' => 1],
            ['name' => '2° Hora', 'start_time' => '08:45', 'end_time' => '09:30', 'type' => 'academic', 'order_index' => 2],
            ['name' => 'Recreo 1', 'start_time' => '09:30', 'end_time' => '10:00', 'type' => 'break', 'order_index' => 3],
            ['name' => '3° Hora', 'start_time' => '10:00', 'end_time' => '10:45', 'type' => 'academic', 'order_index' => 4],
            ['name' => '4° Hora', 'start_time' => '10:45', 'end_time' => '11:30', 'type' => 'academic', 'order_index' => 5],
        ];

        $slots = [];
        foreach ($defaults as $d) {
            $s = TimeSlot::create(array_merge($d, [
                'id'       => (string) Str::uuid(),
                'level_id' => $levelId
            ]));
            $slots[] = $this->formatSlot($s);
        }
        
        return collect($slots);
    }
}
