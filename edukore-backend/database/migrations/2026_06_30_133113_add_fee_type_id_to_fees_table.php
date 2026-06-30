<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->foreignUuid('fee_type_id')->nullable()->constrained('fee_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropForeign(['fee_type_id']);
            $table->dropColumn('fee_type_id');
        });
    }
};
