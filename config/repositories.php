<?php

declare(strict_types=1);

use App\Domain\Task\TaskRepository;
use App\Infrastructure\Persistence\PdoTaskRepository;

use function DI\autowire;

return [

    TaskRepository::class => autowire(PdoTaskRepository::class),

];
