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
        try {
            $apiKey = env('GEMINI_API_KEY');
            
            if (empty($apiKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la clave API de Gemini en el archivo .env',
                    'env_api_key' => 'No configurada'
                ]);
            }
            
            Log::info('Probando API de Gemini', ['api_key_length' => strlen($apiKey)]);
            
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";

            $response = Http::timeout(30)->post($url, [
                "contents" => [
                    [
                        "parts" => [
                            ["text" => "Escribe solo una frase corta sobre fútbol"]
                        ]
                    ]
                ]
            ]);
            
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error en la respuesta de la API',
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'env_api_key_length' => strlen($apiKey)
                ]);
            }

            $responseData = $response->json();
            
            if (!isset($responseData['candidates']) || 
                !isset($responseData['candidates'][0]['content']) ||
                !isset($responseData['candidates'][0]['content']['parts']) ||
                !isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                
                return response()->json([
                    'success' => false,
                    'message' => 'Respuesta con formato inesperado',
                    'response' => $responseData,
                    'env_api_key_length' => strlen($apiKey)
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'API de Gemini funcionando correctamente',
                'text' => $responseData['candidates'][0]['content']['parts'][0]['text'],
                'env_api_key_length' => strlen($apiKey)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al probar la API de Gemini: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function analizarPartido(Request $request)
    {
        try {
            Log::debug('Iniciando analizarPartido con datos: ', [
                'request_has_match_data' => $request->has('match_data'),
                'has_local' => $request->has('local'),
                'has_visitante' => $request->has('visitante')
            ]);
            
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
        
        try {
            // Buscar equipos por ID
            $homeTeam = \App\Models\Team::find($homeTeamId);
            $awayTeam = \App\Models\Team::find($awayTeamId);
            
            if (!$homeTeam || !$awayTeam) {
                Log::warning('Uno o ambos equipos no se encontraron en la base de datos', [
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId
                ]);
            } else {
                Log::info('Equipos encontrados en la base de datos', [
                    'home_team' => $homeTeam->name,
                    'away_team' => $awayTeam->name
                ]);
            }
            
            // Obtener estadísticas del equipo local desde la base de datos
            $homeTeamStats = \App\Models\TeamStats::where('team_id', $homeTeamId)->first();
            if ($homeTeamStats && !empty($homeTeamStats->stats_json)) {
                $statsData = is_string($homeTeamStats->stats_json) 
                    ? json_decode($homeTeamStats->stats_json, true) 
                    : $homeTeamStats->stats_json;
                
                if (json_last_error() === JSON_ERROR_NONE && $statsData) {
                    Log::info('Estadísticas del equipo local encontradas en la base de datos', [
                        'team_id' => $homeTeamId,
                        'team_name' => $homeTeamName,
                        'stats_keys' => is_array($statsData) ? array_keys($statsData) : 'no_keys'
                    ]);
                    
                    $result['home']['stats'] = $statsData;
                } else {
                    Log::warning('Error al decodificar JSON de estadísticas del equipo local', [
                        'team_id' => $homeTeamId,
                        'json_error' => json_last_error_msg()
                    ]);
                }
            } else {
                Log::warning('No se encontraron estadísticas del equipo local en la base de datos', [
                    'team_id' => $homeTeamId
                ]);
            }
            
            // Obtener estadísticas del equipo visitante desde la base de datos
            $awayTeamStats = \App\Models\TeamStats::where('team_id', $awayTeamId)->first();
            if ($awayTeamStats && !empty($awayTeamStats->stats_json)) {
                $statsData = is_string($awayTeamStats->stats_json) 
                    ? json_decode($awayTeamStats->stats_json, true) 
                    : $awayTeamStats->stats_json;
                
                if (json_last_error() === JSON_ERROR_NONE && $statsData) {
                    Log::info('Estadísticas del equipo visitante encontradas en la base de datos', [
                        'team_id' => $awayTeamId,
                        'team_name' => $awayTeamName,
                        'stats_keys' => is_array($statsData) ? array_keys($statsData) : 'no_keys'
                    ]);
                    
                    $result['away']['stats'] = $statsData;
                } else {
                    Log::warning('Error al decodificar JSON de estadísticas del equipo visitante', [
                        'team_id' => $awayTeamId,
                        'json_error' => json_last_error_msg()
                    ]);
                }
            } else {
                Log::warning('No se encontraron estadísticas del equipo visitante en la base de datos', [
                    'team_id' => $awayTeamId
                ]);
            }
            
            // Obtener partidos recientes del equipo local
            $homeRecentMatches = FootballMatch::where(function($query) use ($homeTeamId) {
                $query->where('home_team_id', $homeTeamId)
                      ->orWhere('away_team_id', $homeTeamId);
            })
            ->orderBy('match_date', 'desc')
            ->take(5)
            ->get();
            
            if ($homeRecentMatches->count() > 0) {
                foreach ($homeRecentMatches as $match) {
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
                
                Log::info('Partidos recientes del equipo local encontrados en la base de datos', [
                    'team_id' => $homeTeamId,
                    'match_count' => $homeRecentMatches->count()
                ]);
            } else {
                Log::warning('No se encontraron partidos recientes del equipo local en la base de datos', [
                    'team_id' => $homeTeamId
                ]);
            }
            
            // Obtener partidos recientes del equipo visitante
            $awayRecentMatches = FootballMatch::where(function($query) use ($awayTeamId) {
                $query->where('home_team_id', $awayTeamId)
                      ->orWhere('away_team_id', $awayTeamId);
            })
            ->orderBy('match_date', 'desc')
            ->take(5)
            ->get();
            
            if ($awayRecentMatches->count() > 0) {
                foreach ($awayRecentMatches as $match) {
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
                
                Log::info('Partidos recientes del equipo visitante encontrados en la base de datos', [
                    'team_id' => $awayTeamId,
                    'match_count' => $awayRecentMatches->count()
                ]);
            } else {
                Log::warning('No se encontraron partidos recientes del equipo visitante en la base de datos', [
                    'team_id' => $awayTeamId
                ]);
            }
            
            // Obtener enfrentamientos directos
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
            
            if ($h2hMatches->count() > 0) {
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
                
                Log::info('Enfrentamientos directos encontrados en la base de datos', [
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'match_count' => $h2hMatches->count()
                ]);
            } else {
                Log::warning('No se encontraron enfrentamientos directos en la base de datos', [
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId
                ]);
            }
            
            // Si no tenemos datos suficientes, intentar complementar con datos de la API solo si tenemos api_team_id
            if ((!isset($result['home']['stats']) || !isset($result['away']['stats']) || 
                empty($result['home']['recent_matches']) || empty($result['away']['recent_matches']) || 
                empty($result['head_to_head'])) && $homeTeam && $awayTeam) {
                
                // Verificar si tenemos api_team_id
                if ($homeTeam->api_team_id && $awayTeam->api_team_id) {
                    Log::info('Intentando complementar datos faltantes desde la API', [
                        'home_api_team_id' => $homeTeam->api_team_id,
                        'away_api_team_id' => $awayTeam->api_team_id
                    ]);
                    
                    $footballDataService = app(\App\Services\FootballDataService::class);
                    
                    // Solo obtener estadísticas desde la API si faltan en la base de datos
                    if (!isset($result['home']['stats'])) {
                        $homeStats = $footballDataService->getTeamStats($homeTeam->api_team_id);
                        if ($homeStats) {
                            $result['home']['stats'] = $homeStats;
                        }
                    }
                    
                    if (!isset($result['away']['stats'])) {
                        $awayStats = $footballDataService->getTeamStats($awayTeam->api_team_id);
                        if ($awayStats) {
                            $result['away']['stats'] = $awayStats;
                        }
                    }
                    
                    // Solo obtener partidos recientes desde la API si faltan en la base de datos
                    if (empty($result['home']['recent_matches'])) {
                        $homeRecentMatches = $footballDataService->getTeamRecentMatches($homeTeam->api_team_id);
                        if ($homeRecentMatches && isset($homeRecentMatches['response']) && is_array($homeRecentMatches['response'])) {
                            $result['home']['recent_matches'] = $homeRecentMatches['response'];
                        }
                    }
                    
                    if (empty($result['away']['recent_matches'])) {
                        $awayRecentMatches = $footballDataService->getTeamRecentMatches($awayTeam->api_team_id);
                        if ($awayRecentMatches && isset($awayRecentMatches['response']) && is_array($awayRecentMatches['response'])) {
                            $result['away']['recent_matches'] = $awayRecentMatches['response'];
                        }
                    }
                    
                    // Solo obtener enfrentamientos directos desde la API si faltan en la base de datos
                    if (empty($result['head_to_head'])) {
                        $h2h = $footballDataService->getHeadToHead($homeTeam->api_team_id, $awayTeam->api_team_id);
                        if ($h2h && isset($h2h['response']) && is_array($h2h['response'])) {
                            $result['head_to_head'] = $h2h['response'];
                        }
                    }
                } else {
                    Log::warning('No se pueden complementar datos desde la API, faltan api_team_id', [
                        'home_api_team_id' => $homeTeam ? $homeTeam->api_team_id : null,
                        'away_api_team_id' => $awayTeam ? $awayTeam->api_team_id : null
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error al obtener datos estadísticos: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
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
        try {
            Log::debug('Entrando a generarAnalisisIA', [
                'home_team' => $homeTeam,
                'away_team' => $awayTeam,
                'statsData_type' => gettype($statsData)
            ]);
            
            // Verificar que statsData tenga la estructura esperada
            if (!is_array($statsData)) {
                Log::error('statsData no es un array en generarAnalisisIA', ['type' => gettype($statsData)]);
                return 'ERROR: Formato de datos estadísticos incorrecto. Por favor, intente nuevamente.';
            }
            
            // Asegurar que statsData tenga todas las claves necesarias
            $requiredKeys = ['home', 'away', 'head_to_head'];
            foreach ($requiredKeys as $key) {
                if (!isset($statsData[$key])) {
                    Log::error("Clave '$key' faltante en statsData", ['keys' => array_keys($statsData)]);
                    $statsData[$key] = $key === 'head_to_head' ? [] : ['name' => $key === 'home' ? $homeTeam : $awayTeam, 'stats' => null, 'recent_matches' => []];
                }
            }
            
            // Verificar si tenemos estadísticas
            $homeHasStats = !empty($statsData['home']['stats']);
            $awayHasStats = !empty($statsData['away']['stats']);
            $hasH2h = !empty($statsData['head_to_head']);
            
            Log::debug('Datos disponibles para análisis', [
                'home_has_stats' => $homeHasStats,
                'away_has_stats' => $awayHasStats,
                'has_h2h' => $hasH2h,
                'home_recent_matches_type' => isset($statsData['home']['recent_matches']) ? gettype($statsData['home']['recent_matches']) : 'not set',
                'away_recent_matches_type' => isset($statsData['away']['recent_matches']) ? gettype($statsData['away']['recent_matches']) : 'not set'
            ]);
            
            // Asegurar que recent_matches sea siempre un array
            if (!isset($statsData['home']['recent_matches']) || !is_array($statsData['home']['recent_matches'])) {
                $statsData['home']['recent_matches'] = [];
            }
            if (!isset($statsData['away']['recent_matches']) || !is_array($statsData['away']['recent_matches'])) {
                $statsData['away']['recent_matches'] = [];
            }
            
            // Formatear datos para el prompt
            $homeForm = $this->formatTeamForm($statsData['home']['recent_matches']);
            $awayForm = $this->formatTeamForm($statsData['away']['recent_matches']);
            $h2hData = $this->formatHeadToHead($statsData['head_to_head']);
            
            // Estadísticas adicionales
            $homeStats = $this->formatTeamStats($statsData['home']['stats'] ?? null);
            $awayStats = $this->formatTeamStats($statsData['away']['stats'] ?? null);
            
            // Crear prompt mejorado para análisis más precisos, enfocado en datos reales de la base de datos
            $prompt = "ANÁLISIS PROFESIONAL DE PARTIDO PARA APOSTADORES: $homeTeam vs. $awayTeam\n\n" .
            "Fecha y hora: $matchDate\n" .
            "Estadio: $venue\n\n";
        
            // Añadir información sobre disponibilidad de datos
            if (!$homeHasStats || !$awayHasStats) {
                $prompt .= "NOTA: ⚠️ Hay datos estadísticos limitados disponibles para este análisis. " . 
                    "La predicción se basará en los datos disponibles y conocimiento general sobre los equipos. " .
                    "Los resultados deben considerarse con precaución.\n\n";
            }
            
            // Indicar fuente de datos (base de datos local)
            $prompt .= "ANÁLISIS BASADO EN DATOS REALES ALMACENADOS EN NUESTRA BASE DE DATOS\n" .
                "Este análisis utiliza estadísticas reales y verificadas de ambos equipos almacenadas en nuestra base de datos para la temporada 2024-2025.\n\n";
            
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
                "1. Realiza un análisis detallado y preciso del partido basado EXCLUSIVAMENTE en los datos REALES proporcionados.\n" .
                "2. Evalúa la forma actual de ambos equipos y calcula porcentajes de probabilidad específicos para victoria/empate/derrota.\n" .
                "3. Analiza detalladamente las tendencias ofensivas y defensivas de ambos equipos usando estadísticas numéricas exactas.\n" .
                "4. Evalúa cuantitativamente la importancia del factor cancha y resultados previos entre ambos equipos.\n" .
                "5. Usa EXCLUSIVAMENTE los datos estadísticos proporcionados de nuestra base de datos para hacer predicciones.\n" .
                "6. Proporciona un PRONÓSTICO FINAL con probabilidades precisas (%) para cada resultado (1-X-2).\n" .
                "7. Calcula probabilidades exactas para el mercado de goles totales (Over/Under).\n\n" .
                
                "MERCADOS DE APUESTAS A CONSIDERAR (CON PROBABILIDADES ESPECÍFICAS):\n" .
                "-------------------------------------------\n" .
                "- Resultado final (1X2) - Probabilidad exacta para cada resultado\n" .
                "- Doble oportunidad (1X, 12, X2) - Probabilidad exacta para cada combinación\n" .
                "- Hándicap asiático (principales líneas) - Probabilidad por línea\n" .
                "- Ambos equipos marcan (BTTS) - Sí/No con probabilidad\n" .
                "- Más/Menos goles (Líneas: 0.5, 1.5, 2.5, 3.5, 4.5) - Probabilidad para cada línea\n" .
                "- Marcador exacto (los 3 más probables con su % de probabilidad)\n" .
                "- Primer/Último goleador (los 3 más probables con su % de probabilidad)\n" .
                "- Número de córners (Over/Under) - Probabilidad para cada línea\n" .
                "- Número de tarjetas (Over/Under) - Probabilidad para cada línea\n" .
                "- Ganador al descanso / final de partido - Probabilidad para cada combinación\n\n" .
            
            "RECOMENDACIONES DE APUESTAS:\n" .
            "-------------------------------------------\n" .
                "Proporciona CINCO recomendaciones de apuestas concretas y específicas clasificadas por nivel de riesgo y valor esperado:\n\n" .
                
                "1. APUESTA DE BAJO RIESGO (SEGURA): La más segura, aunque con menor cuota.\n" .
                "   - Especifica un mercado exacto con una probabilidad calculada (≥75%)\n" .
                "   - Indica la cuota justa que debería tener (100/probabilidad)\n" .
                "   - Indica la cuota mínima para apostar con valor\n" .
                "   - Justifica con datos estadísticos precisos y específicos de nuestra base de datos\n\n" .
                
                "2. APUESTA DE RIESGO MODERADO (VALOR): Balance entre probabilidad y retorno.\n" .
                "   - Especifica un mercado exacto con una probabilidad calculada (50-75%)\n" .
                "   - Indica la cuota justa que debería tener (100/probabilidad)\n" .
                "   - Indica la cuota mínima para apostar con valor\n" .
                "   - Justifica con datos estadísticos precisos y específicos de nuestra base de datos\n\n" .
                
                "3. APUESTA DE RIESGO MEDIO (EQUILIBRADA): Buena relación riesgo/beneficio.\n" .
                "   - Especifica un mercado exacto con una probabilidad calculada (40-60%)\n" .
                "   - Indica la cuota justa que debería tener (100/probabilidad)\n" .
                "   - Indica la cuota mínima para apostar con valor\n" .
                "   - Justifica con datos estadísticos precisos y específicos de nuestra base de datos\n\n" .
                
                "4. APUESTA DE ALTO RIESGO (ESPECULATIVA): Mayor cuota potencial.\n" .
                "   - Especifica un mercado exacto con una probabilidad calculada (20-40%)\n" .
                "   - Indica la cuota justa que debería tener (100/probabilidad)\n" .
                "   - Indica la cuota mínima para apostar con valor\n" .
                "   - Justifica con datos estadísticos precisos y específicos de nuestra base de datos\n\n" .
                
                "5. APUESTA VALOR EXTREMO (LONGSHOT): Altísimo retorno potencial.\n" .
                "   - Especifica un mercado exacto con una probabilidad calculada (<20%)\n" .
                "   - Indica la cuota justa que debería tener (100/probabilidad)\n" .
                "   - Indica la cuota mínima para apostar con valor\n" .
                "   - Justifica con datos estadísticos precisos y específicos de nuestra base de datos\n\n" .
                
                "Para cada recomendación, incluye obligatoriamente:\n" .
                "- Tipo de apuesta específico (no solo la categoría genérica)\n" .
                "- Probabilidad estimada calculada con porcentaje exacto (%)\n" .
                "- Justificación basada en datos estadísticos concretos y detallados de nuestra base de datos\n" .
                "- Cuota justa matemáticamente calculada (100/probabilidad)\n" .
                "- Cuota mínima exacta recomendada para considerar valor\n" .
                "- Nivel de confianza (Muy alta, Alta, Media, Baja, Muy baja)\n\n" .
            
            "FORMATO DEL ANÁLISIS:\n" .
            "-------------------------------------------\n" .
                "- Comienza con un RESUMEN EJECUTIVO conciso del partido.\n" .
                "- Sigue con ANÁLISIS POR EQUIPO detallado y basado en datos.\n" .
                "- Incluye FACTORES CLAVE con impacto cuantificado en el resultado.\n" .
                "- Añade ANÁLISIS DE MERCADOS con probabilidades precisas.\n" .
                "- Proporciona CINCO RECOMENDACIONES DE APUESTAS muy específicas.\n" .
                "- Usa formato estructurado con encabezados claros y porcentajes exactos.\n" .
                "- Todos los datos estadísticos deben ser precisos y concretos.\n" .
                "- Finaliza con RESUMEN DE PRONÓSTICOS con predicciones calculadas matemáticamente.\n\n" .
                
                "IMPORTANTE: Basa tu análisis ÚNICAMENTE en los datos proporcionados de nuestra base de datos. No inventes ni asumas estadísticas que no se han proporcionado. Si faltan datos para algún aspecto del análisis, indícalo claramente y basa tus predicciones en los datos disponibles.\n\n" .
                
                "AVISO LEGAL (INCLUIR SIEMPRE AL FINAL DEL ANÁLISIS):\n" .
                "**NOTA IMPORTANTE:** Este análisis se basa **exclusivamente** en los datos proporcionados. " .
                "La falta de información detallada sobre alguno de los equipos y la ausencia de datos de la temporada actual " .
                "pueden limitar la precisión de las predicciones. Las cuotas recomendadas son un punto de referencia y " .
                "pueden variar según la casa de apuestas. Es fundamental realizar una investigación adicional antes de realizar cualquier apuesta.";

            Log::debug('Prompt generado correctamente', [
                'prompt_length' => strlen($prompt)
            ]);

            // Obtener la clave API de Gemini del archivo .env
            $apiKey = env('GEMINI_API_KEY');
            
            // Verificar si tenemos la clave API
            if (empty($apiKey)) {
                Log::error('No se encontró la clave API de Gemini en el archivo .env');
                return 'ERROR: No se encontró la clave API de Gemini. Por favor, configure GEMINI_API_KEY en el archivo .env.';
            }
            
            Log::debug('Enviando solicitud a la API de Gemini');
            
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";

            // Realizar petición a la API de Gemini
            $response = Http::timeout(60)->post($url, [
                "contents" => [
                    [
                        "parts" => [
                            ["text" => $prompt]
                        ]
                    ]
                ]
            ]);

            // Verificar si la solicitud fue exitosa
            if (!$response->successful()) {
                Log::error('Error al comunicarse con la API de Gemini', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return 'Error al comunicarse con la API de Gemini: ' . $response->status() . ' - ' . $response->body();
            }

            // Decodificar la respuesta
            $aiResponse = $response->json();
            
            // Verificar la estructura de la respuesta
            if (!isset($aiResponse['candidates']) || 
                !isset($aiResponse['candidates'][0]['content']) || 
                !isset($aiResponse['candidates'][0]['content']['parts']) || 
                !isset($aiResponse['candidates'][0]['content']['parts'][0]['text'])) {
                
                Log::error('Respuesta de Gemini con formato inesperado', [
                    'response' => $aiResponse
                ]);
                
                return 'Error: La respuesta de la API de Gemini tiene un formato inesperado. Respuesta: ' . json_encode($aiResponse);
            }
            
            // Extraer el texto del análisis
            $analysisText = $aiResponse['candidates'][0]['content']['parts'][0]['text'];
            
            // Verificar si tenemos texto
            if (empty($analysisText)) {
                Log::warning('Gemini devolvió una respuesta vacía');
                return 'La API de Gemini devolvió una respuesta vacía. Por favor, intente nuevamente.';
            }
            
            Log::debug('Análisis generado correctamente', [
                'length' => strlen($analysisText)
            ]);
        
        return $analysisText;
            
        } catch (\Exception $e) {
            Log::error('Error al generar análisis con Gemini', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 'Error al generar el análisis: ' . $e->getMessage();
        }
    }
    
    /**
     * Formatear datos de forma reciente del equipo
     */
    private function formatTeamForm($matches)
    {
        if (empty($matches)) {
            Log::debug('No hay partidos recientes disponibles');
            return "No hay datos recientes disponibles";
        }
        
        // Verificar que matches sea un array
        if (!is_array($matches)) {
            Log::error('matches no es un array en formatTeamForm', [
                'matches_type' => gettype($matches),
                'matches_value' => substr(is_string($matches) ? $matches : '', 0, 100)
            ]);
            return "Error: Formato de partidos incorrecto";
        }
        
        // Identificar estructura de datos basado en las claves disponibles
        $sampleMatch = reset($matches);
        $isApiFormat = isset($sampleMatch['teams']);
        
        Log::debug('Formato de partidos detectado', [
            'is_api_format' => $isApiFormat ? 'yes' : 'no',
            'sample_keys' => is_array($sampleMatch) ? array_keys($sampleMatch) : 'no_sample'
        ]);
        
        $form = [];
        
        if ($isApiFormat) {
            // Formato desde la API de football-data
            foreach ($matches as $match) {
                try {
                    // Verificar si el partido ya terminó
                    if (!isset($match['fixture']['status']['short']) || $match['fixture']['status']['short'] !== 'FT') {
                        continue; // Saltar partidos que no han terminado
                    }
                    
                    // Obtener resultado
                    $goals = $match['goals'] ?? null;
                    $home = $goals['home'] ?? null;
                    $away = $goals['away'] ?? null;
                    
                    // Si no hay goles, saltar
                    if ($home === null || $away === null) {
                        continue;
                    }
                    
                    $forma = '';
                    $teams = $match['teams'] ?? null;
                    
                    if (!$teams || !isset($teams['home']) || !isset($teams['away'])) {
                        continue;
                    }
                    
                    if ($teams['home']['winner'] === true) {
                        $forma = $teams['home']['id'] === $match['teams']['home']['id'] ? 'V' : 'D';
                    } elseif ($teams['away']['winner'] === true) {
                        $forma = $teams['away']['id'] === $match['teams']['home']['id'] ? 'V' : 'D';
                    } else {
                        $forma = 'E';
                    }
                    
                    $form[] = [
                        'result' => $forma,
                        'match_date' => $match['fixture']['date'] ?? 'N/A',
                        'opponent' => $teams['home']['id'] === $match['teams']['home']['id'] ? $teams['away']['name'] : $teams['home']['name'],
                        'score' => "$home-$away"
                    ];
                } catch (\Exception $e) {
                    Log::error('Error procesando partido reciente: ' . $e->getMessage(), [
                        'match' => json_encode(array_keys($match))
                    ]);
                }
            }
        } else {
            // Formato alternativo (posiblemente de la base de datos)
            foreach ($matches as $match) {
                if (isset($match['result'])) {
                    $form[] = [
                        'result' => $match['result'],
                        'match_date' => $match['match_date'] ?? 'N/A',
                        'opponent' => $match['opponent'] ?? 'Desconocido',
                        'score' => ($match['goals_for'] ?? '?') . '-' . ($match['goals_against'] ?? '?')
                    ];
                }
            }
        }
        
        // Limitar a los últimos 5 partidos
        $form = array_slice($form, 0, 5);
        
        if (empty($form)) {
            return "No hay partidos recientes disponibles para mostrar";
        }
        
        // Formatear resultado
        $resultado = [];
        foreach ($form as $partido) {
            $color = $partido['result'] === 'V' ? 'verde' : ($partido['result'] === 'E' ? 'gris' : 'rojo');
            $resultado[] = "- " . date('d/m/Y', strtotime($partido['match_date'])) . 
                ": vs " . $partido['opponent'] . 
                " (" . $partido['score'] . ") - **" . $partido['result'] . "**";
        }
        
        return implode("\n", $resultado);
    }
    
    /**
     * Formatear estadísticas del equipo
     */
    private function formatTeamStats($stats)
    {
        // Verificar que stats exista y sea un array
        if (empty($stats)) {
            Log::debug('No hay estadísticas disponibles para formatear');
            return "No hay estadísticas disponibles";
        }
        
        // Verificar que stats sea un array
        if (!is_array($stats)) {
            Log::error('stats no es un array en formatTeamStats', [
                'stats_type' => gettype($stats),
                'stats_value' => substr(is_string($stats) ? $stats : '', 0, 100)
            ]);
            return "Error: Formato de estadísticas incorrecto";
        }
        
        // Verificar la estructura de los datos
        Log::debug('Estructura de estadísticas recibida', [
            'has_team' => isset($stats['team']) ? 'yes' : 'no',
            'has_history' => isset($stats['history']) ? 'yes' : 'no',
            'has_currentSeason' => isset($stats['currentSeason']) ? 'yes' : 'no',
            'has_fixtures' => isset($stats['fixtures']) ? 'yes' : 'no',
            'keys' => array_keys($stats)
        ]);
        
        $formattedStats = "ESTADÍSTICAS DETALLADAS:\n";
        
        // Diferentes formatos posibles de los datos
        // 1. Formato almacenado en la base de datos desde team_stats.stats_json (formato completo)
        if (isset($stats['team'])) {
            // Información básica del equipo
            $formattedStats .= "- Equipo: " . ($stats['team']['name'] ?? 'N/A') . "\n";
            
            if (isset($stats['team']['founded'])) {
                $formattedStats .= "- Fundado en: " . $stats['team']['founded'] . "\n";
            }
            
            if (isset($stats['team']['venue'])) {
                $formattedStats .= "- Estadio: " . $stats['team']['venue'] . "\n";
            }
            
            // Si tenemos datos de historia/rendimiento
            if (isset($stats['history']) && isset($stats['history']['performance'])) {
                $performance = $stats['history']['performance'];
                
                if (isset($performance['summary'])) {
                    $summary = $performance['summary'];
                    $formattedStats .= "\nESTADÍSTICAS GENERALES DE TEMPORADA:\n";
                    $formattedStats .= "- Partidos jugados: " . ($summary['played'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Victorias: " . ($summary['won'] ?? 'N/A') . " (" . 
                        (isset($summary['winPercentage']) ? round($summary['winPercentage'], 1) : 'N/A') . "%)\n";
                    $formattedStats .= "- Empates: " . ($summary['draw'] ?? 'N/A') . " (" . 
                        (isset($summary['drawPercentage']) ? round($summary['drawPercentage'], 1) : 'N/A') . "%)\n";
                    $formattedStats .= "- Derrotas: " . ($summary['lost'] ?? 'N/A') . " (" . 
                        (isset($summary['lossPercentage']) ? round($summary['lossPercentage'], 1) : 'N/A') . "%)\n";
                    $formattedStats .= "- Goles a favor: " . ($summary['goalsFor'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Goles en contra: " . ($summary['goalsAgainst'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Diferencia de goles: " . ($summary['goalDifference'] ?? 'N/A') . "\n";
                    
                    if (isset($summary['cleanSheets'])) {
                        $formattedStats .= "- Porterías a cero: " . $summary['cleanSheets'] . " (" . 
                            (isset($summary['cleanSheetPercentage']) ? round($summary['cleanSheetPercentage'], 1) : 'N/A') . "%)\n";
                    }
                    
                    if (isset($summary['failedToScore'])) {
                        $formattedStats .= "- Partidos sin marcar: " . $summary['failedToScore'] . " (" . 
                            (isset($summary['failedToScorePercentage']) ? round($summary['failedToScorePercentage'], 1) : 'N/A') . "%)\n";
                    }
                }
                
                // Promedios
                if (isset($performance['averages'])) {
                    $averages = $performance['averages'];
                    $formattedStats .= "\nPROMEDIOS:\n";
                    $formattedStats .= "- Goles marcados por partido: " . ($averages['goalsForPerGame'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Goles recibidos por partido: " . ($averages['goalsAgainstPerGame'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Puntos por partido: " . ($averages['pointsPerGame'] ?? 'N/A') . "\n";
                    
                    // Separación local/visitante
                    if (isset($averages['goalsForPerGameHome'])) {
                        $formattedStats .= "- Goles marcados como local: " . $averages['goalsForPerGameHome'] . " por partido\n";
                    }
                    if (isset($averages['goalsAgainstPerGameHome'])) {
                        $formattedStats .= "- Goles recibidos como local: " . $averages['goalsAgainstPerGameHome'] . " por partido\n";
                    }
                    if (isset($averages['goalsForPerGameAway'])) {
                        $formattedStats .= "- Goles marcados como visitante: " . $averages['goalsForPerGameAway'] . " por partido\n";
                    }
                    if (isset($averages['goalsAgainstPerGameAway'])) {
                        $formattedStats .= "- Goles recibidos como visitante: " . $averages['goalsAgainstPerGameAway'] . " por partido\n";
                    }
                }
                
                // Estadísticas como local
                if (isset($performance['home'])) {
                    $home = $performance['home'];
                    $formattedStats .= "\nCOMO LOCAL:\n";
                    $formattedStats .= "- Partidos: " . ($home['played'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Victorias: " . ($home['won'] ?? 'N/A') . " (" . 
                        (isset($home['winPercentage']) ? round($home['winPercentage'], 1) : 'N/A') . "%)\n";
                    $formattedStats .= "- Empates: " . ($home['draw'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Derrotas: " . ($home['lost'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Goles a favor: " . ($home['goalsFor'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Goles en contra: " . ($home['goalsAgainst'] ?? 'N/A') . "\n";
                }
                
                // Estadísticas como visitante
                if (isset($performance['away'])) {
                    $away = $performance['away'];
                    $formattedStats .= "\nCOMO VISITANTE:\n";
                    $formattedStats .= "- Partidos: " . ($away['played'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Victorias: " . ($away['won'] ?? 'N/A') . " (" . 
                        (isset($away['winPercentage']) ? round($away['winPercentage'], 1) : 'N/A') . "%)\n";
                    $formattedStats .= "- Empates: " . ($away['draw'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Derrotas: " . ($away['lost'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Goles a favor: " . ($away['goalsFor'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Goles en contra: " . ($away['goalsAgainst'] ?? 'N/A') . "\n";
                }
                
                // Rachas
                if (isset($performance['streaks'])) {
                    $streaks = $performance['streaks'];
                    $formattedStats .= "\nRACHAS:\n";
                    $formattedStats .= "- Racha actual: " . ($streaks['current'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Racha más larga de victorias: " . ($streaks['longestWin'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Racha más larga de derrotas: " . ($streaks['longestLose'] ?? 'N/A') . "\n";
                }
                
                // Forma reciente
                if (isset($performance['form']) && isset($performance['form']['string']) && $performance['form']['string']) {
                    $formattedStats .= "\nFORMA RECIENTE: " . $performance['form']['string'] . "\n";
                }
                
                // Récords y mayores resultados
                if (isset($performance['records'])) {
                    $records = $performance['records'];
                    $formattedStats .= "\nRÉCORDS:\n";
                    
                    if (isset($records['biggestWin'])) {
                        $formattedStats .= "- Mayor victoria: " . 
                            (isset($records['biggestWin']['score']) ? "por " . $records['biggestWin']['score'] . " goles" : 'N/A');
                        
                        // Intentar añadir detalles del partido si están disponibles
                        if (isset($records['biggestWin']['match'])) {
                            $match = $records['biggestWin']['match'];
                            $formattedStats .= " (vs " . 
                                (isset($match['awayTeam']['name']) ? $match['awayTeam']['name'] : 'Equipo desconocido') . 
                                ", " . (isset($match['score']['fullTime']['home']) && isset($match['score']['fullTime']['away']) ? 
                                $match['score']['fullTime']['home'] . "-" . $match['score']['fullTime']['away'] : 'Resultado desconocido') . ")";
                        }
                        $formattedStats .= "\n";
                    }
                    
                    if (isset($records['biggestDefeat'])) {
                        $formattedStats .= "- Mayor derrota: " . 
                            (isset($records['biggestDefeat']['score']) ? "por " . $records['biggestDefeat']['score'] . " goles" : 'N/A');
                        
                        // Intentar añadir detalles del partido si están disponibles
                        if (isset($records['biggestDefeat']['match'])) {
                            $match = $records['biggestDefeat']['match'];
                            $formattedStats .= " (vs " . 
                                (isset($match['homeTeam']['name']) ? $match['homeTeam']['name'] : 'Equipo desconocido') . 
                                ", " . (isset($match['score']['fullTime']['home']) && isset($match['score']['fullTime']['away']) ? 
                                $match['score']['fullTime']['home'] . "-" . $match['score']['fullTime']['away'] : 'Resultado desconocido') . ")";
                        }
                        $formattedStats .= "\n";
                    }
                }
                
                // Rendimiento por mes, si está disponible
                if (isset($performance['by_month']) && !empty($performance['by_month'])) {
                    $formattedStats .= "\nRENDIMIENTO POR MES:\n";
                    $months = [
                        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
                        '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
                        '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
                    ];
                    
                    foreach ($performance['by_month'] as $yearMonth => $monthData) {
                        // Extraer año y mes (formato "YYYY-MM")
                        if (preg_match('/^(\d{4})-(\d{2})$/', $yearMonth, $matches)) {
                            $year = $matches[1];
                            $month = $matches[2];
                            $monthName = $months[$month] ?? $month;
                            
                            $formattedStats .= "- " . $monthName . " " . $year . ": ";
                            if (isset($monthData['played'])) {
                                $formattedStats .= $monthData['played'] . " partidos, ";
                            }
                            if (isset($monthData['won']) && isset($monthData['draw']) && isset($monthData['lost'])) {
                                $formattedStats .= $monthData['won'] . "V-" . $monthData['draw'] . "E-" . $monthData['lost'] . "D, ";
                            }
                            if (isset($monthData['goalsFor']) && isset($monthData['goalsAgainst'])) {
                                $formattedStats .= $monthData['goalsFor'] . " goles a favor, " . $monthData['goalsAgainst'] . " en contra";
                            }
                            $formattedStats .= "\n";
                        }
                    }
                }
            }
        }
        
        // 2. Formato de estadísticas de temporada actual
        if (isset($stats['currentSeason']) && isset($stats['currentSeason']['stats'])) {
            $seasonStats = $stats['currentSeason']['stats'];
            
            if (!isset($stats['history'])) { // Solo mostrar si no ya mostramos la sección history
                if (isset($seasonStats['summary'])) {
                    $summary = $seasonStats['summary'];
                    $formattedStats .= "\nESTADÍSTICAS DE TEMPORADA ACTUAL:\n";
                    $formattedStats .= "- Partidos jugados: " . ($summary['played'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Victorias: " . ($summary['won'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Empates: " . ($summary['draw'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Derrotas: " . ($summary['lost'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Goles a favor: " . ($summary['goalsFor'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Goles en contra: " . ($summary['goalsAgainst'] ?? 'N/A') . "\n";
                    
                    if (isset($summary['cleanSheets'])) {
                        $formattedStats .= "- Porterías a cero: " . $summary['cleanSheets'] . "\n";
                    }
                    
                    if (isset($summary['failedToScore'])) {
                        $formattedStats .= "- Partidos sin marcar: " . $summary['failedToScore'] . "\n";
                    }
                }
                
                // Estadísticas en casa
                if (isset($seasonStats['home'])) {
                    $home = $seasonStats['home'];
                    $formattedStats .= "\nCOMO LOCAL:\n";
                    $formattedStats .= "- Partidos: " . ($home['played'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Victorias: " . ($home['won'] ?? 'N/A') . " (" . 
                        (isset($home['played']) && $home['played'] > 0 ? round(($home['won'] / $home['played']) * 100, 1) : 'N/A') . "%)\n";
                    $formattedStats .= "- Goles a favor: " . ($home['goalsFor'] ?? 'N/A') . " (" . 
                        (isset($home['played']) && $home['played'] > 0 ? round(($home['goalsFor'] / $home['played']), 2) : 'N/A') . " por partido)\n";
                    $formattedStats .= "- Goles en contra: " . ($home['goalsAgainst'] ?? 'N/A') . "\n";
                }
                
                // Estadísticas fuera de casa
                if (isset($seasonStats['away'])) {
                    $away = $seasonStats['away'];
                    $formattedStats .= "\nCOMO VISITANTE:\n";
                    $formattedStats .= "- Partidos: " . ($away['played'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Victorias: " . ($away['won'] ?? 'N/A') . " (" . 
                        (isset($away['played']) && $away['played'] > 0 ? round(($away['won'] / $away['played']) * 100, 1) : 'N/A') . "%)\n";
                    $formattedStats .= "- Goles a favor: " . ($away['goalsFor'] ?? 'N/A') . " (" . 
                        (isset($away['played']) && $away['played'] > 0 ? round(($away['goalsFor'] / $away['played']), 2) : 'N/A') . " por partido)\n";
                    $formattedStats .= "- Goles en contra: " . ($away['goalsAgainst'] ?? 'N/A') . "\n";
                }
                
                // Rachas
                if (isset($seasonStats['streaks'])) {
                    $streaks = $seasonStats['streaks'];
                    $formattedStats .= "\nRACHAS:\n";
                    $formattedStats .= "- Racha actual: " . ($streaks['current'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Racha más larga de victorias: " . ($streaks['longestWin'] ?? 'N/A') . "\n";
                    $formattedStats .= "- Racha más larga de derrotas: " . ($streaks['longestLose'] ?? 'N/A') . "\n";
                }
            }
        }
        
        // 3. Formato de estadísticas de partidos (fixtures) - formato alternativo
        if (isset($stats['fixtures']) && !isset($stats['history']) && !isset($stats['currentSeason'])) {
            $fixtures = $stats['fixtures'];
            $formattedStats .= "\nESTADÍSTICAS DE PARTIDOS:\n";
            $formattedStats .= "- Partidos jugados: " . ($fixtures['played']['total'] ?? 'N/A') . "\n";
            $formattedStats .= "- Victorias: " . ($fixtures['wins']['total'] ?? 'N/A') . " - " . 
                (isset($fixtures['played']['total']) && $fixtures['played']['total'] > 0 
                    ? round(($fixtures['wins']['total'] / $fixtures['played']['total']) * 100, 1) : 'N/A') . "%\n";
            $formattedStats .= "  (Local: " . ($fixtures['wins']['home'] ?? 'N/A') . 
                ", Visitante: " . ($fixtures['wins']['away'] ?? 'N/A') . ")\n";
            
            $formattedStats .= "- Empates: " . ($fixtures['draws']['total'] ?? 'N/A') . " - " . 
                (isset($fixtures['played']['total']) && $fixtures['played']['total'] > 0 
                    ? round(($fixtures['draws']['total'] / $fixtures['played']['total']) * 100, 1) : 'N/A') . "%\n";
                    
            $formattedStats .= "- Derrotas: " . ($fixtures['loses']['total'] ?? 'N/A') . " - " . 
                (isset($fixtures['played']['total']) && $fixtures['played']['total'] > 0 
                    ? round(($fixtures['loses']['total'] / $fixtures['played']['total']) * 100, 1) : 'N/A') . "%\n";
        }
        
        // 4. Información de goles (formato alternativo)
        if (isset($stats['goals']) && !isset($stats['history']) && !isset($stats['currentSeason'])) {
            $goals = $stats['goals'];
            $formattedStats .= "\nGOLES:\n";
            
            if (isset($goals['for']['total'])) {
                $formattedStats .= "- Goles marcados: " . ($goals['for']['total']['total'] ?? 'N/A') . "\n";
                $formattedStats .= "  (Local: " . ($goals['for']['total']['home'] ?? 'N/A') . 
                    ", Visitante: " . ($goals['for']['total']['away'] ?? 'N/A') . ")\n";
            } elseif (isset($goals['for'])) {
                $formattedStats .= "- Goles marcados: " . ($goals['for'] ?? 'N/A') . "\n";
            }
            
            if (isset($goals['against']['total'])) {
                $formattedStats .= "- Goles recibidos: " . ($goals['against']['total']['total'] ?? 'N/A') . "\n";
                $formattedStats .= "  (Local: " . ($goals['against']['total']['home'] ?? 'N/A') . 
                    ", Visitante: " . ($goals['against']['total']['away'] ?? 'N/A') . ")\n";
            } elseif (isset($goals['against'])) {
                $formattedStats .= "- Goles recibidos: " . ($goals['against'] ?? 'N/A') . "\n";
            }
            
            // Promedio de goles por partido
            if (isset($stats['fixtures']) && isset($stats['fixtures']['played']['total']) && $stats['fixtures']['played']['total'] > 0) {
                $played = $stats['fixtures']['played']['total'];
                $formattedStats .= "- Promedio de goles a favor: " . 
                    (isset($goals['for']['total']['total']) ? round($goals['for']['total']['total'] / $played, 2) : 'N/A') . " por partido\n";
                $formattedStats .= "- Promedio de goles en contra: " . 
                    (isset($goals['against']['total']['total']) ? round($goals['against']['total']['total'] / $played, 2) : 'N/A') . " por partido\n";
            }
        }
        
        // 5. Porterías a cero y partidos sin marcar (formato alternativo)
        if (isset($stats['clean_sheet']) && !isset($stats['history']) && !isset($stats['currentSeason'])) {
            $formattedStats .= "\nDEFENSA Y ATAQUE:\n";
            $formattedStats .= "- Porterías a cero: " . ($stats['clean_sheet']['total'] ?? 'N/A');
            
            if (isset($stats['fixtures']) && isset($stats['fixtures']['played']['total']) && $stats['fixtures']['played']['total'] > 0) {
                $played = $stats['fixtures']['played']['total'];
                $formattedStats .= " (" . round(($stats['clean_sheet']['total'] / $played) * 100, 1) . "% de los partidos)\n";
            } else {
                $formattedStats .= "\n";
            }
        }
        
        if (isset($stats['failed_to_score']) && !isset($stats['history']) && !isset($stats['currentSeason'])) {
            if (!strpos($formattedStats, "DEFENSA Y ATAQUE")) {
                $formattedStats .= "\nDEFENSA Y ATAQUE:\n";
            }
            
            $formattedStats .= "- Partidos sin marcar: " . ($stats['failed_to_score']['total'] ?? 'N/A');
            
            if (isset($stats['fixtures']) && isset($stats['fixtures']['played']['total']) && $stats['fixtures']['played']['total'] > 0) {
                $played = $stats['fixtures']['played']['total'];
                $formattedStats .= " (" . round(($stats['failed_to_score']['total'] / $played) * 100, 1) . "% de los partidos)\n";
            } else {
                $formattedStats .= "\n";
            }
        }
        
        // 6. Récords y mayores resultados (formato alternativo)
        if (isset($stats['biggest']) && !isset($stats['history']) && !isset($stats['currentSeason'])) {
            $biggest = $stats['biggest'];
            $formattedStats .= "\nRÉCORDS:\n";
            
            if (isset($biggest['wins'])) {
                $formattedStats .= "- Mayor victoria: ";
                if (isset($biggest['wins']['home'])) {
                    $formattedStats .= "Local " . $biggest['wins']['home'];
                }
                if (isset($biggest['wins']['away'])) {
                    if (isset($biggest['wins']['home'])) {
                        $formattedStats .= ", ";
                    }
                    $formattedStats .= "Visitante " . $biggest['wins']['away'];
                }
                $formattedStats .= "\n";
            }
            
            if (isset($biggest['loses'])) {
                $formattedStats .= "- Mayor derrota: ";
                if (isset($biggest['loses']['home'])) {
                    $formattedStats .= "Local " . $biggest['loses']['home'];
                }
                if (isset($biggest['loses']['away'])) {
                    if (isset($biggest['loses']['home'])) {
                        $formattedStats .= ", ";
                    }
                    $formattedStats .= "Visitante " . $biggest['loses']['away'];
                }
                $formattedStats .= "\n";
            }
        }
        
        return $formattedStats;
    }
    
    /**
     * Formatear enfrentamientos directos
     */
    private function formatHeadToHead($matches)
    {
        if (empty($matches)) {
            Log::debug('No hay enfrentamientos directos');
            return "No hay enfrentamientos directos recientes";
        }
        
        // Verificar que matches sea un array
        if (!is_array($matches)) {
            Log::error('matches no es un array en formatHeadToHead', [
                'matches_type' => gettype($matches),
                'matches_value' => substr(is_string($matches) ? $matches : '', 0, 100)
            ]);
            return "Error: Formato de enfrentamientos incorrecto";
        }
        
        // Limitar a los últimos 5 partidos
        $matches = array_slice($matches, 0, 5);
        
        // Identificar estructura de datos
        $sampleMatch = reset($matches);
        $isApiFormat = isset($sampleMatch['teams']);
        
        Log::debug('Formato de H2H detectado', [
            'is_api_format' => $isApiFormat ? 'yes' : 'no',
            'sample_keys' => is_array($sampleMatch) ? array_keys($sampleMatch) : 'no_sample'
        ]);
        
        $formattedMatches = [];
        
        if ($isApiFormat) {
            // Formato desde la API
            foreach ($matches as $match) {
                try {
                    // Verificar si el partido ya terminó
                    if (!isset($match['fixture']['status']['short']) || $match['fixture']['status']['short'] !== 'FT') {
                        continue; // Saltar partidos que no han terminado
                    }
                    
                    $goals = $match['goals'] ?? null;
                    if (!$goals || !isset($goals['home']) || !isset($goals['away'])) {
                        continue;
                    }
                    
                    $teams = $match['teams'] ?? null;
                    if (!$teams || !isset($teams['home']['name']) || !isset($teams['away']['name'])) {
                        continue;
                    }
                    
                    $matchDate = isset($match['fixture']['date']) ? 
                        date('d/m/Y', strtotime($match['fixture']['date'])) : 'N/A';
                    
                    $formattedMatches[] = "- " . $matchDate . ": " . 
                        $teams['home']['name'] . " " . $goals['home'] . " - " . 
                        $goals['away'] . " " . $teams['away']['name'];
                } catch (\Exception $e) {
                    Log::error('Error procesando H2H: ' . $e->getMessage(), [
                        'match' => json_encode(array_keys($match))
                    ]);
                }
            }
        } else {
            // Formato alternativo
            foreach ($matches as $match) {
                if (isset($match['date']) && isset($match['home_team']) && isset($match['away_team'])) {
                    $matchDate = date('d/m/Y', strtotime($match['date']));
                    $formattedMatches[] = "- " . $matchDate . ": " . 
                        $match['home_team'] . " " . ($match['home_goals'] ?? '?') . " - " . 
                        ($match['away_goals'] ?? '?') . " " . $match['away_team'];
                }
            }
        }
        
        if (empty($formattedMatches)) {
            return "No hay enfrentamientos directos recientes para mostrar";
        }
        
        return implode("\n", $formattedMatches);
    }
} 