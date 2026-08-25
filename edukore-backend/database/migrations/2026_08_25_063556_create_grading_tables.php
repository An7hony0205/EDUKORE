<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('grade_audits');
        Schema::dropIfExists('grades'); // Drop the conflicting old table

        Schema::create('academic_terms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('code', 20)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order_index')->default(1);
            $table->timestamps();
        });

        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->uuid('course_assignment_id');
            $table->foreign('course_assignment_id')->references('id')->on('course_assignments')->cascadeOnDelete();
            
            $table->uuid('term_id');
            $table->foreign('term_id')->references('id')->on('academic_terms')->cascadeOnDelete();
            
            $table->string('name', 100);
            $table->decimal('weight', 5, 2)->default(1.00);
            $table->integer('order_index')->default(1);
            
            $table->timestamps();
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->uuid('evaluation_criterion_id');
            $table->foreign('evaluation_criterion_id')->references('id')->on('evaluation_criteria')->cascadeOnDelete();
            
            $table->uuid('student_id');
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            
            $table->decimal('score', 4, 2)->nullable();
            $table->string('letter_grade', 10)->nullable();
            $table->text('feedback')->nullable();
            
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            
            $table->timestamps();

            $table->unique(['evaluation_criterion_id', 'student_id'], 'grades_criterion_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
        Schema::dropIfExists('evaluation_criteria');
        Schema::dropIfExists('academic_terms');
    }
};
