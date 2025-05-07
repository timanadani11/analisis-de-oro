<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FootballDataService;
use Illuminate\Support\Facades\Log;

class FootballController extends Controller
{
    protected $footballDataService;

    public function __construct(FootballDataService $footballDataService)
    {
        $this->footballDataService = $footballDataService;
    }

    /**
     * Get today's football matches
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTodayMatches()
    {
        try {
            Log::info('Solicitando partidos del día');
            
            $result = $this->footballDataService->getTodayMatches();
            
            Log::info('Respuesta de partidos del día', [
                'success' => $result['success'] ?? false,
                'count' => $result['count'] ?? 0
            ]);
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error obteniendo partidos del día', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo partidos: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * Fetch and save league data
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchLeague(Request $request)
    {
        $request->validate([
            'league_id' => 'required|integer',
            'season' => 'required|integer|min:2000|max:' . (date('Y') + 1),
        ]);

        $leagueId = $request->input('league_id');
        $season = $request->input('season');

        Log::info('API request to fetch league data', [
            'league_id' => $leagueId,
            'season' => $season
        ]);

        $result = $this->footballDataService->fetchAndSaveLeague($leagueId, $season);

        return response()->json($result);
    }

    /**
     * Test connection to football API
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function testApiConnection()
    {
        $connected = $this->footballDataService->testConnection();

        return response()->json([
            'success' => $connected,
            'message' => $connected ? 'Conexión establecida correctamente' : 'No se pudo conectar con la API',
        ]);
    }

    /**
     * Fetch and save teams data
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchTeams(Request $request)
    {
        $request->validate([
            'league_id' => 'required|integer',
            'season' => 'required|integer|min:2000|max:' . (date('Y') + 1),
        ]);

        $leagueId = $request->input('league_id');
        $season = $request->input('season');

        Log::info('API request to fetch teams data', [
            'league_id' => $leagueId,
            'season' => $season
        ]);

        $result = $this->footballDataService->fetchAndSaveTeams($leagueId, $season);

        return response()->json($result);
    }

    /**
     * Obtener estadísticas detalladas de los equipos de un partido para análisis
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMatchStatistics(Request $request)
    {
        try {
            $request->validate([
                'home_team_id' => 'required|integer',
                'away_team_id' => 'required|integer',
                'league_id' => 'required|integer',
                'season' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            ]);

            $homeTeamId = $request->input('home_team_id');
            $awayTeamId = $request->input('away_team_id');
            $leagueId = $request->input('league_id');
            $season = $request->input('season');

            Log::info('API request para obtener estadísticas del partido', [
                'home_team_id' => $homeTeamId,
                'away_team_id' => $awayTeamId,
                'league_id' => $leagueId,
                'season' => $season
            ]);

            $result = $this->footballDataService->getMatchTeamsStatistics(
                $homeTeamId, 
                $awayTeamId, 
                $leagueId, 
                $season
            );
            
            Log::info('Respuesta de la API para estadísticas del partido', [
                'success' => $result['success'] ?? false,
                'has_data' => isset($result['data']) ? 'yes' : 'no',
                'message' => $result['message'] ?? 'No message'
            ]);
            
            if (isset($result['success']) && $result['success'] && !isset($result['data'])) {
                Log::error('Respuesta inconsistente: éxito sin datos', $result);
                return response()->json([
                    'success' => false,
                    'message' => 'Respuesta inconsistente de la API: éxito sin datos',
                    'data' => null
                ]);
            }
            
            if (isset($result['data']) && isset($result['data']['homeTeam']) && is_null($result['data']['homeTeam'])) {
                Log::error('Datos del equipo local no disponibles', [
                    'home_team_id' => $homeTeamId,
                    'result' => $result
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron obtener los datos del equipo local',
                    'data' => null
                ]);
            }
            
            if (isset($result['data']) && isset($result['data']['awayTeam']) && is_null($result['data']['awayTeam'])) {
                Log::error('Datos del equipo visitante no disponibles', [
                    'away_team_id' => $awayTeamId,
                    'result' => $result
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron obtener los datos del equipo visitante',
                    'data' => null
                ]);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error procesando estadísticas del partido', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error procesando estadísticas: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    /**
     * Obtener estadísticas detalladas de los equipos a partir de nombres de equipo
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTeamStats(Request $request)
    {
        try {
            $request->validate([
                'home_team' => 'required|string',
                'away_team' => 'required|string',
                'league_id' => 'nullable|integer',
                'season' => 'nullable|integer|min:2000|max:' . (date('Y') + 1),
            ]);

            $homeTeamName = $request->input('home_team');
            $awayTeamName = $request->input('away_team');
            $leagueId = $request->input('league_id', 0);
            $season = $request->input('season', date('Y'));

            Log::info('Solicitando estadísticas de equipos por nombre', [
                'home_team' => $homeTeamName,
                'away_team' => $awayTeamName,
                'league_id' => $leagueId,
                'season' => $season
            ]);

            // Buscar IDs de equipos en la base de datos por nombre
            $homeTeam = \App\Models\Team::where('name', 'like', '%' . $homeTeamName . '%')->first();
            $awayTeam = \App\Models\Team::where('name', 'like', '%' . $awayTeamName . '%')->first();

            if (!$homeTeam || !$awayTeam) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron uno o ambos equipos en la base de datos',
                    'home_team_found' => !is_null($homeTeam),
                    'away_team_found' => !is_null($awayTeam),
                ]);
            }

            // Si no se especificó una liga, usar la liga asociada al equipo local
            if ($leagueId == 0 && $homeTeam->league_id) {
                $leagueId = \App\Models\League::find($homeTeam->league_id)->api_league_id ?? 0;
            }

            // Si aún no tenemos ID de liga, usar una predeterminada (Premier League)
            if ($leagueId == 0) {
                $leagueId = 39; // ID para Premier League como fallback
            }

            $result = $this->footballDataService->getMatchTeamsStatistics(
                $homeTeam->api_team_id,
                $awayTeam->api_team_id,
                $leagueId,
                $season
            );
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Error procesando estadísticas de equipos', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error procesando estadísticas: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
} 