<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            
            // Ledger entry details
            $table->enum('type', ['credit', 'debit']); // credit = in, debit = out
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('description');
            
            // Relaciones polimórficas para saber qué generó el movimiento (Payment, Refund, etc.)
            $table->nullableUuidMorphs('reference');
            
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
