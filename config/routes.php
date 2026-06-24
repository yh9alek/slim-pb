<?php

declare(strict_types=1);

use App\Application\Action\TaskController;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

// El router asocia método + URI a una acción (resuelta desde el contenedor).
return function (App $app): void {

    # Ruta Raíz
    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('JALANDO. 👍');
        return $response;
    });

    $app->group('/tasks', function (Group $group): void {

        $group->get('',                [TaskController::class, 'index']);
        $group->get('/{id:[0-9]+}',    [TaskController::class, 'show']);
        $group->post('',               [TaskController::class, 'store']);
        $group->put('/{id:[0-9]+}',    [TaskController::class, 'update']);
        $group->delete('/{id:[0-9]+}', [TaskController::class, 'destroy']);

    });
};
