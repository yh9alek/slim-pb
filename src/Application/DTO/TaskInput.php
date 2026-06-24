<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Task\Exception\TaskValidationException;

final class TaskInput
{
    public function __construct(
        public string $title,
        public bool $completed,
    ) {}

    /**
     * @param array<string, mixed> $data
     * @throws TaskValidationException
     */
    public static function validate(array $data): self
    {
        $title = $data['title'] ?? '';

        if (trim($title) === '') {
            throw new TaskValidationException('El título es obligatorio.');
        }

        if (mb_strlen($title) > 255) {
            throw new TaskValidationException('El campo "title" no puede superar 255 caracteres.');
        }

        return new self(
            $title,
            (bool) ($data['completed'] ?? false),
        );
    }
}
