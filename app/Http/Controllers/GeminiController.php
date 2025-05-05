<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeminiController extends Controller
{
    public function testGemini()
    {
        $apiKey = env('GEMINI_API_KEY');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";

        $response = Http::post($url, [
            "contents" => [
                [
                    "parts" => [
                        ["text" => "Explícame cómo funciona la inteligencia artificial"]
                    ]
                ]
            ]
        ]);

        return $response->json();
    }

    public function analizarPartido(Request $request)
    {
        $local = $request->input('local');
        $visitante = $request->input('visitante');
        $fecha = $request->input('fecha');

        $prompt = "Partido: $local vs. $visitante\n\n" .
            "Fecha y hora: $fecha\n\n" .
            "Estadísticas recientes (últimos 5–10 partidos de cada equipo):\n" .
            "- Resultados (victorias, empates, derrotas)\n" .
            "- Goles a favor y en contra\n" .
            "- Rendimiento en casa/fuera\n\n" .
            "Enfrentamientos directos (H2H):\n" .
            "- Últimos 5 choques y resultados\n\n" .
            "Plantillas y bajas:\n" .
            "- Lesionados / sancionados clave\n" .
            "- Formaciones y jugadores destacados\n\n" .
            "Condiciones externas:\n" .
            "- Clima (temperatura, lluvia, viento)\n" .
            "- Estado del campo\n\n" .
            "Cuotas de apuestas (bookmakers):\n" .
            "- 1X2\n" .
            "- Más/menos de 2.5 goles\n" .
            "- Hándicap asiático (si aplica)\n" .
            "- Otras apuestas populares (doble oportunidad, ambos marcan, etc.)\n\n" .
            "Instrucciones de análisis:\n" .
            "- Evaluación de forma: Compara el nivel actual y la tendencia de ambos equipos.\n" .
            "- Ventajas tácticas: Identifica fortalezas/debilidades de tácticas y estilos de juego.\n" .
            "- Impacto de bajas y rotaciones: Cuánto afecta la ausencia de jugadores claves.\n" .
            "- Condiciones del partido: Cómo influye el clima o el estado del campo.\n" .
            "- Probabilidades implícitas: Convierte cuotas en probabilidades implícitas y ajústalas según tu análisis de valor real.\n" .
            "- Gestión de riesgo: Considera el perfil de apuesta (bajo, medio, alto riesgo).\n\n" .
            "Salida esperada:\n" .
            "- Resumen ejecutivo (2–3 frases)\n" .
            "- Análisis detallado dividido en secciones:\n" .
            "  - Forma y estadísticas\n" .
            "  - Táctica y enfrentamiento\n" .
            "  - Bajas y ajustes de plantilla\n" .
            "  - Factores externos\n" .
            "  - Valor de las cuotas\n" .
            "- Recomendación de apuesta: Mercado sugerido (e.g. 'Más de 2.5 goles', 'Victoria local con hándicap -0.5', etc.), cuota promedio observada, probabilidad estimada (%), justificación de valor esperado (EV) y nivel de confianza.\n" .
            "- Veredicto final: Indica la apuesta más segura para este partido y consejos de gestión de bankroll.";

        $apiKey = env('GEMINI_API_KEY');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";

        $response = Http::post($url, [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ]
        ]);

        return response()->json($response->json());
    }
} 