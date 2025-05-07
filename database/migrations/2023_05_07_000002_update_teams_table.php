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
        if (!Schema::hasTable('teams')) {
            Schema::create('teams', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('country')->nullable();
                $table->string('logo')->nullable();
                $table->string('stadium')->nullable();
                $table->string('city')->nullable();
                $table->integer('api_team_id')->nullable()->index();
                $table->foreignId('league_id')->nullable()->constrained('leagues')->onDelete('set null');
                $table->integer('founded')->nullable();
                $table->string('venue_name')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        } else {
            // Si la tabla ya existe, agregar solo las columnas que faltan
            Schema::table('teams', function (Blueprint $table) {
                if (!Schema::hasColumn('teams', 'api_team_id')) {
                    $table->integer('api_team_id')->nullable()->index();
                }
                if (!Schema::hasColumn('teams', 'founded')) {
                    $table->integer('founded')->nullable();
                }
                if (!Schema::hasColumn('teams', 'venue_name')) {
                    $table->string('venue_name')->nullable();
                }
                if (!Schema::hasColumn('teams', 'metadata')) {
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