<?php

declare(strict_types=1);

namespace App\Application\Http\Controller;

use App\Application\DTO\TaskInput;
use App\Application\Service\TaskService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

// Controlador WEB: devuelve HTML renderizado con Twig.
final class TaskWebController
{
    public function __construct(
        private readonly TaskService $service,
        private readonly Twig $twig,
    ) {}

    public function index(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'pages/tasks/index.twig', [
            'tasks' => $this->service->list(),
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'pages/tasks/create.twig');
    }
}
