<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de bloques de hora dinámicos (Time Slots)
     */
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Permite horarios distintos por nivel (null = general)
            $table->uuid('level_id')->nullable();
            $table->foreign('level_id')
                  ->references('id')->on('academic_levels')
                  ->cascadeOnDelete();
                  
            $table->string('name', 100);
            $table->time('start_time');
            $table->time('end_time');
            
            // 'academic' | 'break' | 'assembly'
            $table->string('type', 20)->default('academic');
            
            $table->integer('order_index')->default(0);
            $table->timestamps();
            
            // Índices
            $table->index('level_id', 'time_slots_level_idx');
            $table->index('order_index', 'time_slots_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
