<?php

declare(strict_types=1);

namespace App\Application\Core\Throttle;

// Contrato del almacén de contadores. Permite cambiar la implementación
// (disco, APCu, Redis, PDO...) sin tocar el middleware.
interface RateLimiterStore
{
    // Registra un golpe para $key dentro de una ventana de $window segundos
    // y devuelve el estado resultante.
    public function hit(string $key, int $window): RateLimit;
}
