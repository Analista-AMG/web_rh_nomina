{{-- MODAL DAR DE BAJA --}}
<x-ui.modal-shell id="baja-contrato-modal" max-width="600px">

    {{-- Header custom: título actualizable por JS sin perder el ícono --}}
    <div class="px-8 py-6 border-b border-light-border dark:border-dark-border flex items-center justify-between gap-4">
        <h3 class="text-lg font-bold text-red-600 dark:text-red-400 flex items-center gap-2">
            <i class="fa-solid fa-user-minus text-sm"></i>
            <span id="baja-modal-titulo">Dar de Baja</span>
        </h3>
        <button onclick="closeModal('baja-contrato-modal')"
            class="w-8 h-8 rounded-full flex items-center justify-center text-light-muted hover:text-light-text dark:hover:text-dark-text hover:bg-black/8 dark:hover:bg-white/10 transition-colors">
            <i class="fa-solid fa-times"></i>
        </button>
    </div>

    <div class="p-8">
        <form id="form-baja-contrato">
            <input type="hidden" id="baja-contrato-id">
            <input type="hidden" id="baja-id">

            {{-- Info del colaborador --}}
            <div class="mb-6 p-4 bg-light-bg dark:bg-dark-bg rounded-lg border border-light-border dark:border-dark-border">
                <p class="text-[10px] font-bold uppercase tracking-widest text-light-muted dark:text-dark-muted mb-1">Colaborador</p>
                <p id="baja-colaborador-nombre" class="text-base font-bold text-light-text dark:text-dark-text"></p>
                <p id="baja-colaborador-doc" class="text-sm text-light-muted dark:text-dark-muted"></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-forms.field label="Fecha de Baja *" for="baja-fecha">
                    <x-forms.text-input id="baja-fecha" type="date" class="w-full" required />
                </x-forms.field>

                <x-forms.field label="Motivo de Baja *" for="baja-motivo">
                    <x-forms.text-input id="baja-motivo" type="text" class="w-full" placeholder="Ej: Renuncia voluntaria" required />
                </x-forms.field>

                <x-forms.field label="¿Aviso con 15 días?" for="baja-aviso-15-dias">
                    <x-forms.select id="baja-aviso-15-dias" class="w-full">
                        <option value="0">No</option>
                        <option value="1">Sí</option>
                    </x-forms.select>
                </x-forms.field>

                <x-forms.field label="¿Recomienda reingreso?" for="baja-recomienda-reingreso">
                    <x-forms.select id="baja-recomienda-reingreso" class="w-full">
                        <option value="1">Sí</option>
                        <option value="0">No</option>
                    </x-forms.select>
                </x-forms.field>
            </div>

            <div class="mt-4">
                <x-forms.field label="Observación" for="baja-observacion">
                    <textarea id="baja-observacion" rows="3" class="form-input w-full"
                        placeholder="Observaciones adicionales sobre la baja..."></textarea>
                </x-forms.field>
            </div>

            {{-- Advertencia --}}
            <div class="mt-5 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-sm text-red-700 dark:text-red-300 flex items-start gap-2">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5 flex-shrink-0"></i>
                    <span id="baja-advertencia-texto">Esta acción registrará la baja del colaborador. El contrato pasará a estado <strong>Finalizado</strong> una vez cumplida la fecha.</span>
                </p>
            </div>

        </form>
    </div>

    {{-- Footer custom: justify-between para botón Eliminar (izquierda) + acciones (derecha) --}}
    <div class="px-8 py-4 border-t border-light-border dark:border-dark-border flex justify-between items-center bg-light-bg dark:bg-dark-bg">
        <div id="baja-eliminar-container" class="hidden">
            <x-forms.secondary-button type="button" id="btn-eliminar-baja">
                <i class="fa-solid fa-rotate-left mr-2"></i>Eliminar Baja
            </x-forms.secondary-button>
        </div>
        <div id="baja-spacer"></div>
        <div class="flex gap-3">
            <x-forms.secondary-button type="button" onclick="closeModal('baja-contrato-modal')">Cancelar</x-forms.secondary-button>
            <x-forms.danger-button type="button" id="btn-confirmar-baja">Confirmar Baja</x-forms.danger-button>
        </div>
    </div>

</x-ui.modal-shell>
