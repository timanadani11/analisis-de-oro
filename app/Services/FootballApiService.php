<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\FootballMatch;

class FootballApiService
{
    protected $baseUrl;
    protected $apiKey;
    protected $apiHost;
    protected $importantLeagueIds;

    public function __construct()
    {
        $this->baseUrl = 'https://v3.football.api-sports.io';
        $this->apiKey = config('services.football_api.key');
        $this->apiHost = config('services.football_api.host', 'v3.football.api-sports.io');
        
        // Definir IDs de ligas importantes para reutilización en varios métodos
        $this->importantLeagueIds = [
            // Principales ligas europeas
            39,  // Premier League (Inglaterra)
            140, // La Liga (España)
            135, // Serie A (Italia)
            78,  // Bundesliga (Alemania)
            61,  // Ligue 1 (Francia)
            
            // Otras ligas importantes
            128, // Liga Profesional Argentina
            71,  // Brasileirao (Brasil)
            98,  // MLS (Estados Unidos)
            179, // Liga MX (México)
            
            // Copas internacionales
            2,   // Champions League
            3,   // Europa League
            45,  // FA Cup (Inglaterra)
            48,  // Carabao Cup (Inglaterra)
            143, // Copa del Rey (España)
            137, // Coppa Italia (Italia)
            81,  // DFB Pokal (Alemania)
            66,  // Coupe de France (Francia)
            
            // Torneos de selecciones
            1,   // Mundial
            4,   // Eurocopa
            5,   // Copa América
            10,  // Copa Africana de Naciones
        ];
        
        // Log API configuration for debugging
        Log::info('Football API Service initialized', [
            'baseUrl' => $this->baseUrl,
            'apiKey' => $this->apiKey ? substr($this->apiKey, 0, 5) . '...' : 'Not configured',
            'apiHost' => $this->apiHost
        ]);
    }

