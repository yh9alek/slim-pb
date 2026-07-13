import store from '../store/store';

/**
 * Elimina una tarea y quita su <li> del DOM.
 * @param {HTMLLIElement} li
 */
export const deleteTask = async (li) => {
    await store.remove(Number(li.dataset.id));
    li.remove();
};
