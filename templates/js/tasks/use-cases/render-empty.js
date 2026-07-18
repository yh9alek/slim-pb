const htmlEmpty = () => `
    <div class="flex flex-col items-center gap-3 px-6 py-20 text-center">
        <div class="grid h-16 w-16 place-items-center rounded-2xl bg-base-200 text-base-content/40">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="h-7 w-7"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
        </div>
        <p class="text-lg font-semibold text-base-content">No hay tareas todavía</p>
        <p class="max-w-xs text-sm text-base-content/50">Crea tu primera tarea para empezar a organizar tu día.</p>
    </div>`;

export const renderEmpty = () => {
    document.querySelector('.card-body').innerHTML += htmlEmpty();
};
