<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\FootballMatch;
use App\Models\League;
use App\Models\Player;
use App\Models\Coach;
use App\Models\Season;
use App\Models\Sport;
use App\Models\Team;
use App\Services\FootballDataService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchCompetitionMatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'football:fetch-competition-matches 
                            {--league_id= : ID de la liga en la BD}
                            {--api_league_id= : ID de la liga en la API}
                            {--season= : Año de la temporada (ej: 2023)}
                            {--all_active : Obtener partidos de todas las ligas activas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and store matches for specified competitions/leagues from the API';

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
        $this->info('Starting to fetch competition matches...');

        $leagueId = $this->option('league_id');
        $apiLeagueId = $this->option('api_league_id');
        $seasonYear = $this->option('season'); // Puede ser null
        $allActive = $this->option('all_active');

        if (!$leagueId && !$apiLeagueId && !$allActive) {
            $this->error('Please specify --league_id, --api_league_id, or use --all_active.');
            return 1;
        }

        $leaguesToFetch = collect();

        if ($leagueId) {
            $league = League::find($leagueId);
            if ($league && $league->api_league_id) {
                $leaguesToFetch->push($league);
            } else {
                $this->warn("League with DB ID {$leagueId} not found or has no api_league_id.");
            }
        }

        if ($apiLeagueId) {
            $league = League::where('api_league_id', $apiLeagueId)->first();
            if ($league) {
                if (!$leaguesToFetch->contains('id', $league->id)) {
                    $leaguesToFetch->push($league);
                }
            } else {
                // Si no existe en BD, creamos un objeto temporal para obtener su api_league_id
                $leaguesToFetch->push((object)['id' => null, 'name' => "API ID: {$apiLeagueId}", 'api_league_id' => $apiLeagueId]);
            }
        }

        if ($allActive) {
            $activeLeagues = League::where('active', true)->whereNotNull('api_league_id')->get();
            foreach ($activeLeagues as $league) {
                if (!$leaguesToFetch->contains('id', $league->id)) {
                    $leaguesToFetch->push($league);
                }
            }
            $this->info("Found {$activeLeagues->count()} active leagues to fetch.");
        }

        if ($leaguesToFetch->isEmpty()) {
            $this->warn('No leagues found to fetch matches for.');
            return 1;
        }

        // Get the football sport
        $sport = Sport::where('name', 'Football')->first();
        if (!$sport) {
            $this->error('Football sport not found in database');
            return 1;
        }

        foreach ($leaguesToFetch as $league) {
            $this->fetchMatchesForLeague($league, $sport, $seasonYear);
            if ($leaguesToFetch->count() > 1) {
                $this->info("Waiting 60 seconds before fetching next league to respect API limits...");
                sleep(60); 
            }
        }

        $this->info('Completed fetching competition matches!');
        return 0;
    }

    /**
     * Fetch matches for a specific league
     */
    protected function fetchMatchesForLeague(object $league, Sport $sport, ?string $seasonYear)
    {
        $this->info("Fetching matches for league: {$league->name} (API ID: {$league->api_league_id})...");
        
        // Si la liga no tiene ID de BD (es un objeto temporal), intentamos encontrarla o crearla
        $dbLeague = $league->id ? League::find($league->id) : null;

        $response = $this->footballApiService->getMatchesByCompetition($league->api_league_id, $seasonYear);
        
        if (!$response || !isset($response['matches']) || empty($response['matches'])) {
            if (isset($response['message'])) {
                 $this->warn("API Error for {$league->name}: " . $response['message']);
            } else {
                $this->warn("No matches found for league {$league->name} or API request failed");
            }
            return;
        }
        
        $fixtures = $response['matches'];
        $this->info("Found " . count($fixtures) . " matches for league {$league->name}.");
        
        $progressBar = $this->output->createProgressBar(count($fixtures));
        $progressBar->start();
        
        foreach ($fixtures as $fixtureData) {
            try {
                // Si dbLeague es null (porque se pasó solo api_league_id y no existía en BD)
                // Necesitamos encontrarla o crearla aquí usando la info del primer partido
                if (!$dbLeague && isset($fixtureData['competition'])) {
                    $dbLeague = $this->findOrCreateLeague($fixtureData['competition'], $sport, $fixtureData['season']['currentMatchday'] ?? null );
                }
                if (!$dbLeague) {
                    $this->error("Could not determine or create league for API ID: {$league->api_league_id}");
                    continue;
                }
                $this->processFixture($fixtureData, $sport, $dbLeague);
            } catch (\Exception $e) {
                Log::error('Error processing fixture for competition match', [
                    'fixture_id_api' => $fixtureData['id'] ?? 'unknown',
                    'league_name' => $league->name,
                    'error' => $e->getMessage(),
                    'trace_small' => substr($e->getTraceAsString(), 0, 500),
                ]);
                $this->error("Error processing fixture for league {$league->name}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Process and store a single fixture
     * Esta función es una adaptación de la que existe en FetchFootballMatches
     * Se le añade el parámetro $league para no tener que re-evaluarla en cada fixture.
     */
    protected function processFixture(array $fixtureData, Sport $sport, League $league)
    {
        $fixtureId = $fixtureData['id'];
        $date = Carbon::parse($fixtureData['utcDate']);
        $statusApi = $fixtureData['status'] ?? 'SCHEDULED';
        $status = $this->mapStatus($statusApi);

        // Season data
        $apiSeasonData = $fixtureData['season'] ?? [];
        $seasonYear = $apiSeasonData['startDate'] ? Carbon::parse($apiSeasonData['startDate'])->year : ($apiSeasonData['endDate'] ? Carbon::parse($apiSeasonData['endDate'])->year : $date->year);
        $season = $this->findOrCreateSeason($league, $seasonYear, $apiSeasonData);
        
        // Teams data - La API de /matches no siempre trae el 'crest' del equipo, solo el id y nombre.
        // Usaremos findOrCreateTeam que ya busca en BD y si no existe, usa la info básica que trae la API de partidos.
        // Si se quiere info más detallada del equipo, se debe correr football:update-teams
        $homeTeamData = ['id' => $fixtureData['homeTeam']['id'], 'name' => $fixtureData['homeTeam']['name'], 'logo' => $fixtureData['homeTeam']['crest'] ?? null];
        $awayTeamData = ['id' => $fixtureData['awayTeam']['id'], 'name' => $fixtureData['awayTeam']['name'], 'logo' => $fixtureData['awayTeam']['crest'] ?? null];
        
        $homeTeam = $this->findOrCreateTeam($homeTeamData, $league); 
        $awayTeam = $this->findOrCreateTeam($awayTeamData, $league);
        
        // Scores
        $homeScore = $fixtureData['score']['fullTime']['home'] ?? null;
        $awayScore = $fixtureData['score']['fullTime']['away'] ?? null;
        
        // Halftime scores
        $homeHalftimeScore = $fixtureData['score']['halfTime']['home'] ?? null;
        $awayHalftimeScore = $fixtureData['score']['halfTime']['away'] ?? null;
        
        // Venue y Referee - Estos datos no siempre vienen en el endpoint /matches de una competición.
        // El endpoint /matches/{matchId} sí los trae de forma más fiable.
        $venue = null; // Lógica para obtener venue si es posible
        $referee = null; // Lógica para obtener referee si es posible

        // Round - Puede que no venga en este endpoint, o que venga como matchday
        $round = $fixtureData['matchday'] ?? $fixtureData['round'] ?? ($fixtureData['season']['currentMatchday'] == $fixtureData['matchday'] ? $fixtureData['matchday'] : null);

        FootballMatch::updateOrCreate(
            ['api_fixture_id' => $fixtureId],
            [
                'sport_id' => $sport->id,
                'league_id' => $league->id,
                'season_id' => $season->id,
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'match_date' => $date,
                'venue' => $venue,
                'referee' => $referee,
                'round' => $round,
                'status' => $status,
                'home_goals' => $homeScore,
                'away_goals' => $awayScore,
                'home_halftime_goals' => $homeHalftimeScore, // Corregido de home_halftime_score
                'away_halftime_goals' => $awayHalftimeScore, // Corregido de away_halftime_score
                // 'stats' => null, // El endpoint de competiciones no suele traerlos
                // 'events' => null,
                // 'lineups' => null,
                'metadata' => json_encode($fixtureData), // Guardamos toda la respuesta para futura referencia
            ]
        );
    }

    /**
     * Find or create a league
     * Adaptado para recibir $currentMatchday opcionalmente, que a veces viene en el fixture
     */
    protected function findOrCreateLeague(array $leagueData, Sport $sport, ?int $currentMatchday = null)
    {
        $apiLeagueId = $leagueData['id'];
        $league = League::where('api_league_id', $apiLeagueId)->first();
        
        if (!$league) {
            $country = null;
            if (!empty($leagueData['area']['name'])) {
                $country = Country::firstOrCreate(
                    ['name' => $leagueData['area']['name']],
                    [
                        'code' => $leagueData['area']['code'] ?? null,
                        'flag' => $leagueData['area']['flag'] ?? null,
                    ]
                );
            }
            
            $league = League::create([
                'sport_id' => $sport->id,
                'country_id' => $country ? $country->id : null,
                'name' => $leagueData['name'],
                'type' => $leagueData['type'] ?? 'LEAGUE', // Asumir LEAGUE si no viene
                'logo' => $leagueData['emblem'] ?? null,
                'api_league_id' => $apiLeagueId,
                'current_matchday' => $currentMatchday, // Nuevo campo potencial
                'metadata' => json_encode($leagueData),
            ]);
            $this->info("Created new league: {$leagueData['name']}");
        } else {
            // Actualizar current_matchday si se proporciona y es diferente
            if ($currentMatchday && $league->current_matchday != $currentMatchday) {
                $league->update(['current_matchday' => $currentMatchday]);
            }
        }
        return $league;
    }

    /**
     * Find or create a season
     * Modificado para aceptar $apiSeasonData opcionalmente
     */
    protected function findOrCreateSeason(League $league, $year, ?array $apiSeasonData = [])
    {
        $seasonDataToCreate = [
            'name' => ($apiSeasonData['displayName'] ?? null) ?: ($year . '/' . ($year + 1)),
            'start_date' => isset($apiSeasonData['startDate']) ? Carbon::parse($apiSeasonData['startDate'])->format('Y-m-d') : null,
            'end_date' => isset($apiSeasonData['endDate']) ? Carbon::parse($apiSeasonData['endDate'])->format('Y-m-d') : null,
            'current_matchday' => $apiSeasonData['currentMatchday'] ?? null,
            'current' => $apiSeasonData['currentMatchday'] ? true : false, // Una heurística simple para 'current'
        ];

        // Filtrar valores nulos para no intentar insertar NULL en campos que podrían no ser nullable
        // y no tienen valor por defecto, si la API no los provee.
        // Sin embargo, el error indica que 'start_date' SÍ necesita un valor.
        // Si la API no trae startDate, necesitamos un fallback o asegurar que la columna sea nullable.

        // Por ahora, asumimos que si la API no trae las fechas, la columna debe ser nullable o tener default.
        // Si 'start_date' es mandatorio y no viene, el error persistirá.

        return Season::firstOrCreate(
            [
                'league_id' => $league->id,
                'year' => $year,
            ],
            array_filter($seasonDataToCreate, function($value) { return $value !== null; })
        );
    }

    /**
     * Find or create a team.
     * Se le añade el parámetro $dbLeague para asegurar que el equipo se asocie a la liga correcta.
     */
    protected function findOrCreateTeam(array $teamData, League $dbLeague)
    {
        $apiTeamId = $teamData['id'];
        $team = Team::where('api_team_id', $apiTeamId)->first();
        
        if (!$team) {
            $team = Team::create([
                'name' => $teamData['name'],
                'logo' => $teamData['logo'] ?? null,
                'api_team_id' => $apiTeamId,
                'league_id' => $dbLeague->id, // Asegurar la asociación a la liga actual
                // No tenemos más datos del equipo desde este endpoint de partidos de competición
                // 'country', 'founded', 'venue_name' etc. se obtendrían con football:update-teams
                'metadata' => json_encode($teamData), 
            ]);
            $this->info("Created new team: {$teamData['name']} for league {$dbLeague->name}");
        } elseif (!$team->league_id) {
            // Si el equipo ya existe pero no tiene liga asignada (podría pasar si se creó desde otra fuente)
            // y esta es una liga válida, lo asignamos.
            $team->update(['league_id' => $dbLeague->id]);
        }
        
        return $team;
    }

    /**
     * Map API status to our status
     */
    protected function mapStatus(string $apiStatus): string
    {
        $statusMap = [
            'SCHEDULED' => 'scheduled',
            'TIMED' => 'scheduled',
            'IN_PLAY' => 'live',
            'PAUSED' => 'halftime',
            'FINISHED' => 'finished',
            'SUSPENDED' => 'suspended',
            'POSTPONED' => 'postponed',
            'CANCELLED' => 'canceled',
            'AWARDED' => 'awarded',
        ];
        return $statusMap[strtoupper($apiStatus)] ?? 'unknown';
    }
}
