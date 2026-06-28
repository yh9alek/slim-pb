<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

// Middleware PSR-15: corta en seco las sondas que el navegador lanza contra
// /.well-known/appspecific/* — por ejemplo, Chrome pide com.chrome.devtools.json
// al abrir las herramientas de desarrollador.

final class WellKnownProbeMiddleware implements MiddlewareInterface
{
    // Namespace reservado para configuración específica de herramientas del
    // navegador. Restringirlo a appspecific/ evita interferir con otros usos
    // legítimos de .well-known (security.txt, acme-challenge, etc.).
    private const PROBE_PREFIX = '/.well-known/appspecific/';

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    public function process(Request $request, Handler $handler): Response
    {
        if (str_starts_with($request->getUri()->getPath(), self::PROBE_PREFIX)) {
            return $this->responseFactory->createResponse(204);
        }

        return $handler->handle($request);
    }
}
