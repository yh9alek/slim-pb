<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

// Phinx se ejecuta por CLI, fuera del bootstrap de la app, así que carga el
// .env por su cuenta para reutilizar EXACTAMENTE la misma config de BD.
if (class_exists(Dotenv::class) && is_file(__DIR__ . '/.env')) {
    Dotenv::createImmutable(__DIR__)->safeLoad();
}

$dsn  = $_ENV['DB_DSN'] ?? getenv('DB_DSN') ?: 'sqlite::memory:';
$user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: null;
$pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: null;

// Adaptador y nombre de BD derivados del DSN (única fuente de verdad).
$driver = strtok($dsn, ':') ?: 'mysql';
$adapter = match ($driver) {
    'sqlite' => 'sqlite',
    'pgsql' => 'pgsql',
    default => 'mysql',
};

if ($adapter === 'sqlite') {
    $path = substr($dsn, strlen('sqlite:'));      // ruta de archivo o ':memory:'
    $name = $path === '' ? ':memory:' : $path;
} else {
    preg_match('/dbname=([^;]+)/', $dsn, $matches);
    $name = $matches[1] ?? '';
}

// Reutilizamos una conexión PDO construida desde el mismo DSN de la app.
$connection = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$environment = [
    'adapter' => $adapter,
    'name' => $name,
    'connection' => $connection,
];

// En sqlite, 'name' ya es la ruta completa: evitamos que Phinx le añada sufijo.
if ($adapter === 'sqlite') {
    $environment['suffix'] = '';
}

return [
    'paths' => [
        'migrations' => __DIR__ . '/database/migrations',
        'seeds' => __DIR__ . '/database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'app',
        'app' => $environment,
    ],
    'version_order' => 'creation',
];
