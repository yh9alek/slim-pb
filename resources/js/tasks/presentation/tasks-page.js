import '../../../css/tasks.css';
import store from '../store/store';
import { toggleTask, deleteTask } from '../use-cases';

// Capa de presentación de la vista de tareas. NO crea el HTML (eso lo
// hace Twig en el servidor): realza el DOM ya renderizado. Este archivo
// es el entry de Vite de la vista (Pattern A).

const list = document.querySelector('[data-tasks]');

if (list) {
    // 1) Hidratar el estado desde los <li> que renderizó el servidor.
    store.hydrate(
        [...list.querySelectorAll('li[data-id]')].map((li) => ({
            id: Number(li.dataset.id),
            title: li.dataset.title,
            completed: li.dataset.completed === 'true',
        })),
    );

    // 2) Acciones, delegadas en la lista (sirven aunque cambien los <li>).
    list.addEventListener('click', (e) => {
        const li = e.target.closest('li[data-id]');
        if (!li) return;

        if (e.target.matches('[data-action="toggle"]')) toggleTask(li);
        if (e.target.matches('[data-action="delete"]')) deleteTask(li);
    });

    // 3) Filtros: estado de vista, solo muestran/ocultan <li>.
    const search = document.querySelector('[data-filter-text]');
    const pending = document.querySelector('[data-filter-pending]');
    const count = document.querySelector('[data-count]');

    const applyFilters = () => {
        const text = (search?.value ?? '').toLowerCase();
        const onlyPending = pending?.checked ?? false;
        let visible = 0;

        list.querySelectorAll('li[data-id]').forEach((li) => {
            const matchesText = li.dataset.title.toLowerCase().includes(text);
            const matchesPending = !onlyPending || li.dataset.completed !== 'true';
            const show = matchesText && matchesPending;

            li.hidden = !show;
            if (show) visible += 1;
        });

        if (count) count.textContent = String(visible);
    };

    search?.addEventListener('input', applyFilters);
    pending?.addEventListener('change', applyFilters);
    applyFilters();
}
