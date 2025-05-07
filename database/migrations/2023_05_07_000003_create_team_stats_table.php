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
        if (!Schema::hasTable('team_stats')) {
            Schema::create('team_stats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
                $table->foreignId('league_id')->constrained('leagues')->onDelete('cascade');
                $table->integer('season');
                $table->json('stats_json');
                $table->timestamps();
                
                // Índice para búsqueda rápida de estadísticas por equipo, liga y temporada
                $table->unique(['team_id', 'league_id', 'season']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_stats');
    }
}; 