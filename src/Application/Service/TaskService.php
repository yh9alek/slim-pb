<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\CreateTaskInput;
use App\Application\DTO\UpdateTaskInput;
use App\Domain\Task\Task;
use App\Domain\Task\TaskRepository;
use Psr\Log\LoggerInterface;

// Lógica de negocio / casos de uso. Depende de ABSTRACCIONES
// (la interfaz del repositorio), nunca de PDO ni de Slim.
// Es reutilizable desde HTTP, CLI o una cola de trabajos.
final readonly class TaskService
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly LoggerInterface $logger,
    ) { }

    /**
     * @return Task[]
     */
    public function list(): array
    {
        return $this->tasks->findAll();
    }

    public function find(int $id): Task
    {
        return $this->tasks->findById($id);
    }

    public function create(CreateTaskInput $input): Task
    {
        $saved = $this->tasks->save(new Task(id: null, title: $input->title));

        $this->logger->info('Tarea creada', ['id' => $saved->id]);

        return $saved;
    }

    public function update(int $id, UpdateTaskInput $input): Task {

        $existing = $this->tasks->findById($id);

        $new = new Task(
            $existing->id,
            $input->title,
            $input->completed,
        );

        $updated = $this->tasks->update($new);

        $this->logger->info('Tarea actualizada', ['id' => $updated->id]);

        return $updated;
    }

    public function delete(int $id): void {
        $this->tasks->findById($id);
        $this->tasks->delete($id);

        $this->logger->info('Tarea eliminada', ['id' => $id]);
    }
}
