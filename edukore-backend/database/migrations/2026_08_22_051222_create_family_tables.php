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
        Schema::create('families', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name')->comment('E.g., Familia García López');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->uuid('family_id');
            $table->uuid('user_id'); // This connects to the parent/guardian user
            $table->string('relation_type')->comment('Padre, Madre, Apoderado, Tutor, etc.');
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('can_view_info')->default(true);
            $table->timestamps();

            $table->foreign('family_id')->references('id')->on('families')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('family_students', function (Blueprint $table) {
            $table->id();
            $table->uuid('family_id');
            $table->uuid('student_id');
            $table->string('relation_description')->nullable();
            $table->timestamps();

            $table->foreign('family_id')->references('id')->on('families')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_students');
        Schema::dropIfExists('family_members');
        Schema::dropIfExists('families');
    }
};
