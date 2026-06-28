<?php

declare(strict_types=1);

namespace App\Application\Throttle;

// Almacén de ventana fija sobre el sistema de archivos. Sin dependencias ni
// base de datos: un archivo por clave con "conteo|expiración", y flock para
// que el incremento sea atómico entre procesos.
final readonly class FileRateLimiterStore implements RateLimiterStore
{
    public function __construct(private string $directory) {}

    public function hit(string $key, int $window): RateLimit
    {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0775, true);
        }

        $file = $this->directory . '/' . sha1($key) . '.txt';
        $now = time();

        $handle = fopen($file, 'c+');
        if ($handle === false) {
            // Si el almacén no está disponible, no bloqueamos al usuario.
            return new RateLimit(1, $window);
        }

        flock($handle, LOCK_EX);

        $hits = 0;
        $expiresAt = 0;
        $contents = stream_get_contents($handle);

        if (is_string($contents) && $contents !== '') {
            [$storedHits, $storedExpiry] = array_pad(explode('|', $contents, 2), 2, '0');
            $hits = (int) $storedHits;
            $expiresAt = (int) $storedExpiry;
        }

        // Ventana fija: si expiró (o no existía), reiniciamos el contador.
        if ($expiresAt <= $now) {
            $hits = 0;
            $expiresAt = $now + $window;
        }

        ++$hits;

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, $hits . '|' . $expiresAt);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return new RateLimit($hits, max(0, $expiresAt - $now));
    }
}
