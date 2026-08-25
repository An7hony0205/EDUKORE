<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de asistencia diaria por sección académica.
     * Paradigma: Sección + Alumno + Fecha → Estado
     * (Independiente de la tabla `attendance` que trabaja por course_assignment.)
     */
    public function up(): void
    {
        Schema::create('section_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('section_id');
            $table->uuid('student_id');
            $table->date('date');
            $table->string('status', 20)->default('present')
                  ->comment('present | late | absent | justified');
            $table->text('remarks')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('section_id')
                  ->references('id')->on('academic_sections')
                  ->cascadeOnDelete();

            $table->foreign('student_id')
                  ->references('id')->on('students')
                  ->cascadeOnDelete();

            $table->foreign('created_by')
                  ->references('id')->on('users')
                  ->nullOnDelete();

            // Unique: un registro por alumno por sección por día
            $table->unique(['section_id', 'student_id', 'date'], 'sec_att_unique');

            // Índices para consultas frecuentes
            $table->index(['section_id', 'date'], 'sec_att_section_date_idx');
            $table->index('student_id', 'sec_att_student_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_attendances');
    }
};
