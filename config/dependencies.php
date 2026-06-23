<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [

    // Logger PSR-3
    LoggerInterface::class => function (ContainerInterface $c): LoggerInterface {
        $cfg = $c->get(SettingsInterface::class)->get('logger');

        $handler = new StreamHandler($cfg['path'], $cfg['level']);
        $handler->setFormatter(new LineFormatter(
            format: "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n\n",
            dateFormat: 'Y-m-d H:i:s',
            allowInlineLineBreaks: true,
            ignoreEmptyContextAndExtra: true,
        ));

        $logger = new Logger($cfg['name']);
        $logger->pushProcessor(new UidProcessor());
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
];
