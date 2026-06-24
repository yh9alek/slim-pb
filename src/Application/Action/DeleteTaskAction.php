<?php

declare(strict_types=1);

namespace App\Application\Action;

use App\Application\Service\TaskService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Override;

final class DeleteTaskAction extends Action
{
    public function __construct(
        private readonly TaskService $service
    ) {}

    #[Override]
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->service->delete(
            (int) $args['id']
        );

        return $this->noContent($response);
    }
}
