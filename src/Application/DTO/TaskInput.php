<?php

declare(strict_types=1);

namespace App\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

// El DTO ya no valida: solo DECLARA sus reglas mediante atributos.
// La validación la ejecuta el servicio Validator (Symfony), de forma
// que el "qué" (reglas) y el "cómo" (motor) quedan separados.
final class TaskInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'El título es obligatorio.')]
        #[Assert\Length(
            max: 255,
            maxMessage: 'El título no puede superar {{ limit }} caracteres.',
        )]
        public string $title = '',
        public bool $completed = false,
    ) {}

    /**
     * Mapea datos crudos de la petición al DTO (sin validar todavía).
     *
     * @param array<string, mixed> $data
     */
    public static function get(array $data): self
    {
        return new self(
            title: trim((string) ($data['title'] ?? '')),
            completed: (bool) @($data['completed'] ?? false),
        );
    }
}
