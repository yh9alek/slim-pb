<?php

declare(strict_types=1);

use App\Application\Core\Validation\SymfonyValidator;
use App\Application\Core\Validation\Validator;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function DI\autowire;

// Definiciones de validación. Se mantienen aparte (como repositories.php
// o views.php) para que cada módulo de configuración tenga una sola
// responsabilidad.
return [

    // Validador de Symfony con mapeo por atributos (#[Assert\*]).
    ValidatorInterface::class => fn(): ValidatorInterface
        => Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator(),

    // Nuestro contrato -> adaptador que envuelve a Symfony.
    // autowire inyecta el ValidatorInterface de arriba.
    Validator::class => autowire(SymfonyValidator::class),

];
