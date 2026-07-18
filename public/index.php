<?php

declare(strict_types=1);

use App\Application\Core\Handler\HttpErrorHandler;
use App\Application\Core\Handler\ShutdownHandler;
use App\Application\Core\Settings\SettingsInterface;
use Dotenv\Dotenv;
use Psr\Log\LoggerInterface;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\ResponseEmitter;
use Slim\Views\Twig;

require __DIR__ . '/../vendor/autoload.php';

# 1. Variables de entorno
Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

# 2. App ya cableada (contenedor, middleware, rutas, manejador de errores).
$app = (require __DIR__ . '/../bootstrap/app.php')();

$container = $app->getContainer();
assert($container !== null);

$displayErrorDetails = (bool) $container->get(SettingsInterface::class)->get('displayErrorDetails');

# 3. Construimos la petición desde las globales.
$request = ServerRequestCreatorFactory::create()->createServerRequestFromGlobals();

# 4. Red de seguridad para errores fatales que escapan al ErrorMiddleware.
$errorHandler = new HttpErrorHandler(
    $app->getCallableResolver(),
    $app->getResponseFactory(),
    $container->get(Twig::class),
    $container->get(LoggerInterface::class),
);
register_shutdown_function(new ShutdownHandler($request, $errorHandler, $displayErrorDetails));
ini_set('display_errors', '0');

# 5. Procesar y emitir: ruta -> middleware -> acción -> respuesta
$response = $app->handle($request);
(new ResponseEmitter())->emit($response);
