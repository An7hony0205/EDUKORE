<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::create('course_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->index();
            $table->uuid('section_id')->index();
            $table->uuid('teacher_id')->index();
            $table->timestamps();
            
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('attendance', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_assignment_id')->index();
            $table->uuid('enrollment_id')->index();
            $table->date('date');
            $table->enum('status', ['Presente', 'Tardanza', 'Ausente', 'Justificado']);
            $table->timestamps();
            
            $table->foreign('course_assignment_id')->references('id')->on('course_assignments')->onDelete('cascade');
            $table->foreign('enrollment_id')->references('id')->on('enrollments')->onDelete('cascade');
        });

        Schema::create('rubrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->json('criteria'); // JSON structure for the rubric grid
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::create('evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_assignment_id')->index();
            $table->uuid('rubric_id')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->timestamps();
            
            $table->foreign('course_assignment_id')->references('id')->on('course_assignments')->onDelete('cascade');
            $table->foreign('rubric_id')->references('id')->on('rubrics')->onDelete('set null');
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('evaluation_id')->index();
            $table->uuid('enrollment_id')->index();
            $table->decimal('score', 5, 2)->nullable();
            $table->json('rubric_results')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
            
            $table->foreign('evaluation_id')->references('id')->on('evaluations')->onDelete('cascade');
            $table->foreign('enrollment_id')->references('id')->on('enrollments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('rubrics');
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('course_assignments');
        Schema::dropIfExists('courses');
    }
};
