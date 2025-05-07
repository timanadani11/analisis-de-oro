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
        // Verificar si la tabla existe, si no existe, crearla
        if (!Schema::hasTable('leagues')) {
            Schema::create('leagues', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('country')->nullable();
                $table->string('logo')->nullable();
                $table->string('type')->default('LEAGUE');
                $table->boolean('active')->default(true);
                $table->integer('api_league_id')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        } else {
            // Si la tabla ya existe, agregar solo las columnas que faltan
            Schema::table('leagues', function (Blueprint $table) {
                if (!Schema::hasColumn('leagues', 'api_league_id')) {
                    $table->integer('api_league_id')->nullable()->index();
                }
                if (!Schema::hasColumn('leagues', 'type')) {
                    $table->string('type')->default('LEAGUE');
                }
                if (!Schema::hasColumn('leagues', 'active')) {
                    $table->boolean('active')->default(true);
                }
                if (!Schema::hasColumn('leagues', 'metadata')) {
                    $table->json('metadata')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No eliminar la tabla ni las columnas para no perder datos
    }
}; 