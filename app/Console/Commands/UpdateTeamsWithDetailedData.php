<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\Player;
use App\Models\Coach;
use App\Models\League;
use App\Services\FootballDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateTeamsWithDetailedData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'football:update-teams 
                            {--team_id= : ID específico del equipo que se desea actualizar}
                            {--all : Actualizar todos los equipos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los equipos con información detallada desde la API de football-data.org';

    /**
     * The football API service
     *
     * @var FootballDataService
     */
    protected $footballApiService;

    /**
     * Create a new command instance.
     */
    public function __construct(FootballDataService $footballApiService)
    {
        parent::__construct();
        $this->footballApiService = $footballApiService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando actualización de equipos con información detallada...');

        $teamId = $this->option('team_id');
        $updateAll = $this->option('all');

        if (!$teamId && !$updateAll) {
            $this->error('Debe especificar --team_id o --all para actualizar equipos.');
            return 1;
        }

        if ($teamId) {
            // Actualizar un equipo específico
            $team = Team::find($teamId);
            if (!$team) {
                $this->error("No se encontró el equipo con ID: {$teamId}");
                return 1;
            }
            $this->updateTeam($team);
        } else {
            // Actualizar todos los equipos
            $teams = Team::whereNotNull('api_team_id')->get();
            $total = $teams->count();
            $this->info("Se actualizarán {$total} equipos");

            $progress = $this->output->createProgressBar($total);
            $progress->start();

            foreach ($teams as $team) {
                $this->updateTeam($team, false);
                $progress->advance();
                // Pequeña pausa para no sobrecargar la API
                sleep(60);
            }

            $progress->finish();
            $this->newLine();
        }

        $this->info('¡Actualización completada!');
        return 0;
    }

    /**
     * Actualiza la información de un equipo con detalles desde la API
     *
     * @param Team $team El equipo a actualizar
     * @param bool $verbose Si es true, muestra mensajes detallados
     * @return bool
     */
    protected function updateTeam(Team $team, $verbose = true)
    {
        if ($verbose) {
            $this->info("Actualizando equipo: {$team->name} (ID: {$team->id}, API ID: {$team->api_team_id})");
        }

        try {
            // Obtener información detallada del equipo
            $detailedTeamData = $this->footballApiService->getTeamDetails($team->api_team_id);
            
            if (!$detailedTeamData) {
                if ($verbose) {
                    $this->warn("No se pudo obtener información detallada para el equipo {$team->name}");
                }
                return false;
            }

            // Actualizar información básica del equipo
            $team->update([
                'short_name' => $detailedTeamData['shortName'] ?? $team->short_name,
                'tla' => $detailedTeamData['tla'] ?? $team->tla,
                'country' => $detailedTeamData['area']['name'] ?? $team->country,
                'logo' => $detailedTeamData['crest'] ?? $team->logo,
                'founded' => $detailedTeamData['founded'] ?? $team->founded,
                'venue_name' => $detailedTeamData['venue'] ?? $team->venue_name,
                'address' => $detailedTeamData['address'] ?? $team->address,
                'website' => $detailedTeamData['website'] ?? $team->website,
                'club_colors' => $detailedTeamData['clubColors'] ?? $team->club_colors,
                'metadata' => json_encode($detailedTeamData),
            ]);
            
            // Actualizar liga si está disponible
            if (isset($detailedTeamData['runningCompetitions']) && !empty($detailedTeamData['runningCompetitions']) && !$team->league_id) {
                foreach ($detailedTeamData['runningCompetitions'] as $competition) {
                    $league = League::where('api_league_id', $competition['id'])->first();
                    if ($league) {
                        $team->update(['league_id' => $league->id]);
                        break;
                    }
                }
            }
            
            // Actualizar jugadores
            if (isset($detailedTeamData['squad']) && !empty($detailedTeamData['squad'])) {
                $this->storeTeamSquad($team, $detailedTeamData['squad'], $verbose);
            }
            
            // Actualizar entrenador
            if (isset($detailedTeamData['coach']) && !empty($detailedTeamData['coach'])) {
                $this->storeTeamCoach($team, $detailedTeamData['coach'], $verbose);
            }
            
            if ($verbose) {
                $this->info("¡Equipo {$team->name} actualizado correctamente!");
            }
            
            return true;
        } catch (\Exception $e) {
            if ($verbose) {
                $this->error("Error actualizando el equipo {$team->name}: " . $e->getMessage());
            }
            Log::error('Error actualizando equipo', [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false;
        }
    }

    /**
     * Store team squad information
     */
    protected function storeTeamSquad(Team $team, array $squad, $verbose = true)
    {
        if ($verbose) {
            $this->info("Guardando plantilla para el equipo {$team->name} ({$team->id})");
        }
        
        foreach ($squad as $playerData) {
            try {
                $player = [
                    'team_id' => $team->id,
                    'api_player_id' => $playerData['id'] ?? null,
                    'name' => $playerData['name'] ?? '',
                    'first_name' => $playerData['firstName'] ?? null,
                    'last_name' => $playerData['lastName'] ?? null,
                    'position' => $playerData['position'] ?? null,
                    'date_of_birth' => isset($playerData['dateOfBirth']) ? date('Y-m-d', strtotime($playerData['dateOfBirth'])) : null,
                    'nationality' => $playerData['nationality'] ?? null,
                    'shirt_number' => $playerData['shirtNumber'] ?? null,
                    'market_value' => $playerData['marketValue'] ?? null,
                    'contract_start' => isset($playerData['contract']['start']) ? date('Y-m-d', strtotime($playerData['contract']['start'])) : null,
                    'contract_until' => isset($playerData['contract']['until']) ? date('Y-m-d', strtotime($playerData['contract']['until'])) : null,
                    'metadata' => json_encode($playerData),
                ];

                Player::updateOrCreate(
                    [
                        'team_id' => $team->id,
                        'api_player_id' => $playerData['id'] ?? null
                    ],
                    $player
                );
            } catch (\Exception $e) {
                if ($verbose) {
                    $this->error("Error al guardar jugador: " . $e->getMessage());
                }
                Log::error('Error guardando jugador', [
                    'player_name' => $playerData['name'] ?? 'Unknown',
                    'team_id' => $team->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }
    
    /**
     * Store team coach information
     */
    protected function storeTeamCoach(Team $team, array $coachData, $verbose = true)
    {
        if ($verbose) {
            $this->info("Guardando entrenador para el equipo {$team->name} ({$team->id})");
        }
        
        try {
            $coach = [
                'team_id' => $team->id,
                'api_coach_id' => $coachData['id'] ?? null,
                'name' => $coachData['name'] ?? '',
                'first_name' => $coachData['firstName'] ?? null,
                'last_name' => $coachData['lastName'] ?? null,
                'date_of_birth' => isset($coachData['dateOfBirth']) ? date('Y-m-d', strtotime($coachData['dateOfBirth'])) : null,
                'nationality' => $coachData['nationality'] ?? null,
                'contract_start' => isset($coachData['contract']['start']) ? date('Y-m-d', strtotime($coachData['contract']['start'])) : null,
                'contract_until' => isset($coachData['contract']['until']) ? date('Y-m-d', strtotime($coachData['contract']['until'])) : null,
                'metadata' => json_encode($coachData),
            ];
            
            Coach::updateOrCreate(
                [
                    'team_id' => $team->id,
                    'api_coach_id' => $coachData['id'] ?? null
                ],
                $coach
            );
        } catch (\Exception $e) {
            if ($verbose) {
                $this->error("Error al guardar entrenador: " . $e->getMessage());
            }
            Log::error('Error guardando entrenador', [
                'coach_name' => $coachData['name'] ?? 'Unknown',
                'team_id' => $team->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
