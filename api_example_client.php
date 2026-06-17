<?php

// Ejemplo PHP de consumo de la API de Posts y Comentarios.
// Guarda este archivo en la raíz del proyecto y ejecútalo con:
// php api_example_client.php
//
// Asegúrate de cambiar el token por el valor real de API_KEY en tu .env.

$apiUrl = 'http://localhost/api/api.php';
$token = 'tu_token_de_autorizacion_aqui'; // Reemplaza con tu token real

function request($method, $url, $token = null, $body = null) {
    // Configuración de los encabezados y opciones para la petición 
    $headers = [
        'Content-Type: application/json',
    ];

    // Si se proporciona un token, lo agregamos al encabezado de autorización
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    // Configuración de las opciones para la función file_get_contents

    $options = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true,
        ],
    ];

    // Si se proporciona un cuerpo para métodos como POST o PUT, lo codificamos como JSON
    if ($body !== null) {
        $options['http']['content'] = json_encode($body);
    }

    // Creamos el contexto de la petición con las opciones configuradas
    $context = stream_context_create($options); 
    // Realizamos la petición y capturamos la respuesta y el código de estado HTTP
    $response = file_get_contents($url, false, $context);

    $statusCode = null;
    if (isset($http_response_header[0])) {// Extraemos el código de estado HTTP de la respuesta
        preg_match('#HTTP/\d\.\d\s+(\d+)#', $http_response_header[0], $matches);
        $statusCode = isset($matches[1]) ? (int) $matches[1] : null;
    }

    return [
        'status' => $statusCode,
        'body' => json_decode($response, true),
    ];
}

function printResult($title, $result) {
    echo "<h3>$title</h3>";
    echo "<p><strong>Status:</strong> " . $result['status'] . "</p>";
    echo "<pre>" . json_encode($result['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
}

// Ejemplos de consumo
printResult('GET /posts', request('GET', "$apiUrl/posts"));

printResult('GET /posts/5', request('GET', "$apiUrl/posts/5"));

printResult('GET /posts/5/comments', request('GET', "$apiUrl/posts/5/comments"));

printResult('POST /posts', request('POST', "$apiUrl/posts", $token, [
    'title' => 'Post desde PHP',
    'content' => 'Contenido creado con el cliente PHP',
    'status' => 'draft',
]));

printResult('DELETE /comments/12', request('DELETE', "$apiUrl/comments/12", $token));
