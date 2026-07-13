<?php

declare(strict_types=1);

// Router para el servidor embebido de PHP (php -S), usado por el script
// "composer serve" / "composer dev". Sirve directamente los archivos
// estaticos que existan en public/ y enruta todo lo demas por el front
// controller, para que funcionen las URLs limpias (/tasks, /tasks/5, ...).
// En produccion NO se usa: ahi manda el .htaccess / nginx.

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path !== '/' && file_exists(__DIR__ . '/public' . $path)) {
    return false; // deja que el servidor sirva el archivo estatico tal cual
}

require __DIR__ . '/public/index.php';
