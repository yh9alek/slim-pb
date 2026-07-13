<?php

declare(strict_types=1);

use App\Application\Core\Settings\Settings;
use App\Application\Core\Settings\SettingsInterface;
use Monolog\Level;

return [
    SettingsInterface::class => fn(): SettingsInterface => new Settings([
        'displayErrorDetails' => ($_ENV['APP_ENV'] ?? 'dev') !== 'prod',
        'db' => [
            'dsn' => $_ENV['DB_DSN'] ?? 'sqlite::memory:',
            'username' => $_ENV['DB_USER'] ?? null,
            'password' => $_ENV['DB_PASS'] ?? null,
        ],
        'logger' => [
            'name' => 'app',
            'path' => __DIR__ . '/../var/log/app.log',
            'level' => Level::Debug,
        ],
        // Throttling: nº de peticiones por ventana (segundos), por IP.
        'throttle' => [
            'limit' => (int) ($_ENV['THROTTLE_LIMIT'] ?? 30),
            'window' => (int) ($_ENV['THROTTLE_WINDOW'] ?? 60),
        ],
    ]),
];
