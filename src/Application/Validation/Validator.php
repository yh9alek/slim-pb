<?php

declare(strict_types=1);

namespace App\Application\Validation;

use App\Domain\Shared\ValidationException;

// Contrato de validación de la aplicación. El dominio y los
// controladores dependen de ESTA abstracción, no de Symfony.
// Cambiar de librería = cambiar solo la implementación.
interface Validator
{
    /**
     * Valida un objeto contra las restricciones declaradas en él
     * (atributos, en la implementación de Symfony).
     *
     * @throws ValidationException si hay uno o más errores.
     */
    public function validate(object $input): void;
}
