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
use App\Models\League;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
    public function matches(Request $request)
    {
        Log::debug('Admin matches: Incoming request', $request->all());

        try {
            // Modificamos la consulta para que funcione incluso si season_id es null
            $query = FootballMatch::with(['league.country', 'homeTeam', 'awayTeam'])
                               ->orderBy('match_date', 'desc');

            // Añadimos la relación con season solo si existe
            $query->leftJoin('seasons', 'football_matches.season_id', '=', 'seasons.id')
                  ->select('football_matches.*');

            // Search filter
            if ($request->filled('search')) {
                $searchTerm = $request->input('search');
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereHas('homeTeam', function ($ht) use ($searchTerm) {
                        $ht->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('awayTeam', function ($at) use ($searchTerm) {
                        $at->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('league', function ($l) use ($searchTerm) {
                        $l->where('name', 'like', "%{$searchTerm}%");
                    });
                });
            }

            // League filter (by league name)
            if ($request->filled('league_name')) {
                $query->whereHas('league', function ($l) use ($request) {
                    $l->where('name', $request->input('league_name'));
                });
            }

            // Status filter
            if ($request->filled('status')) {
                $status = $request->input('status');
                
                // Mapear valores comunes de estado a los almacenados en la base de datos
                $statusMapping = [
                    'live' => ['in progress', 'live', 'in_play', '1h', '2h'],
                    'finished' => ['match finished', 'finished', 'complete', 'completed']
                ];
                
                if (isset($statusMapping[$status])) {
                    $query->whereIn(DB::raw('LOWER(status)'), $statusMapping[$status]);
                } else {
                    $query->whereRaw('LOWER(status) = ?', [strtolower($status)]);
                }
            }

            // Filtro por fecha seleccionada (nuevo)
            if ($request->filled('selected_date')) {
                $selectedDate = $request->input('selected_date');
                $query->whereDate('match_date', $selectedDate);
            }
            // Date filter from tabs (mantener para compatibilidad)
            else if ($request->input('date_filter') === 'today') {
                $query->whereDate('match_date', Carbon::today());
            }
            // Por defecto, mostrar partidos de hoy
            else {
                $query->whereDate('match_date', Carbon::today());
            }

            Log::debug('Admin matches: Query count before pagination', ['count' => $query->count()]);
            
            // Registramos una muestra de lo que hay en la BD para diagnóstico
            $sampleMatch = FootballMatch::first();
            Log::debug('Ejemplo de un partido en la BD:', [
                'id' => $sampleMatch->id ?? 'No hay partidos',
                'status' => $sampleMatch->status ?? 'N/A',
                'home_team' => $sampleMatch->homeTeam->name ?? 'N/A',
                'away_team' => $sampleMatch->awayTeam->name ?? 'N/A'
            ]);

            $matches = $query->paginate(10)->withQueryString();

            Log::debug('Admin matches: Paginated matches data sample', ['first_match' => $matches->first()]);

            $allLeagues = League::with('country')->select('id', 'name', 'country_id')->orderBy('name')->get();

            // Procesar los datos de los partidos para adaptarlos al formato que espera la vista
            $matches->getCollection()->transform(function ($match) {
                return [
                    'id' => $match->id,
                    'api_fixture_id' => $match->api_fixture_id,
                    'match_date' => $match->match_date,
                    'status' => $match->status, // Mantenemos el valor original tal como está en la BD
                    'round' => $match->round,
                    'venue' => $match->venue,
                    'referee' => $match->referee,
                    'home_goals' => $match->home_goals ?? 0,
                    'away_goals' => $match->away_goals ?? 0,
                    'elapsed_time' => $match->elapsed_time,
                    
                    // Datos de la liga
                    'league_id' => $match->league->id ?? null,
                    'league_name' => $match->league->name ?? 'Liga Desconocida',
                    'league_logo' => $match->league->logo ?? null,
                    'league_country' => $match->league->country->name ?? 'País Desconocido',
                    
                    // Datos del equipo local
                    'home_team_id' => $match->homeTeam->id ?? null,
                    'home_team_name' => $match->homeTeam->name ?? 'Equipo Local',
                    'home_team_logo' => $match->homeTeam->logo ?? null,
                    
                    // Datos del equipo visitante
                    'away_team_id' => $match->awayTeam->id ?? null,
                    'away_team_name' => $match->awayTeam->name ?? 'Equipo Visitante',
                    'away_team_logo' => $match->awayTeam->logo ?? null,
                    
                    // Incluir temporada si está disponible
                    'season' => $match->season ? [
                        'id' => $match->season->id,
                        'year' => $match->season->year,
                        'name' => $match->season->name,
                    ] : null,
                    
                    // Liga completa si es necesario para acceso a propiedades anidadas
                    'league' => $match->league,
                    'home_team' => $match->homeTeam,
                    'away_team' => $match->awayTeam,
                ];
            });

        } catch (\Exception $e) {
            Log::error('Error fetching matches for admin: ' . $e->getMessage(), ['exception' => $e]);
            $matches = new LengthAwarePaginator([], 0, 10);
            $allLeagues = collect([]);
        }

        return Inertia::render('Admin/Matches/Index', [
            'matches' => $matches,
            'filters' => $request->only(['search', 'league_name', 'status', 'date_filter', 'selected_date']),
            'leaguesForFilter' => $allLeagues,
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

    /**
     * Obtener partidos de una fecha específica desde la API
     */
    public function fetchMatchesByDate(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        
        Log::info('Obteniendo partidos para la fecha', ['date' => $date]);
        
        try {
            // Usar el servicio de football-data.org
            $apiService = app(FootballDataService::class);
            
            // Crear un método para obtener partidos por fecha
            $endpoint = 'matches';
            $params = ['date' => $date];
            
            $response = $apiService->makeRequest($endpoint, $params);
            
            if (!$response || !isset($response['matches'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron obtener los partidos para la fecha seleccionada',
                    'data' => []
                ]);
            }
            
            $matches = $response['matches'];
            
            // Procesar los partidos para tener el mismo formato que esperamos
            $processedMatches = collect($matches)->map(function($match) {
                return [
                    'id' => $match['id'],
                    'league' => [
                        'id' => $match['competition']['id'] ?? 0,
                        'name' => $match['competition']['name'] ?? 'Desconocida',
                        'country' => $match['area']['name'] ?? '',
                        'logo' => null // Normalmnete no hay logos en esta API
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
                        'date' => $match['utcDate'] ?? null,
                        'venue' => isset($match['venue']) ? $match['venue'] : null,
                        'status' => $match['status'] ?? 'SCHEDULED',
                        'elapsed' => null,
                    ],
                    'goals' => [
                        'home' => $match['score']['fullTime']['home'] ?? 0,
                        'away' => $match['score']['fullTime']['away'] ?? 0
                    ],
                    'score' => [
                        'halftime' => [
                            'home' => $match['score']['halfTime']['home'] ?? 0,
                            'away' => $match['score']['halfTime']['away'] ?? 0
                        ],
                        'fulltime' => [
                            'home' => $match['score']['fullTime']['home'] ?? 0,
                            'away' => $match['score']['fullTime']['away'] ?? 0
                        ]
                    ]
                ];
            })->toArray();
            
            return response()->json([
                'success' => true,
                'message' => 'Partidos obtenidos correctamente',
                'count' => count($processedMatches),
                'data' => $processedMatches
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener partidos por fecha', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener partidos: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
}
