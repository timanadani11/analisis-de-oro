<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Models\Team;
use App\Models\TeamStats;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TeamStatsController extends Controller
{
    /**
     * Mostrar la página de gestión de estadísticas de equipos
     */
    public function index(Request $request)
    {
        // Contar estadísticas
        $totalTeams = Team::count();
        $teamsWithStats = TeamStats::count();
        $teamsWithoutStats = $totalTeams - $teamsWithStats;
        
        // Obtener equipos paginados con sus estadísticas
        $query = Team::query()
            ->select('teams.*', DB::raw('team_stats.id as has_stats'))
            ->leftJoin('team_stats', 'teams.id', '=', 'team_stats.team_id');
        
        // Filtros
        if ($request->has('search')) {
            $query->where('teams.name', 'like', '%' . $request->search . '%');
        }
        
        if ($request->has('has_stats')) {
            if ($request->has_stats === 'yes') {
                $query->whereNotNull('team_stats.id');
            } elseif ($request->has_stats === 'no') {
                $query->whereNull('team_stats.id');
            }
        }
        
        $teams = $query->paginate(20)->withQueryString();
        
        return Inertia::render('Admin/Teams/Stats', [
            'teams' => $teams,
            'filters' => $request->only(['search', 'has_stats']),
            'stats' => [
                'total_teams' => $totalTeams,
                'teams_with_stats' => $teamsWithStats,
                'teams_without_stats' => $teamsWithoutStats,
                'coverage_percentage' => $totalTeams > 0 ? round(($teamsWithStats / $totalTeams) * 100, 2) : 0
            ]
        ]);
    }
    
    /**
     * Sincronizar estadísticas para un equipo específico
     */
    public function syncTeam(Request $request, $id)
    {
        try {
            $exitCode = Artisan::call('teams:sync-stats', [
                '--team_id' => $id,
                '--force' => $request->has('force')
            ]);
            
            $output = Artisan::output();
            
            if ($exitCode === 0 && !str_contains($output, 'Error')) {
                return redirect()->back()->with('success', 'Estadísticas sincronizadas correctamente');
            } else {
                return redirect()->back()->with('error', 'Error al sincronizar estadísticas: ' . $output);
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al sincronizar estadísticas: ' . $e->getMessage());
        }
    }
    
    /**
     * Sincronizar estadísticas para todos los equipos
     */
    public function syncAll(Request $request)
    {
        try {
            // Este proceso puede ser largo, así que lo ejecutamos en segundo plano
            $command = 'teams:sync-stats';
            if ($request->has('force')) {
                $command .= ' --force';
            }
            
            Artisan::queue($command);
            
            return redirect()->back()->with('success', 'Proceso de sincronización iniciado. Esto puede tardar varios minutos.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al iniciar la sincronización: ' . $e->getMessage());
        }
    }
    
    /**
     * Ver estadísticas detalladas de un equipo
     */
    public function show($id)
    {
        $team = Team::findOrFail($id);
        $stats = TeamStats::where('team_id', $id)->first();
        
        if (!$stats) {
            return redirect()->route('admin.team-stats.index')->with('error', 'Este equipo no tiene estadísticas');
        }
        
        // Decodificar estadísticas JSON
        $statsData = json_decode($stats->stats_json, true);
        
        return Inertia::render('Admin/Teams/StatsDetail', [
            'team' => $team,
            'stats' => $stats,
            'stats_data' => $statsData
        ]);
    }
    
    /**
     * Eliminar estadísticas de un equipo
     */
    public function destroy($id)
    {
        $stats = TeamStats::where('team_id', $id)->first();
        
        if (!$stats) {
            return redirect()->back()->with('error', 'No se encontraron estadísticas para este equipo');
        }
        
        $stats->delete();
        
        return redirect()->back()->with('success', 'Estadísticas eliminadas correctamente');
    }
} 