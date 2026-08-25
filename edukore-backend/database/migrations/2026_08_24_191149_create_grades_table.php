<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('academic_level_id')->constrained('academic_levels')->cascadeOnDelete();
            $table->string('name');           // 1er Grado, 2do Grado …
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_grades');
    }
};
