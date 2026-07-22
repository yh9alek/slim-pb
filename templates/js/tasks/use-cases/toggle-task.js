import store from '../store/store';
import { toast } from '../../lib/toast';

/**
 * @param {HTMLLIElement} li
 */
export const toggleTask = async (li) => {
    const completed = await store.toggle(Number(li.dataset.id));

    li.dataset.completed = String(completed);
    toast.success(completed ? 'Tarea completada' : 'Tarea reabierta');
};
