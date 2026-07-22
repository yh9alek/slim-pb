<?php

declare(strict_types=1);

namespace App\Application\Http\Controller;

use App\Application\DTO\TaskInput;
use App\Application\Service\TaskService;
use App\Application\Http\Api;
use App\Application\Core\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class TaskController extends Api
{
    public function __construct(
        private readonly TaskService $service,
        private readonly Validator $validator,
    ) {}

    public function index(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'data' => $this->service->list(),
        ]);
    }

    /**
     * @param array<string, string> $args Parámetros de ruta
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        return $this->json($response, [
            'data' => $this->service->find((int) $args['id']),
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $input = TaskInput::get((array) $request->getParsedBody());

        // Si falla, lanza ValidationException -> HttpErrorHandler -> 422 JSON.
        $this->validator->validate($input);

        return $this->json($response, [
            'data' => $this->service->create($input),
            'msg'  => 'Se ha creado la tarea con éxito.',
        ], 201);
    }

    /**
     * @param array<string, string> $args Parámetros de ruta
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $this->service->find((int) $args['id']);

        $input = TaskInput::get((array) $request->getParsedBody());
        $this->validator->validate($input);

        $updated = $this->service->update(
            (int) $args['id'],
            $input,
        );

        return $this->json($response, [
            'data' => $updated,
            'msg'  => 'Se actualizó la tarea con éxito.',
        ]);
    }

    /**
     * @param array<string, string> $args Parámetros de ruta
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $this->service->delete(
            (int) $args['id'],
        );

        return $this->noContent($response);
    }
}
