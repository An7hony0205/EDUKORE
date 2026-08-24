<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega document_number a la tabla parents.
 *
 * - NOT NULL: el DNI es obligatorio según regla de negocio.
 * - index(): habilita búsquedas rápidas por DNI sin full-table scan.
 * - El campo NO tiene unique() global aquí porque en un sistema multi-tenant
 *   podría necesitarse el mismo DNI en tenants distintos. La unicidad se valida
 *   a nivel de aplicación en StoreParentRequest (Rule::unique dentro del tenant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->string('document_number', 30)
                  ->nullable() // nullable para que la migración no rompa filas existentes
                  ->after('user_id')
                  ->index('parents_document_number_idx'); // índice nombrado explícitamente
        });
    }

    public function down(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->dropIndex('parents_document_number_idx');
            $table->dropColumn('document_number');
        });
    }
};
