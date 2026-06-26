<?php

declare(strict_types=1);

use App\Application\Handler\HttpErrorHandler;
use App\Application\Handler\ShutdownHandler;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\ResponseEmitter;
use Slim\Views\Twig;

require __DIR__ . '/../vendor/autoload.php';

# 1. Variables de entorno
Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

# 2. Contenedor de dependencias (PSR-11)
$builder = new ContainerBuilder();
$builder->addDefinitions(require __DIR__ . '/../config/settings.php');
$builder->addDefinitions(require __DIR__ . '/../config/dependencies.php');
$builder->addDefinitions(require __DIR__ . '/../config/repositories.php');
$builder->addDefinitions(require __DIR__ . '/../config/views.php');

if (($_ENV['APP_ENV'] ?? 'dev') === 'prod') {
    $builder->enableCompilation(__DIR__ . '/../var/cache');
}

$container = $builder->build();

# 3. La app Slim se construye a partir del contenedor
AppFactory::setContainer($container);
$app = AppFactory::create();

$displayErrorDetails = (bool) $container->get(SettingsInterface::class)->get('displayErrorDetails');

# 4. Construimos la petición desde las globales.
$request = ServerRequestCreatorFactory::create()->createServerRequestFromGlobals();

# 5. Un ÚNICO manejador de errores, compartido por el middleware y el shutdown.
$errorHandler = new HttpErrorHandler(
    $app->getCallableResolver(),
    $app->getResponseFactory(),
    $container->get(Twig::class),
    $container->get(LoggerInterface::class),
);

# 6. Red de seguridad para errores fatales que escapan al ErrorMiddleware.
register_shutdown_function(new ShutdownHandler($request, $errorHandler, $displayErrorDetails));

# 7. Pipeline de middleware, rutas de API y rutas web (HTML)
(require __DIR__ . '/../config/middleware.php')($app, $errorHandler);
(require __DIR__ . '/../routes/api.php')($app);
(require __DIR__ . '/../routes/web.php')($app);

# 8. Procesar y emitir: ruta -> middleware -> acción -> respuesta
$response = $app->handle($request);
(new ResponseEmitter())->emit($response);
