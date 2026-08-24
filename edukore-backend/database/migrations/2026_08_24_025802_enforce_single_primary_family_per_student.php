<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * FASE 1: Integridad de Base de Datos para Gestión de Familias.
 *
 * Esta migración realiza dos operaciones atómicas:
 *
 * 1. Agrega la columna `is_primary` a `family_students` (DEFAULT false).
 *    Sin esta columna, la regla de negocio "solo una familia principal por
 *    estudiante" no puede aplicarse ni en la capa ORM ni en el motor de BD.
 *
 * 2. Agrega la columna `is_primary` a `family_members` y corrige el DEFAULT
 *    de `can_view_info` de true → false (regla de negocio innegociable).
 *
 * 3. Crea un UNIQUE INDEX PARCIAL de PostgreSQL en `family_students`
 *    que filtra únicamente las filas donde is_primary = true.
 *    Esto garantiza a nivel de motor que un estudiante tenga EXACTAMENTE
 *    una familia principal — la transacción en el controlador es la primera
 *    línea de defensa; este índice es la segunda.
 *    (Los índices parciales no están disponibles en MySQL — solo PostgreSQL.)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Agregar is_primary a family_students ───────────────────────────
        Schema::table('family_students', function (Blueprint $table) {
            // Indica si esta familia es la familia principal del estudiante.
            // Solo UNA fila por student_id puede tener is_primary = true
            // (garantizado por el índice parcial de abajo).
            $table->boolean('is_primary')->default(false)->after('relation_description');
        });

        // ── 2. Correcciones en family_members ─────────────────────────────────
        Schema::table('family_members', function (Blueprint $table) {
            // La regla de negocio establece can_view_info = false por defecto.
            // La migración original tenía default(true) — se corrige aquí.
            $table->boolean('can_view_info')->default(false)->change();
        });

        // ── 3. Índice único PARCIAL (PostgreSQL) ──────────────────────────────
        // Un índice parcial solo indexa las filas que cumplen la condición WHERE.
        // Efecto: la BD rechaza automáticamente un segundo INSERT/UPDATE con
        // is_primary = true para el mismo student_id con una violación de
        // constraint única (código de error PostgreSQL 23505).
        // Blueprint de Laravel no soporta índices parciales nativamente,
        // por lo que se ejecuta SQL crudo.
        DB::statement(
            'CREATE UNIQUE INDEX family_students_one_primary_idx
             ON family_students (student_id)
             WHERE is_primary = true'
        );
    }

    public function down(): void
    {
        // Elimina el índice parcial primero (depende de la columna)
        DB::statement('DROP INDEX IF EXISTS family_students_one_primary_idx');

        Schema::table('family_students', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });

        // Revierte can_view_info al default incorrecto original para poder
        // hacer rollback limpio. En producción preferir no usar down().
        Schema::table('family_members', function (Blueprint $table) {
            $table->boolean('can_view_info')->default(true)->change();
        });
    }
};
