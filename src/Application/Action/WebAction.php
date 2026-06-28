<?php

declare(strict_types=1);

namespace App\Application\Action;

use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class WebAction
{
    public function __construct(
        private readonly Twig $twig,
    ) {}

    public function __invoke(Response $response, Request $request): Response
    {
        return $this->twig->render($response, '');
    }
}
