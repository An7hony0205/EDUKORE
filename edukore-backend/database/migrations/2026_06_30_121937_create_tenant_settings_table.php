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
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            
            $table->string('logo_url')->nullable();
            $table->string('theme_color')->default('#4f46e5'); // Indigo 600 default
            $table->string('timezone')->default('UTC');
            $table->string('grading_scale')->default('numeric_20'); // numeric_20, letters
            
            // Finanzas
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->string('currency_default', 3)->default('USD');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
