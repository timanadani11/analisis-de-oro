<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MatchesController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminAuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\FootballController;
use App\Http\Controllers\FootballDataController;

// Rutas públicas
Route::get('/', function () {
    return Inertia::render('Dashboard', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'auth' => [
            'user' => auth()->user(),
        ],
    ]);
})->name('home');

// Rutas de autenticación
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

// Rutas públicas que requieren datos
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'auth' => [
            'user' => auth()->user(),
        ],
        'stats' => [
            'usuarios' => \App\Models\User::count(),
            'partidos' => \App\Models\FootballMatch::count(),
        ],
    ]);
})->name('dashboard');

Route::get('/predictions', function () {
    return Inertia::render('Predictions', [
        'auth' => [
            'user' => auth()->user(),
        ],
    ]);
})->name('predictions');

// Rutas de partidos con datos
Route::get('/matches', [MatchesController::class, 'index'])->name('matches');
Route::get('/matches/date/{date}', [MatchesController::class, 'byDate'])->name('matches.by-date');

// Rutas protegidas
Route::middleware('auth')->group(function () {
    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Rutas de análisis y estadísticas
    Route::get('/analysis', function () {
        return Inertia::render('Analysis', [
            'auth' => [
                'user' => auth()->user(),
            ],
        ]);
    })->name('analysis');
    
    Route::get('/teams', function () {
        return Inertia::render('Teams', [
            'auth' => [
                'user' => auth()->user(),
            ],
        ]);
    })->name('teams');
    
    Route::get('/statistics', function () {
        return Inertia::render('Statistics', [
            'auth' => [
                'user' => auth()->user(),
            ],
        ]);
    })->name('statistics');
    
    Route::get('/premium', function () {
        return Inertia::render('Premium', [
            'auth' => [
                'user' => auth()->user(),
            ],
        ]);
    })->name('premium');
});

// Rutas de administración
Route::prefix('admin')->name('admin.')->group(function () {
    // Área pública de administración
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'loginForm'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login']);
    });

    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    
    // Área protegida de administración
    Route::middleware(['auth', 'App\Http\Middleware\AdminMiddleware'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/matches', [AdminController::class, 'matches'])->name('matches');
        Route::get('/subscriptions', [AdminController::class, 'subscriptions'])->name('subscriptions');
        Route::get('/api', [AdminController::class, 'api'])->name('api');
        
        // Rutas dinámicas para la API de fútbol (panel de admin)
        Route::get('/api/action/test-connection', [AdminController::class, 'testApiConnection']);
        Route::get('/api/action/fetch-today-matches', [AdminController::class, 'fetchTodayMatches']);
        Route::get('/api/action/sync-leagues', [AdminController::class, 'syncLeagues']);
        Route::get('/api/action/sync-teams', [AdminController::class, 'syncTeams']);
        Route::post('/api/action/save-matches', [AdminController::class, 'saveMatches']);
        
        // Ruta para la prueba de football-data.org
        Route::get('/football-data-test', [AdminController::class, 'footballDataTest'])->name('football-data-test');
    });
});

// Rutas para la API de fútbol
Route::prefix('football-api')->name('football-api.')->group(function () {
    Route::get('/test-connection', [FootballController::class, 'testApiConnection'])->name('test-connection');
    Route::get('/today-matches', [FootballController::class, 'getTodayMatches'])->name('today-matches');
    Route::post('/fetch-league', [FootballController::class, 'fetchLeague'])->name('fetch-league');
    Route::post('/fetch-teams', [FootballController::class, 'fetchTeams'])->name('fetch-teams');
    Route::post('/match-statistics', [FootballController::class, 'getMatchStatistics'])->name('match-statistics');
});

// Ruta para probar la API de Gemini
Route::get('/test-gemini', [GeminiController::class, 'testGemini']);

// Ruta para analizar un partido
Route::post('/analizar-partido', [GeminiController::class, 'analizarPartido']);

// Ruta para obtener estadísticas de equipos
Route::post('/estadisticas-equipos', [FootballController::class, 'getTeamStats']);

// Rutas para la API football-data.org
Route::prefix('football-data')->name('football-data.')->group(function () {
    Route::get('/test', [FootballDataController::class, 'testApiConnection'])->name('test');
    Route::get('/champions-teams', [FootballDataController::class, 'getChampionsLeagueTeams'])->name('champions-teams');
    Route::post('/team-stats', [FootballDataController::class, 'getTeamStats'])->name('team-stats');
    Route::post('/matchup-stats', [FootballDataController::class, 'getMatchupStats'])->name('matchup-stats');
});

// Ya no necesitamos incluir las rutas de autenticación ya que las hemos definido explícitamente
require __DIR__.'/auth.php';

// Rutas de importación de datos de football-data.org
Route::prefix('admin/football-data')->middleware(['auth'])->group(function () {
    Route::get('/import', [App\Http\Controllers\Admin\FootballDataImportController::class, 'index'])->name('football-data.import');
    Route::post('/import/leagues', [App\Http\Controllers\Admin\FootballDataImportController::class, 'importLeagues'])->name('football-data.import.leagues');
    Route::post('/import/teams', [App\Http\Controllers\Admin\FootballDataImportController::class, 'importTeams'])->name('football-data.import.teams');
    Route::post('/import/team-stats', [App\Http\Controllers\Admin\FootballDataImportController::class, 'importTeamStats'])->name('football-data.import.team-stats');
    Route::post('/import/all', [App\Http\Controllers\Admin\FootballDataImportController::class, 'importAll'])->name('football-data.import.all');
    Route::get('/team-stats/{teamId}', [App\Http\Controllers\Admin\FootballDataImportController::class, 'viewTeamStats'])->name('football-data.team-stats');
});
