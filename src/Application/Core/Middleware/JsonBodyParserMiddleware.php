<?php

declare(strict_types=1);

namespace App\Application\Core\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

// Middleware PSR-15: decodifica el cuerpo JSON una sola vez,
// de forma reutilizable para todas las rutas.
final class JsonBodyParserMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        if (str_contains($request->getHeaderLine('Content-Type'), 'application/json')) {
            $contents = (string) $request->getBody();

            if ($contents !== '') {
                $decoded = json_decode($contents, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $request = $request->withParsedBody($decoded);
                }
            }
        }

        return $handler->handle($request);
    }
}
