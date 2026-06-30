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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('fee_id')->constrained('fees')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete(); // Quién registró o realizó el pago
            
            $table->decimal('amount_paid', 10, 2);
            $table->date('payment_date');
            
            $table->string('payment_method'); // 'cash', 'transfer', 'credit_card', 'stripe'
            $table->string('transaction_id')->nullable(); // ID de transacción externa
            
            $table->string('receipt_url')->nullable(); // Link a PDF de boleta/factura
            $table->jsonb('metadata')->nullable(); // Payload crudo del webhook de pago
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
