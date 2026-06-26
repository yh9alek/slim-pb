<?php

declare(strict_types=1);

use App\Application\Handler\HttpErrorHandler;
use App\Application\Middleware\JsonBodyParserMiddleware;
use App\Application\Middleware\WellKnownProbeMiddleware;
use App\Application\Settings\SettingsInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

return function (App $app, HttpErrorHandler $errorHandler): void {
    $container = $app->getContainer();
    $settings = null;

    if ($container !== null) {
        $settings = $container->get(SettingsInterface::class);

        $app->add(JsonBodyParserMiddleware::class);
        $app->addRoutingMiddleware();

        // Habilita Twig en la app
        $app->add(TwigMiddleware::createFromContainer($app, Twig::class));

        $errorMiddleware = $app->addErrorMiddleware(
            (bool) $settings->get('displayErrorDetails'),
            true,
            true,
            $container->get(LoggerInterface::class),
        );
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        // El más externo: descarta el ruido de DevTools (/.well-known/appspecific/*)
        // antes del enrutado, para que no se registre como 404 en el log.
        $app->add(new WellKnownProbeMiddleware($app->getResponseFactory()));
    }
};
