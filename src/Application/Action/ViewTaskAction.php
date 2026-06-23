<?php

declare(strict_types=1);

namespace App\Application\Action;

use App\Application\Service\TaskService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Override;

final class ViewTaskAction extends Action
{
    public function __construct(
        private readonly TaskService $service
    ) { }

    #[Override]
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $task = $this->service->find((int) $args['id']);

        return $this->json($response, ['data' => $task]);
    }
}
