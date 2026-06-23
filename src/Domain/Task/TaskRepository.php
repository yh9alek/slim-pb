<?php

declare(strict_types=1);

namespace App\Domain\Task;

use App\Domain\Task\Exception\TaskNotFoundException;

// Contrato de persistencia. El dominio define QUÉ necesita;
// la infraestructura decide CÓMO se cumple.
interface TaskRepository
{
    /**
     * @return Task[]
     */
    public function findAll(): array;

    /**
     * @throws TaskNotFoundException
     */
    public function findById(int $id): Task;

    public function save(Task $task): Task;

    public function update(Task $task): Task;

    public function delete(int $id): void;
}
