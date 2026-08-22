<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->string('status')->default('DRAFT')->after('date')->comment('DRAFT, PUBLISHED, CLOSED');
        });

        // Migrate data
        DB::statement("UPDATE evaluations SET status = 'PUBLISHED' WHERE is_published = true");

        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->boolean('is_published')->default(false);
        });

        DB::statement("UPDATE evaluations SET is_published = true WHERE status = 'PUBLISHED'");

        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
