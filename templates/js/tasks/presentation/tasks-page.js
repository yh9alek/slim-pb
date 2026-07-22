import store from '../store/store';
import { toggleTask, deleteTask, renderEmpty } from '../use-cases';

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
        const id = e.target.closest('li[data-id]');
        if (!id) return;

        if (e.target.matches('[data-action="toggle"]')) {
            toggleTask(id);
        };
        if (e.target.matches('[data-action="delete"]')) {
            deleteTask(id);

            const count = store.getCount();

            document.querySelector('[data-count]').textContent = String(count);

            if (count <= 0) {
                renderEmpty();
            }
        }
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
