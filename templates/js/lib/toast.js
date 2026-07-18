import Swal from 'sweetalert2';

// Reparto de responsabilidades:
//   - Swal.fire (modal)  -> errores que exigen atención (400/500, en lib/http.js).
//   - toast (aquí)       -> confirmaciones breves que no interrumpen.

const base = Swal.mixin({
    toast: true,
    position: 'bottom-end',
    showConfirmButton: false,
    timer: 2000,
    timerProgressBar: true,
    // Pausa el temporizador mientras el cursor está encima.
    didOpen: (el) => {
        el.addEventListener('mouseenter', Swal.stopTimer);
        el.addEventListener('mouseleave', Swal.resumeTimer);
    },
});

/**
 * @param {string} title Texto breve; el toast no usa cuerpo largo.
 * @param {'success'|'error'|'info'|'warning'} icon
 */
const fire = (title, icon) => base.fire({ icon, title });

export const toast = {
    success: (title) => fire(title, 'success'),
    error: (title) => fire(title, 'error'),
    info: (title) => fire(title, 'info'),
    warning: (title) => fire(title, 'warning'),
};
