<?php

declare(strict_types=1);

namespace App\Application\Handler;

use ErrorException;
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
    ) {}

    public function __invoke(): void
    {
        $error = error_get_last();

        if ($error === null || ($error['type'] & self::FATAL) === 0) {
            return;
        }

        // Excepción que lleva la UBICACIÓN REAL del fatal (archivo y línea que
        // reporta PHP). Va como "previous" para que la página de depuración
        // señale el lugar a corregir, no el punto donde se construye este 500.
        $fatal = new ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line'],
        );

        $exception = new HttpInternalServerErrorException(
            $this->request,
            $this->buildMessage($error),
            $fatal,
        );

        $response = ($this->errorHandler)(
            $this->request,
            $exception,
            $this->displayErrorDetails,
            true,  // logErrors: un fatal es un 500, y queremos verlo en el log
            true,  // logErrorDetails
        );

        // Descartamos cualquier salida parcial (incluida la cruda de PHP) para
        // que la página de error no quede precedida de texto desbordado.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

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
