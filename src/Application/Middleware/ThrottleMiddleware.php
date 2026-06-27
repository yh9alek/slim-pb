<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Throttle\RateLimiterStore;
use App\Application\Throttle\ThrottleException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Log\LoggerInterface;

// Limita las peticiones por IP: $limit peticiones cada $window segundos.
// Al superarlo lanza un 429.
final class ThrottleMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RateLimiterStore $store,
        private readonly LoggerInterface $logger,
        private readonly int $limit,
        private readonly int $window,
        private readonly string $name = 'global',
    ) {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $ip = $this->clientIp($request);
        $result = $this->store->hit(sprintf('throttle:%s:%s', $this->name, $ip), $this->window);

        if ($result->hits > $this->limit) {
            if ($result->hits === $this->limit + 1) {
                $this->logger->warning('Límite de peticiones excedido', [
                    'ip' => $ip,
                    'limit' => $this->limit,
                    'window' => $this->window,
                ]);
            }

            throw new ThrottleException($request, $result->retryAfter, $this->limit);
        }

        $response = $handler->handle($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->limit)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $this->limit - $result->hits));
    }

    private function clientIp(Request $request): string
    {
        $server = $request->getServerParams();

        return isset($server['REMOTE_ADDR']) && is_string($server['REMOTE_ADDR'])
            ? $server['REMOTE_ADDR']
            : 'unknown';
    }
}
