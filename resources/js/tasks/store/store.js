const API = '/api/tasks';

const state = {
    tasks: [],
};

/**
 * Carga el estado con las tareas que el servidor ya renderizó (SSR).
 * @param {Array<{id:number,title:string,completed:boolean}>} tasks
 */
const hydrate = (tasks) => {
    state.tasks = tasks;
};

const getTasks = () => [...state.tasks];

/**
 * @param {number} id
 */
const find = (id) => state.tasks.find((task) => task.id === id);

/**
 * PUT /api/tasks/:id — alterna "completada". PUT reemplaza el recurso
 * completo, así que se envía también el título.
 * @param {number} id
 */
const toggle = async (id) => {
    const task = find(id);

    const resp = await fetch(`${API}/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ title: task.title, completed: !task.completed }),
    });

    const { data } = await resp.json();
    state.tasks = state.tasks.map((t) => (t.id === id ? data : t));

    return data;
};

/**
 * DELETE /api/tasks/:id
 * @param {number} id
 */
const remove = async (id) => {
    await fetch(`${API}/${id}`, { method: 'DELETE' });
    state.tasks = state.tasks.filter((t) => t.id !== id);
};

export default {
    hydrate,
    getTasks,
    find,
    toggle,
    remove,
};
