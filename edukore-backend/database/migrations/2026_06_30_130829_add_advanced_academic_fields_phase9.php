<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->string('status')->default('abierto'); // abierto, cerrado
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->string('status')->default('activo'); // activo, inactivo
        });

        Schema::table('course_assignments', function (Blueprint $table) {
            $table->string('schedule')->nullable();
            $table->string('room')->nullable();
            $table->integer('weekly_hours')->default(0);
            $table->boolean('is_substitute')->default(false);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            // No podemos hacer change() fácilmente en SQLite sin dependencias pesadas
            // pero el campo original status en enrollments ya es string. 
            // Manejaremos 'preinscrito', 'matriculado', etc. por aplicación.
        });
    }

    public function down(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('course_assignments', function (Blueprint $table) {
            $table->dropColumn(['schedule', 'room', 'weekly_hours', 'is_substitute']);
        });
    }
};
