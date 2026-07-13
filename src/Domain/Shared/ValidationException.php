<?php

declare(strict_types=1);

namespace App\Domain\Shared;

// Categoría de error de validación. Sigue siendo abstracta: solo se
// instancian sus subtipos concretos. Ahora transporta, además del
// mensaje, un mapa campo => mensajes para alimentar APIs y formularios.
/** @phpstan-consistent-constructor */
abstract class ValidationException extends \InvalidArgumentException
{
    /** @var array<string, list<string>> */
    private array $errors = [];

    /**
     * Crea la excepción a partir de un mapa de errores por campo.
     *
     * Se usa `static` para que las subclases concretas
     * (p. ej. ValidationFailedException) sean el tipo devuelto.
     *
     * @param array<string, list<string>> $errors
     */
    public static function withErrors(
        array $errors,
        string $message = 'Los datos enviados no son válidos.',
    ): static {
        $exception = new static($message);
        $exception->errors = $errors;

        return $exception;
    }

    /**
     * Mapa campo => lista de mensajes. Vacío si la validación
     * falló sin detalle por campo.
     *
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
