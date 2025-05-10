<?php

// Obtener la clave API del archivo .env
$envFile = file_get_contents('.env');
preg_match('/GEMINI_API_KEY=([^\n\r]+)/', $envFile, $matches);
$apiKey = $matches[1] ?? null;

if (empty($apiKey)) {
    echo "Error: No se encontró la clave API de Gemini en el archivo .env\n";
    exit(1);
}

echo "Clave API encontrada: " . substr($apiKey, 0, 5) . "...\n";

// Crear la URL de la API con la clave
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;

// Datos para enviar a la API
$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => "Escribe solo una frase corta sobre fútbol"]
            ]
        ]
    ]
];

// Convertir a JSON
$jsonData = json_encode($data);

// Inicializar cURL
$ch = curl_init($url);

// Configurar opciones de cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);

// Ejecutar la petición
$response = curl_exec($ch);

// Verificar si hubo errores
if (curl_errno($ch)) {
    echo "Error cURL: " . curl_error($ch) . "\n";
    exit(1);
}

// Obtener el código de respuesta HTTP
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Código de respuesta HTTP: " . $httpCode . "\n";

// Cerrar sesión cURL
curl_close($ch);

// Decodificar la respuesta JSON
$responseData = json_decode($response, true);

// Verificar si la respuesta tiene el formato esperado
if (
    !isset($responseData['candidates']) || 
    !isset($responseData['candidates'][0]['content']) || 
    !isset($responseData['candidates'][0]['content']['parts']) || 
    !isset($responseData['candidates'][0]['content']['parts'][0]['text'])
) {
    echo "Error: Respuesta con formato inesperado\n";
    echo "Respuesta completa: " . $response . "\n";
    exit(1);
}

// Mostrar el texto generado
echo "Respuesta exitosa de Gemini API:\n";
echo $responseData['candidates'][0]['content']['parts'][0]['text'] . "\n";

?> 