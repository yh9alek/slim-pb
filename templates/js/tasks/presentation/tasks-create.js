import { http } from "../../lib/http";

const title = document.getElementById('title');
const completed = document.getElementById('completed');
const taskForm  = document.getElementById('task-form');

taskForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const resp = await http.post(taskForm.getAttribute('action'), {
        title: String(title.value),
        completed: Boolean(completed.checked),
    });

    if (resp.status === 201) {
        window.location.href = '/tasks';
    }

});
