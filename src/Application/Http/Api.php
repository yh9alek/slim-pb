<?php

declare(strict_types=1);

namespace App\Application\Http;

use Psr\Http\Message\ResponseInterface as Response;

// Acción base: centraliza la serialización JSON para no repetirla.
abstract class Api
{
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
