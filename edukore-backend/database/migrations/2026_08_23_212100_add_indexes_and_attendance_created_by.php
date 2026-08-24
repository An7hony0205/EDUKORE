<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CONTEXTO:
     * Esta migración resuelve dos hallazgos del P0 de seguridad:
     *
     * 1. ÍNDICES FALTANTES — La auditoría de pg_indexes reveló que las tablas
     *    families, family_members y family_students no tienen índices B-Tree en
     *    sus columnas de unión. Las consultas de la Triple Barrera en
     *    ParentPortalController usan estas columnas en subconsultas EXISTS anidadas.
     *    Sin índices, PostgreSQL realiza full table scans.
     *
     * 2. AUTORÍA DE ASISTENCIA (created_by) — La tabla attendance no registra
     *    quién creó el registro original. Aunque Spatie LogsActivity solo trackea
     *    modificaciones de 'status' (por diseño, para evitar saturación en bulk),
     *    la ausencia de un campo de autoría impide responder legalmente a "¿quién
     *    registró esta asistencia?" en una disputa padre-colegio.
     *
     *    Solución: Agregar 'created_by' (user_id del docente que ejecutó el bulk).
     *    NO es nullable por diseño: si falta, el registro es inaceptable.
     */
    public function up(): void
    {
        // ── ÍNDICES: families ─────────────────────────────────────────────────
        Schema::table('families', function (Blueprint $table) {
            // Para la Barrera 2 de ParentPortalController: families.tenant_id
            $table->index('tenant_id', 'families_tenant_id_idx');
        });

        // ── ÍNDICES: family_members ───────────────────────────────────────────
        Schema::table('family_members', function (Blueprint $table) {
            // Para la Barrera 3: family_members.user_id (el padre buscado)
            $table->index('user_id', 'family_members_user_id_idx');
            // Para el JOIN interno: family_members.family_id
            $table->index('family_id', 'family_members_family_id_idx');
            // Índice compuesto para la subconsulta completa (user_id + can_view_info)
            // PostgreSQL puede usar esto directamente para la cláusula WHERE
            $table->index(['user_id', 'can_view_info'], 'family_members_user_access_idx');
        });

        // ── ÍNDICES: family_students ──────────────────────────────────────────
        Schema::table('family_students', function (Blueprint $table) {
            // Para el JOIN family → students
            $table->index('family_id', 'family_students_family_id_idx');
            $table->index('student_id', 'family_students_student_id_idx');
            // Índice compuesto (evita double lookup)
            $table->index(['family_id', 'student_id'], 'family_students_compound_idx');
        });

        // ── AUTORÍA: attendance.created_by ────────────────────────────────────
        Schema::table('attendance', function (Blueprint $table) {
            // Registra el user_id del docente que ejecutó el registro original.
            // nullable: false sería ideal pero rompería registros existentes.
            // nullable: true para migración segura, pero la aplicación DEBE llenarlo.
            $table->uuid('created_by')->nullable()->after('notes');

            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete(); // Si el docente es eliminado, preservamos el registro

            // Índice para consultas de auditoría: "¿qué registró el docente X?"
            $table->index('created_by', 'attendance_created_by_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex('attendance_created_by_idx');
            $table->dropColumn('created_by');
        });

        Schema::table('family_students', function (Blueprint $table) {
            $table->dropIndex('family_students_compound_idx');
            $table->dropIndex('family_students_student_id_idx');
            $table->dropIndex('family_students_family_id_idx');
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->dropIndex('family_members_user_access_idx');
            $table->dropIndex('family_members_family_id_idx');
            $table->dropIndex('family_members_user_id_idx');
        });

        Schema::table('families', function (Blueprint $table) {
            $table->dropIndex('families_tenant_id_idx');
        });
    }
};
