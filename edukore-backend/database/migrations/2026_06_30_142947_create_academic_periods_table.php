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
        Schema::create('academic_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('academic_year_id')->index();
            $table->string('name'); // e.g. Bimestre 1, Trimestre 1
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_locked')->default(false); // For grade locking
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
        });

        // Add to evaluations
        Schema::table('evaluations', function (Blueprint $table) {
            $table->uuid('academic_period_id')->nullable()->index();
            $table->foreign('academic_period_id')->references('id')->on('academic_periods')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropForeign(['academic_period_id']);
            $table->dropColumn('academic_period_id');
        });

        Schema::dropIfExists('academic_periods');
    }
};
