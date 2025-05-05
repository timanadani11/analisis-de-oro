<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class TestFootballApi extends Command
{
    protected $signature = 'api:test-football';
    protected $description = 'Prueba la conexión con la API de fútbol';

    public function handle()
    {
        $apiKey = config('services.football_api.key');
        $apiHost = config('services.football_api.host', 'v3.football.api-sports.io');
        $baseUrl = 'https://v3.football.api-sports.io';
        
        // Prueba con diferentes fechas para encontrar partidos disponibles
        $dates = [
            Carbon::now()->format('Y-m-d'),
            Carbon::now()->subDay()->format('Y-m-d'),
        ];
        
        $this->info('Configuración de la API:');
        $this->info('API Key: ' . substr($apiKey, 0, 5) . '...');
        $this->info('API Host: ' . $apiHost);
        $this->info('URL Base: ' . $baseUrl);
        
        // Probar primero el endpoint de status para verificar la autenticación
        $this->info("\nProbando el endpoint de status...");
        try {
            $response = Http::withHeaders([
                'x-rapidapi-key' => $apiKey,
                'x-rapidapi-host' => $apiHost,
            ])->get("{$baseUrl}/status");
            
            $this->info('Código de respuesta: ' . $response->status());
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("Respuesta del estado de la API:");
                $this->info("- Cuenta: " . ($data['response']['account']['name'] ?? 'No disponible'));
                $this->info("- Plan: " . ($data['response']['subscription']['plan'] ?? 'No disponible'));
                $this->info("- Solicitudes: " . 
                    ($data['response']['requests']['current'] ?? '?') . " de " . 
                    ($data['response']['requests']['limit_day'] ?? '?') . " diarias");
            } else {
                $this->error("Error al verificar el estado de la API:");
                $this->line($response->body());
            }
        } catch (\Exception $e) {
            $this->error("Error al conectar con la API (status): " . $e->getMessage());
        }
        
        // Probar el endpoint de ligas
        $this->info("\nProbando el endpoint de ligas...");
        try {
            $response = Http::withHeaders([
                'x-rapidapi-key' => $apiKey,
                'x-rapidapi-host' => $apiHost,
            ])->get("{$baseUrl}/leagues");
            
            $this->info('Código de respuesta: ' . $response->status());
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("Total de ligas: " . ($data['results'] ?? 0));
                
                if (isset($data['response']) && is_array($data['response']) && count($data['response']) > 0) {
                    $this->info("Primeras 3 ligas:");
                    for ($i = 0; $i < min(3, count($data['response'])); $i++) {
                        $league = $data['response'][$i];
                        $this->line("- " . $league['league']['name'] . " (" . $league['country']['name'] . ")");
                    }
                } else {
                    $this->warn("No se encontraron ligas en la respuesta.");
                }
            } else {
                $this->error("Error al obtener ligas:");
                $this->line($response->body());
            }
        } catch (\Exception $e) {
            $this->error("Error al conectar con la API (leagues): " . $e->getMessage());
        }
        
        // Probar el endpoint de partidos con varias fechas
        foreach ($dates as $date) {
            $this->info("\nProbando partidos para la fecha: {$date}");
            try {
                $response = Http::withHeaders([
                    'x-rapidapi-key' => $apiKey,
                    'x-rapidapi-host' => $apiHost,
                ])->get("{$baseUrl}/fixtures", [
                    'date' => $date
                ]);
                
                $this->info('Código de respuesta: ' . $response->status());
                
                if ($response->successful()) {
                    $data = $response->json();
                    $totalMatches = isset($data['response']) ? count($data['response']) : 0;
                    $this->info("Total de partidos: {$totalMatches}");
                    
                    if ($totalMatches > 0) {
                        $this->info("Primeros 2 partidos:");
                        for ($i = 0; $i < min(2, $totalMatches); $i++) {
                            $match = $data['response'][$i];
                            $this->line("--------------------------------------------");
                            $this->line("Partido " . ($i+1) . ":");
                            $this->line("Liga: " . ($match['league']['name'] ?? 'Desconocido'));
                            $this->line("Local: " . ($match['teams']['home']['name'] ?? 'Desconocido'));
                            $this->line("Visitante: " . ($match['teams']['away']['name'] ?? 'Desconocido'));
                            $this->line("Fecha: " . ($match['fixture']['date'] ?? 'Desconocido'));
                            $this->line("Estado: " . ($match['fixture']['status']['long'] ?? 'Desconocido'));
                        }
                    } else {
                        $this->warn("No se encontraron partidos para la fecha {$date}.");
                    }
                } else {
                    $this->error("Error al obtener partidos para {$date}:");
                    $this->line($response->body());
                }
            } catch (\Exception $e) {
                $this->error("Error al conectar con la API (fixtures): " . $e->getMessage());
            }
        }
        
        return Command::SUCCESS;
    }
}
