<?php

declare(strict_types=1);

namespace App\Application\Core\Asset;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

// Expone la función vite('resources/js/app.js') a las plantillas.
final class ViteExtension extends AbstractExtension
{
    public function __construct(private readonly Vite $vite) {}

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            // is_safe html: la salida son etiquetas, no debe autoescaparse.
            new TwigFunction('vite', $this->vite->tags(...), ['is_safe' => ['html']]),
        ];
    }
}
