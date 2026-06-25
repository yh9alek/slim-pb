<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;

return [
    Twig::class => function (ContainerInterface $c): Twig {
        $settings = $c->get(SettingsInterface::class);

        // En desarrollo desactivamos la caché para ver los cambios al instante;
        // en producción Twig compila las plantillas a var/cache/twig.
        return Twig::create(__DIR__ . '/../templates', [
            'cache' => $settings->get('displayErrorDetails')
                ? false
                : __DIR__ . '/../var/cache/twig',
        ]);
    },
];
