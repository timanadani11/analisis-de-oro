<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FootballDataService;
use Illuminate\Support\Facades\Log;

class FootballDataController extends Controller
{
    protected $footballDataService;

    public function __construct(FootballDataService $footballDataService)
    {
        $this->footballDataService = $footballDataService;
    }

    /**
     * Prueba la conexión con la API de football-data.org
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function testApiConnection()
    {
        $connected = $this->footballDataService->testConnection();

        return response()->json([
            'success' => $connected,
            'message' => $connected ? 'Conexión establecida correctamente con Football-Data.org' : 'No se pudo conectar con la API',
        ]);
    }

    /**
     * Obtiene los equipos de la Champions League
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChampionsLeagueTeams()
    {
        $result = $this->footballDataService->getTeams('CL');

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener los equipos de la Champions League',
                'data' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Equipos de Champions League obtenidos correctamente',
            'total' => $result['count'] ?? 0,
            'season' => $result['season'] ?? null,
            'data' => $result['teams'] ?? []
        ]);
    }

    /**
     * Obtiene estadísticas de un equipo específico
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTeamStats(Request $request)
    {
        $request->validate([
            'team_id' => 'required|integer'
        ]);

        $teamId = $request->input('team_id');
        $result = $this->footballDataService->getTeamStats($teamId);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener las estadísticas del equipo',
                'data' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Estadísticas de equipo obtenidas correctamente',
            'data' => $result
        ]);
    }

    /**
     * Obtiene estadísticas de un enfrentamiento entre dos equipos
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMatchupStats(Request $request)
    {
        $request->validate([
            'home_team_id' => 'required|integer',
            'away_team_id' => 'required|integer'
        ]);

        $homeTeamId = $request->input('home_team_id');
        $awayTeamId = $request->input('away_team_id');

        $result = $this->footballDataService->getMatchupStats($homeTeamId, $awayTeamId);

        if (!$result['homeTeam'] || !$result['awayTeam']) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener las estadísticas de uno o ambos equipos',
                'home_team_found' => !is_null($result['homeTeam']),
                'away_team_found' => !is_null($result['awayTeam']),
                'data' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Estadísticas de enfrentamiento obtenidas correctamente',
            'data' => $result
        ]);
    }
} 