const htmlTask = (task) => `
    <li class="task group flex items-center gap-3 px-4 py-3 transition-colors hover:bg-base-200/40" data-id="${task.id}" data-title="${task.title}" data-completed="${task.completed}">
        <button type="button" data-action="toggle" aria-label="Alternar completada" class="grid h-6 w-6 shrink-0 place-items-center rounded-full border-2 border-base-content/20 text-transparent transition-colors hover:border-primary
                group-data-[completed=true]:border-success group-data-[completed=true]:bg-success group-data-[completed=true]:text-success-content cursor-pointer">
            <svg class="pointer-events-none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
        </button>
        <span class="task__title grow font-medium text-base-content transition-colors group-data-[completed=true]:text-base-content/40 group-data-[completed=true]:line-through">${task.title}</span>
        <span class="badge badge-sm badge-warning badge-soft font-semibold group-data-[completed=true]:hidden">Pendiente</span>
        <span class="badge badge-sm badge-success badge-soft font-semibold hidden group-data-[completed=true]:inline-flex">Completada</span>
        <button type="button" data-action="delete" aria-label="Eliminar" title="Eliminar" class="btn btn-square btn-ghost btn-xs text-base-content/30 transition-colors hover:bg-error/10 hover:text-error cursor-pointer">
            <svg class="pointer-events-none w-[16px] h-[16px]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M10 11v6M14 11v6"></path></svg>
        </button>
    </li>`;

export const renderTasks = (tasks) => {
    document.querySelector('ul[data-tasks]').innerHTML = tasks.map(htmlTask).join('');
};
