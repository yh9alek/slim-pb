<?php

declare(strict_types=1);

use App\Application\Asset\Vite;
use App\Application\Asset\ViteExtension;
use App\Application\Settings\SettingsInterface;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;

return [
    Twig::class => function (ContainerInterface $c): Twig {
        $settings = $c->get(SettingsInterface::class);

        $twig = Twig::create(__DIR__ . '/../templates', [
            'cache' => $settings->get('displayErrorDetails')
                ? false
                : __DIR__ . '/../var/cache/twig',
        ]);

        // Integración con Vite: la función vite() en las plantillas.
        $vite = new Vite(
            hotFile: __DIR__ . '/../public/hot',
            manifestPath: __DIR__ . '/../public/build/.vite/manifest.json',
            buildBase: '/build/',
        );
        $twig->addExtension(new ViteExtension($vite));

        return $twig;
    },
];
