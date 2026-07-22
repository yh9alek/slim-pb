<?php

declare(strict_types=1);

use App\Application\Core\Asset\Vite;
use App\Application\Core\Asset\ViteExtension;
use App\Application\Core\Settings\SettingsInterface;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;

return [
    Twig::class => function (ContainerInterface $c): Twig {
        $settings = $c->get(SettingsInterface::class);

        $twig = Twig::create(__DIR__ . '/../templates/views', [
            'cache' => $settings->get('displayErrorDetails')
                ? false
                : __DIR__ . '/../var/cache/twig',
        ]);

        // Vite compila a public/build, que cuelga del document root (public/),
        // de modo que el navegador puede descargar los assets en /build/...
        $vite = new Vite(
            hotFile: __DIR__ . '/../public/hot',
            manifestPath: __DIR__ . '/../public/build/.vite/manifest.json',
            buildBase: '/build/',
        );
        $twig->addExtension(new ViteExtension($vite));

        return $twig;
    },
];
