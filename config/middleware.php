<?php

declare(strict_types=1);

use App\Application\Handler\HttpErrorHandler;
use App\Application\Middleware\JsonBodyParserMiddleware;
use App\Application\Settings\SettingsInterface;
use Psr\Log\LoggerInterface;
use Slim\App;

return function (App $app, HttpErrorHandler $errorHandler): void {
    $container = $app->getContainer();
    $settings = $container->get(SettingsInterface::class);

    $app->add(JsonBodyParserMiddleware::class);
    $app->addRoutingMiddleware();

    $errorMiddleware = $app->addErrorMiddleware(
        (bool) $settings->get('displayErrorDetails'),
        true,
        true,
        $container->get(LoggerInterface::class),
    );
    $errorMiddleware->setDefaultErrorHandler($errorHandler);
};
