<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->app = testApp();
});

it('devuelve 200 y una lista vacía cuando no hay tareas', function (): void {
    $response = apiRequest($this->app, 'GET', '/api/tasks');

    expect($response->getStatusCode())->toBe(200)
        ->and(jsonBody($response)['data'])->toBe([]);
});

it('crea una tarea y devuelve 201', function (): void {
    $response = apiRequest($this->app, 'POST', '/api/tasks', ['title' => 'Comprar pan']);

    expect($response->getStatusCode())->toBe(201);

    $body = jsonBody($response);
    expect($body['data']['id'])->toBe(1)
        ->and($body['data']['title'])->toBe('Comprar pan')
        ->and($body['data']['completed'])->toBeFalse()
        ->and($body)->toHaveKey('msg');
});

it('rechaza un título vacío con 422 y errores por campo', function (): void {
    $response = apiRequest($this->app, 'POST', '/api/tasks', ['title' => '']);

    expect($response->getStatusCode())->toBe(422);

    $body = jsonBody($response);
    expect($body['error']['status'])->toBe(422)
        ->and($body['error']['errors'])->toHaveKey('title');
});

it('muestra una tarea existente', function (): void {
    apiRequest($this->app, 'POST', '/api/tasks', ['title' => 'Tarea']);

    $response = apiRequest($this->app, 'GET', '/api/tasks/1');

    expect($response->getStatusCode())->toBe(200)
        ->and(jsonBody($response)['data']['title'])->toBe('Tarea');
});

it('devuelve 404 al pedir una tarea inexistente', function (): void {
    $response = apiRequest($this->app, 'GET', '/api/tasks/999');

    expect($response->getStatusCode())->toBe(404)
        ->and(jsonBody($response)['error']['status'])->toBe(404);
});

it('actualiza una tarea existente', function (): void {
    apiRequest($this->app, 'POST', '/api/tasks', ['title' => 'Borrador']);

    $response = apiRequest($this->app, 'PUT', '/api/tasks/1', [
        'title' => 'Final',
        'completed' => true,
    ]);

    expect($response->getStatusCode())->toBe(200);

    $body = jsonBody($response);
    expect($body['data']['title'])->toBe('Final')
        ->and($body['data']['completed'])->toBeTrue();
});

it('devuelve 404 al actualizar una tarea inexistente', function (): void {
    $response = apiRequest($this->app, 'PUT', '/api/tasks/999', ['title' => 'X']);

    expect($response->getStatusCode())->toBe(404);
});

it('elimina una tarea (204) y luego ya no la encuentra (404)', function (): void {
    apiRequest($this->app, 'POST', '/api/tasks', ['title' => 'Borrar']);

    $deleted = apiRequest($this->app, 'DELETE', '/api/tasks/1');
    expect($deleted->getStatusCode())->toBe(204);

    $missing = apiRequest($this->app, 'GET', '/api/tasks/1');
    expect($missing->getStatusCode())->toBe(404);
});

it('devuelve 404 al eliminar una tarea inexistente', function (): void {
    $response = apiRequest($this->app, 'DELETE', '/api/tasks/999');

    expect($response->getStatusCode())->toBe(404);
});

it('responde 204 en el healthcheck', function (): void {
    $response = apiRequest($this->app, 'GET', '/health');

    expect($response->getStatusCode())->toBe(204);
});
