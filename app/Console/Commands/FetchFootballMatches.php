<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\FootballMatch;
use App\Models\League;
use App\Models\Season;
use App\Models\Sport;
use App\Models\Team;
use App\Services\FootballApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchFootballMatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'football:fetch-matches {--date= : Specific date to fetch (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch football matches for today and tomorrow from the API';

    /**
     * The football API service
     *
     * @var FootballApiService
     */
    protected $apiService;

    /**
     * Create a new command instance.
     */
    public function __construct(FootballApiService $apiService)
    {
        parent::__construct();
        $this->apiService = $apiService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to fetch football matches...');

        $date = $this->option('date');
        
        if ($date) {
            $this->fetchMatchesForDate($date);
        } else {
            // Fetch today and tomorrow
            $today = Carbon::today()->format('Y-m-d');
            $tomorrow = Carbon::tomorrow()->format('Y-m-d');
            
            $this->fetchMatchesForDate($today);
            $this->fetchMatchesForDate($tomorrow);
        }

        $this->info('Completed fetching football matches!');
    }

    /**
     * Fetch matches for a specific date
     */
    protected function fetchMatchesForDate(string $date)
    {
        $this->info("Fetching matches for {$date}...");
        
        $response = $this->apiService->getFixturesByDate($date);
        
        if (!$response || !isset($response['response']) || empty($response['response'])) {
            $this->warn("No matches found for {$date} or API request failed");
            return;
        }
        
        $fixtures = $response['response'];
        $this->info("Found " . count($fixtures) . " matches for {$date}");
        
        // Get the football sport
        $sport = Sport::where('name', 'Football')->first();
        if (!$sport) {
            $this->error('Football sport not found in database');
            return;
        }
        
        $progressBar = $this->output->createProgressBar(count($fixtures));
        $progressBar->start();
        
        foreach ($fixtures as $fixture) {
            try {
                $this->processFixture($fixture, $sport);
            } catch (\Exception $e) {
                Log::error('Error processing fixture', [
                    'fixture_id' => $fixture['fixture']['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error('Error processing fixture: ' . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Process and store a single fixture
     */
    protected function processFixture(array $fixture, Sport $sport)
    {
        // Extract fixture data
        $fixtureId = $fixture['fixture']['id'];
        $date = Carbon::parse($fixture['fixture']['date']);
        $venue = $fixture['fixture']['venue']['name'] ?? null;
        $referee = $fixture['fixture']['referee'] ?? null;
        $status = $this->mapStatus($fixture['fixture']['status']['short'] ?? 'NS');
        
        // League data
        $leagueData = $fixture['league'];
        $league = $this->findOrCreateLeague($leagueData, $sport);
        
        // Season data
        $seasonYear = $leagueData['season'];
        $season = $this->findOrCreateSeason($league, $seasonYear);
        
        // Teams data
        $homeTeam = $this->findOrCreateTeam($fixture['teams']['home']);
        $awayTeam = $this->findOrCreateTeam($fixture['teams']['away']);
        
        // Scores
        $homeScore = $fixture['goals']['home'] ?? 0;
        $awayScore = $fixture['goals']['away'] ?? 0;
        
        // Halftime scores
        $homeHalftimeScore = $fixture['score']['halftime']['home'] ?? null;
        $awayHalftimeScore = $fixture['score']['halftime']['away'] ?? null;
        
        // Create or update match
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
                'round' => $fixture['league']['round'] ?? null,
                'status' => $status,
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'home_halftime_score' => $homeHalftimeScore,
                'away_halftime_score' => $awayHalftimeScore,
                'stats' => isset($fixture['statistics']) ? json_encode($fixture['statistics']) : null,
                'events' => isset($fixture['events']) ? json_encode($fixture['events']) : null,
                'lineups' => isset($fixture['lineups']) ? json_encode($fixture['lineups']) : null,
                'metadata' => json_encode($fixture),
            ]
        );
    }

    /**
     * Find or create a league
     */
    protected function findOrCreateLeague(array $leagueData, Sport $sport)
    {
        $apiLeagueId = $leagueData['id'];
        
        $league = League::where('api_league_id', $apiLeagueId)->first();
        
        if (!$league) {
            // Find or create country
            $country = null;
            if (!empty($leagueData['country'])) {
                $country = Country::firstOrCreate(
                    ['name' => $leagueData['country']],
                    [
                        'code' => $leagueData['flag'] ? substr($leagueData['flag'], -2) : null,
                        'flag' => $leagueData['flag'] ?? null,
                    ]
                );
            }
            
            $league = League::create([
                'sport_id' => $sport->id,
                'country_id' => $country ? $country->id : null,
                'name' => $leagueData['name'],
                'type' => $leagueData['type'] ?? null,
                'logo' => $leagueData['logo'] ?? null,
                'api_league_id' => $apiLeagueId,
                'metadata' => json_encode($leagueData),
            ]);
        }
        
        return $league;
    }

    /**
     * Find or create a season
     */
    protected function findOrCreateSeason(League $league, $year)
    {
        return Season::firstOrCreate(
            [
                'league_id' => $league->id,
                'year' => $year,
            ],
            [
                'name' => $year,
                'current' => true,
            ]
        );
    }

    /**
     * Find or create a team
     */
    protected function findOrCreateTeam(array $teamData)
    {
        $apiTeamId = $teamData['id'];
        
        $team = Team::where('api_team_id', $apiTeamId)->first();
        
        if (!$team) {
            $team = Team::create([
                'name' => $teamData['name'],
                'country' => null, // We would need more data to get this
                'logo' => $teamData['logo'] ?? null,
                'api_team_id' => $apiTeamId,
                'metadata' => json_encode($teamData),
            ]);
        }
        
        return $team;
    }

    /**
     * Map API status to our status
     */
    protected function mapStatus(string $apiStatus): string
    {
        $statusMap = [
            'NS' => 'scheduled',
            'TBD' => 'scheduled',
            'FT' => 'finished',
            'AET' => 'finished',
            'PEN' => 'finished',
            '1H' => 'live',
            '2H' => 'live',
            'HT' => 'halftime',
            'ET' => 'live',
            'BT' => 'break',
            'P' => 'penalty',
            'SUSP' => 'suspended',
            'INT' => 'interrupted',
            'PST' => 'postponed',
            'CANC' => 'canceled',
            'ABD' => 'abandoned',
            'AWD' => 'awarded',
            'WO' => 'walkover',
        ];
        
        return $statusMap[$apiStatus] ?? 'unknown';
    }
}
