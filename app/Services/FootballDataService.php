<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FootballDataService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = 'https://api.football-data.org/v4';
        $this->apiKey = '1a1b253648f343d29a808ac62f90a859';
    }

    /**
     * Realiza una petición a la API
     *
     * @param string $endpoint
     * @param array $params
     * @return array|null
     */
    public function makeRequest(string $endpoint, array $params = [])
    {
        try {
            Log::info('Making Football-Data.org API request', [
                'endpoint' => $endpoint,
                'params' => $params,
            ]);

            $headers = [
                'X-Auth-Token' => $this->apiKey,
            ];

            $response = Http::withHeaders($headers)
                ->get("{$this->baseUrl}/{$endpoint}", $params);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Successful Football-Data.org API response', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                ]);
                return $data;
            }

            Log::error('Football-Data.org API request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Football-Data.org API exception', [
                'message' => $e->getMessage(),
                'endpoint' => $endpoint,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Prueba la conexión con la API
     * 
     * @return bool
     */
    public function testConnection()
    {
        try {
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiKey,
            ])->get("{$this->baseUrl}/competitions");
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Football-Data.org API Connection Test failed', [
                'message' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Obtiene y guarda los equipos de una liga específica
     * 
     * @param string $leagueId El ID de la liga en la API
     * @param string $season La temporada (por ejemplo, 2024)
     * @return array Resultado de la operación
     */
    public function fetchAndSaveTeams($leagueId, $season)
    {
        try {
            Log::info('Obteniendo equipos', [
                'league_id' => $leagueId,
                'season' => $season
            ]);

            // Buscar liga en la base de datos o crear una nueva
            $league = \App\Models\League::firstOrCreate(
                ['api_league_id' => $leagueId],
                ['name' => "Liga ID {$leagueId}", 'active' => true]
            );

            // Hacer la petición a la API para obtener los equipos de la liga
            $response = $this->makeRequest("competitions/{$leagueId}/teams", [
                'season' => $season
            ]);

            if (!$response || !isset($response['teams'])) {
                return [
                    'success' => false,
                    'message' => 'No se pudieron obtener los equipos desde la API',
                    'total' => 0,
                    'saved' => 0,
                    'errors' => ['La respuesta de la API no contiene equipos']
                ];
            }

            $teamsData = $response['teams'];
            $total = count($teamsData);
            $saved = 0;
            $errors = [];

            foreach ($teamsData as $teamData) {
                try {
                    // Buscar equipo existente o crear nuevo
                    $team = \App\Models\Team::updateOrCreate(
                        ['api_team_id' => $teamData['id']],
                        [
                            'name' => $teamData['name'],
                            'logo' => $teamData['crest'] ?? null,
                            'league_id' => $league->id,
                            'short_name' => $teamData['shortName'] ?? null,
                            'tla' => $teamData['tla'] ?? null,
                            'founded' => $teamData['founded'] ?? null,
                            'venue_name' => $teamData['venue'] ?? null,
                            'address' => $teamData['address'] ?? null,
                            'website' => $teamData['website'] ?? null,
                            'club_colors' => $teamData['clubColors'] ?? null,
                            'country' => $teamData['area']['name'] ?? null,
                            'city' => null, // No disponible en esta API
                            'stadium' => $teamData['venue'] ?? null,
                            'metadata' => [
                                'raw_api_data' => $teamData,
                                'last_updated' => now()->toDateTimeString(),
                                'season' => $season
                            ]
                        ]
                    );

                    $saved++;
                } catch (\Exception $e) {
                    Log::error('Error al guardar equipo', [
                        'team_id' => $teamData['id'] ?? 'unknown',
                        'team_name' => $teamData['name'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);

                    $errors[] = "Error al guardar el equipo {$teamData['name']}: " . $e->getMessage();
                }
            }

            // Actualizar la información de la liga si es la primera vez
            if ($league->name === "Liga ID {$leagueId}" && isset($response['competition'])) {
                $competitionData = $response['competition'];
                $league->update([
                    'name' => $competitionData['name'] ?? $league->name,
                    'logo' => $competitionData['emblem'] ?? null,
                    'country' => $competitionData['area']['name'] ?? null,
                    'type' => $competitionData['type'] ?? 'LEAGUE',
                    'metadata' => [
                        'raw_api_data' => $competitionData,
                        'last_updated' => now()->toDateTimeString()
                    ]
                ]);
            }

            return [
                'success' => true,
                'message' => "Se han procesado {$total} equipos de la liga {$league->name}",
                'total' => $total,
                'saved' => $saved,
                'errors' => $errors
            ];
        } catch (\Exception $e) {
            Log::error('Error general al obtener y guardar equipos', [
                'league_id' => $leagueId,
                'season' => $season,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error general: ' . $e->getMessage(),
                'total' => 0,
                'saved' => 0,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Obtiene los partidos del día actual
     * 
     * @return array Datos formateados de los partidos del día
     */
    public function getTodayMatches()
    {
        try {
            $today = Carbon::today()->format('Y-m-d');
            Log::info('Obteniendo partidos del día', ['date' => $today]);

            // Usar el endpoint de matches con el filtro por fecha
            $response = $this->makeRequest('matches', [
                'date' => $today
            ]);
            
            if (!$response || !isset($response['matches'])) {
                return [
                    'success' => false,
                    'message' => 'No se pudieron obtener los partidos del día',
                    'data' => []
                ];
            }
            
            // Lista de IDs de ligas importantes para filtrar
            $importantLeagueIds = [
                2000, // Champions League
                2001, // Europa League
                2002, // Champions League (Liga de Campeones)
                2003, // Liga Holandesa (Eredivisie)
                2014, // La Liga (España)
                2015, // Ligue 1 (Francia)
                2016, // Premier League (Inglaterra)
                2017, // Primeira Liga (Portugal)
                2019, // Serie A (Italia)
                2021, // Premier League (Inglaterra)
                2002, // Bundesliga (Alemania)
                2013, // Brasileirao (Brasil)
                2152, // Copa Libertadores
            ];
            
            // Diccionario de logos de competiciones (esta API no siempre incluye logos directamente)
            $competitionLogos = [
                2000 => 'https://crests.football-data.org/CL.png', // Champions League
                2001 => 'https://crests.football-data.org/EL.png', // Europa League
                2002 => 'https://crests.football-data.org/BL1.png', // Bundesliga
                2003 => 'https://crests.football-data.org/DED.png', // Eredivisie
                2014 => 'https://crests.football-data.org/PD.png', // La Liga
                2015 => 'https://crests.football-data.org/FL1.png', // Ligue 1
                2016 => 'https://crests.football-data.org/CL.png', // Championship
                2017 => 'https://crests.football-data.org/PPL.png', // Primeira Liga
                2019 => 'https://crests.football-data.org/SA.png', // Serie A
                2021 => 'https://crests.football-data.org/PL.png', // Premier League
                2013 => 'https://crests.football-data.org/BSA.png', // Brasileirao
                2152 => 'https://crests.football-data.org/CLI.png', // Copa Libertadores
            ];
            
            $matches = $response['matches'];
            $processedMatches = [];
            
            foreach ($matches as $match) {
                // Filtrar solo partidos de ligas importantes
                $competitionId = $match['competition']['id'] ?? 0;
                if (!in_array($competitionId, $importantLeagueIds)) {
                    continue;
                }
                
                $status = $match['status'] ?? 'SCHEDULED';
                $score = $match['score'] ?? [];
                
                // Obtener logo de competición del diccionario o usar valor predeterminado
                $competitionLogo = $competitionLogos[$competitionId] ?? null;
                
                $processedMatches[] = [
                    'id' => $match['id'],
                    'league' => [
                        'id' => $competitionId,
                        'name' => $match['competition']['name'] ?? 'Desconocida',
                        'country' => $match['area']['name'] ?? '',
                        'logo' => $competitionLogo
                    ],
                    'teams' => [
                        'home' => [
                            'id' => $match['homeTeam']['id'] ?? 0,
                            'name' => $match['homeTeam']['name'] ?? 'Equipo Local',
                            'logo' => $match['homeTeam']['crest'] ?? null
                        ],
                        'away' => [
                            'id' => $match['awayTeam']['id'] ?? 0,
                            'name' => $match['awayTeam']['name'] ?? 'Equipo Visitante',
                            'logo' => $match['awayTeam']['crest'] ?? null
                        ]
                    ],
                    'fixture' => [
                        'date' => $match['utcDate'] ?? $today,
                        'venue' => isset($match['venue']) ? $match['venue'] : null,
                        'status' => $this->mapMatchStatus($status),
                        'elapsed' => $this->calculateElapsedTime($match['utcDate'] ?? '', $status),
                    ],
                    'goals' => [
                        'home' => $score['fullTime']['home'] ?? 0,
                        'away' => $score['fullTime']['away'] ?? 0
                    ],
                    'score' => [
                        'halftime' => [
                            'home' => $score['halfTime']['home'] ?? 0,
                            'away' => $score['halfTime']['away'] ?? 0
                        ],
                        'fulltime' => [
                            'home' => $score['fullTime']['home'] ?? 0,
                            'away' => $score['fullTime']['away'] ?? 0
                        ]
                    ]
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Partidos obtenidos correctamente',
                'count' => count($processedMatches),
                'data' => $processedMatches
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener partidos del día', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al obtener partidos: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Mapea los estados de partido de football-data.org al formato requerido
     */
    private function mapMatchStatus($status)
    {
        switch ($status) {
            case 'SCHEDULED':
                return 'Not Started';
            case 'LIVE':
            case 'IN_PLAY':
                return 'In Progress';
            case 'PAUSED':
                return 'Half Time';
            case 'FINISHED':
                return 'Match Finished';
            case 'SUSPENDED':
                return 'Suspended';
            case 'POSTPONED':
                return 'Postponed';
            case 'CANCELLED':
                return 'Cancelled';
            default:
                return $status;
        }
    }
    
    /**
     * Calcula el tiempo transcurrido en minutos para partidos en curso
     */
    private function calculateElapsedTime($matchDate, $status)
    {
        if ($status !== 'IN_PLAY' && $status !== 'LIVE') {
            return null;
        }
        
        try {
            $matchTime = Carbon::parse($matchDate);
            $now = Carbon::now();
            $diffInMinutes = $matchTime->diffInMinutes($now);
            
            // Limitar a 90 minutos + tiempo adicional razonable
            return min($diffInMinutes, 100);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtiene los equipos de una competición
     * 
     * @param string $competitionCode Código de competición (ej. CL, PL, etc)
     * @return array|null
     */
    public function getTeams($competitionCode)
    {
        return $this->makeRequest("competitions/{$competitionCode}/teams");
    }

    /**
     * Obtiene estadísticas detalladas de un equipo
     * 
     * @param int $teamId ID del equipo
     * @return array|null
     */
    public function getTeamStats($teamId)
    {
        Log::info('Obteniendo estadísticas detalladas para equipo', ['team_id' => $teamId]);
        
        // 1. Obtener información básica del equipo
        $team = $this->makeRequest("teams/{$teamId}");
        if (!$team) {
            Log::error('No se pudo obtener información del equipo', ['team_id' => $teamId]);
            return null;
        }
        
        // 2. Obtener la temporada actual para usar en las peticiones
        $currentYear = date('Y');
        $season = ($currentYear > 6) ? $currentYear : $currentYear - 1;
        
        // 3. Obtener estadísticas de la temporada actual
        $teamSeasonStats = null;
        
        // Intentar obtener la liga principal del equipo
        $leagueId = null;
        if (isset($team['runningCompetitions']) && count($team['runningCompetitions']) > 0) {
            // Tomar la primera competición como liga principal
            $leagueId = $team['runningCompetitions'][0]['id'];
            
            // Intentar obtener estadísticas específicas de la temporada
            $seasonStats = $this->makeRequest("teams/{$teamId}/matches", [
                'season' => $season,
                'competitions' => $leagueId
            ]);
            
            if ($seasonStats && isset($seasonStats['matches'])) {
                $teamSeasonStats = $this->processMatchesData($seasonStats['matches'], $teamId);
            }
        }
        
        // 4. Obtener más partidos para análisis histórico (los últimos 20)
        $matches = $this->makeRequest("teams/{$teamId}/matches", [
            'limit' => 20,
            'status' => 'FINISHED'
        ]);
        
        $matchesHistory = [];
        $historicalPerformance = null;
        
        if ($matches && isset($matches['matches'])) {
            $matchesHistory = $matches['matches'];
            $historicalPerformance = $this->processMatchesData($matchesHistory, $teamId);
        }
        
        // 5. Obtener la plantilla actual del equipo
        $squad = null;
        if (isset($team['squad'])) {
            $squad = $team['squad'];
        }
        
        // 6. Obtener partidos próximos
        $upcomingMatches = $this->makeRequest("teams/{$teamId}/matches", [
            'status' => 'SCHEDULED',
            'limit' => 5
        ]);
        
        // Combinar toda la información en un objeto completo de estadísticas
        $statsObject = [
            // Información básica
            'team' => [
                'id' => $team['id'],
                'name' => $team['name'],
                'shortName' => $team['shortName'] ?? null,
                'tla' => $team['tla'] ?? null,
                'crest' => $team['crest'] ?? null,
                'address' => $team['address'] ?? null,
                'website' => $team['website'] ?? null,
                'founded' => $team['founded'] ?? null,
                'clubColors' => $team['clubColors'] ?? null,
                'venue' => $team['venue'] ?? null,
                'coach' => $team['coach'] ?? null,
            ],
            
            // Competiciones actuales
            'competitions' => $team['runningCompetitions'] ?? [],
            
            // Estadísticas de la temporada actual
            'currentSeason' => [
                'year' => $season,
                'leagueId' => $leagueId,
                'stats' => $teamSeasonStats,
            ],
            
            // Historial de partidos y rendimiento
            'history' => [
                'performance' => $historicalPerformance,
                'matches' => $matchesHistory,
            ],
            
            // Plantilla
            'squad' => $squad,
            
            // Próximos partidos
            'upcomingMatches' => $upcomingMatches['matches'] ?? []
        ];
        
        Log::info('Estadísticas de equipo obtenidas correctamente', [
            'team_id' => $teamId,
            'stats_categories' => array_keys($statsObject),
            'matches_count' => count($matchesHistory),
            'squad_size' => $squad ? count($squad) : 0
        ]);
        
        return $statsObject;
    }
    
    /**
     * Procesa datos de partidos para extraer estadísticas de rendimiento
     * 
     * @param array $matches Lista de partidos
     * @param int $teamId ID del equipo para el que se procesan las estadísticas
     * @return array Estadísticas procesadas
     */
    private function processMatchesData(array $matches, int $teamId)
    {
        // Contadores generales
        $played = 0;
        $won = 0;
        $draw = 0;
        $lost = 0;
        $goalsFor = 0;
        $goalsAgainst = 0;
        
        // Contadores por local/visitante
        $playedHome = 0;
        $wonHome = 0;
        $drawHome = 0;
        $lostHome = 0;
        $goalsForHome = 0;
        $goalsAgainstHome = 0;
        
        $playedAway = 0;
        $wonAway = 0;
        $drawAway = 0;
        $lostAway = 0;
        $goalsForAway = 0;
        $goalsAgainstAway = 0;
        
        // Rachas y récords
        $currentStreak = 0;  // Positivo para victorias, negativo para derrotas, 0 para empates
        $longestWinStreak = 0;
        $longestLoseStreak = 0;
        $biggestWin = ['score' => 0, 'match' => null];
        $biggestDefeat = ['score' => 0, 'match' => null];
        
        // Análisis por temporada y mes
        $seasonPerformance = [];
        $monthPerformance = [];
        
        // Forma reciente (últimos 5 partidos)
        $form = [];
        
        // Procesar cada partido
        foreach ($matches as $match) {
            if ($match['status'] !== 'FINISHED') {
                continue; // Solo procesar partidos terminados
            }
            
            $played++;
            
            // Determinar si el equipo es local o visitante
            $isHome = $match['homeTeam']['id'] == $teamId;
            $localGoals = $match['score']['fullTime']['home'] ?? 0;
            $awayGoals = $match['score']['fullTime']['away'] ?? 0;
            
            // Calcular goles a favor y en contra
            $teamGoals = $isHome ? $localGoals : $awayGoals;
            $opponentGoals = $isHome ? $awayGoals : $localGoals;
            
            $goalsFor += $teamGoals;
            $goalsAgainst += $opponentGoals;
            
            // Determinar resultado (victoria, empate, derrota)
            $result = '';
            if ($localGoals > $awayGoals) {
                $result = $isHome ? 'W' : 'L';
            } elseif ($localGoals < $awayGoals) {
                $result = $isHome ? 'L' : 'W';
            } else {
                $result = 'D';
            }
            
            // Actualizar contadores de victorias, empates y derrotas
            if ($result === 'W') {
                $won++;
                if ($isHome) $wonHome++;
                else $wonAway++;
                
                // Racha
                $currentStreak = ($currentStreak > 0) ? $currentStreak + 1 : 1;
                $longestWinStreak = max($longestWinStreak, $currentStreak);
                
                // Verificar si es la mayor victoria
                $scoreDiff = $teamGoals - $opponentGoals;
                if ($scoreDiff > $biggestWin['score']) {
                    $biggestWin['score'] = $scoreDiff;
                    $biggestWin['match'] = $match;
                }
            } elseif ($result === 'L') {
                $lost++;
                if ($isHome) $lostHome++;
                else $lostAway++;
                
                // Racha
                $currentStreak = ($currentStreak < 0) ? $currentStreak - 1 : -1;
                $longestLoseStreak = min($longestLoseStreak, $currentStreak);
                
                // Verificar si es la mayor derrota
                $scoreDiff = $opponentGoals - $teamGoals;
                if ($scoreDiff > $biggestDefeat['score']) {
                    $biggestDefeat['score'] = $scoreDiff;
                    $biggestDefeat['match'] = $match;
                }
            } else { // Empate
                $draw++;
                if ($isHome) $drawHome++;
                else $drawAway++;
                $currentStreak = 0;
            }
            
            // Actualizar contadores de local/visitante
            if ($isHome) {
                $playedHome++;
                $goalsForHome += $teamGoals;
                $goalsAgainstHome += $opponentGoals;
            } else {
                $playedAway++;
                $goalsForAway += $teamGoals;
                $goalsAgainstAway += $opponentGoals;
            }
            
            // Añadir al registro de forma (últimos 5 partidos, más recientes primero)
            if (count($form) < 5) {
                array_unshift($form, $result);
            }
            
            // Análisis por temporada
            $matchSeason = isset($match['season']['startDate']) 
                ? date('Y', strtotime($match['season']['startDate'])) 
                : substr($match['utcDate'], 0, 4);
                
            if (!isset($seasonPerformance[$matchSeason])) {
                $seasonPerformance[$matchSeason] = [
                    'played' => 0, 'won' => 0, 'draw' => 0, 'lost' => 0,
                    'goalsFor' => 0, 'goalsAgainst' => 0
                ];
            }
            $seasonPerformance[$matchSeason]['played']++;
            $seasonPerformance[$matchSeason][$result === 'W' ? 'won' : ($result === 'D' ? 'draw' : 'lost')]++;
            $seasonPerformance[$matchSeason]['goalsFor'] += $teamGoals;
            $seasonPerformance[$matchSeason]['goalsAgainst'] += $opponentGoals;
            
            // Análisis por mes
            $matchMonth = date('Y-m', strtotime($match['utcDate']));
            if (!isset($monthPerformance[$matchMonth])) {
                $monthPerformance[$matchMonth] = [
                    'played' => 0, 'won' => 0, 'draw' => 0, 'lost' => 0,
                    'goalsFor' => 0, 'goalsAgainst' => 0
                ];
            }
            $monthPerformance[$matchMonth]['played']++;
            $monthPerformance[$matchMonth][$result === 'W' ? 'won' : ($result === 'D' ? 'draw' : 'lost')]++;
            $monthPerformance[$matchMonth]['goalsFor'] += $teamGoals;
            $monthPerformance[$matchMonth]['goalsAgainst'] += $opponentGoals;
        }
        
        // Calcular porcentajes y promedios
        $winPercentage = $played > 0 ? round(($won / $played) * 100, 2) : 0;
        $drawPercentage = $played > 0 ? round(($draw / $played) * 100, 2) : 0;
        $lossPercentage = $played > 0 ? round(($lost / $played) * 100, 2) : 0;
        
        $goalsForAvg = $played > 0 ? round($goalsFor / $played, 2) : 0;
        $goalsAgainstAvg = $played > 0 ? round($goalsAgainst / $played, 2) : 0;
        
        // Porterías a cero
        $cleanSheets = 0;
        $failedToScore = 0;
        
        foreach ($matches as $match) {
            if ($match['status'] !== 'FINISHED') continue;
            
            $isHome = $match['homeTeam']['id'] == $teamId;
            $opponentGoals = $isHome 
                ? ($match['score']['fullTime']['away'] ?? 0) 
                : ($match['score']['fullTime']['home'] ?? 0);
            
            $teamGoals = $isHome 
                ? ($match['score']['fullTime']['home'] ?? 0) 
                : ($match['score']['fullTime']['away'] ?? 0);
                
            if ($opponentGoals === 0) {
                $cleanSheets++;
            }
            
            if ($teamGoals === 0) {
                $failedToScore++;
            }
        }
        
        // Formatear la forma como una cadena (WDLWW)
        $formString = implode('', $form);
        
        // Crear objeto completo de estadísticas
        return [
            'summary' => [
                'played' => $played,
                'won' => $won,
                'draw' => $draw,
                'lost' => $lost,
                'goalsFor' => $goalsFor,
                'goalsAgainst' => $goalsAgainst,
                'goalDifference' => $goalsFor - $goalsAgainst,
                'points' => ($won * 3) + $draw,
                'winPercentage' => $winPercentage,
                'drawPercentage' => $drawPercentage,
                'lossPercentage' => $lossPercentage,
                'cleanSheets' => $cleanSheets,
                'cleanSheetPercentage' => $played > 0 ? round(($cleanSheets / $played) * 100, 2) : 0,
                'failedToScore' => $failedToScore,
                'failedToScorePercentage' => $played > 0 ? round(($failedToScore / $played) * 100, 2) : 0,
            ],
            'averages' => [
                'goalsForPerGame' => $goalsForAvg,
                'goalsAgainstPerGame' => $goalsAgainstAvg,
                'pointsPerGame' => $played > 0 ? round((($won * 3) + $draw) / $played, 2) : 0,
                'goalsForPerGameHome' => $playedHome > 0 ? round($goalsForHome / $playedHome, 2) : 0,
                'goalsAgainstPerGameHome' => $playedHome > 0 ? round($goalsAgainstHome / $playedHome, 2) : 0,
                'goalsForPerGameAway' => $playedAway > 0 ? round($goalsForAway / $playedAway, 2) : 0,
                'goalsAgainstPerGameAway' => $playedAway > 0 ? round($goalsAgainstAway / $playedAway, 2) : 0,
            ],
            'home' => [
                'played' => $playedHome,
                'won' => $wonHome,
                'draw' => $drawHome,
                'lost' => $lostHome,
                'goalsFor' => $goalsForHome,
                'goalsAgainst' => $goalsAgainstHome,
                'winPercentage' => $playedHome > 0 ? round(($wonHome / $playedHome) * 100, 2) : 0,
            ],
            'away' => [
                'played' => $playedAway,
                'won' => $wonAway,
                'draw' => $drawAway,
                'lost' => $lostAway,
                'goalsFor' => $goalsForAway,
                'goalsAgainst' => $goalsAgainstAway,
                'winPercentage' => $playedAway > 0 ? round(($wonAway / $playedAway) * 100, 2) : 0,
            ],
            'streaks' => [
                'current' => $currentStreak,
                'longestWin' => $longestWinStreak,
                'longestLose' => abs($longestLoseStreak),
            ],
            'records' => [
                'biggestWin' => $biggestWin,
                'biggestDefeat' => $biggestDefeat,
            ],
            'form' => [
                'string' => $formString,
                'last5' => $form,
            ],
            'by_season' => $seasonPerformance,
            'by_month' => $monthPerformance,
        ];
    }

    /**
     * Obtiene información sobre un partido específico
     * 
     * @param int $matchId ID del partido
     * @return array|null
     */
    public function getMatch($matchId)
    {
        return $this->makeRequest("matches/{$matchId}");
    }

    /**
     * Obtiene estadísticas de ambos equipos para un enfrentamiento
     * 
     * @param int $homeTeamId ID del equipo local
     * @param int $awayTeamId ID del equipo visitante
     * @return array
     */
    public function getMatchupStats($homeTeamId, $awayTeamId)
    {
        $homeTeam = $this->getTeamStats($homeTeamId);
        $awayTeam = $this->getTeamStats($awayTeamId);

        // Buscar enfrentamientos directos (head to head)
        $h2h = [];
        if ($homeTeam && $awayTeam) {
            // En football-data.org no hay endpoint directo para H2H
            // Podríamos intentar encontrar cruces en el historial de partidos
            if (isset($homeTeam['matches']) && isset($homeTeam['matches']['matches'])) {
                foreach ($homeTeam['matches']['matches'] as $match) {
                    if ($match['homeTeam']['id'] == $awayTeamId || $match['awayTeam']['id'] == $awayTeamId) {
                        $h2h[] = $match;
                    }
                }
            }
        }

        return [
            'homeTeam' => $homeTeam ? $homeTeam['team'] : null,
            'awayTeam' => $awayTeam ? $awayTeam['team'] : null,
            'homeTeamMatches' => $homeTeam ? ($homeTeam['matches']['matches'] ?? []) : [],
            'awayTeamMatches' => $awayTeam ? ($awayTeam['matches']['matches'] ?? []) : [],
            'headToHead' => $h2h
        ];
    }

    /**
     * Obtiene información detallada de un equipo específico
     * 
     * @param int $teamId ID del equipo
     * @return array|null Datos detallados del equipo o null si hay error
     */
    public function getTeamDetails($teamId)
    {
        try {
            Log::info('Obteniendo detalles del equipo', ['team_id' => $teamId]);
            
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiKey,
            ])->get("{$this->baseUrl}/teams/{$teamId}");
            
            if ($response->successful()) {
                $data = $response->json();
                Log::info('Datos del equipo obtenidos correctamente', ['team_id' => $teamId]);
                return $data;
            } else {
                Log::error('Error al obtener datos del equipo', [
                    'team_id' => $teamId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Excepción al obtener datos del equipo', [
                'team_id' => $teamId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Obtiene los partidos de una competición específica.
     *
     * @param string $competitionApiId El ID de la competición en la API.
     * @param string|null $season El año de la temporada (ej: "2023").
     * @param string|null $dateFrom Fecha desde (ej: "2023-08-01").
     * @param string|null $dateTo Fecha hasta (ej: "2024-05-30").
     * @return array|null
     */
    public function getMatchesByCompetition(string $competitionApiId, ?string $season = null, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $endpoint = "competitions/{$competitionApiId}/matches";
        $params = [];

        if ($season) {
            $params['season'] = $season;
        }
        if ($dateFrom) {
            $params['dateFrom'] = $dateFrom;
        }
        if ($dateTo) {
            $params['dateTo'] = $dateTo;
        }

        Log::info('Obteniendo partidos por competición', [
            'competition_api_id' => $competitionApiId,
            'params' => $params
        ]);

        return $this->makeRequest($endpoint, $params);
    }

    /**
     * Obtiene los partidos recientes de un equipo
     * 
     * @param int $teamId ID del equipo
     * @param int $limit Límite de partidos a obtener (default 5)
     * @return array|null
     */
    public function getTeamRecentMatches($teamId, $limit = 10)
    {
        Log::info('Obteniendo partidos recientes del equipo', ['team_id' => $teamId, 'limit' => $limit]);
        
        try {
            $url = "https://v3.football.api-sports.io/fixtures";
            $params = [
                'team' => $teamId,
                'last' => $limit,
                'status' => 'FT'  // Solo partidos terminados
            ];
            
            $response = Http::withHeaders([
                'x-rapidapi-host' => 'v3.football.api-sports.io',
                'x-rapidapi-key' => config('services.football_api.key')
            ])->get($url, $params);
            
            if ($response->successful()) {
                Log::info('Partidos recientes obtenidos correctamente', [
                    'team_id' => $teamId,
                    'count' => $response->json()['results'] ?? 0
                ]);
                return $response->json();
            }
            
            Log::error('Error al obtener partidos recientes', [
                'team_id' => $teamId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Excepción al obtener partidos recientes', [
                'team_id' => $teamId,
                'message' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    /**
     * Obtiene los enfrentamientos directos entre dos equipos
     * 
     * @param int $team1Id ID del primer equipo
     * @param int $team2Id ID del segundo equipo
     * @param int $limit Límite de partidos a obtener (default 10)
     * @return array|null
     */
    public function getHeadToHead($team1Id, $team2Id, $limit = 10)
    {
        Log::info('Obteniendo enfrentamientos directos', [
            'team1_id' => $team1Id,
            'team2_id' => $team2Id,
            'limit' => $limit
        ]);
        
        try {
            $url = "https://v3.football.api-sports.io/fixtures/headtohead";
            $params = [
                'h2h' => "{$team1Id}-{$team2Id}",
                'last' => $limit
            ];
            
            $response = Http::withHeaders([
                'x-rapidapi-host' => 'v3.football.api-sports.io',
                'x-rapidapi-key' => config('services.football_api.key')
            ])->get($url, $params);
            
            if ($response->successful()) {
                Log::info('Enfrentamientos directos obtenidos correctamente', [
                    'team1_id' => $team1Id,
                    'team2_id' => $team2Id,
                    'count' => $response->json()['results'] ?? 0
                ]);
                return $response->json();
            }
            
            Log::error('Error al obtener enfrentamientos directos', [
                'team1_id' => $team1Id,
                'team2_id' => $team2Id,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Excepción al obtener enfrentamientos directos', [
                'team1_id' => $team1Id,
                'team2_id' => $team2Id,
                'message' => $e->getMessage()
            ]);
            
            return null;
        }
    }
} 