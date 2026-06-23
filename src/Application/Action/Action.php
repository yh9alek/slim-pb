<?php

declare(strict_types=1);

namespace App\Application\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Acción base: centraliza la serialización JSON para no repetirla.
abstract class Action
{
    /**
     * @param array<string, string> $args
     */
    abstract public function __invoke(Request $request, Response $response, array $args): Response;

    protected function json(Response $response, mixed $data, int $status = 200): Response
    {
        $payload = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );

        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    protected function noContent(Response $response): Response
    {
        return $response->withStatus(204);
    }
}
