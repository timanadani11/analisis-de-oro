<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\FootballMatch;
use App\Models\Subscription;
use App\Services\FootballApiService;
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
        $apiService = app(FootballApiService::class);
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
        
        // Preparar los datos en el formato esperado
        $matchesData = [
            'data' => $matches
        ];
        
        $apiService = app(FootballApiService::class);
        $result = $apiService->saveMatches($matchesData);
        return response()->json($result);
    }
    
    /**
     * Probar la conexión con la API de fútbol
     */
    public function testApiConnection()
    {
        $apiService = app(FootballApiService::class);
        $status = $apiService->testConnection();
        
        if ($status) {
            return response()->json([
                'success' => true,
                'message' => 'Conexión exitosa con la API de fútbol',
                'details' => 'Los parámetros de conexión son correctos y la API responde adecuadamente.'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Error al conectar con la API de fútbol',
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
}
