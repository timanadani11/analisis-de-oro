<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballDataService;

class FetchTeamsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'football:fetch-teams {league_id} {season}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtiene los equipos de una liga específica desde la API de fútbol';

    /**
     * Execute the console command.
     */
    public function handle(FootballDataService $footballDataService)
    {
        $leagueId = $this->argument('league_id');
        $season = $this->argument('season');
        
        $this->info("Obteniendo equipos de la liga ID: {$leagueId} para la temporada {$season}...");
        
        // Test API connection first
        $this->info("Verificando conexión con la API...");
        if (!$footballDataService->testConnection()) {
            $this->error("Error: No se pudo conectar con la API. Verifique sus credenciales e intente nuevamente.");
            return Command::FAILURE;
        }
        
        $this->info("Conexión con la API establecida correctamente.");
        
        // Fetch and save teams data
        $result = $footballDataService->fetchAndSaveTeams($leagueId, $season);
        
        if ($result['success']) {
            $this->info($result['message']);
            
            // Show detailed info
            $this->info("Equipos guardados: {$result['saved']} de {$result['total']}");
            
            // Show errors if any
            if (!empty($result['errors'])) {
                $this->warn("Se encontraron algunos errores:");
                foreach ($result['errors'] as $error) {
                    $this->warn("- {$error}");
                }
            }
            
            return Command::SUCCESS;
        } else {
            $this->error("Error: " . $result['message']);
            return Command::FAILURE;
        }
    }
} 