<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_audits', function (Blueprint $table) {
            $table->id();
            $table->uuid('grade_id');
            $table->uuid('user_id')->nullable()->comment('User who made the change');
            $table->decimal('old_score', 5, 2)->nullable();
            $table->decimal('new_score', 5, 2);
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->foreign('grade_id')->references('id')->on('grades')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_audits');
    }
};
