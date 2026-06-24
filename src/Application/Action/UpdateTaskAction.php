<?php

declare(strict_types=1);

namespace App\Application\Action;

use App\Application\DTO\TaskInput;
use App\Application\Service\TaskService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Override;

final class UpdateTaskAction extends Action
{
    public function __construct(
        private readonly TaskService $service
    ) {}

    #[Override]
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $input = TaskInput::validate((array) $request->getParsedBody());
        $task  = $this->service->update((int) $args['id'], $input);

        return $this->json($response, [
            'data' => $task,
            'msg'  => 'Se ha actualizado la tarea con éxito.',
        ]);
    }
}
