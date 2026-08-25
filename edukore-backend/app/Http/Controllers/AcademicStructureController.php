<?php

namespace App\Http\Controllers;

use App\Models\AcademicLevel;
use App\Models\AcademicYear;
use Illuminate\Http\JsonResponse;

class AcademicStructureController extends Controller
{
    /**
     * Devuelve el árbol completo de estructura académica (Niveles → Grados → Secciones)
     * para los selectores en cascada del frontend.
     *
     * GET /api/academic-structure
     */
    public function index(): JsonResponse
    {
        // Cargamos el árbol: Level → AcademicGrade (sections) → AcademicSection
        $tree = AcademicLevel::with(['grades.sections'])
            ->orderBy('name')
            ->get()
            ->map(function ($level) {
                return [
                    'id'   => $level->id,
                    'name' => $level->name,
                    'grades' => $level->grades->map(function ($grade) {
                        return [
                            'id'       => $grade->id,
                            'name'     => $grade->name,
                            'sections' => $grade->sections->map(function ($section) {
                                return [
                                    'id'           => $section->id,
                                    'name'         => $section->name,
                                    'max_capacity' => $section->max_capacity,
                                    'tutor_id'     => $section->tutor_id,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            });

        return response()->json([
            'data' => $tree,
        ]);
    }

    /**
     * Resumen enriquecido: Niveles → Grados → Secciones con métricas de aforo y tutor.
     *
     * GET /api/academic-structure/summary
     */
    public function summary(): JsonResponse
    {
        $tree = AcademicLevel::with([
            'grades.sections' => function ($q) {
                $q->withCount('students')->with('tutor:id,name,email');
            },
        ])
            ->orderBy('name')
            ->get()
            ->map(function ($level) {
                return [
                    'id'     => $level->id,
                    'name'   => $level->name,
                    'grades' => $level->grades->map(function ($grade) {
                        return [
                            'id'       => $grade->id,
                            'name'     => $grade->name,
                            'sections' => $grade->sections->map(function ($section) {
                                return [
                                    'id'             => $section->id,
                                    'name'           => $section->name,
                                    'max_capacity'   => $section->max_capacity,
                                    'students_count' => $section->students_count,
                                    'tutor'          => $section->tutor
                                        ? ['id' => $section->tutor->id, 'name' => $section->tutor->name]
                                        : null,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            });

        return response()->json(['data' => $tree]);
    }

    /**
     * Lista de estudiantes de una sección específica.
     *
     * GET /api/academic-structure/sections/{sectionId}/students
     */
    public function sectionStudents(string $sectionId): JsonResponse
    {
        $section = \App\Models\AcademicSection::with([
            'students.user:id,name,email',
            'students.parents.user:id,name',
        ])->findOrFail($sectionId);

        $students = $section->students->map(function ($student) {
            $parent = $student->parents->first();
            return [
                'id'               => $student->id,
                'enrollment_number'=> $student->enrollment_number,
                'name'             => $student->user?->name,
                'document_number'  => $student->document_number ?? null,
                'parent_name'      => $parent?->user?->name,
            ];
        });

        return response()->json([
            'section'  => ['id' => $section->id, 'name' => $section->name],
            'students' => $students,
        ]);
    }

    /**
     * Actualiza el tutor de una sección.
     *
     * PUT /api/academic-structure/sections/{id}/tutor
     */
    public function updateTutor(\Illuminate\Http\Request $request, string $sectionId): JsonResponse
    {
        $request->validate([
            'tutor_id' => 'nullable|uuid|exists:users,id',
        ]);

        $section = \App\Models\AcademicSection::findOrFail($sectionId);
        
        // Verificamos opcionalmente que el usuario sea realmente un docente
        if ($request->tutor_id) {
            $user = \App\Models\User::find($request->tutor_id);
            if (!$user || !$user->hasRole('teacher')) {
                return response()->json(['message' => 'El usuario seleccionado no es un docente válido.'], 422);
            }
        }

        $section->tutor_id = $request->tutor_id;
        $section->save();

        return response()->json([
            'message' => 'Tutor asignado correctamente.',
            'tutor'   => $section->tutor ? ['id' => $section->tutor->id, 'name' => $section->tutor->name] : null,
        ]);
    }
}
