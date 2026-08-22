<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');
        
        if (!$query || strlen($query) < 3) {
            return response()->json([]);
        }

        $results = [];

        // For now, only search students
        // Phase 1 scope: global search UI and basic backend endpoint
        
        $user = $request->user();
        
        $queryBuilder = Student::where(function($q) use ($query) {
            $q->where('first_name', 'ilike', "%{$query}%")
              ->orWhere('last_name', 'ilike', "%{$query}%")
              ->orWhere('document_number', 'ilike', "%{$query}%");
        });

        if ($user->hasRole('Teacher')) {
            // Un Docente solo puede buscar estudiantes en sus cursos asignados.
            // Para simplificar la base de la Fase 2, unimos con enrollments -> sections -> course_assignments
            $queryBuilder->whereHas('enrollments.section.courseAssignments', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        }
        
        $students = $queryBuilder->limit(10)->get();
            
        foreach ($students as $student) {
            $results[] = [
                'id' => $student->id,
                'type' => 'student',
                'name' => $student->first_name . ' ' . $student->last_name,
                'meta' => $student->document_number
            ];
        }
        
        return response()->json($results);
    }
}
