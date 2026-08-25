<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Añadir grading_system a tenant_settings
        Schema::table('tenant_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('tenant_settings', 'grading_system')) {
                $table->enum('grading_system', ['competency', 'numeric'])->default('competency')->after('timezone');
            }
        });

        // 2. Tabla course_competencies
        Schema::create('course_competencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id');
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->string('name');
            $table->integer('order_index')->default(1);
            $table->timestamps();
        });

        // 3. Tabla competency_evaluations
        Schema::create('competency_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->uuid('student_id');
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            
            $table->uuid('competency_id');
            $table->foreign('competency_id')->references('id')->on('course_competencies')->cascadeOnDelete();
            
            $table->uuid('term_id');
            $table->foreign('term_id')->references('id')->on('academic_terms')->cascadeOnDelete();
            
            $table->string('score_literal', 2)->nullable(); // AD, A, B, C
            $table->decimal('score_numeric', 4, 2)->nullable();
            $table->text('descriptive_conclusion')->nullable();
            
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            
            $table->timestamps();

            $table->unique(['student_id', 'competency_id', 'term_id'], 'comp_eval_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competency_evaluations');
        Schema::dropIfExists('course_competencies');
        Schema::table('tenant_settings', function (Blueprint $table) {
            $table->dropColumn('grading_system');
        });
    }
};
