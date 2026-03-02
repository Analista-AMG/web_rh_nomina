<!-- MODAL ELIMINAR -->
<x-ui.modal-shell id="delete-modal" max-width="420px">

    <!-- Franja roja superior -->
    <div class="h-2.5 w-full bg-danger"></div>

    <div class="px-7 pt-6 pb-5">
        <!-- Icono + título -->
        <div class="flex items-start gap-4">
            <div class="w-11 h-11 rounded-xl bg-red-100 dark:bg-red-900/25 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-trash text-danger text-base"></i>
            </div>
            <div class="flex-1 min-w-0 pt-0.5">
                <h3 class="text-base font-bold text-light-text dark:text-dark-text">Eliminar colaborador</h3>
                <p class="text-sm text-light-muted dark:text-dark-muted mt-0.5">Esta acción no se puede deshacer.</p>
            </div>
            <button onclick="closeModal('delete-modal')"
                class="w-7 h-7 rounded-full flex items-center justify-center text-light-muted hover:text-light-text dark:hover:text-dark-text hover:bg-black/8 dark:hover:bg-white/10 transition-colors flex-shrink-0">
                <i class="fa-solid fa-times text-xs"></i>
            </button>
        </div>

        <!-- Nombre de la persona -->
        <div class="mt-5 bg-light-bg dark:bg-dark-bg rounded-xl border border-light-border dark:border-dark-border px-4 py-3 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/25 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-user text-danger text-xs"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted">Colaborador</p>
                <p id="delete-nombre" class="text-sm font-bold text-light-text dark:text-dark-text truncate">—</p>
                <p id="delete-doc" class="text-xs text-light-muted dark:text-dark-muted font-mono mt-0.5">—</p>
            </div>
        </div>

        <!-- Advertencia -->
        <p class="mt-4 text-[13px] text-light-muted dark:text-dark-muted leading-relaxed">
            Se eliminarán todos los datos personales de este colaborador. Si tiene contratos asociados, la operación será rechazada.
        </p>
    </div>

    <x-ui.modal-footer modal-id="delete-modal">
        <x-forms.danger-button id="btn-confirm-delete">
            <i class="fa-solid fa-trash text-xs"></i>
            Eliminar
        </x-forms.danger-button>
    </x-ui.modal-footer>

</x-ui.modal-shell>
