<?php

declare(strict_types=1);

use App\Application\Action\TaskWebController;
use Slim\App;

# Rutas que devuelven HTML (SSR con Twig), separadas de la API JSON.

return function (App $app): void {
    $app->get('/tasks',  [TaskWebController::class, 'index']);
    $app->get('/others', [TaskWebController::class, 'others']);
};
