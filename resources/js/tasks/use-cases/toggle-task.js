import store from '../store/store';

/**
 * Alterna el estado de una tarea y refleja el cambio en su <li>.
 * @param {HTMLLIElement} li
 */
export const toggleTask = async (li) => {
    const task = await store.toggle(Number(li.dataset.id));

    li.classList.toggle('task--done', task.completed);
    li.dataset.completed = String(task.completed);
    li.querySelector('[data-action="toggle"]').textContent = task.completed
        ? 'Reabrir'
        : 'Completar';
};
