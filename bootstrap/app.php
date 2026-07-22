<?php

declare(strict_types=1);

use App\Application\Core\Handler\HttpErrorHandler;
use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;

return function (array $definitions = []): App {
    $root = dirname(__DIR__);

    $builder = new ContainerBuilder();
    $builder->addDefinitions(require $root . '/config/settings.php');
    $builder->addDefinitions(require $root . '/config/dependencies.php');
    $builder->addDefinitions(require $root . '/config/repositories.php');
    $builder->addDefinitions(require $root . '/config/validation.php');
    $builder->addDefinitions(require $root . '/config/views.php');

    if ($definitions !== []) {
        $builder->addDefinitions($definitions);
    }

    if (($_ENV['APP_ENV'] ?? 'dev') === 'prod') {
        $builder->enableCompilation($root . '/var/cache');
    }

    $container = $builder->build();

    AppFactory::setContainer($container);
    $app = AppFactory::create();

    $errorHandler = new HttpErrorHandler(
        $app->getCallableResolver(),
        $app->getResponseFactory(),
        $container->get(Twig::class),
        $container->get(LoggerInterface::class),
    );

    (require $root . '/config/middleware.php')($app, $errorHandler);
    (require $root . '/routes/api.php')($app);
    (require $root . '/routes/web.php')($app);

    return $app;
};
