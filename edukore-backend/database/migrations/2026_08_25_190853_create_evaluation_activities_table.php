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
        Schema::create('evaluation_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('evaluation_criterion_id');
            $table->foreign('evaluation_criterion_id')->references('id')->on('evaluation_criteria')->cascadeOnDelete();
            $table->string('name', 100);
            $table->date('due_date')->nullable();
            $table->integer('order_index')->default(1);
            $table->timestamps();
        });

        Schema::dropIfExists('grades');

        Schema::create('grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->uuid('activity_id');
            $table->foreign('activity_id')->references('id')->on('evaluation_activities')->cascadeOnDelete();
            
            $table->uuid('student_id');
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            
            $table->decimal('score', 4, 2)->nullable();
            $table->string('letter_grade', 10)->nullable();
            $table->text('feedback')->nullable();
            
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            
            $table->timestamps();

            $table->unique(['activity_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
        Schema::dropIfExists('evaluation_activities');
    }
};
