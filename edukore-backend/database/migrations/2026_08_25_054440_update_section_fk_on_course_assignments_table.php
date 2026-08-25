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
        Schema::table('course_assignments', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->foreign('section_id')
                  ->references('id')
                  ->on('academic_sections')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_assignments', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->foreign('section_id')
                  ->references('id')
                  ->on('sections')
                  ->cascadeOnDelete();
        });
    }
};