    /**
     * Make an API request to the Football API
     *
     * @param string $endpoint
     * @param array $params
     * @return array|null
     */
    public function makeRequest(string $endpoint, array $params = [])
    {
        Log::info('Making Football API request', [
            'endpoint' => $endpoint,
            'params' => $params,
        ]);
        
        try {
            // Set up the request headers
            $headers = [
                'x-rapidapi-key' => $this->apiKey,
                'x-rapidapi-host' => $this->apiHost,
            ];
            
            Log::debug('Request headers', [
                'headers' => array_merge(['x-rapidapi-key' => substr($this->apiKey, 0, 5) . '...'], ['x-rapidapi-host' => $this->apiHost]),
            ]);
            
            // Make the request
            $response = Http::withHeaders($headers)
                ->get("{$this->baseUrl}/{$endpoint}", $params);
            
            // Check if the request was successful
            if ($response->successful()) {
                $data = $response->json();
                Log::info('Successful API response', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'results' => $data['results'] ?? 0,
                ]);
                return $data;
            }

            // Log the error if the request failed
            Log::error('Football API request failed', [
                'endpoint' => $endpoint,
                'params' => $params,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Football API exception', [
                'message' => $e->getMessage(),
                'endpoint' => $endpoint,
                'params' => $params,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Test the connection with the football API
     * 
     * @return bool
     */
    public function testConnection()
    {
        try {
            // Make the request to a lightweight endpoint (status endpoint)
            $response = Http::withHeaders([
                'x-rapidapi-key' => $this->apiKey,
                'x-rapidapi-host' => $this->apiHost,
            ])->get("{$this->baseUrl}/status");
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('API Connection Test failed', [
                'message' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Get fixtures for a specific date
     * 
     * @param string $date Date in format YYYY-MM-DD
     * @return array|null
     */
    public function getFixturesByDate(string $date)
    {
        return $this->makeRequest('fixtures', ['date' => $date]);
    }

    /**
     * Get today's matches
     * 
     * @return array La respuesta original de la API y los partidos procesados e importantes
     */
    public function getTodayMatches()
    {
        $today = Carbon::today()->format('Y-m-d');
        $response = $this->getFixturesByDate($today);
        
        if (!$response || !isset($response['response'])) {
            return [
                'success' => false,
                'message' => 'No se pudieron obtener los partidos',
                'data' => []
            ];
        }
        
        // Filtrar solo partidos de ligas importantes
        $importantMatches = array_filter($response['response'], function($match) {
            return in_array($match['league']['id'], $this->importantLeagueIds);
        });
        
        $processedMatches = [];
        
        foreach ($importantMatches as $fixture) {
            $processedMatches[] = [
                'id' => $fixture['fixture']['id'],
                'league' => [
                    'id' => $fixture['league']['id'],
                    'name' => $fixture['league']['name'],
                    'country' => $fixture['league']['country'],
                    'logo' => $fixture['league']['logo'],
                ],
                'teams' => [
                    'home' => [
                        'id' => $fixture['teams']['home']['id'],
                        'name' => $fixture['teams']['home']['name'],
                        'logo' => $fixture['teams']['home']['logo'],
                    ],
                    'away' => [
                        'id' => $fixture['teams']['away']['id'],
                        'name' => $fixture['teams']['away']['name'],
                        'logo' => $fixture['teams']['away']['logo'],
                    ]
                ],
                'fixture' => [
                    'date' => $fixture['fixture']['date'],
                    'venue' => $fixture['fixture']['venue']['name'] ?? null,
                    'status' => $fixture['fixture']['status']['long'],
                    'elapsed' => $fixture['fixture']['status']['elapsed'],
                ],
                'goals' => [
                    'home' => $fixture['goals']['home'],
                    'away' => $fixture['goals']['away'],
                ],
                'score' => $fixture['score'],
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Partidos obtenidos correctamente',
            'count' => count($processedMatches),
            'data' => $processedMatches,
            'raw_response' => $response
        ];
    }
    
    /**
     * Guardar los partidos en la base de datos
     * 
     * @param array $matchesData Datos de los partidos desde la API
     * @return array Resultado de la operación
     */
    public function saveMatches(array $matchesData)
    {
        // Logging para debug
        Log::info('Received matches data in service', [
            'data_structure' => json_encode(array_keys($matchesData)),
            'has_data_key' => isset($matchesData['data']) ? 'yes' : 'no',
            'is_array' => is_array($matchesData) ? 'yes' : 'no',
        ]);
        
        // Flexibilidad en la estructura que podemos recibir
        $matches = isset($matchesData['data']) && !empty($matchesData['data']) 
            ? $matchesData['data'] 
            : (array_key_exists(0, $matchesData) ? $matchesData : []);
        
        if (empty($matches)) {
            return [
                'success' => false,
                'message' => 'No hay datos de partidos para guardar',
                'saved' => 0,
                'errors' => ['La estructura de datos no es la esperada']
            ];
        }
        
        $savedCount = 0;
        $errors = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($matches as $match) {
                try {
                    // Verificar si el partido ya existe en la base de datos
                    $existingMatch = FootballMatch::where('api_fixture_id', $match['id'])->first();
                    
                    if ($existingMatch) {
                        $this->saveMatchData($existingMatch, $match);
                    } else {
                        $this->saveMatchData(new FootballMatch(), $match, false);
                    }
                    
                    $savedCount++;
                } catch (\Exception $innerEx) {
                    // Capturar excepciones individuales por partido
                    Log::warning('Error al guardar partido individual', [
                        'match_id' => $match['id'] ?? 'unknown',
                        'error' => $innerEx->getMessage()
                    ]);
                    $errors[] = 'Error en partido ' . ($match['id'] ?? 'desconocido') . ': ' . $innerEx->getMessage();
                    continue; // Continuar con el siguiente partido
                }
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => "Se han guardado {$savedCount} partidos correctamente",
                'saved' => $savedCount,
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al guardar partidos', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al guardar los partidos: ' . $e->getMessage(),
                'saved' => $savedCount,
                'errors' => [$e->getMessage()]
            ];
        }
    }
    
    /**
     * Método unificado para crear o actualizar un partido
     * 
     * @param FootballMatch $footballMatch El modelo (nuevo o existente)
     * @param array $matchData Datos del partido desde la API
     * @param bool $isUpdate Si es actualización o creación
     * @return bool Resultado de la operación
     */
    private function saveMatchData(FootballMatch $footballMatch, array $matchData, bool $isUpdate = true)
    {
        // ID de la API
        if (!$isUpdate) {
            $footballMatch->api_fixture_id = $matchData['id'];
            
            // Datos de la liga
            $footballMatch->league_name = $matchData['league']['name'] ?? 'Desconocida';
            $footballMatch->league_logo = $matchData['league']['logo'] ?? null;
            $footballMatch->league_country = $matchData['league']['country'] ?? 'Desconocido';
            
            // Datos de equipos
            $footballMatch->home_team_name = $matchData['teams']['home']['name'] ?? 'Equipo Local';
            $footballMatch->home_team_logo = $matchData['teams']['home']['logo'] ?? null;
            $footballMatch->away_team_name = $matchData['teams']['away']['name'] ?? 'Equipo Visitante';
            $footballMatch->away_team_logo = $matchData['teams']['away']['logo'] ?? null;
            
            // Venue (solo al crear)
            $footballMatch->venue = $matchData['fixture']['venue']['name'] ?? null;
            
            // Fecha del partido (solo al crear)
            try {
                $footballMatch->match_date = Carbon::parse($matchData['fixture']['date']);
            } catch (\Exception $e) {
                $footballMatch->match_date = now();
                Log::warning('Error parsing match date', [
                    'date' => $matchData['fixture']['date'] ?? 'undefined',
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Datos comunes que se actualizan siempre
        $footballMatch->status = $matchData['fixture']['status']['long'] ?? 'Not Started';
        $footballMatch->home_goals = $matchData['goals']['home'] ?? 0;
        $footballMatch->away_goals = $matchData['goals']['away'] ?? 0;
        $footballMatch->elapsed_time = $matchData['fixture']['status']['elapsed'] ?? 0;
        
        // Datos JSON
        if (isset($matchData['score'])) {
            $footballMatch->statistics = json_encode($matchData['score']);
        }
        
        // Datos extra (solo para partidos nuevos o si están presentes)
        if (!$isUpdate || isset($matchData['events'])) {
            $footballMatch->events = isset($matchData['events']) ? json_encode($matchData['events']) : null;
        }
        
        if (!$isUpdate || isset($matchData['lineups'])) {
            $footballMatch->lineups = isset($matchData['lineups']) ? json_encode($matchData['lineups']) : null;
        }
        
        $footballMatch->save();
        
        $action = $isUpdate ? 'Updated' : 'Created new';
        Log::info("$action football match", [
            'match_id' => $footballMatch->id,
            'fixture_id' => $footballMatch->api_fixture_id,
            'teams' => $footballMatch->home_team_name . ' vs ' . $footballMatch->away_team_name,
            'score' => $footballMatch->home_goals . '-' . $footballMatch->away_goals
        ]);
        
        return true;
    }
}
