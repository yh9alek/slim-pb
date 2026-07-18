import axios from 'axios';
import Swal from 'sweetalert2';

export const http = axios.create({
    baseURL: '/api',
    headers: { Accept: 'application/json' },
});

// Mensajes por defecto cuando la API no envía uno propio.
const FALLBACK = {
    400: 'La petición no se pudo procesar. Revisa los datos e inténtalo de nuevo.',
    500: 'Ocurrió un error en el servidor. Inténtalo más tarde.',
};

// Alerta única y consistente para toda la app.
const alertError = (title, text) =>
    Swal.fire({
        icon: 'error',
        title,
        text,
        confirmButtonText: 'Entendido',
    });

http.interceptors.response.use(
    // 2xx: pasa tal cual.
    (response) => response,

    (error) => {
        // Sin respuesta = error de red o petición cancelada.
        if (!error.response) {
            if (!axios.isCancel(error)) {
                alertError('Sin conexión', 'No se pudo contactar con el servidor.');
            }
            return Promise.reject(error);
        }

        const { status, data } = error.response;

        const message = data?.error?.message;

        if (status === 400) {
            alertError('Solicitud incorrecta', message ?? FALLBACK[400]);
        }

        if (status >= 500) {
            alertError('Error del servidor', message ?? FALLBACK[500]);
        }

        // Siempre se re-lanza: el caso de uso decide si además hace algo
        // (p. ej. pintar los errores de un 422 junto a cada campo).
        return Promise.reject(error);
    },
);
