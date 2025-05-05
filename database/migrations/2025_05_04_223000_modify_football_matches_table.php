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
        // Primero eliminar la tabla anterior si existe
        Schema::dropIfExists('football_matches');
        
        // Crear tabla con estructura simplificada
        Schema::create('football_matches', function (Blueprint $table) {
            $table->id();
            
            // Datos de la API
            $table->integer('api_fixture_id')->nullable()->unique()->comment('ID único del partido en la API');
            
            // Datos de liga simplificados (sin relación)
            $table->string('league_name')->nullable()->comment('Nombre de la liga');
            $table->string('league_logo')->nullable()->comment('URL del logo de la liga');
            $table->string('league_country')->nullable()->comment('País de la liga');
            
            // Datos de equipos simplificados (sin relación)
            $table->string('home_team_name')->nullable()->comment('Nombre del equipo local');
            $table->string('home_team_logo')->nullable()->comment('URL del logo del equipo local');
            $table->string('away_team_name')->nullable()->comment('Nombre del equipo visitante');
            $table->string('away_team_logo')->nullable()->comment('URL del logo del equipo visitante');
            
            // Datos del partido
            $table->dateTime('match_date')->comment('Fecha y hora programada del partido');
            $table->string('status', 50)->default('Not Started')->comment('Estado del partido (ej: Not Started, In Play, Finished)');
            $table->integer('home_goals')->nullable()->comment('Goles del equipo local');
            $table->integer('away_goals')->nullable()->comment('Goles del equipo visitante');
            $table->string('venue')->nullable()->comment('Estadio/sede del partido');
            $table->integer('elapsed_time')->nullable()->comment('Minutos transcurridos si el partido está en juego');
            
            // Datos JSON
            $table->json('statistics')->nullable()->comment('Estadísticas del partido en formato JSON');
            $table->json('events')->nullable()->comment('Eventos del partido (goles, tarjetas, etc.) en formato JSON');
            $table->json('lineups')->nullable()->comment('Alineaciones de los equipos en formato JSON');
            
            // Campos de utilidad
            $table->boolean('is_analyzed')->default(false)->comment('Indica si el partido ha sido analizado');
            $table->timestamps();
            
            // Índices
            $table->index('match_date');
            $table->index('status');
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
