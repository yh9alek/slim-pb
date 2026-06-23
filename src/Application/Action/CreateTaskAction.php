<?php

declare(strict_types=1);

namespace App\Application\Action;

use App\Application\DTO\CreateTaskInput;
use App\Application\Service\TaskService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Override;

// El controlador solo orquesta: arma el DTO, delega en el servicio
// y devuelve la respuesta. Sin lógica de negocio ni SQL.
final class CreateTaskAction extends Action
{
    public function __construct(
        private readonly TaskService $service
    ) { }

    #[Override]
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $input = CreateTaskInput::validate(
            (array) $request->getParsedBody()
        );

        return $this->json($response, [
            'data' => $this->service->create($input),
            'msg'  => 'Se creó la tarea con éxito.',
        ], 201);
    }
}
