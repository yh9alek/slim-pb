<?php

declare(strict_types=1);

// Los tests de arquitectura blindan las reglas de capas que hemos cuidado:
// el dominio no debe conocer ni el framework ni la persistencia.
arch('el dominio se mantiene agnóstico')
    ->expect('App\Domain')
    ->not->toUse(['Slim', 'PDO', 'App\Infrastructure', 'App\Application']);

arch('las acciones no acceden a PDO directamente')
    ->expect('App\Application\Http\Action')
    ->not->toUse('PDO');

arch('no quedan funciones de depuración en el código')
    ->expect(['dd', 'dump', 'var_dump', 'ray'])
    ->not->toBeUsed();
