<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('date');
            $table->enum('event_type', ['FAENA', 'MEETING', 'OTHER'])->default('FAENA');
            $table->enum('status', ['SCHEDULED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'])->default('SCHEDULED');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::create('event_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_id');
            $table->uuid('user_id'); // El apoderado
            $table->enum('status', ['PENDING', 'PRESENT', 'ABSENT', 'EXCUSED'])->default('PENDING');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->unique(['event_id', 'user_id']);
        });

        Schema::create('obligations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('user_id'); // Apoderado o Estudiante
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['PENDING', 'RESOLVED', 'EXCUSED'])->default('PENDING');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obligations');
        Schema::dropIfExists('event_participants');
        Schema::dropIfExists('events');
    }
};
