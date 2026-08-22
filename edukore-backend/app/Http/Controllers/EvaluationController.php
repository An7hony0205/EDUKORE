<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function index(Request $request)
    {
        $query = Evaluation::query();

        if ($request->filled('course_assignment_id')) {
            $query->where('course_assignment_id', $request->course_assignment_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_assignment_id' => 'required|uuid|exists:course_assignments,id',
            'academic_period_id' => 'nullable|uuid|exists:academic_periods,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'rubric_id' => 'nullable|uuid|exists:rubrics,id',
            'category' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0',
            'status' => 'in:DRAFT,PUBLISHED,CLOSED'
        ]);

        $evaluation = Evaluation::create($validated);

        return response()->json($evaluation, 201);
    }

    public function show(Evaluation $evaluation)
    {
        return response()->json($evaluation);
    }

    public function update(Request $request, Evaluation $evaluation)
    {
        $validated = $request->validate([
            'academic_period_id' => 'nullable|uuid|exists:academic_periods,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'rubric_id' => 'nullable|uuid|exists:rubrics,id',
            'category' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0',
            'status' => 'in:DRAFT,PUBLISHED,CLOSED'
        ]);

        if ($evaluation->academicPeriod && $evaluation->academicPeriod->is_locked) {
            return response()->json(['message' => 'No se puede modificar una evaluación en un periodo académico bloqueado.'], 403);
        }
        
        if ($evaluation->status === 'CLOSED') {
            return response()->json(['message' => 'No se puede modificar una evaluación CERRADA.'], 403);
        }

        $evaluation->update($validated);

        return response()->json($evaluation);
    }

    public function publish(Evaluation $evaluation)
    {
        if ($evaluation->academicPeriod && $evaluation->academicPeriod->is_locked) {
            return response()->json(['message' => 'No se puede publicar en un periodo académico bloqueado.'], 403);
        }

        $evaluation->update(['status' => 'PUBLISHED']);
        return response()->json(['message' => 'Notas publicadas', 'evaluation' => $evaluation]);
    }

    public function close(Evaluation $evaluation)
    {
        if ($evaluation->academicPeriod && $evaluation->academicPeriod->is_locked) {
            return response()->json(['message' => 'No se puede cerrar en un periodo académico bloqueado.'], 403);
        }

        $evaluation->update(['status' => 'CLOSED']);
        return response()->json(['message' => 'Evaluación cerrada', 'evaluation' => $evaluation]);
    }

    public function destroy(Evaluation $evaluation)
    {
        if ($evaluation->academicPeriod && $evaluation->academicPeriod->is_locked) {
            return response()->json(['message' => 'No se puede eliminar una evaluación en un periodo académico bloqueado.'], 403);
        }
        
        if ($evaluation->status === 'CLOSED') {
            return response()->json(['message' => 'No se puede eliminar una evaluación CERRADA.'], 403);
        }

        $evaluation->delete();

        return response()->json(null, 204);
    }
}
