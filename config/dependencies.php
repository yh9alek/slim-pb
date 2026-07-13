<?php

declare(strict_types=1);

use App\Application\Core\Middleware\ThrottleMiddleware;
use App\Application\Core\Settings\SettingsInterface;
use App\Application\Core\Throttle\FileRateLimiterStore;
use App\Application\Core\Throttle\RateLimiterStore;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [

    // Logger PSR-3
    LoggerInterface::class => function (ContainerInterface $c): LoggerInterface {
        $settings = $c->get(SettingsInterface::class);
        $cfg = $settings->get('logger');

        $logger = new Logger($cfg['name']);
        $logger->pushProcessor(new UidProcessor());

        $handler = new StreamHandler($cfg['path'], $cfg['level']);
        $handler->setFormatter(new LineFormatter(
            format: "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n\n",
            dateFormat: 'Y-m-d H:i:s',
            allowInlineLineBreaks: true,
            ignoreEmptyContextAndExtra: true,
        ));
        $logger->pushHandler($handler);

        return $logger;
    },

    // Conexión PDO
    PDO::class => function (ContainerInterface $c): PDO {
        $db = $c->get(SettingsInterface::class)->get('db');
        $pdo = new PDO($db['dsn'], $db['username'], $db['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        return $pdo;
    },

    // Almacén de contadores del rate limiter.
    RateLimiterStore::class => fn(): RateLimiterStore
        => new FileRateLimiterStore(__DIR__ . '/../var/cache/throttle'),

    // Middleware de throttling configurado desde settings (límite global).
    ThrottleMiddleware::class => function (ContainerInterface $c): ThrottleMiddleware {
        $cfg = $c->get(SettingsInterface::class)->get('throttle');

        return new ThrottleMiddleware(
            $c->get(RateLimiterStore::class),
            $c->get(LoggerInterface::class),
            (int) $cfg['limit'],
            (int) $cfg['window'],
        );
    },
];
