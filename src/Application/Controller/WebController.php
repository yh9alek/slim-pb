<?php

declare(strict_types=1);

namespace App\Application\Controller;

use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class WebController
{
    public function __construct(
        private readonly Twig $twig
    ){
    }

    public function home(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'home/home.twig');
    }
}
