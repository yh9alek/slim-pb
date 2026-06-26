<?php

declare(strict_types=1);

namespace App\Application\Handler;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\ResponseEmitter;

/* Red de seguridad para errores FATALES de PHP (E_ERROR, memoria agotada...).
   No son Throwable, así que escapan al ErrorMiddleware de Slim: este handler
   los captura en el shutdown y los convierte en un 500 coherente. */

final readonly class ShutdownHandler
{
    // Solo nos interesan los fatales reales; ignoramos avisos/notices para no
    // emitir una respuesta de error sobre una petición que ya terminó bien.
    private const int FATAL = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;

    public function __construct(
        private Request $request,
        private HttpErrorHandler $errorHandler,
        private bool $displayErrorDetails,
    ) {
    }

    public function __invoke(): void
    {
        $error = error_get_last();

        if ($error === null || ($error['type'] & self::FATAL) === 0) {
            return;
        }

        $exception = new HttpInternalServerErrorException(
            $this->request,
            $this->buildMessage($error),
        );

        $response = ($this->errorHandler)(
            $this->request,
            $exception,
            $this->displayErrorDetails,
            true,  // logErrors: un fatal es un 500, y queremos verlo en el log
            true,  // logErrorDetails: traza completa del fatal en el registro
        );

        (new ResponseEmitter())->emit($response);
    }

    /**
     * @param array{type: int, message: string, file: string, line: int} $error
     */
    private function buildMessage(array $error): string
    {
        if (!$this->displayErrorDetails) {
            return 'Ocurrió un error al procesar tu petición. Inténtalo de nuevo más tarde.';
        }

        return sprintf(
            'ERROR FATAL: %s en %s línea %d.',
            $error['message'],
            $error['file'],
            $error['line'],
        );
    }
}
