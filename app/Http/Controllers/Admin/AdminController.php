<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\FootballMatch;
use App\Models\Subscription;
use App\Services\FootballDataService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Mostrar el dashboard de administración
     */
    public function dashboard()
    {
        $stats = [
            'usuarios' => User::count(),
        ];

        // Verificar si existen las tablas antes de intentar contar registros
        try {
            $stats['partidos'] = FootballMatch::count();
        } catch (\Exception $e) {
            $stats['partidos'] = 0;
        }

        try {
            $stats['suscripciones'] = Subscription::count();
        } catch (\Exception $e) {
            $stats['suscripciones'] = 0;
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats
        ]);
    }
    
    /**
     * Mostrar la página de gestión de API
     */
    public function api()
    {
        return Inertia::render('Admin/Api/Index');
    }
    
    /**
     * Obtener los partidos del día actual desde la API
     */
    public function fetchTodayMatches()
    {
        // Usar el servicio de football-data.org
        $apiService = app(FootballDataService::class);
        $result = $apiService->getTodayMatches();
        
        return response()->json($result);
    }
    
    /**
     * Guardar los partidos descargados en la base de datos
     */
    public function saveMatches(Request $request)
    {
        $matches = $request->input('matches');
        
        // Logging para debug
        Log::info('Received matches data to save', [
            'match_count' => is_array($matches) ? count($matches) : 'not an array',
            'data_type' => gettype($matches),
            'sample' => is_array($matches) && !empty($matches) ? json_encode(array_slice($matches, 0, 1)) : 'none'
        ]);
        
        if (!$matches) {
            return response()->json([
                'success' => false,
                'message' => 'No se recibieron datos de partidos para guardar'
            ]);
        }
        
        // Preparar los datos en el formato esperado y guardar en la base de datos
        try {
            $savedCount = 0;
            $errors = [];
            
            foreach ($matches as $match) {
                try {
                    // Buscar si ya existe el partido en la BD
                    $existingMatch = FootballMatch::where('api_fixture_id', $match['id'])->first();
                    
                    if (!$existingMatch) {
                        // Crear un nuevo partido
                        $footballMatch = new FootballMatch();
                        $footballMatch->api_fixture_id = $match['id'];
                        $footballMatch->league_name = $match['league']['name'] ?? 'Desconocida';
                        $footballMatch->league_logo = $match['league']['logo'] ?? null;
                        $footballMatch->league_country = $match['league']['country'] ?? 'Desconocido';
                        
                        $footballMatch->home_team_name = $match['teams']['home']['name'] ?? 'Equipo Local';
                        $footballMatch->home_team_logo = $match['teams']['home']['logo'] ?? null;
                        $footballMatch->away_team_name = $match['teams']['away']['name'] ?? 'Equipo Visitante';
                        $footballMatch->away_team_logo = $match['teams']['away']['logo'] ?? null;
                        
                        $footballMatch->venue = $match['fixture']['venue'] ?? null;
                        
                        try {
                            $footballMatch->match_date = Carbon::parse($match['fixture']['date'] ?? $match['utcDate']);
                        } catch (\Exception $e) {
                            $footballMatch->match_date = now();
                            Log::warning('Error parsing match date', [
                                'date' => $match['fixture']['date'] ?? ($match['utcDate'] ?? 'undefined'),
                                'error' => $e->getMessage()
                            ]);
                        }
                    } else {
                        // Actualizar partido existente
                        $footballMatch = $existingMatch;
                    }
                    
                    // Datos que se actualizan siempre
                    $footballMatch->status = $match['fixture']['status'] ?? $match['status'] ?? 'Not Started';
                    $footballMatch->home_goals = $match['goals']['home'] ?? 0;
                    $footballMatch->away_goals = $match['goals']['away'] ?? 0;
                    $footballMatch->elapsed_time = $match['fixture']['elapsed'] ?? null;
                    
                    // Guardar estadísticas como JSON
                    if (isset($match['score'])) {
                        $footballMatch->statistics = json_encode($match['score']);
                    }
                    
                    $footballMatch->save();
                    $savedCount++;
                    
                } catch (\Exception $innerEx) {
                    // Capturar errores individuales por partido
                    Log::warning('Error al guardar partido individual', [
                        'match_id' => $match['id'] ?? 'unknown',
                        'error' => $innerEx->getMessage()
                    ]);
                    $errors[] = 'Error en partido ' . ($match['id'] ?? 'desconocido') . ': ' . $innerEx->getMessage();
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Se han guardado {$savedCount} partidos correctamente",
                'saved' => $savedCount,
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al guardar partidos', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los partidos: ' . $e->getMessage(),
                'saved' => 0,
                'errors' => [$e->getMessage()]
            ]);
        }
    }
    
    /**
     * Probar la conexión con la API de fútbol
     */
    public function testApiConnection()
    {
        // Usar el servicio de football-data.org
        $apiService = app(FootballDataService::class);
        $status = $apiService->testConnection();
        
        if ($status) {
            return response()->json([
                'success' => true,
                'message' => 'Conexión exitosa con la API de fútbol (football-data.org)',
                'details' => 'Los parámetros de conexión son correctos y la API responde adecuadamente.'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Error al conectar con la API de fútbol (football-data.org)',
            'details' => 'Verifique que la API KEY y la configuración sean correctas.'
        ]);
    }

    /**
     * Mostrar la lista de usuarios
     */
    public function users()
    {
        try {
            $users = User::orderBy('created_at', 'desc')->paginate(10);
        } catch (\Exception $e) {
            $users = collect([])->paginate(10);
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $users
        ]);
    }

    /**
     * Mostrar la lista de partidos
     */
    public function matches()
    {
        try {
            $matches = FootballMatch::orderBy('match_date', 'desc')->paginate(10);
        } catch (\Exception $e) {
            $matches = collect([])->paginate(10);
        }

        return Inertia::render('Admin/Matches/Index', [
            'matches' => $matches
        ]);
    }

    /**
     * Mostrar la lista de suscripciones
     */
    public function subscriptions()
    {
        try {
            $subscriptions = Subscription::orderBy('created_at', 'desc')->paginate(10);
        } catch (\Exception $e) {
            $subscriptions = collect([])->paginate(10);
        }

        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions
        ]);
    }

    /**
     * Sincronizar ligas desde la API (ejemplo)
     */
    public function syncLeagues()
    {
        // Aquí deberías implementar la lógica real de sincronización
        return response()->json([
            'success' => true,
            'message' => 'Sincronización de ligas completada (demo)',
            'data' => []
        ]);
    }

    /**
     * Sincronizar equipos desde la API (ejemplo)
     */
    public function syncTeams()
    {
        // Aquí deberías implementar la lógica real de sincronización
        return response()->json([
            'success' => true,
            'message' => 'Sincronización de equipos completada (demo)',
            'data' => []
        ]);
    }
    
    /**
     * Mostrar la página de prueba de la API de football-data.org
     */
    public function footballDataTest()
    {
        return Inertia::render('Admin/FootballData/Test');
    }
}
