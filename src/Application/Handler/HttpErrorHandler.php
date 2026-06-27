<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Throttle\ThrottleException;
use App\Domain\Shared\NotFoundException;
use App\Domain\Shared\ValidationException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Handlers\ErrorHandler;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Views\Twig;
use Throwable;

// Convierte excepciones en respuestas HTTP:
//  - Navegación del navegador (Accept: text/html) -> vista HTML de error.
//  - API / AJAX (Accept: application/json o */*)   -> JSON, como siempre.
final class HttpErrorHandler extends ErrorHandler
{
    private const array MESSAGES = [
        400 => ['Solicitud incorrecta', 'La petición no se pudo procesar.'],
        401 => ['No autenticado', 'Necesitas iniciar sesión para ver esta página.'],
        403 => ['Acceso denegado', 'No tienes permiso para acceder a este recurso.'],
        404 => ['Página no encontrada', 'La página que buscas no existe o se ha movido.'],
        405 => ['Método no permitido', 'La acción solicitada no está permitida aquí.'],
        419 => ['La página expiró', 'Tu sesión ha caducado. Recarga e inténtalo de nuevo.'],
        429 => ['Demasiadas peticiones', 'Has hecho demasiadas solicitudes. Espera un momento.'],
        500 => ['Error interno', 'Algo salió mal por nuestra parte. Inténtalo más tarde.'],
        503 => ['Servicio no disponible', 'Estamos en mantenimiento. Vuelve en unos minutos.'],
    ];

    public function __construct(
        CallableResolverInterface $callableResolver,
        ResponseFactoryInterface $responseFactory,
        private readonly Twig $twig,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($callableResolver, $responseFactory, $logger);
    }

    protected function writeToErrorLog(): void
    {
        if ($this->exception instanceof ThrottleException) {
            return;
        }

        $this->logError($this->summarize($this->exception));
    }

    private function summarize(Throwable $exception): string
    {
        $summary = sprintf(
            "%s:\n%s\n(código %s) en %s:%d",
            $exception::class,
            $exception->getMessage(),
            (string) $exception->getCode(),
            $exception->getFile(),
            $exception->getLine(),
        );

        // Incluimos la causa (sin traza) cuando hay una excepción previa.
        $previous = $exception->getPrevious();
        if ($previous !== null) {
            $summary .= sprintf(
                "\n | Causa: %s:\n%s en %s:%d",
                $previous::class,
                $previous->getMessage(),
                $previous->getFile(),
                $previous->getLine(),
            );
        }

        return $summary;
    }

    protected function respond(): Response
    {
        $status = $this->resolveStatus();

        return $this->wantsHtml()
            ? $this->htmlResponse($status)
            : $this->jsonResponse($status);
    }

    private function resolveStatus(): int
    {
        $exception = $this->exception;

        return match (true) {
            $exception instanceof NotFoundException   => 404,
            $exception instanceof ValidationException => 422,
            $exception instanceof HttpException       => $exception->getCode(),
            default                                   => 500,
        };
    }

    private function wantsHtml(): bool
    {
        return str_contains($this->request->getHeaderLine('Accept'), 'text/html');
    }

    private function htmlResponse(int $status): Response
    {
        [$title, $message] = self::MESSAGES[$status] ?? ['Error', 'Ocurrió un error inesperado.'];

        $html = $this->renderTemplate($status, $title, $message)
            ?? $this->fallbackHtml($status, $title, $message);

        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write($html);

        return $this->withExtraHeaders($response)->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    private function jsonResponse(int $status): Response
    {
        $exception = $this->exception;

        $message = match (true) {
            $exception instanceof NotFoundException,
            $exception instanceof ValidationException,
            $exception instanceof HttpException => $exception->getMessage(),
            default                             => 'Error interno del servidor.',
        };

        $payload = ['error' => ['status' => $status, 'message' => $message]];

        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $this->withExtraHeaders($response)->withHeader('Content-Type', 'application/json');
    }

    private function renderTemplate(int $status, string $title, string $message): ?string
    {
        try {
            $loader = $this->twig->getEnvironment()->getLoader();

            foreach (["errors/{$status}.twig", 'errors/error.twig'] as $template) {
                if ($loader->exists($template)) {
                    return $this->twig->fetch($template, [
                        'status' => $status,
                        'title' => $title,
                        'message' => $message,
                        'retryAfter' => $this->exception instanceof ThrottleException
                            ? $this->exception->retryAfter
                            : 0,
                    ]);
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function fallbackHtml(int $status, string $title, string $message): string
    {
        $title = htmlspecialchars($title, ENT_QUOTES);
        $message = htmlspecialchars($message, ENT_QUOTES);

        return <<<HTML
            <!DOCTYPE html>
            <html lang="es">
            <head><meta charset="UTF-8"><title>{$status} · {$title}</title></head>
            <body style="font-family:system-ui,sans-serif;text-align:center;padding:4rem 1rem;">
                <h1 style="font-size:3rem;margin:0;">{$status}</h1>
                <p style="font-size:1.25rem;">{$title}</p>
                <p style="color:#6b7280;">{$message}</p>
            </body>
            </html>
            HTML;
    }

    private function withExtraHeaders(Response $response): Response
    {
        $exception = $this->exception;

        if ($exception instanceof HttpMethodNotAllowedException) {
            $response = $response->withHeader('Allow', implode(', ', $exception->getAllowedMethods()));
        }

        if ($exception instanceof ThrottleException) {
            $response = $response
                ->withHeader('Retry-After', (string) $exception->retryAfter)
                ->withHeader('X-RateLimit-Limit', (string) $exception->limit)
                ->withHeader('X-RateLimit-Remaining', '0');
        }

        return $response;
    }
}
