<?php

declare(strict_types=1);

use App\Domain\Task\TaskRepository;
use App\Infrastructure\Persistence\InMemoryTaskRepository;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\TestCase;

use function DI\autowire;

// Vincula el TestCase base a todos los tests de Unit y Feature.
uses(TestCase::class)->in('Feature', 'Unit');

// En tests no queremos que el throttling nos corte (muchas peticiones seguidas)
// ni que se active la compilación de producción.
$_ENV['APP_ENV'] = 'test';
$_ENV['THROTTLE_LIMIT'] = '1000000';

/**
 * App de pruebas: se construye con el repositorio en memoria, así los tests
 * de Feature ejercitan toda la pila HTTP (router, middleware, validación,
 * manejador de errores) sin tocar la base de datos.
 */
function testApp(): App
{
    return (require dirname(__DIR__) . '/bootstrap/app.php')([
        TaskRepository::class => autowire(InMemoryTaskRepository::class),
    ]);
}

/**
 * Lanza una petición JSON contra la app y devuelve la respuesta PSR-7.
 *
 * @param array<string, mixed>|null $body
 */
function apiRequest(App $app, string $method, string $uri, ?array $body = null): ResponseInterface
{
    $request = (new ServerRequestFactory())
        ->createServerRequest($method, $uri)
        ->withHeader('Accept', 'application/json');

    if ($body !== null) {
        $request = $request->withHeader('Content-Type', 'application/json');
        $request->getBody()->write((string) json_encode($body));
        $request->getBody()->rewind();
    }

    return $app->handle($request);
}

/**
 * Decodifica el cuerpo JSON de una respuesta.
 *
 * @return array<string, mixed>
 */
function jsonBody(ResponseInterface $response): array
{
    return (array) json_decode((string) $response->getBody(), true);
}
