<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team;
use App\Models\TeamStats;
use App\Models\League;
use App\Services\FootballDataService;
use Illuminate\Support\Facades\Log;

class SyncTeamStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:sync-stats 
                            {--team_id= : ID específico de un equipo} 
                            {--league_id= : ID de la liga para sincronizar solo equipos de esa liga}
                            {--competition= : Nombre de la competición (busca coincidencias parciales)}
                            {--delay=60 : Tiempo de espera en segundos entre solicitudes} 
                            {--force : Forzar actualización incluso si ya existen estadísticas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza las estadísticas de los equipos en la base de datos';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $teamId = $this->option('team_id');
        $leagueId = $this->option('league_id');
        $competition = $this->option('competition');
        $delay = max(1, intval($this->option('delay'))); // Mínimo 1 segundo
        $force = $this->option('force');
        
        // Si se especificó un equipo, sincronizar solo ese equipo
        if ($teamId) {
            $team = Team::find($teamId);
            if (!$team) {
                $this->error("No se encontró el equipo con ID {$teamId}");
                return 1;
            }
            
            $this->syncTeamStats($team, $force);
            return 0;
        }
        
        // Construir la consulta base para obtener equipos
        $teamsQuery = Team::query();
        
        // Si se especificó una liga por ID
        if ($leagueId) {
            $league = League::find($leagueId);
            if (!$league) {
                $this->error("No se encontró la liga con ID {$leagueId}");
                return 1;
            }
            
            $this->info("Sincronizando equipos de la liga: {$league->name}");
            $teamsQuery->where('league_id', $leagueId);
        }
        // Si se especificó una competición por nombre
        else if ($competition) {
            // Buscar ligas cuyo nombre contenga el valor de competition
            $leagues = League::where('name', 'like', "%{$competition}%")->get();
            if ($leagues->isEmpty()) {
                $this->error("No se encontraron ligas que coincidan con '{$competition}'");
                return 1;
            }
            
            $leagueIds = $leagues->pluck('id')->toArray();
            $this->info("Encontradas " . count($leagueIds) . " ligas que coinciden con '{$competition}'");
            $teamsQuery->whereIn('league_id', $leagueIds);
        }
        
        // Obtener equipos según los filtros
        if (!$force) {
            // Solo equipos sin estadísticas si no se fuerza actualización
            $teamsQuery->whereNotIn('id', TeamStats::select('team_id'));
            $teams = $teamsQuery->get();
            $this->info("Sincronizando estadísticas para " . $teams->count() . " equipos sin estadísticas...");
        } else {
            $teams = $teamsQuery->get();
            $this->info("Forzando actualización de estadísticas para " . $teams->count() . " equipos...");
        }
        
        if ($teams->isEmpty()) {
            $this->info("No hay equipos que sincronizar con los filtros especificados.");
            return 0;
        }
        
        $bar = $this->output->createProgressBar($teams->count());
        $bar->start();
        
        $success = 0;
        $failed = 0;
        
        foreach ($teams as $index => $team) {
            $result = $this->syncTeamStats($team, $force, true);
            if ($result) {
                $success++;
            } else {
                $failed++;
            }
            $bar->advance();
            
            // Esperar entre solicitudes, excepto en la última
            if ($index < $teams->count() - 1) {
                $this->info("\nEsperando {$delay} segundos para evitar sobrecargar la API...");
                sleep($delay);
            }
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Sincronización completada: {$success} exitosos, {$failed} fallidos");
        
        return 0;
    }
    
    /**
     * Sincroniza las estadísticas de un equipo
     *
     * @param \App\Models\Team $team
     * @param bool $force
     * @param bool $verbose
     * @return bool
     */
    private function syncTeamStats($team, $force = false, $verbose = true)
    {
        try {
            // Verificar si ya existen estadísticas para este equipo
            $existingStats = TeamStats::where('team_id', $team->id)->first();
            
            if ($existingStats && !$force) {
                if ($verbose) {
                    $this->line("El equipo {$team->name} ya tiene estadísticas. Use --force para actualizar.");
                }
                return true;
            }
            
            // Obtener estadísticas desde el servicio
            $footballDataService = app(FootballDataService::class);
            $stats = $footballDataService->getTeamStats($team->id);
            
            if (!$stats) {
                if ($verbose) {
                    $this->error("No se pudieron obtener estadísticas para {$team->name}");
                }
                return false;
            }

            // Intentar obtener league_id de diferentes ubicaciones posibles en la estructura JSON
            $leagueId = null;

            // Primero intenta obtener de la estructura tradicional
            if (isset($stats['team']['league']['id'])) {
                $leagueId = $stats['team']['league']['id'];
            } 
            // Luego intenta obtener de competitions (data de football-data.org)
            else if (isset($stats['competitions']) && is_array($stats['competitions']) && count($stats['competitions']) > 0) {
                // Usar la primera competición como liga principal
                $leagueId = $stats['competitions'][0]['id'];
            } 
            // Finalmente intenta usar currentSeason si existe
            else if (isset($stats['currentSeason']['leagueId'])) {
                $leagueId = $stats['currentSeason']['leagueId'];
            }

            // Si aún no hay league_id, usar un valor predeterminado (como 1)
            if (!$leagueId && $team->league_id) {
                $leagueId = $team->league_id; // Usar la liga del equipo si está disponible
            } else if (!$leagueId) {
                $leagueId = 1; // Valor por defecto si no hay otra opción
            }

            // Verificar si el ID de liga existe en la tabla de ligas
            $leagueExists = \App\Models\League::where('id', $leagueId)->exists();
            
            // Si no existe, buscar o crear la liga usando el nombre del equipo
            if (!$leagueExists) {
                $leagueId = $this->findOrCreateLeague($stats, $team);
            }

            // Extraer la temporada
            $season = $stats['team']['league']['season'] ?? 
                     $stats['currentSeason']['year'] ?? 
                     date('Y');
            
            // Crear o actualizar estadísticas
            if ($existingStats) {
                $existingStats->update([
                    'league_id' => $leagueId,
                    'season' => $season,
                    'stats_json' => json_encode($stats)
                ]);
                
                if ($verbose) {
                    $this->info("Estadísticas de {$team->name} actualizadas correctamente");
                }
            } else {
                TeamStats::create([
                    'team_id' => $team->id,
                    'league_id' => $leagueId,
                    'season' => $season,
                    'stats_json' => json_encode($stats)
                ]);
                
                if ($verbose) {
                    $this->info("Estadísticas de {$team->name} creadas correctamente");
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error al sincronizar estadísticas para {$team->name}", [
                'error' => $e->getMessage(),
                'team_id' => $team->id
            ]);
            
            if ($verbose) {
                $this->error("Error al procesar {$team->name}: " . $e->getMessage());
            }
            
            return false;
        }
    }

    /**
     * Encuentra o crea una liga basada en los datos del equipo
     * 
     * @param array $stats Los datos estadísticos del equipo
     * @param \App\Models\Team $team El equipo
     * @return int El ID de la liga
     */
    private function findOrCreateLeague($stats, $team)
    {
        // Intentar extraer el nombre de la liga
        $leagueName = null;
        
        // Buscar en diferentes ubicaciones posibles
        if (isset($stats['team']['league']['name'])) {
            $leagueName = $stats['team']['league']['name'];
        } elseif (isset($stats['competitions'][0]['name'])) {
            $leagueName = $stats['competitions'][0]['name'];
        }
        
        // Si tenemos un nombre, buscar la liga por nombre
        if ($leagueName) {
            $league = \App\Models\League::where('name', 'like', "%{$leagueName}%")->first();
            
            if ($league) {
                return $league->id;
            }
        }
        
        // Si el equipo tiene país, buscar o crear una liga genérica para ese país
        $country = $team->country;
        if ($country) {
            $leagueName = $leagueName ?: "{$country} League"; // Liga genérica con el nombre del país
            
            $league = \App\Models\League::firstOrCreate(
                ['name' => $leagueName],
                [
                    'country' => $country,
                    'type' => 'LEAGUE',
                    'active' => true
                ]
            );
            
            return $league->id;
        }
        
        // Si todo falla, usar la primera liga disponible o crear una genérica
        $firstLeague = \App\Models\League::first();
        if ($firstLeague) {
            return $firstLeague->id;
        }
        
        // Crear una liga genérica como último recurso
        $league = \App\Models\League::create([
            'name' => 'Default League',
            'country' => 'International',
            'type' => 'LEAGUE',
            'active' => true
        ]);
        
        return $league->id;
    }
} 