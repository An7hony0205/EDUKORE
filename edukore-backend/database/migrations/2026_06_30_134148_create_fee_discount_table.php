<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_discount', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('fee_id')->constrained('fees')->cascadeOnDelete();
            $table->foreignUuid('discount_id')->constrained('discounts')->cascadeOnDelete();
            
            // Auditoría de quién aplicó el descuento
            $table->foreignUuid('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['fee_id', 'discount_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_discount');
    }
};
