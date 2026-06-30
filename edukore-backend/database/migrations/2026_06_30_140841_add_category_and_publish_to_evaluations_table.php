<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->string('category')->default('General'); // e.g., Tarea, Examen, Participación
            $table->decimal('weight', 5, 2)->default(100.00); // 100% by default
            $table->boolean('is_published')->default(false); // Controls if grades are visible to students
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn(['category', 'weight', 'is_published']);
        });
    }
};
