<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MatchesController extends Controller
{
    /**
     * Mostrar los partidos del día actual
     */
    public function index()
    {
        // Obtener los partidos del día actual
        $today = Carbon::today();
        $matches = FootballMatch::whereDate('match_date', $today)
            ->orderBy('match_date', 'asc')
            ->get();
            
        // Agrupar partidos por liga
        $matchesByLeague = $matches->groupBy('league_name');
        
        // Preparar los datos para la vista
        $leagues = [];
        foreach ($matchesByLeague as $leagueName => $leagueMatches) {
            // Tomar el primer partido para obtener datos de la liga
            $firstMatch = $leagueMatches->first();
            
            $leagues[] = [
                'name' => $leagueName,
                'country' => $firstMatch->league_country,
                'logo' => $firstMatch->league_logo,
                'matches' => $leagueMatches->map(function($match) {
                    return [
                        'id' => $match->id,
                        'date' => $match->match_date,
                        'status' => $match->status,
                        'home_team' => [
                            'name' => $match->home_team_name,
                            'logo' => $match->home_team_logo,
                        ],
                        'away_team' => [
                            'name' => $match->away_team_name,
                            'logo' => $match->away_team_logo,
                        ],
                        'score' => [
                            'home' => $match->home_goals,
                            'away' => $match->away_goals,
                        ],
                        'elapsed_time' => $match->elapsed_time,
                        'venue' => $match->venue,
                    ];
                })
            ];
        }
        
        // Obtener días para la navegación (3 días antes y después)
        $days = [];
        for ($i = -3; $i <= 3; $i++) {
            $date = Carbon::today()->addDays($i);
            $day = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('d'),
                'month' => $date->format('M'),
                'day_name' => $date->format('D'),
                'is_today' => $date->isToday(),
                'formatted' => $date->format('d/m/Y'),
            ];
            $days[] = $day;
        }
        
        // Estadísticas de partidos
        $matchStats = [
            'total' => $matches->count(),
            'live' => $matches->where('status', 'In Progress')->count(),
            'finished' => $matches->where('status', 'Match Finished')->count(),
            'upcoming' => $matches->where('status', 'Not Started')->count(),
        ];
        
        return Inertia::render('Matches', [
            'leagues' => $leagues,
            'days' => $days,
            'currentDate' => $today->format('Y-m-d'),
            'stats' => $matchStats,
        ]);
    }
    
    /**
     * Mostrar partidos de una fecha específica
     */
    public function byDate($date)
    {
        try {
            $selectedDate = Carbon::parse($date);
        } catch (\Exception $e) {
            $selectedDate = Carbon::today();
        }
        
        // Obtener los partidos para la fecha seleccionada
        $matches = FootballMatch::whereDate('match_date', $selectedDate)
            ->orderBy('match_date', 'asc')
            ->get();
            
        // Agrupar partidos por liga
        $matchesByLeague = $matches->groupBy('league_name');
        
        // Preparar los datos para la vista
        $leagues = [];
        foreach ($matchesByLeague as $leagueName => $leagueMatches) {
            // Tomar el primer partido para obtener datos de la liga
            $firstMatch = $leagueMatches->first();
            
            $leagues[] = [
                'name' => $leagueName,
                'country' => $firstMatch->league_country,
                'logo' => $firstMatch->league_logo,
                'matches' => $leagueMatches->map(function($match) {
                    return [
                        'id' => $match->id,
                        'date' => $match->match_date,
                        'status' => $match->status,
                        'home_team' => [
                            'name' => $match->home_team_name,
                            'logo' => $match->home_team_logo,
                        ],
                        'away_team' => [
                            'name' => $match->away_team_name,
                            'logo' => $match->away_team_logo,
                        ],
                        'score' => [
                            'home' => $match->home_goals,
                            'away' => $match->away_goals,
                        ],
                        'elapsed_time' => $match->elapsed_time,
                        'venue' => $match->venue,
                    ];
                })
            ];
        }
        
        // Obtener días para la navegación (3 días antes y después)
        $days = [];
        for ($i = -3; $i <= 3; $i++) {
            $date = $selectedDate->copy()->addDays($i);
            $day = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('d'),
                'month' => $date->format('M'),
                'day_name' => $date->format('D'),
                'is_today' => $date->isToday(),
                'formatted' => $date->format('d/m/Y'),
            ];
            $days[] = $day;
        }
        
        // Estadísticas de partidos
        $matchStats = [
            'total' => $matches->count(),
            'live' => $matches->where('status', 'In Progress')->count(),
            'finished' => $matches->where('status', 'Match Finished')->count(),
            'upcoming' => $matches->where('status', 'Not Started')->count(),
        ];
        
        return Inertia::render('Matches', [
            'leagues' => $leagues,
            'days' => $days,
            'currentDate' => $selectedDate->format('Y-m-d'),
            'stats' => $matchStats,
        ]);
    }
}
