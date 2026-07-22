<?php

declare(strict_types=1);

namespace App\Application\Core\Validation;

use Symfony\Component\Validator\Validator\ValidatorInterface;

// Adaptador: envuelve el validador de Symfony y traduce sus
// violaciones a nuestro contrato. Es el ÚNICO punto del código que
// conoce Symfony; el resto de la app solo ve la interfaz Validator.
final readonly class SymfonyValidator implements Validator
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {}

    public function validate(object $input): void
    {
        $violations = $this->validator->validate($input);

        if (count($violations) === 0) {
            return;
        }

        // Agrupamos por campo: title => ['msg1', 'msg2'], ...
        $errors = [];
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            $errors[$field][] = (string) $violation->getMessage();
        }

        throw ValidationFailedException::withErrors($errors);
    }
}
