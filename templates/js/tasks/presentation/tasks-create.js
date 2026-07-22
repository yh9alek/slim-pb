import { http } from "../../lib/http";

const title = document.getElementById('title');
const spanError = document.querySelector('.input-error');
const completed = document.getElementById('completed');
const taskForm  = document.getElementById('task-form');

taskForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    try {
        const resp = await http.post(taskForm.getAttribute('action'), {
            title: String(title.value),
            completed: Boolean(completed.checked),
        });

        if (resp.status === 201) {
            window.location.href = '/tasks';
        }
    } catch (error) {
        console.log(Object.entries(error));
        if (error.status === 422) {
            title.classList.add('border-red-500');
            spanError.style.display = 'block';
            spanError.textContent = 'El título es obligatorio.';
        }
    }

});
