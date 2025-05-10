<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\FetchFootballMatches::class,
        \App\Console\Commands\SyncTeamStats::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Ejecutar la recolección de partidos todos los días a las 00:05
        $schedule->command('football:fetch-matches')
                 ->dailyAt('00:05')
                 ->appendOutputTo(storage_path('logs/football-matches-fetch.log'));

        // Sincronizar estadísticas de equipo por competición una vez al día
        // Primeras 5 ligas de fútbol principales
        $schedule->command('teams:sync-stats --competition="Premier League" --delay=60')
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/teams-sync-premier.log'));
            
        $schedule->command('teams:sync-stats --competition="La Liga" --delay=60')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/teams-sync-laliga.log'));
            
        $schedule->command('teams:sync-stats --competition="Serie A" --delay=60')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/teams-sync-seriea.log'));
            
        $schedule->command('teams:sync-stats --competition="Bundesliga" --delay=60')
            ->dailyAt('04:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/teams-sync-bundesliga.log'));
            
        $schedule->command('teams:sync-stats --competition="Ligue 1" --delay=60')
            ->dailyAt('05:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/teams-sync-ligue1.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
