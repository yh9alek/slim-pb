<?php

declare(strict_types=1);

use App\Application\DTO\TaskInput;
use App\Application\Service\TaskService;
use App\Domain\Task\Exception\TaskNotFoundException;
use App\Domain\Task\Exception\TaskValidationException;
use App\Infrastructure\Persistence\InMemoryTaskRepository;
use Psr\Log\NullLogger;

beforeEach(function (): void {
    // El repositorio en memoria nos da un servicio testeable sin tocar la BD.
    $this->service = new TaskService(new InMemoryTaskRepository(), new NullLogger());
});

it('crea una tarea y le asigna un id', function (): void {
    $task = $this->service->create(new TaskInput('Comprar pan'));

    expect($task->id)->toBe(1)
        ->and($task->title)->toBe('Comprar pan')
        ->and($task->completed)->toBeFalse();
});

it('lista las tareas creadas', function (): void {
    $this->service->create(new TaskInput('Tarea 1'));
    $this->service->create(new TaskInput('Tarea 2'));

    expect($this->service->list())->toHaveCount(2);
});

it('actualiza una tarea existente', function (): void {
    $created = $this->service->create(new TaskInput('Borrador'));

    $updated = $this->service->update($created->id, new TaskInput('Final', completed: true));

    expect($updated->title)->toBe('Final')
        ->and($updated->completed)->toBeTrue();
});

it('lanza una excepción al buscar una tarea inexistente', function (): void {
    $this->service->find(999);
})->throws(TaskNotFoundException::class);

it('rechaza un título vacío al construir el DTO', function (): void {
    TaskInput::get(['title' => '   ']);
})->throws(TaskValidationException::class);
