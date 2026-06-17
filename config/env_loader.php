<?php

function loadEnv($path) {
    if (!file_exists($path)) {
        return false; // Si el archivo .env no existe, no hace nada
    }

    // Leemos el archivo línea por línea
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Ignorar líneas que sean comentarios (que empiecen con #)
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Dividir la línea en NOMBRE y VALOR usando el signo "="
        list($name, $value) = explode('=', $line, 2);
        
        $name = trim($name);
        $value = trim($value);

        // Guardar en las variables de entorno globales de PHP
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}