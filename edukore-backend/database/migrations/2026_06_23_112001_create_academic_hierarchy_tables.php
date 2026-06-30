<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('year_name'); // e.g. "2026"
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::create('levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('academic_year_id')->index();
            $table->string('name'); // e.g. "Secundaria"
            $table->timestamps();
            
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
        });

        Schema::create('grade_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('level_id')->index();
            $table->string('name'); // e.g. "1ro"
            $table->timestamps();
            
            $table->foreign('level_id')->references('id')->on('levels')->onDelete('cascade');
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('grade_level_id')->index();
            $table->string('name'); // e.g. "A"
            $table->integer('capacity')->default(30);
            $table->timestamps();
            
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
        Schema::dropIfExists('grade_levels');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('academic_years');
    }
};
