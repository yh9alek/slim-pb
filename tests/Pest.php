<?php

declare(strict_types=1);

use Tests\TestCase;

// Vincula el TestCase base a todos los tests de Unit y Feature.
uses(TestCase::class)->in('Feature', 'Unit');
