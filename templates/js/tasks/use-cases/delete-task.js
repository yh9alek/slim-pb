import store from '../store/store';
import { renderEmpty } from './render-empty';
import { toast } from '../../lib/toast';

/**
 * @param {HTMLLIElement} li
 */
export const deleteTask = async (li) => {

    await store.remove(Number(li.dataset.id));

    li.remove();
    toast.success('Tarea eliminada');

    if (store.getCount() === 0) {
        renderEmpty();
    }
};
