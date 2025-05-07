<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Team;
use App\Models\TeamStats;
use App\Models\FootballMatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GeminiController extends Controller
{
    public function testGemini()
    {
        $apiKey = env('GEMINI_API_KEY');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";

        $response = Http::post($url, [
            "contents" => [
                [
                    "parts" => [
                        ["text" => "Explícame cómo funciona la inteligencia artificial"]
                    ]
                ]
            ]
        ]);

        return $response->json();
    }

    public function analizarPartido(Request $request)
    {
        try {
            // Si recibimos datos del partido directamente, analizamos con nuestra lógica
            if ($request->has('match_data')) {
                $matchData = $request->input('match_data');
                
                Log::info('Iniciando análisis de partido con datos recibidos', [
                    'has_home_team' => isset($matchData['homeTeam']) ? 'yes' : 'no',
                    'has_away_team' => isset($matchData['awayTeam']) ? 'yes' : 'no',
                ]);
                
                // Verificar que tengamos los datos mínimos necesarios
                if (!isset($matchData['homeTeam']) || !isset($matchData['awayTeam'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Datos insuficientes para realizar el análisis, faltan equipos',
                    ]);
                }
                
                // Verificar que tengamos información del equipo
                if (!isset($matchData['homeTeam']['team']) || !isset($matchData['awayTeam']['team'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Estructura de datos incompleta para análisis',
                    ]);
                }
                
                // Extraer información básica
                $homeTeamId = $matchData['homeTeam']['team']['id'] ?? 0;
                $awayTeamId = $matchData['awayTeam']['team']['id'] ?? 0;
                $homeTeamName = $matchData['homeTeam']['team']['name'] ?? null;
                $awayTeamName = $matchData['awayTeam']['team']['name'] ?? null;
                $matchDate = $matchData['match']['date'] ?? Carbon::now()->toDateTimeString();
                $venue = $matchData['match']['venue'] ?? 'Desconocido';
                
                Log::debug('IDs de equipos recibidos', [
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'home_team_name' => $homeTeamName,
                    'away_team_name' => $awayTeamName
                ]);
                
                if (empty($homeTeamName) || empty($awayTeamName)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudieron obtener los nombres de los equipos',
                    ]);
                }
                
                // Buscar datos adicionales desde la base de datos
                $statsData = $this->obtenerDatosEstadisticos($homeTeamId, $awayTeamId, $homeTeamName, $awayTeamName);
                
                // Generar análisis con IA
                $analysisText = $this->generarAnalisisIA($homeTeamName, $awayTeamName, $matchDate, $venue, $statsData);
                
                return response()->json([
                    'success' => true,
                    'analysis' => $analysisText
                ]);
            }
            
            // Formato básico (compatibilidad)
            $local = $request->input('local');
            $visitante = $request->input('visitante');
            $fecha = $request->input('fecha');
            
            Log::debug('Análisis por formato básico', [
                'local' => $local,
                'visitante' => $visitante
            ]);
            
            if (!$local || !$visitante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos insuficientes para realizar el análisis'
                ]);
            }

            // Buscar equipos por nombre con coincidencia parcial
            // Caso especial: Paris Saint Germain puede estar como "PSG" o "Paris"
            $homeTeamQuery = DB::table('teams');
            
            if (strpos(strtolower($local), 'paris') !== false || 
                strpos(strtolower($local), 'psg') !== false ||
                strpos(strtolower($local), 'saint germain') !== false) {
                $homeTeamQuery->where(function($query) {
                    $query->where('name', 'like', '%Paris%')
                          ->orWhere('name', 'like', '%PSG%')
                          ->orWhere('name', 'like', '%Saint Germain%');
                });
            } else {
                // Para los demás equipos, búsqueda normal
                $homeTeamQuery->where('name', 'like', '%' . $local . '%');
            }
            
            $homeTeam = $homeTeamQuery->first();
            
            // Igual para Arsenal
            $awayTeamQuery = DB::table('teams');
            
            if (strpos(strtolower($visitante), 'arsenal') !== false) {
                $awayTeamQuery->where('name', 'like', '%Arsenal%');
            } else {
                $awayTeamQuery->where('name', 'like', '%' . $visitante . '%');
            }
            
            $awayTeam = $awayTeamQuery->first();
            
            Log::debug('Equipos encontrados por nombre', [
                'home_team' => $homeTeam ? 'found' : 'not found',
                'home_team_id' => $homeTeam->id ?? 'N/A',
                'away_team' => $awayTeam ? 'found' : 'not found',
                'away_team_id' => $awayTeam->id ?? 'N/A'
            ]);
            
            // Si no encontramos los equipos, probamos con IDs directos para Paris y Arsenal
            if (!$homeTeam && (strpos(strtolower($local), 'paris') !== false || 
                strpos(strtolower($local), 'psg') !== false)) {
                Log::debug('Usando ID directo para PSG (21)');
                $homeTeamId = 21; // ID directo del PSG
                $homeTeamName = 'Paris Saint Germain';
            } else if ($homeTeam) {
                $homeTeamId = $homeTeam->id;
                $homeTeamName = $homeTeam->name;
            } else {
                $homeTeamId = 0;
                $homeTeamName = $local;
            }
            
            if (!$awayTeam && strpos(strtolower($visitante), 'arsenal') !== false) {
                Log::debug('Usando ID directo para Arsenal (5)');
                $awayTeamId = 5; // ID directo de Arsenal
                $awayTeamName = 'Arsenal FC';
            } else if ($awayTeam) {
                $awayTeamId = $awayTeam->id;
                $awayTeamName = $awayTeam->name;
            } else {
                $awayTeamId = 0;
                $awayTeamName = $visitante;
            }
            
            // Obtener estadísticas si tenemos los equipos
            $statsData = $this->obtenerDatosEstadisticos(
                $homeTeamId, 
                $awayTeamId, 
                $homeTeamName, 
                $awayTeamName
            );
            
            // Generar análisis con IA
            $analysisText = $this->generarAnalisisIA($homeTeamName, $awayTeamName, $fecha, 'Desconocido', $statsData);
            
            return response()->json([
                'success' => true,
                'analysis' => $analysisText
            ]);
        } catch (\Exception $e) {
            Log::error('Error en analizarPartido', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el análisis: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtener datos estadísticos de dos equipos desde la base de datos
     */
    private function obtenerDatosEstadisticos($homeTeamId, $awayTeamId, $homeTeamName, $awayTeamName)
    {
        Log::debug('Obteniendo datos estadísticos', [
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'home_team_name' => $homeTeamName,
            'away_team_name' => $awayTeamName
        ]);
        
        $result = [
            'home' => [
                'name' => $homeTeamName,
                'stats' => null,
                'recent_matches' => []
            ],
            'away' => [
                'name' => $awayTeamName,
                'stats' => null,
                'recent_matches' => []
            ],
            'head_to_head' => []
        ];
        
        // Obtener estadísticas de los equipos usando consultas directas
        if ($homeTeamId > 0) {
            // Buscar estadísticas directamente en la tabla team_stats
            $homeStats = DB::table('team_stats')->where('team_id', $homeTeamId)->first();
            
            Log::debug('Estadísticas del equipo local', [
                'team_id' => $homeTeamId, 
                'found' => $homeStats ? 'yes' : 'no'
            ]);
            
            if ($homeStats && isset($homeStats->stats_json)) {
                // Decodificar JSON para obtener las estadísticas
                $statsJson = is_string($homeStats->stats_json) ? 
                    json_decode($homeStats->stats_json, true) : $homeStats->stats_json;
                    
                $result['home']['stats'] = $statsJson;
                
                Log::debug('Estadísticas decodificadas del equipo local', [
                    'keys' => $statsJson ? array_keys($statsJson) : 'none'
                ]);
            } else {
                // Si no hay estadísticas en la BD, intentar con la API de football-data.org
                try {
                    $footballDataService = app(\App\Services\FootballDataService::class);
                    $teamStats = $footballDataService->getTeamStats($homeTeamId);
                    
                    if ($teamStats) {
                        $result['home']['stats'] = [
                            'team' => $teamStats['team'],
                            'fixtures' => [
                                'played' => [
                                    'total' => $teamStats['currentSeason']['stats']['summary']['played'] ?? 0,
                                    'home' => $teamStats['currentSeason']['stats']['home']['played'] ?? 0,
                                    'away' => $teamStats['currentSeason']['stats']['away']['played'] ?? 0
                                ],
                                'wins' => [
                                    'total' => $teamStats['currentSeason']['stats']['summary']['won'] ?? 0,
                                    'home' => $teamStats['currentSeason']['stats']['home']['won'] ?? 0,
                                    'away' => $teamStats['currentSeason']['stats']['away']['won'] ?? 0
                                ],
                                'draws' => [
                                    'total' => $teamStats['currentSeason']['stats']['summary']['draw'] ?? 0,
                                    'home' => $teamStats['currentSeason']['stats']['home']['draw'] ?? 0,
                                    'away' => $teamStats['currentSeason']['stats']['away']['draw'] ?? 0
                                ],
                                'loses' => [
                                    'total' => $teamStats['currentSeason']['stats']['summary']['lost'] ?? 0,
                                    'home' => $teamStats['currentSeason']['stats']['home']['lost'] ?? 0,
                                    'away' => $teamStats['currentSeason']['stats']['away']['lost'] ?? 0
                                ]
                            ],
                            'goals' => [
                                'for' => [
                                    'total' => [
                                        'total' => $teamStats['currentSeason']['stats']['summary']['goalsFor'] ?? 0,
                                        'home' => $teamStats['currentSeason']['stats']['home']['goalsFor'] ?? 0,
                                        'away' => $teamStats['currentSeason']['stats']['away']['goalsFor'] ?? 0
                                    ]
                                ],
                                'against' => [
                                    'total' => [
                                        'total' => $teamStats['currentSeason']['stats']['summary']['goalsAgainst'] ?? 0,
                                        'home' => $teamStats['currentSeason']['stats']['home']['goalsAgainst'] ?? 0,
                                        'away' => $teamStats['currentSeason']['stats']['away']['goalsAgainst'] ?? 0
                                    ]
                                ]
                            ],
                            'clean_sheet' => [
                                'total' => $teamStats['currentSeason']['stats']['summary']['cleanSheets'] ?? 0
                            ],
                            'failed_to_score' => [
                                'total' => $teamStats['currentSeason']['stats']['summary']['failedToScore'] ?? 0
                            ]
                        ];
                        
                        // Obtener partidos recientes de la API
                        if (!empty($teamStats['history']['matches'])) {
                            $recentMatches = array_slice($teamStats['history']['matches'], 0, 5);
                            foreach ($recentMatches as $match) {
                                $isHome = $match['homeTeam']['id'] == $homeTeamId;
                                $result['home']['recent_matches'][] = [
                                    'opponent' => $isHome ? $match['awayTeam']['name'] : $match['homeTeam']['name'],
                                    'goals_for' => $isHome ? $match['score']['fullTime']['home'] : $match['score']['fullTime']['away'],
                                    'goals_against' => $isHome ? $match['score']['fullTime']['away'] : $match['score']['fullTime']['home'],
                                    'match_date' => substr($match['utcDate'], 0, 10),
                                    'is_home' => $isHome,
                                    'result' => $this->determinarResultado(
                                        $match['score']['fullTime']['home'],
                                        $match['score']['fullTime']['away'],
                                        $isHome
                                    )
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error obteniendo estadísticas desde API', [
                        'team_id' => $homeTeamId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Obtener últimos 5 partidos del equipo local si aún no se llenaron por la API
            if (empty($result['home']['recent_matches'])) {
                $homeMatches = FootballMatch::where(function($query) use ($homeTeamId) {
                    $query->where('home_team_id', $homeTeamId)
                          ->orWhere('away_team_id', $homeTeamId);
                })
                ->orderBy('match_date', 'desc')
                ->take(5)
                ->get();
                
                foreach ($homeMatches as $match) {
                    $isHome = $match->home_team_id == $homeTeamId;
                    $result['home']['recent_matches'][] = [
                        'opponent' => $isHome ? $match->awayTeam->name : $match->homeTeam->name,
                        'goals_for' => $isHome ? $match->home_goals : $match->away_goals,
                        'goals_against' => $isHome ? $match->away_goals : $match->home_goals,
                        'match_date' => $match->match_date->format('Y-m-d'),
                        'is_home' => $isHome,
                        'result' => $this->getMatchResult($match, $isHome)
                    ];
                }
            }
        }
        
        if ($awayTeamId > 0) {
            // Buscar estadísticas directamente en la tabla team_stats
            $awayStats = DB::table('team_stats')->where('team_id', $awayTeamId)->first();
            
            Log::debug('Estadísticas del equipo visitante', [
                'team_id' => $awayTeamId, 
                'found' => $awayStats ? 'yes' : 'no'
            ]);
            
            if ($awayStats && isset($awayStats->stats_json)) {
                // Decodificar JSON para obtener las estadísticas
                $statsJson = is_string($awayStats->stats_json) ? 
                    json_decode($awayStats->stats_json, true) : $awayStats->stats_json;
                    
                $result['away']['stats'] = $statsJson;
                
                Log::debug('Estadísticas decodificadas del equipo visitante', [
                    'keys' => $statsJson ? array_keys($statsJson) : 'none'
                ]);
            } else {
                // Si no hay estadísticas en la BD, intentar con la API de football-data.org
                try {
                    $footballDataService = app(\App\Services\FootballDataService::class);
                    $teamStats = $footballDataService->getTeamStats($awayTeamId);
                    
                    if ($teamStats) {
                        $result['away']['stats'] = [
                            'team' => $teamStats['team'],
                            'fixtures' => [
                                'played' => [
                                    'total' => $teamStats['currentSeason']['stats']['summary']['played'] ?? 0,
                                    'home' => $teamStats['currentSeason']['stats']['home']['played'] ?? 0,
                                    'away' => $teamStats['currentSeason']['stats']['away']['played'] ?? 0
                                ],
                                'wins' => [
                                    'total' => $teamStats['currentSeason']['stats']['summary']['won'] ?? 0,
                                    'home' => $teamStats['currentSeason']['stats']['home']['won'] ?? 0,
                                    'away' => $teamStats['currentSeason']['stats']['away']['won'] ?? 0
                                ],
                                'draws' => [
                                    'total' => $teamStats['currentSeason']['stats']['summary']['draw'] ?? 0,
                                    'home' => $teamStats['currentSeason']['stats']['home']['draw'] ?? 0,
                                    'away' => $teamStats['currentSeason']['stats']['away']['draw'] ?? 0
                                ],
                                'loses' => [
                                    'total' => $teamStats['currentSeason']['stats']['summary']['lost'] ?? 0,
                                    'home' => $teamStats['currentSeason']['stats']['home']['lost'] ?? 0,
                                    'away' => $teamStats['currentSeason']['stats']['away']['lost'] ?? 0
                                ]
                            ],
                            'goals' => [
                                'for' => [
                                    'total' => [
                                        'total' => $teamStats['currentSeason']['stats']['summary']['goalsFor'] ?? 0,
                                        'home' => $teamStats['currentSeason']['stats']['home']['goalsFor'] ?? 0,
                                        'away' => $teamStats['currentSeason']['stats']['away']['goalsFor'] ?? 0
                                    ]
                                ],
                                'against' => [
                                    'total' => [
                                        'total' => $teamStats['currentSeason']['stats']['summary']['goalsAgainst'] ?? 0,
                                        'home' => $teamStats['currentSeason']['stats']['home']['goalsAgainst'] ?? 0,
                                        'away' => $teamStats['currentSeason']['stats']['away']['goalsAgainst'] ?? 0
                                    ]
                                ]
                            ],
                            'clean_sheet' => [
                                'total' => $teamStats['currentSeason']['stats']['summary']['cleanSheets'] ?? 0
                            ],
                            'failed_to_score' => [
                                'total' => $teamStats['currentSeason']['stats']['summary']['failedToScore'] ?? 0
                            ]
                        ];
                        
                        // Obtener partidos recientes de la API
                        if (!empty($teamStats['history']['matches'])) {
                            $recentMatches = array_slice($teamStats['history']['matches'], 0, 5);
                            foreach ($recentMatches as $match) {
                                $isHome = $match['homeTeam']['id'] == $awayTeamId;
                                $result['away']['recent_matches'][] = [
                                    'opponent' => $isHome ? $match['awayTeam']['name'] : $match['homeTeam']['name'],
                                    'goals_for' => $isHome ? $match['score']['fullTime']['home'] : $match['score']['fullTime']['away'],
                                    'goals_against' => $isHome ? $match['score']['fullTime']['away'] : $match['score']['fullTime']['home'],
                                    'match_date' => substr($match['utcDate'], 0, 10),
                                    'is_home' => $isHome,
                                    'result' => $this->determinarResultado(
                                        $match['score']['fullTime']['home'],
                                        $match['score']['fullTime']['away'],
                                        $isHome
                                    )
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error obteniendo estadísticas desde API', [
                        'team_id' => $awayTeamId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Obtener últimos 5 partidos del equipo visitante si aún no se llenaron por la API
            if (empty($result['away']['recent_matches'])) {
                $awayMatches = FootballMatch::where(function($query) use ($awayTeamId) {
                    $query->where('home_team_id', $awayTeamId)
                          ->orWhere('away_team_id', $awayTeamId);
                })
                ->orderBy('match_date', 'desc')
                ->take(5)
                ->get();
                
                foreach ($awayMatches as $match) {
                    $isHome = $match->home_team_id == $awayTeamId;
                    $result['away']['recent_matches'][] = [
                        'opponent' => $isHome ? $match->awayTeam->name : $match->homeTeam->name,
                        'goals_for' => $isHome ? $match->home_goals : $match->away_goals,
                        'goals_against' => $isHome ? $match->away_goals : $match->home_goals,
                        'match_date' => $match->match_date->format('Y-m-d'),
                        'is_home' => $isHome,
                        'result' => $this->getMatchResult($match, $isHome)
                    ];
                }
            }
        }
        
        // Obtener enfrentamientos directos
        if ($homeTeamId > 0 && $awayTeamId > 0) {
            // Primero buscar en la base de datos local
            $h2hMatches = FootballMatch::where(function($query) use ($homeTeamId, $awayTeamId) {
                $query->where(function($q) use ($homeTeamId, $awayTeamId) {
                    $q->where('home_team_id', $homeTeamId)
                      ->where('away_team_id', $awayTeamId);
                })->orWhere(function($q) use ($homeTeamId, $awayTeamId) {
                    $q->where('home_team_id', $awayTeamId)
                      ->where('away_team_id', $homeTeamId);
                });
            })
            ->orderBy('match_date', 'desc')
            ->take(5)
            ->get();
            
            Log::debug('Enfrentamientos directos en BD', [
                'count' => $h2hMatches->count()
            ]);
            
            // Si no hay suficientes enfrentamientos en la BD, intentar con la API
            if ($h2hMatches->count() < 3) {
                try {
                    $footballDataService = app(\App\Services\FootballDataService::class);
                    $matchupStats = $footballDataService->getMatchupStats($homeTeamId, $awayTeamId);
                    
                    if (!empty($matchupStats['headToHead'])) {
                        foreach ($matchupStats['headToHead'] as $match) {
                            $homeIsFirstTeam = $match['homeTeam']['id'] == $homeTeamId;
                            $result['head_to_head'][] = [
                                'date' => substr($match['utcDate'], 0, 10),
                                'home_team' => $homeIsFirstTeam ? $homeTeamName : $awayTeamName,
                                'away_team' => $homeIsFirstTeam ? $awayTeamName : $homeTeamName,
                                'home_goals' => $homeIsFirstTeam ? $match['score']['fullTime']['home'] : $match['score']['fullTime']['away'],
                                'away_goals' => $homeIsFirstTeam ? $match['score']['fullTime']['away'] : $match['score']['fullTime']['home'],
                                'winner' => $this->determinarGanador(
                                    $match['score']['fullTime']['home'], 
                                    $match['score']['fullTime']['away'], 
                                    $homeIsFirstTeam
                                )
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error obteniendo H2H desde API', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Si aún no tenemos H2H o si la API no devolvió nada, usar los de la BD
            if (empty($result['head_to_head'])) {
                foreach ($h2hMatches as $match) {
                    $homeIsFirstTeam = $match->home_team_id == $homeTeamId;
                    $result['head_to_head'][] = [
                        'date' => $match->match_date->format('Y-m-d'),
                        'home_team' => $homeIsFirstTeam ? $homeTeamName : $awayTeamName,
                        'away_team' => $homeIsFirstTeam ? $awayTeamName : $homeTeamName,
                        'home_goals' => $homeIsFirstTeam ? $match->home_goals : $match->away_goals,
                        'away_goals' => $homeIsFirstTeam ? $match->away_goals : $match->home_goals,
                        'winner' => $this->getWinner($match, $homeTeamId, $awayTeamId)
                    ];
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Determina el resultado de un partido de la API (victoria, derrota, empate)
     */
    private function determinarResultado($homeGoals, $awayGoals, $isHome)
    {
        if ($homeGoals === $awayGoals) {
            return 'empate';
        }
        
        if ($isHome) {
            return $homeGoals > $awayGoals ? 'victoria' : 'derrota';
        } else {
            return $awayGoals > $homeGoals ? 'victoria' : 'derrota';
        }
    }
    
    /**
     * Determina el ganador en un enfrentamiento directo de la API
     */
    private function determinarGanador($homeGoals, $awayGoals, $homeIsFirstTeam)
    {
        if ($homeGoals === $awayGoals) {
            return 'empate';
        }
        
        if ($homeGoals > $awayGoals) {
            return $homeIsFirstTeam ? 'local' : 'visitante';
        } else {
            return $homeIsFirstTeam ? 'visitante' : 'local';
        }
    }
    
    /**
     * Determinar el resultado de un partido (victoria, derrota, empate)
     */
    private function getMatchResult($match, $isHome)
    {
        if ($match->home_goals === $match->away_goals) {
            return 'empate';
        }
        
        if ($isHome) {
            return $match->home_goals > $match->away_goals ? 'victoria' : 'derrota';
        } else {
            return $match->away_goals > $match->home_goals ? 'victoria' : 'derrota';
        }
    }
    
    /**
     * Determinar el ganador de un enfrentamiento directo
     */
    private function getWinner($match, $homeTeamId, $awayTeamId)
    {
        if ($match->home_goals === $match->away_goals) {
            return 'empate';
        }
        
        if ($match->home_goals > $match->away_goals) {
            return $match->home_team_id == $homeTeamId ? 'local' : 'visitante';
        } else {
            return $match->away_team_id == $homeTeamId ? 'local' : 'visitante';
        }
    }
    
    /**
     * Generar análisis mediante Gemini API
     */
    private function generarAnalisisIA($homeTeam, $awayTeam, $matchDate, $venue, $statsData)
    {
        // Verificar si tenemos estadísticas
        $homeHasStats = !empty($statsData['home']['stats']);
        $awayHasStats = !empty($statsData['away']['stats']);
        $hasH2h = !empty($statsData['head_to_head']);
        
        Log::debug('Datos disponibles para análisis', [
            'home_has_stats' => $homeHasStats,
            'away_has_stats' => $awayHasStats,
            'has_h2h' => $hasH2h
        ]);
        
        // Formatear datos para el prompt
        $homeForm = $this->formatTeamForm($statsData['home']['recent_matches'] ?? []);
        $awayForm = $this->formatTeamForm($statsData['away']['recent_matches'] ?? []);
        $h2hData = $this->formatHeadToHead($statsData['head_to_head'] ?? []);
        
        // Estadísticas adicionales
        $homeStats = $this->formatTeamStats($statsData['home']['stats'] ?? null);
        $awayStats = $this->formatTeamStats($statsData['away']['stats'] ?? null);
        
        // Crear prompt mejorado
        $prompt = "Análisis de partido de fútbol: $homeTeam vs. $awayTeam\n\n" .
            "Fecha y hora: $matchDate\n" .
            "Estadio: $venue\n\n";
        
        // Añadir información sobre disponibilidad de datos
        if (!$homeHasStats || !$awayHasStats) {
            $prompt .= "NOTA: ⚠️ Hay datos estadísticos limitados disponibles para este análisis. " . 
                "La predicción se basará en los datos disponibles y conocimiento general sobre los equipos. " .
                "Los resultados deben considerarse con precaución.\n\n";
        }
            
        $prompt .= "DATOS ESTADÍSTICOS DEL EQUIPO LOCAL: $homeTeam\n" .
            "-------------------------------------------\n" .
            "Forma reciente: $homeForm\n" .
            "$homeStats\n\n" .
            
            "DATOS ESTADÍSTICOS DEL EQUIPO VISITANTE: $awayTeam\n" .
            "-------------------------------------------\n" .
            "Forma reciente: $awayForm\n" .
            "$awayStats\n\n" .
            
            "HISTORIAL DE ENFRENTAMIENTOS DIRECTOS:\n" .
            "-------------------------------------------\n" .
            "$h2hData\n\n" .
            
            "INSTRUCCIONES PARA EL ANÁLISIS:\n" .
            "-------------------------------------------\n" .
            "1. Realiza un análisis detallado del partido basado en los datos proporcionados.\n" .
            "2. Evalúa la forma actual de ambos equipos y sus probabilidades de victoria/empate.\n" .
            "3. Analiza las tendencias ofensivas y defensivas de ambos equipos.\n" .
            "4. Valora la importancia del factor cancha y los resultados previos entre ambos equipos.\n" .
            "5. Proporciona un PRONÓSTICO FINAL con la probabilidad estimada (%) para cada resultado posible.\n\n" .
            
            "RECOMENDACIONES DE APUESTAS:\n" .
            "-------------------------------------------\n" .
            "Proporciona TRES recomendaciones de apuestas distintas clasificadas por nivel de riesgo:\n\n" .
            
            "1. APUESTA DE BAJO RIESGO: La más segura, aunque con menor cuota/retorno.\n" .
            "   - Corresponde a un evento con alta probabilidad (>70%)\n" .
            "   - Ejemplos: Doble oportunidad (1X/X2), Menos/Más de X goles, Hándicap conservador\n" .
            "   - Ideal para apostantes conservadores o gestión segura del bankroll\n\n" .
            
            "2. APUESTA DE RIESGO MODERADO: Balance entre probabilidad y retorno.\n" .
            "   - Corresponde a un evento con probabilidad media (40-70%)\n" .
            "   - Ejemplos: 1X2, Ambos equipos marcan, Resultado exacto con margen\n" .
            "   - Adecuada para apostantes con cierta tolerancia al riesgo\n\n" .
            
            "3. APUESTA DE ALTO RIESGO: Mayor cuota potencial pero menor probabilidad.\n" .
            "   - Corresponde a un evento con baja probabilidad (<40%)\n" .
            "   - Ejemplos: Resultado exacto, Goleador, Hándicap agresivo\n" .
            "   - Para apostantes con alta tolerancia al riesgo y bankroll diversificado\n\n" .
            
            "Para cada recomendación, incluye:\n" .
            "- Tipo de apuesta (1X2, más/menos goles, ambos marcan, hándicap, etc.)\n" .
            "- Probabilidad estimada de éxito (%)\n" .
            "- Justificación basada en datos estadísticos\n" .
            "- Cuota estimada/recomendada\n\n" .
            
            "FORMATO DEL ANÁLISIS:\n" .
            "-------------------------------------------\n" .
            "- Comienza con un resumen ejecutivo del partido (2-3 frases).\n" .
            "- Divide el análisis en secciones claras (Forma, Ofensiva, Defensiva, H2H, etc.).\n" .
            "- Resalta los datos estadísticos más relevantes que influyen en tu predicción.\n" .
            "- Si hay ausencia de datos en alguna categoría, indícalo claramente pero haz una predicción basada en lo disponible.\n" .
            "- Finaliza con las tres recomendaciones de apuesta clasificadas por riesgo.\n" .
            "- Usa formato claro con encabezados, listas y énfasis donde sea apropiado.";

        $apiKey = env('GEMINI_API_KEY');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";

        $response = Http::post($url, [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ]
        ]);

        $aiResponse = $response->json();
        
        // Verificar si la respuesta contiene texto
        $analysisText = $aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? 'No se pudo generar análisis';
        
        return $analysisText;
    }
    
    /**
     * Formatear datos de forma reciente del equipo
     */
    private function formatTeamForm($matches)
    {
        if (empty($matches)) {
            return "No hay datos recientes disponibles";
        }
        
        $form = [];
        $resultado = [
            'victorias' => 0,
            'empates' => 0,
            'derrotas' => 0,
            'goles_favor' => 0,
            'goles_contra' => 0
        ];
        
        foreach ($matches as $match) {
            if ($match['result'] === 'victoria') {
                $form[] = 'V';
                $resultado['victorias']++;
            } elseif ($match['result'] === 'empate') {
                $form[] = 'E';
                $resultado['empates']++;
            } else {
                $form[] = 'D';
                $resultado['derrotas']++;
            }
            
            $resultado['goles_favor'] += $match['goals_for'];
            $resultado['goles_contra'] += $match['goals_against'];
        }
        
        $formString = implode('-', $form);
        $totalMatches = count($matches);
        
        $resumenForm = "$formString (Últimos $totalMatches partidos)\n";
        $resumenForm .= "Balance: {$resultado['victorias']}V {$resultado['empates']}E {$resultado['derrotas']}D\n";
        $resumenForm .= "Goles: {$resultado['goles_favor']} a favor, {$resultado['goles_contra']} en contra\n";
        $resumenForm .= "Promedio: " . round($resultado['goles_favor'] / max(1, $totalMatches), 2) . " goles por partido";
        
        return $resumenForm;
    }
    
    /**
     * Formatear estadísticas del equipo
     */
    private function formatTeamStats($stats)
    {
        if (empty($stats)) {
            return "No hay estadísticas disponibles";
        }
        
        // Verificar la estructura de los datos y adaptarla
        Log::debug('Estructura de estadísticas recibida', [
            'has_team' => isset($stats['team']),
            'has_fixtures' => isset($stats['fixtures']),
            'keys' => array_keys($stats)
        ]);
        
        $formattedStats = "ESTADÍSTICAS DETALLADAS:\n";
        
        // Si los datos vienen de la API con estructura diferente
        if (isset($stats['team']) && !isset($stats['fixtures'])) {
            // Es posible que sea un formato diferente (datos directos de la API)
            $formattedStats .= "- Equipo: " . ($stats['team']['name'] ?? 'N/A') . "\n";
            
            if (isset($stats['team']['venue'])) {
                $formattedStats .= "- Estadio: " . $stats['team']['venue'] . "\n";
            }
            
            if (isset($stats['team']['founded'])) {
                $formattedStats .= "- Fundado en: " . $stats['team']['founded'] . "\n";
            }
            
            // Información básica del equipo
            if (isset($stats['statistics'])) {
                $formattedStats .= "\nESTADÍSTICAS DE TEMPORADA:\n";
                $statistics = $stats['statistics'];
                
                if (isset($statistics['matches'])) {
                    $matches = $statistics['matches'];
                    $formattedStats .= "- Partidos jugados: " . ($matches['played']['total'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Victorias: " . ($matches['wins']['total'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Empates: " . ($matches['draws']['total'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Derrotas: " . ($matches['loses']['total'] ?? 'N/A') . "\n";
                }
                
                if (isset($statistics['goals'])) {
                    $goals = $statistics['goals'];
                    $formattedStats .= "- Goles marcados: " . ($goals['for']['total'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Goles recibidos: " . ($goals['against']['total'] ?? 'N/A') . "\n";
                }
            }
            
            return $formattedStats;
        }
        
        // Formato original
        if (isset($stats['fixtures'])) {
            $fixtures = $stats['fixtures'];
            $formattedStats .= "- Partidos jugados: " . ($fixtures['played']['total'] ?? 'N/A') . "\n";
            $formattedStats .= "- Victorias: " . ($fixtures['wins']['total'] ?? 'N/A') . 
                " (Local: " . ($fixtures['wins']['home'] ?? 'N/A') . 
                ", Visitante: " . ($fixtures['wins']['away'] ?? 'N/A') . ")\n";
            $formattedStats .= "- Empates: " . ($fixtures['draws']['total'] ?? 'N/A') . "\n";
            $formattedStats .= "- Derrotas: " . ($fixtures['loses']['total'] ?? 'N/A') . "\n";
        }
        
        if (isset($stats['goals'])) {
            $goals = $stats['goals'];
            
            if (isset($goals['for']['total'])) {
                $formattedStats .= "- Goles marcados: " . ($goals['for']['total']['total'] ?? 'N/A') . 
                    " (Local: " . ($goals['for']['total']['home'] ?? 'N/A') . 
                    ", Visitante: " . ($goals['for']['total']['away'] ?? 'N/A') . ")\n";
            }
            
            if (isset($goals['against']['total'])) {
                $formattedStats .= "- Goles recibidos: " . ($goals['against']['total']['total'] ?? 'N/A') . 
                    " (Local: " . ($goals['against']['total']['home'] ?? 'N/A') . 
                    ", Visitante: " . ($goals['against']['total']['away'] ?? 'N/A') . ")\n";
            }
        }
        
        if (isset($stats['clean_sheet'])) {
            $formattedStats .= "- Porterías a cero: " . ($stats['clean_sheet']['total'] ?? 'N/A') . "\n";
        }
        
        if (isset($stats['failed_to_score'])) {
            $formattedStats .= "- Partidos sin marcar: " . ($stats['failed_to_score']['total'] ?? 'N/A') . "\n";
        }
        
        if (isset($stats['biggest'])) {
            $biggest = $stats['biggest'];
            $formattedStats .= "- Mayor victoria: " . 
                (isset($biggest['wins']['home']) ? "Local " . $biggest['wins']['home'] : 'N/A') . ", " .
                (isset($biggest['wins']['away']) ? "Visitante " . $biggest['wins']['away'] : 'N/A') . "\n";
            $formattedStats .= "- Mayor derrota: " . 
                (isset($biggest['loses']['home']) ? "Local " . $biggest['loses']['home'] : 'N/A') . ", " .
                (isset($biggest['loses']['away']) ? "Visitante " . $biggest['loses']['away'] : 'N/A') . "\n";
        }
        
        return $formattedStats;
    }
    
    /**
     * Formatear enfrentamientos directos
     */
    private function formatHeadToHead($matches)
    {
        if (empty($matches)) {
            return "No hay enfrentamientos directos recientes";
        }
        
        $resultado = [
            'local' => 0,
            'visitante' => 0,
            'empates' => 0,
            'goles_local' => 0,
            'goles_visitante' => 0
        ];
        
        $h2hFormatted = "Últimos " . count($matches) . " enfrentamientos directos:\n";
        
        foreach ($matches as $match) {
            $h2hFormatted .= "- {$match['date']}: {$match['home_team']} {$match['home_goals']}-{$match['away_goals']} {$match['away_team']}\n";
            
            if ($match['winner'] === 'local') {
                $resultado['local']++;
            } elseif ($match['winner'] === 'visitante') {
                $resultado['visitante']++;
            } else {
                $resultado['empates']++;
            }
            
            $resultado['goles_local'] += $match['home_goals'];
            $resultado['goles_visitante'] += $match['away_goals'];
        }
        
        $h2hFormatted .= "\nBalance H2H: {$resultado['local']} victorias local, {$resultado['empates']} empates, {$resultado['visitante']} victorias visitante\n";
        $h2hFormatted .= "Promedio de goles: " . round($resultado['goles_local'] / count($matches), 2) . " local, " . 
                          round($resultado['goles_visitante'] / count($matches), 2) . " visitante";
        
        return $h2hFormatted;
    }
} 