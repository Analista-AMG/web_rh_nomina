{{-- MODAL EDITAR MOVIMIENTO --}}
<x-ui.modal-shell id="edit-movimiento-modal" max-width="900px">

    {{-- Header con datos del colaborador --}}
    <div class="px-8 py-5 border-b border-light-border dark:border-dark-border sticky top-0 bg-white dark:bg-dark-card z-10">
        <div class="flex justify-between items-start">
            <div class="flex items-center gap-4">
                <div id="edit-mov-avatar"
                    class="h-14 w-14 rounded-2xl bg-primary/10 flex items-center justify-center text-primary text-xl font-black flex-shrink-0">
                </div>
                <div>
                    <p id="edit-mov-nombre-header" class="text-lg font-bold text-light-text dark:text-dark-text">Colaborador</p>
                    <div class="flex items-center gap-2.5 mt-1">
                        <p id="edit-mov-documento-header" class="text-sm font-mono font-semibold text-light-muted dark:text-dark-muted"></p>
                        <span id="edit-mov-tipo-badge" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary"></span>
                    </div>
                </div>
            </div>
            <button type="button" onclick="closeModal('edit-movimiento-modal')"
                class="w-8 h-8 rounded-full flex items-center justify-center text-light-muted hover:text-light-text dark:hover:text-dark-text hover:bg-black/8 dark:hover:bg-white/10 transition-colors">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
    </div>

    <form id="form-edit-movimiento">
        <input type="hidden" id="edit-mov-id">

        <div class="p-8 space-y-8">

            {{-- Sección: Datos del Movimiento --}}
            <x-ui.modal-section label="Datos del Movimiento" icon="fa-file-contract">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <x-forms.field label="Fecha Inicio" for="edit-mov-inicio">
                        <x-forms.text-input id="edit-mov-inicio" type="date" class="w-full" />
                    </x-forms.field>

                    <x-forms.field label="Fecha Fin" for="edit-mov-fin">
                        <x-forms.text-input id="edit-mov-fin" type="date" class="w-full" />
                    </x-forms.field>

                    <x-forms.field label="Condición" for="edit-mov-condicion-id">
                        <x-forms.select id="edit-mov-condicion-id" class="w-full">
                            <option value="">Cargando...</option>
                        </x-forms.select>
                    </x-forms.field>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <x-forms.field label="Cargo" for="edit-mov-cargo-id">
                        <x-forms.select id="edit-mov-cargo-id" class="w-full">
                            <option value="">Cargando...</option>
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Planilla" for="edit-mov-planilla-id">
                        <x-forms.select id="edit-mov-planilla-id" class="w-full">
                            <option value="">Cargando...</option>
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Centro de Costo" for="edit-mov-centro-costo-id">
                        <x-forms.select id="edit-mov-centro-costo-id" class="w-full">
                            <option value="">Cargando...</option>
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Familia" for="edit-mov-familia-id">
                        <x-forms.select id="edit-mov-familia-id" class="w-full">
                            <option value="">Cargando...</option>
                        </x-forms.select>
                    </x-forms.field>
                </div>
            </x-ui.modal-section>

            {{-- Sección: Remuneración --}}
            <x-ui.modal-section label="Remuneración" icon="fa-money-bill-wave">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-forms.field label="Haber Básico (S/)" for="edit-mov-haber">
                        <x-forms.text-input id="edit-mov-haber" type="number" step="0.01" class="w-full font-mono" placeholder="0.00" />
                    </x-forms.field>

                    <x-forms.field label="Asignación Familiar" for="edit-mov-asignacion">
                        <x-forms.select id="edit-mov-asignacion" class="w-full">
                            <option value="0">No</option>
                            <option value="1">Sí</option>
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Moneda" for="edit-mov-moneda-id">
                        <x-forms.select id="edit-mov-moneda-id" class="w-full">
                            <option value="">Cargando...</option>
                        </x-forms.select>
                    </x-forms.field>
                </div>
            </x-ui.modal-section>

            {{-- Sección: Previsional --}}
            <x-ui.modal-section label="Previsional" icon="fa-building-columns">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <x-forms.field label="Fondo de Pensiones" for="edit-mov-fp-id">
                        <x-forms.select id="edit-mov-fp-id" class="w-full">
                            <option value="">Cargando...</option>
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Banco" for="edit-mov-banco-id">
                        <x-forms.select id="edit-mov-banco-id" class="w-full">
                            <option value="">Cargando...</option>
                        </x-forms.select>
                    </x-forms.field>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-forms.field label="Nº Cuenta" for="edit-mov-numero-cuenta">
                        <x-forms.text-input id="edit-mov-numero-cuenta" type="text" class="w-full font-mono" placeholder="Número de cuenta" />
                    </x-forms.field>

                    <x-forms.field label="CCI" for="edit-mov-codigo-interbancario">
                        <x-forms.text-input id="edit-mov-codigo-interbancario" type="text" class="w-full font-mono" placeholder="Código interbancario" />
                    </x-forms.field>
                </div>
            </x-ui.modal-section>

        </div>

        <x-ui.modal-footer modal-id="edit-movimiento-modal">
            <x-forms.primary-button type="button" id="btn-save-movimiento">
                <i class="fa-solid fa-save mr-2"></i>Guardar Cambios
            </x-forms.primary-button>
        </x-ui.modal-footer>

    </form>

</x-ui.modal-shell>
