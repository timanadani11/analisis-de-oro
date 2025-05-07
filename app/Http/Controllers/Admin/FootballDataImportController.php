<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use App\Models\League;
use App\Models\Team;
use App\Models\TeamStats;
use Illuminate\Support\Facades\DB;

class FootballDataImportController extends Controller
{
    /**
     * Mostrar la página de importación de datos
     */
    public function index()
    {
        // Obtener estadísticas actuales
        $stats = [
            'leagues' => League::count(),
            'teams' => Team::count(),
            'teamStats' => TeamStats::count(),
        ];

        // Obtener lista de ligas para el selector
        $leagues = League::select('id', 'name', 'country', 'logo', 'api_league_id')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/FootballData/Import', [
            'stats' => $stats,
            'leagues' => $leagues
        ]);
    }

    /**
     * Importar ligas/competiciones
     */
    public function importLeagues()
    {
        $output = null;
        $exitCode = Artisan::call('football:import', [
            'type' => 'leagues'
        ], $output);

        return response()->json([
            'success' => $exitCode === 0,
            'message' => $exitCode === 0 ? 'Ligas importadas correctamente' : 'Error al importar ligas',
            'data' => [
                'leagues_count' => League::count()
            ]
        ]);
    }

    /**
     * Importar equipos de una liga específica
     */
    public function importTeams(Request $request)
    {
        try {
            $validated = $request->validate([
                'league_code' => 'required|string|max:10',
                'season' => 'nullable|integer'
            ]);

            $leagueCode = $request->input('league_code');
            $season = $request->input('season', date('Y'));

            \Log::debug('Importación de equipos iniciada', [
                'league_code' => $leagueCode,
                'season' => $season,
                'request_data' => $request->all()
            ]);

            $output = null;
            $exitCode = Artisan::call('football:import', [
                'type' => 'teams',
                '--league' => $leagueCode,
                '--season' => $season
            ], $output);

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0 ? 'Equipos importados correctamente' : 'Error al importar equipos',
                'data' => [
                    'teams_count' => Team::count()
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación al importar equipos', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error en los datos enviados',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al importar equipos', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al importar equipos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Importar estadísticas de los equipos de una liga
     */
    public function importTeamStats(Request $request)
    {
        try {
            $validated = $request->validate([
                'league_code' => 'required|string|max:10',
            ]);

            $leagueCode = $request->input('league_code');

            \Log::debug('Importación de estadísticas iniciada', [
                'league_code' => $leagueCode,
                'request_data' => $request->all()
            ]);

            $output = null;
            $exitCode = Artisan::call('football:import', [
                'type' => 'stats',
                '--league' => $leagueCode
            ], $output);

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0 ? 'Estadísticas importadas correctamente' : 'Error al importar estadísticas',
                'data' => [
                    'stats_count' => TeamStats::count()
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación al importar estadísticas', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error en los datos enviados',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al importar estadísticas', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al importar estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver estadísticas detalladas de un equipo
     */
    public function viewTeamStats($teamId)
    {
        $team = Team::with(['league', 'latestStats'])->findOrFail($teamId);
        
        return Inertia::render('Admin/FootballData/TeamStats', [
            'team' => $team
        ]);
    }

    /**
     * Importar todo (ligas, equipos y estadísticas)
     */
    public function importAll(Request $request)
    {
        try {
            $validated = $request->validate([
                'league_code' => 'required|string|max:10',
                'season' => 'nullable|integer'
            ]);

            $leagueCode = $request->input('league_code');
            $season = $request->input('season', date('Y'));

            \Log::debug('Importación completa iniciada', [
                'league_code' => $leagueCode,
                'season' => $season,
                'request_data' => $request->all()
            ]);

            $output = null;
            $exitCode = Artisan::call('football:import', [
                'type' => 'all',
                '--league' => $leagueCode,
                '--season' => $season
            ], $output);

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0 ? 'Importación completa realizada correctamente' : 'Error en la importación',
                'data' => [
                    'leagues_count' => League::count(),
                    'teams_count' => Team::count(),
                    'stats_count' => TeamStats::count()
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación en importación completa', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error en los datos enviados',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en importación completa', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error en la importación: ' . $e->getMessage()
            ], 500);
        }
    }
} 