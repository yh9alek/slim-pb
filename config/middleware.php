<?php

declare(strict_types=1);

use App\Application\Core\Handler\HttpErrorHandler;
use App\Application\Core\Middleware\JsonBodyParserMiddleware;
use App\Application\Core\Middleware\ThrottleMiddleware;
use App\Application\Core\Middleware\WellKnownProbeMiddleware;
use App\Application\Core\Settings\SettingsInterface;
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

        // Throttling
        $app->add(ThrottleMiddleware::class);

        $errorMiddleware = $app->addErrorMiddleware(
            (bool) $settings->get('displayErrorDetails'),
            true,
            true,
            $container->get(LoggerInterface::class),
        );
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        // Descarta el ruido de DevTools (/.well-known/appspecific/*)
        // antes del enrutado, para que no se registre como 404 en el log.
        $app->add(new WellKnownProbeMiddleware($app->getResponseFactory()));
    }
};
