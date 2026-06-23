<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Domain\Shared\NotFoundException;
use App\Domain\Shared\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpException;
use Slim\Handlers\ErrorHandler;

// Convierte excepciones de dominio en códigos HTTP coherentes.
final class HttpErrorHandler extends ErrorHandler
{
    protected function respond(): Response
    {
        $exception = $this->exception;

        [$status, $message] = match (true) {
            $exception instanceof NotFoundException    => [404, $exception->getMessage()],
            $exception instanceof ValidationException  => [422, $exception->getMessage()],
            $exception instanceof HttpException        => [$exception->getCode(), $exception->getMessage()],
            default                                    => [500, 'Error interno del servidor.'],
        };

        $payload = [
            'error' => [
                'status' => $status,
                'message' => $message,
            ],
        ];

        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );

        return $response->withHeader('Content-Type', 'application/json');
    }
}
