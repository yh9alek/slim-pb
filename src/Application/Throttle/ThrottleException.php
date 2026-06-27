<?php

declare(strict_types=1);

namespace App\Application\Throttle;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpException;

// 429 que transporta el contexto del límite para que el manejador de errores
// pueda emitir Retry-After y X-RateLimit-*.
final class ThrottleException extends HttpException
{
    protected $code = 429;
    protected string $title = '429 Too Many Requests';
    protected string $description = 'Has enviado demasiadas peticiones en poco tiempo.';

    public function __construct(
        Request $request,
        public readonly int $retryAfter,
        public readonly int $limit,
    ) {
        parent::__construct($request, 'Demasiadas peticiones.');
    }
}
