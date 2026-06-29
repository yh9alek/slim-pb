<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Domain\Shared\ValidationException;

// Excepción concreta y genérica que lanza el validador cuando un DTO
// no cumple sus restricciones. El HttpErrorHandler la captura por su
// tipo base (ValidationException) y responde con 422.
final class ValidationFailedException extends ValidationException {}
