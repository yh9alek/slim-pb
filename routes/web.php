<?php

declare(strict_types=1);


use App\Application\Controller\WebController;
use App\Application\Controller\TaskWebController;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\App;

# Rutas que devuelven HTML (SSR con Twig), separadas de la API JSON.

return function (App $app): void {

    $app->get('/', [WebController::class, 'home']);

    # HEALTHCHECK
    $app->get('/health', fn (Request $request, Response $response): Response => $response->withStatus(204));

    # TASKS
    $app->get('/tasks', [TaskWebController::class, 'index']);
};
