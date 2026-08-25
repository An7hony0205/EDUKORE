<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Horario semanal por sección: cada fila = un bloque pedagógico.
     */
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('section_id');
            $table->foreign('section_id')
                  ->references('id')->on('academic_sections')
                  ->cascadeOnDelete();

            // nullable para bloques tipo 'break'/'assembly' sin asignación docente
            $table->uuid('course_assignment_id')->nullable();
            $table->foreign('course_assignment_id')
                  ->references('id')->on('course_assignments')
                  ->nullOnDelete();

            // 1=Lunes … 5=Viernes
            $table->unsignedTinyInteger('day_of_week');

            $table->time('start_time');
            $table->time('end_time');

            // Aula física (puede diferir del aula habitual de la asignación)
            $table->string('room', 60)->nullable();

            // 'academic' | 'break' | 'assembly'
            $table->string('type', 20)->default('academic');

            $table->timestamps();

            // Índices de consulta
            $table->index(['section_id', 'day_of_week'], 'schedules_section_day_idx');
            $table->index('course_assignment_id', 'schedules_assignment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
