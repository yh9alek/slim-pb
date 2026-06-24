<?php

declare(strict_types=1);

namespace App\Application\Settings;

use InvalidArgumentException;

final readonly class Settings implements SettingsInterface
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(private array $settings) {}

    public function get(string $key): mixed
    {
        return $this->settings[$key]
            ?? throw new InvalidArgumentException(sprintf('Ajuste no definido: "%s".', $key));
    }
}
