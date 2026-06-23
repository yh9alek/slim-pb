<?php

declare(strict_types=1);

namespace App\Domain\Task;

use JsonSerializable;

// Entidad de dominio: inmutable y sin saber nada de HTTP ni de SQL.
final readonly class Task implements JsonSerializable
{
    public function __construct(
        public ?int $id,
        public string $title,
        public bool $completed = false,
    ) { }

    public function markCompleted(): self
    {
        return new self($this->id, $this->title, true);
    }

    /**
     * @return array{id: int|null, title: string, completed: bool}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'completed' => $this->completed,
        ];
    }
}
