<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballDataService;
use App\Http\Controllers\Admin\AdminController;
use App\Models\League;

class FetchLeagueData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'football:fetch-league {league_id} {season}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtiene los datos de una liga específica por ID y temporada desde la API de fútbol';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching league data from API...');
        
        // Crear instancia del servicio
        $footballApiService = app(FootballDataService::class);
        
        if (!$footballApiService) {
            $this->error('Could not instantiate FootballDataService');
            return 1;
        }
        
        $leagueId = $this->argument('league_id');
        $season = $this->argument('season');
        
        $this->info("Obteniendo datos de la liga ID: {$leagueId} para la temporada {$season}...");
        
        // Test API connection first
        $this->info("Verificando conexión con la API...");
        if (!$footballApiService->testConnection()) {
            $this->error("Error: No se pudo conectar con la API. Verifique sus credenciales e intente nuevamente.");
            return Command::FAILURE;
        }
        
        $this->info("Conexión con la API establecida correctamente.");
        
        // Fetch and save league data
        $result = $footballApiService->fetchAndSaveLeague($leagueId, $season);
        
        if ($result['success']) {
            $this->info($result['message']);
            
            // Show league details if available
            if (isset($result['data'])) {
                $league = $result['data'];
                $this->info("Detalles de la liga:");
                $this->info("- ID: {$league->id}");
                $this->info("- Nombre: {$league->name}");
                $this->info("- País: {$league->country}");
                $this->info("- Tipo: {$league->type}");
            }
            
            return Command::SUCCESS;
        } else {
            $this->error("Error: " . $result['message']);
            return Command::FAILURE;
        }
    }
} 