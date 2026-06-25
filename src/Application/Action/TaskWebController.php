<?php

declare(strict_types=1);

namespace App\Application\Action;

use App\Application\Service\TaskService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

// Controlador WEB: devuelve HTML renderizado con Twig.
// No extiende Action (no serializa JSON), pero reutiliza el MISMO TaskService
// que la API. Una lógica de negocio, dos presentaciones.
final class TaskWebController
{
    public function __construct(
        private readonly TaskService $service,
        private readonly Twig $twig,
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'tasks/index.twig', [
            'tasks' => $this->service->list(),
        ]);
    }

    public function others(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'others/index.twig');
    }
}
