<?php

declare(strict_types=1);

namespace App\Application\Core\Settings;

interface SettingsInterface
{
    public function get(string $key): mixed;
}
