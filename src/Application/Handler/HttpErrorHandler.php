<?php

declare(strict_types=1);

namespace App\Application\Handler;

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

// Convierte excepciones en respuestas coherentes y NEGOCIA el formato:
//  - Navegación del navegador (Accept: text/html) -> vista HTML de error.
//  - API / AJAX (Accept: application/json o */*)   -> JSON, como siempre.
final class HttpErrorHandler extends ErrorHandler
{
    // Título y mensaje amigable por código (para las vistas HTML).
    private const array MESSAGES = [
        400 => ['Solicitud incorrecta', 'La petición no se pudo procesar.'],
        401 => ['No autenticado', 'Necesitas iniciar sesión para ver esta página.'],
        403 => ['Acceso denegado', 'No tienes permiso para acceder a este recurso.'],
        404 => ['Página no encontrada', 'La página que buscas no existe o se ha movido.'],
        405 => ['Método no permitido', 'La acción solicitada no está permitida aquí.'],
        419 => ['La página expiró', 'Tu sesión ha caducado. Recarga e inténtalo de nuevo.'],
        429 => ['Demasiadas peticiones', 'Has hecho demasiadas solicitudes. Espera un momento.'],
        500 => ['Error interno', 'Algo salió mal por nuestra parte. Inténtalo más tarde.'],
        503 => ['Servicio no disponible', 'Estamos en mantenimiento.'],
    ];

    public function __construct(
        CallableResolverInterface $callableResolver,
        ResponseFactoryInterface $responseFactory,
        private readonly Twig $twig,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($callableResolver, $responseFactory, $logger);
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
        // Una navegación tradicional del navegador incluye text/html en Accept;
        // los clientes de API piden application/json (o */*).
        return str_contains($this->request->getHeaderLine('Accept'), 'text/html');
    }

    private function htmlResponse(int $status): Response
    {
        [$title, $message] = self::MESSAGES[$status] ?? ['Error', 'Ocurrió un error inesperado.'];

        $html = $this->renderTemplate($status, $title, $message)
            ?? $this->fallbackHtml($status, $title, $message);

        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write($html);

        return $this->withAllow($response)->withHeader('Content-Type', 'text/html; charset=utf-8');
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

        return $this->withAllow($response)->withHeader('Content-Type', 'application/json');
    }

    // Intenta una plantilla específica (errors/404.twig) y, si no existe, la
    // genérica. Cualquier fallo de render cae al HTML mínimo de respaldo.
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
                    ]);
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    // HTML autocontenido: se usa si la plantilla falta o falla al renderizar,
    // de modo que la página de error NUNCA depende del pipeline de assets.
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

    // En un 405, la respuesta debe indicar los métodos permitidos.
    private function withAllow(Response $response): Response
    {
        $exception = $this->exception;

        if ($exception instanceof HttpMethodNotAllowedException) {
            return $response->withHeader('Allow', implode(', ', $exception->getAllowedMethods()));
        }

        return $response;
    }
}
