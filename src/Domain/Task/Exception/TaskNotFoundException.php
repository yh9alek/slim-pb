<?php

declare(strict_types=1);

namespace App\Domain\Task\Exception;

use App\Domain\Shared\NotFoundException;

final class TaskNotFoundException extends NotFoundException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('No existe una tarea con el id %d.', $id));
    }
}
