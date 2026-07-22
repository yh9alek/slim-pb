import { http } from '../../lib/http';

const API = '/tasks';

/**
 * @param {{id:number, title:string, completed:boolean}} task
 */
export const fetchToggle = async ({ id, title, completed }) => {
    const { data } = await http.put(`${API}/${id}`, {
        title,
        completed: !completed,
    });

    return data.data;
};

/**
 * @param {number} id
 */
export const fetchRemove = async (id) => {
    await http.delete(`${API}/${id}`);
};
