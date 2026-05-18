{{-- MODAL EVALUAR CONTRATO — Paso 1 del flujo Nuevo Contrato --}}
<x-ui.modal-shell id="evaluar-contrato-modal" max-width="480px">

    <x-ui.modal-header modal-id="evaluar-contrato-modal" title="Nuevo Contrato" icon="fa-file-circle-plus" />

    <div class="p-8">
        <form id="form-evaluar-contrato">
            <div class="space-y-5">

                <x-forms.field label="Número de Documento" for="evaluar-numero-documento">
                    <x-forms.text-input
                        id="evaluar-numero-documento"
                        name="numero_documento"
                        class="w-full"
                        placeholder="Ej: 12345678"
                        required
                    />
                    <div id="evaluar-persona-nombre" class="text-sm text-light-muted dark:text-dark-muted mt-2 min-h-[1.25rem] transition-all duration-300"></div>
                </x-forms.field>

                <x-forms.field label="Planilla" for="evaluar-planilla-id">
                    <x-forms.select id="evaluar-planilla-id" name="planilla_id" class="w-full" required>
                        <option value="">Cargando...</option>
                    </x-forms.select>
                </x-forms.field>

                <x-forms.field label="Inicio del Contrato" for="evaluar-fecha-inicio">
                    <x-forms.text-input
                        id="evaluar-fecha-inicio"
                        name="fecha_inicio"
                        type="date"
                        class="w-full disabled:opacity-50 disabled:cursor-not-allowed"
                        required
                        disabled
                    />
                </x-forms.field>

            </div>
        </form>
    </div>

    <x-ui.modal-footer modal-id="evaluar-contrato-modal">
        <x-forms.primary-button type="submit" form="form-evaluar-contrato" id="btn-evaluar-contrato">
            <i class="fa-solid fa-magnifying-glass mr-2"></i>Evaluar
        </x-forms.primary-button>
    </x-ui.modal-footer>

</x-ui.modal-shell>
