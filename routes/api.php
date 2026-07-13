<?php

declare(strict_types=1);

use App\Application\Http\Controller\TaskController;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

# Rutas de API (JSON)

return function (App $app): void {

    $app->group('/api/tasks', function (Group $group): void {

        $group->get('',                [TaskController::class, 'index']);
        $group->get('/{id:[0-9]+}',    [TaskController::class, 'show']);
        $group->post('',               [TaskController::class, 'store']);
        $group->put('/{id:[0-9]+}',    [TaskController::class, 'update']);
        $group->delete('/{id:[0-9]+}', [TaskController::class, 'destroy']);

    });
};
