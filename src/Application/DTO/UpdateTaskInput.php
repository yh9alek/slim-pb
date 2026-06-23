<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Task\Exception\TaskValidationException;

final class UpdateTaskInput {

    public function __construct(
        public string $title,
        public bool $completed,
    ) { }

    public static function validate(array $data): self {
        $title = $data['title'] ?? '';

        if(trim($title) === '') {
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
