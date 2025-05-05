<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFootballMatchesTable extends Migration
{
    public function up()
    {
        Schema::create('football_matches', function (Blueprint $table) {
            $table->id();
            
            // ID único del partido en la API
            $table->string('api_fixture_id')->nullable()->unique();
            
            // Datos del partido
            $table->datetime('match_date')->nullable();
            $table->string('status')->default('Not Started');
            $table->integer('home_goals')->default(0);
            $table->integer('away_goals')->default(0);
            $table->string('referee')->nullable();
            $table->string('venue')->nullable();
            $table->integer('elapsed_time')->default(0);
            
            // Datos de equipos
            $table->string('home_team_name')->nullable();
            $table->string('away_team_name')->nullable();
            $table->string('home_team_logo')->nullable();
            $table->string('away_team_logo')->nullable();
            
            // Datos de liga
            $table->string('league_name')->nullable();
            $table->string('league_logo')->nullable();
            $table->string('league_country')->nullable();
            
            // Datos adicionales en formato JSON
            $table->json('statistics')->nullable();
            $table->json('events')->nullable();
            $table->json('lineups')->nullable();
            $table->string('round')->nullable();
            $table->boolean('is_analyzed')->default(false);
            
            $table->timestamps();
            
            // Índices para mejorar el rendimiento
            $table->index('match_date');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('football_matches');
    }
}
