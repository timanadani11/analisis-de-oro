<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballDataService;
use App\Models\League;
use App\Models\Team;
use App\Models\TeamStats;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ImportFootballDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'football:import {type=all : Type of import (all, leagues, teams, stats)} {--league=CL : League code for teams/stats import} {--season=2024 : Season to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from football-data.org API';

    /**
     * The football data service.
     *
     * @var \App\Services\FootballDataService
     */
    protected $footballDataService;

    /**
     * Create a new command instance.
     *
     * @param  \App\Services\FootballDataService  $footballDataService
     * @return void
     */
    public function __construct(FootballDataService $footballDataService)
    {
        parent::__construct();
        $this->footballDataService = $footballDataService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $leagueCode = $this->option('league');
        $season = $this->option('season');

        $this->info("Starting import process for '{$type}' data...");

        if (!$this->checkConnection()) {
            $this->error('🔴 Could not connect to football-data.org API. Check your API key and try again.');
            return 1;
        }

        switch ($type) {
            case 'leagues':
                $this->importLeagues();
                break;
            case 'teams':
                $this->importTeams($leagueCode, $season);
                break;
            case 'stats':
                $this->importTeamStats($leagueCode);
                break;
            case 'all':
                $this->importLeagues();
                $this->importTeams($leagueCode, $season);
                $this->importTeamStats($leagueCode);
                break;
            default:
                $this->error("Invalid import type: {$type}");
                return 1;
        }

        $this->info('✅ Import process completed successfully!');
        return 0;
    }

    /**
     * Check connection to football-data.org API
     */
    private function checkConnection()
    {
        $this->info('Checking connection to football-data.org API...');
        $result = $this->footballDataService->testConnection();
        if ($result) {
            $this->info('✅ Connection successful!');
        }
        return $result;
    }

    /**
     * Import available leagues/competitions
     */
    private function importLeagues()
    {
        $this->info('Importing leagues/competitions...');
        $result = $this->footballDataService->makeRequest('competitions');
        
        if (!$result || !isset($result['competitions'])) {
            $this->error('🔴 Failed to fetch competitions data.');
            return;
        }

        $count = 0;
        $competitions = $result['competitions'];
        $totalCompetitions = count($competitions);
        $this->info("Found {$totalCompetitions} competitions");

        $bar = $this->output->createProgressBar($totalCompetitions);
        $bar->start();

        foreach ($competitions as $competition) {
            try {
                $league = League::updateOrCreate(
                    ['api_league_id' => $competition['id']],
                    [
                        'name' => $competition['name'],
                        'type' => $competition['type'] ?? 'LEAGUE',
                        'country' => $competition['area']['name'] ?? 'International',
                        'logo' => $competition['emblem'] ?? null,
                        'active' => true,
                        'metadata' => json_encode($competition),
                    ]
                );
                $count++;
            } catch (\Exception $e) {
                Log::error('Error importing league', [
                    'league' => $competition['name'],
                    'error' => $e->getMessage()
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Imported/updated {$count} competitions");
    }

    /**
     * Import teams for a specific league
     */
    private function importTeams($leagueCode, $season)
    {
        $this->info("Importing teams for league '{$leagueCode}' and season '{$season}'...");
        $result = $this->footballDataService->getTeams($leagueCode);

        if (!$result || !isset($result['teams'])) {
            $this->error('🔴 Failed to fetch teams data.');
            return;
        }

        // Get league ID
        $league = League::where('api_league_id', $result['competition']['id'] ?? 0)->first();
        if (!$league) {
            $this->warn('League not found in database, creating it...');
            try {
                $league = League::create([
                    'api_league_id' => $result['competition']['id'],
                    'name' => $result['competition']['name'],
                    'type' => $result['competition']['type'] ?? 'LEAGUE',
                    'country' => $result['competition']['area']['name'] ?? 'International',
                    'logo' => $result['competition']['emblem'] ?? null,
                    'active' => true,
                    'metadata' => json_encode($result['competition']),
                ]);
                $this->info('✅ League created');
            } catch (\Exception $e) {
                $this->error('🔴 Failed to create league: ' . $e->getMessage());
                return;
            }
        }

        $count = 0;
        $teams = $result['teams'];
        $totalTeams = count($teams);
        $this->info("Found {$totalTeams} teams");

        $bar = $this->output->createProgressBar($totalTeams);
        $bar->start();

        foreach ($teams as $teamData) {
            try {
                $team = Team::updateOrCreate(
                    ['api_team_id' => $teamData['id']],
                    [
                        'name' => $teamData['name'],
                        'country' => $teamData['area']['name'] ?? null,
                        'logo' => $teamData['crest'] ?? null,
                        'stadium' => $teamData['venue'] ?? null,
                        'city' => null, // Not available in API
                        'league_id' => $league->id,
                        'founded' => $teamData['founded'] ?? null,
                        'venue_name' => $teamData['venue'] ?? null,
                        'metadata' => json_encode($teamData),
                    ]
                );
                $count++;
            } catch (\Exception $e) {
                Log::error('Error importing team', [
                    'team' => $teamData['name'],
                    'error' => $e->getMessage()
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Imported/updated {$count} teams");
    }

    /**
     * Import team statistics
     */
    private function importTeamStats($leagueCode)
    {
        $this->info("Importing team statistics for league '{$leagueCode}'...");
        
        // First get the teams for this league
        $league = League::where('name', 'like', "%{$leagueCode}%")
            ->orWhere('metadata', 'like', "%{$leagueCode}%")
            ->first();
            
        if (!$league) {
            $this->error('🔴 League not found in database. Import teams first.');
            return;
        }

        $teams = Team::where('league_id', $league->id)->get();
        if ($teams->isEmpty()) {
            $this->error('🔴 No teams found for this league. Import teams first.');
            return;
        }

        $count = 0;
        $totalTeams = $teams->count();
        $this->info("Found {$totalTeams} teams to update statistics");

        $bar = $this->output->createProgressBar($totalTeams);
        $bar->start();

        foreach ($teams as $team) {
            try {
                $statsData = $this->footballDataService->getTeamStats($team->api_team_id);
                
                if ($statsData && isset($statsData['team'])) {
                    // Create/update team stats record
                    $stats = DB::table('team_stats')->updateOrInsert(
                        [
                            'team_id' => $team->id,
                            'league_id' => $league->id,
                            'season' => now()->year,
                        ],
                        [
                            'stats_json' => json_encode($statsData),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                    $count++;
                    
                    // Small delay to avoid rate limiting
                    sleep(1);
                }
            } catch (\Exception $e) {
                Log::error('Error importing team stats', [
                    'team' => $team->name,
                    'error' => $e->getMessage()
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Imported/updated statistics for {$count} teams");
    }
} 