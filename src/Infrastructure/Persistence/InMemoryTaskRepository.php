<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Shared\NotFoundException;
use App\Domain\Task\Task;
use App\Domain\Task\TaskRepository;

final class InMemoryTaskRepository implements TaskRepository
{
    /** @var array<int, Task> */
    private array $tasks = [];

    private int $nextId = 1;

    public function findAll(): array
    {
        return array_values($this->tasks);
    }

    public function findById(int $id): Task
    {
        return $this->tasks[$id] ?? throw new NotFoundException('Tarea no encontrada.');
    }

    public function save(Task $task): Task
    {
        $id = $this->nextId++;
        $stored = new Task($id, $task->title, $task->completed);
        $this->tasks[$id] = $stored;

        return $stored;
    }

    public function update(Task $task): Task
    {
        $this->tasks[(int) $task->id] = $task;

        return $task;
    }

    public function delete(int $id): void
    {
        unset($this->tasks[$id]);
    }
}
