<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class DatabaseSeeder extends AbstractSeed
{
    public function getDependencies(): array
    {
        return [
            'TaskSeeder',
        ];
    }

    public function run(): void {}
}
