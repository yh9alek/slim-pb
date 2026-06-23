<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Task\Exception\TaskValidationException;

// DTO inmutable: valida y transporta la entrada desde el borde HTTP
// hacia el servicio, sin acoplar el dominio al formato de la petición.
final readonly class CreateTaskInput
{
    public function __construct(public string $title) { }

    /**
     * @param array<string, mixed> $data
     *
     * @throws TaskValidationException
     */
    public static function validate(array $data): self
    {
        $title = $data['title'] ?? '';

        if(trim($title) === '') {
            throw new TaskValidationException('El título es obligatorio.');
        }

        if (mb_strlen($title) > 255) {
            throw new TaskValidationException('El campo "title" no puede superar 255 caracteres.');
        }

        return new self($title);
    }
}
