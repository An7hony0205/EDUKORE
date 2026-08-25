<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');           // Primaria, Secundaria, Inicial
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_levels');
    }
};
