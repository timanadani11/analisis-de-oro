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
        Schema::dropIfExists('football_matches'); // Eliminar si existe para empezar de cero
        
        Schema::create('football_matches', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('sport_id')->nullable();
            $table->foreign('sport_id')->references('id')->on('sports')->onDelete('set null');

            $table->unsignedBigInteger('league_id')->nullable();
            $table->foreign('league_id')->references('id')->on('leagues')->onDelete('set null');

            $table->unsignedBigInteger('season_id')->nullable();
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('set null');
            
            $table->unsignedBigInteger('home_team_id')->nullable();
            $table->foreign('home_team_id')->references('id')->on('teams')->onDelete('set null');

            $table->unsignedBigInteger('away_team_id')->nullable();
            $table->foreign('away_team_id')->references('id')->on('teams')->onDelete('set null');
            
            $table->integer('api_fixture_id')->nullable()->unique()->comment('ID único del partido en la API');
            $table->dateTime('match_date')->comment('Fecha y hora programada del partido');
            $table->string('status', 50)->default('scheduled')->comment('Estado del partido');
            $table->string('round')->nullable()->comment('Jornada o ronda del partido');
            $table->string('venue')->nullable()->comment('Estadio/sede del partido');
            $table->string('referee')->nullable()->comment('Árbitro del partido');
            
            $table->integer('home_goals')->nullable()->comment('Goles del equipo local (tiempo completo)');
            $table->integer('away_goals')->nullable()->comment('Goles del equipo visitante (tiempo completo)');
            $table->integer('home_halftime_goals')->nullable()->comment('Goles del local al medio tiempo');
            $table->integer('away_halftime_goals')->nullable()->comment('Goles del visitante al medio tiempo');
            // No necesitamos home_fulltime_goals y away_fulltime_goals si home_goals/away_goals ya representan el tiempo completo.

            $table->integer('elapsed_time')->nullable()->comment('Minutos transcurridos si el partido está en juego');
            
            $table->json('statistics')->nullable()->comment('Estadísticas del partido en formato JSON');
            $table->json('events')->nullable()->comment('Eventos del partido (goles, tarjetas, etc.) en formato JSON');
            $table->json('lineups')->nullable()->comment('Alineaciones de los equipos en formato JSON');
            $table->json('metadata')->nullable()->comment('Todos los datos originales de la API para este fixture');
            
            $table->boolean('is_analyzed')->default(false)->comment('Indica si el partido ha sido analizado');
            $table->timestamps();
            
            // Índices
            $table->index('match_date');
            $table->index('status');
            $table->index('home_team_id');
            $table->index('away_team_id');
            $table->index('league_id');
            $table->index('season_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('football_matches');
    }
};
