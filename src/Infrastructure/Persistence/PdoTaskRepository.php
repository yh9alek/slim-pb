<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Task\Exception\TaskNotFoundException;
use App\Domain\Task\Task;
use App\Domain\Task\TaskRepository;
use PDO;

// Implementación concreta del contrato del dominio sobre PDO.
// Todo el SQL queda confinado aquí.
final readonly class PdoTaskRepository implements TaskRepository
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * returns Task[]
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, title, completed FROM tasks ORDER BY id;');

        if ($stmt === false) {
            return [];
        }

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function findById(int $id): Task
    {
        $stmt = $this->pdo->prepare('SELECT id, title, completed FROM tasks WHERE id = :id;');

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        if ($row === false) {
            throw TaskNotFoundException::withId($id);
        }

        return $this->hydrate($row);
    }

    public function save(Task $task): Task
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tasks (title, completed) VALUES (:title, :completed);',
        );

        $stmt->bindValue(':title',     $task->title,     PDO::PARAM_STR);
        $stmt->bindValue(':completed', $task->completed, PDO::PARAM_BOOL);
        $stmt->execute();

        return new Task(
            id: (int) $this->pdo->lastInsertId(),
            title: $task->title,
            completed: $task->completed,
        );
    }

    public function update(Task $task): Task
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tasks SET title = :title, completed = :completed WHERE id = :id;',
        );

        $stmt->bindValue(':title',     $task->title,     PDO::PARAM_STR);
        $stmt->bindValue(':completed', $task->completed, PDO::PARAM_BOOL);
        $stmt->bindValue(':id',        $task->id,        PDO::PARAM_INT);

        $stmt->execute();

        return $task;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id;');

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * @param array{id: int|string, title: string, completed: int|string} $row
     */
    private function hydrate(array $row): Task
    {
        return new Task(
            id: (int) $row['id'],
            title: (string) $row['title'],
            completed: (bool) $row['completed'],
        );
    }
}
