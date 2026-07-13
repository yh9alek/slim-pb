<?php

declare(strict_types=1);

namespace App\Application\Core\Throttle;

// Estado de la ventana tras registrar un golpe.
final readonly class RateLimit
{
    public function __construct(
        public int $hits,        // peticiones acumuladas en la ventana actual
        public int $retryAfter,  // segundos que faltan para que la ventana expire
    ) {}
}
