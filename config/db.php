<?php

// 1. Importamos y ejecutamos el lector del .env
require_once 'env_loader.php';
// Apuntamos al archivo .env que está en la raíz (un nivel arriba de config/)
loadEnv(__DIR__ . '/../.env'); 

try {
    // 2. Extraemos los datos directamente desde $_ENV
    $host = $_ENV['DB_HOST'];
    $dbname = $_ENV['DB_NAME'];
    $username = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASS'];

    // 3. Creamos la conexión PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Configurar PDO para que lance excepciones en caso de error SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Si la base de datos falla, respondemos con un error limpio al cliente
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
    exit;
}