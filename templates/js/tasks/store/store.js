import { fetchRemove, fetchToggle } from '../api/tasks.api';

const state = {
    tasks: {},
};

const getCount = () => Object.entries(state.tasks).length;

/**
 * @param {Array<{id:number,title:string,completed:boolean}>} tasks
 */
const hydrate = (tasks) => {
    tasks.forEach((t) => {
        state.tasks[t.id] = t;
    });
};

const toggle = async (id) => {
    const task = find(id);

    if (task === undefined) {
        return null;
    }

    await fetchToggle(task);

    return (task.completed = !task.completed);
};

const remove = async (id) => {
    await fetchRemove(id);
    delete state.tasks[id];
};

/**
 * @param {number} id
 */
const find = (id) => state.tasks[id];

export default {
    hydrate,
    find,
    toggle,
    remove,
    getCount,
};
