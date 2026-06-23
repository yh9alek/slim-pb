<?php

declare(strict_types=1);

use App\Application\Action\CreateTaskAction;
use App\Application\Action\DeleteTaskAction;
use App\Application\Action\ListTasksAction;
use App\Application\Action\UpdateTaskAction;
use App\Application\Action\ViewTaskAction;
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

        $group->get('',                ListTasksAction::class);
        $group->get('/{id:[0-9]+}',    ViewTaskAction::class);
        $group->post('',               CreateTaskAction::class);
        $group->put('/{id:[0-9]+}',    UpdateTaskAction::class);
        $group->delete('/{id:[0-9]+}', DeleteTaskAction::class);

    });
};
