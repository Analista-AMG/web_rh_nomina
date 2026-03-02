{{-- MODAL EDITAR CONTRATO --}}
<x-ui.modal-shell id="edit-modal" max-width="740px">

    <x-ui.modal-header modal-id="edit-modal" title="Editar Contrato" icon="fa-file-pen" />

    <div class="p-8">
        <form id="edit-form">
            <input type="hidden" id="edit-id">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-forms.field label="Colaborador" for="edit-empleado">
                    <x-forms.readonly-input id="edit-empleado" class="w-full" />
                </x-forms.field>

                <x-forms.field label="Cargo" for="edit-cargo">
                    <x-forms.readonly-input id="edit-cargo" class="w-full" />
                    <p class="text-xs text-light-muted dark:text-dark-muted mt-1">Para cambiar cargo, use Añadir Movimiento.</p>
                </x-forms.field>

                <x-forms.field label="Fecha Inicio" for="edit-inicio">
                    <x-forms.text-input id="edit-inicio" type="date" class="w-full" required />
                </x-forms.field>

                <x-forms.field label="Fecha Fin" for="edit-fin">
                    <x-forms.text-input id="edit-fin" type="date" class="w-full" />
                </x-forms.field>

                <x-forms.field label="Haber Básico (S/)" for="edit-salario">
                    <x-forms.text-input id="edit-salario" type="number" step="0.01" class="w-full font-mono" required />
                </x-forms.field>
            </div>
        </form>
    </div>

    <x-ui.modal-footer modal-id="edit-modal">
        <x-forms.primary-button type="button" id="btn-save-contrato">Guardar Cambios</x-forms.primary-button>
    </x-ui.modal-footer>

</x-ui.modal-shell>
