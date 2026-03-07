{{-- ═══════════════════════════════════════════════════════════════════════
     MODAL: SOLICITAR colaborador (2 pasos)
════════════════════════════════════════════════════════════════════════ --}}
<x-ui.modal-shell id="modal-solicitar" max-width="520px">

    {{-- Header con step indicator --}}
    <div class="px-8 pt-6 pb-4 border-b border-light-border dark:border-dark-border">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                        <i class="fa-solid fa-arrow-down-to-bracket text-primary text-sm"></i>
                    </span>
                    Solicitar colaborador
                </h2>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5" id="solic-subtitulo">Selecciona el período del préstamo</p>
            </div>
            <button onclick="closeModal('modal-solicitar')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <div class="flex items-center gap-2 mt-4">
            <div class="flex items-center gap-1.5">
                <div id="step-dot-1" class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-colors bg-primary text-white">1</div>
                <span id="step-label-1" class="text-xs font-medium text-primary">Período</span>
            </div>
            <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700 mx-1" id="step-line"></div>
            <div class="flex items-center gap-1.5">
                <div id="step-dot-2" class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-colors bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500">2</div>
                <span id="step-label-2" class="text-xs font-medium text-gray-400 dark:text-gray-500">Colaborador</span>
            </div>
        </div>
    </div>

    {{-- ── PASO 1: Período ── --}}
    <div id="solic-step-1" class="px-8 py-6 space-y-5">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-widest text-light-muted dark:text-dark-muted mb-3 flex items-center gap-1.5">
                <i class="fa-solid fa-calendar-range text-primary text-[13px]"></i> Período del préstamo
            </p>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Desde</label>
                    <x-forms.text-input type="date" id="solicitar-fecha-inicio" oninput="actualizarDuracion('solicitar')" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Hasta</label>
                    <x-forms.text-input type="date" id="solicitar-fecha-fin" oninput="actualizarDuracion('solicitar')" />
                </div>
            </div>
            <div id="solic-duracion-wrap" class="hidden mt-3 flex justify-center">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-300 rounded-full text-sm font-medium">
                    <i class="fa-solid fa-calendar-check text-xs"></i>
                    <span id="solic-duracion-texto"></span>
                </span>
            </div>
        </div>
    </div>

    {{-- ── PASO 2: Buscar colaborador ── --}}
    <div id="solic-step-2" class="hidden px-8 py-5 space-y-4 overflow-y-auto" style="max-height:62vh">

        {{-- Resumen período --}}
        <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-300 text-sm font-medium">
            <i class="fa-solid fa-calendar-range text-xs"></i>
            <span id="solic-rango-texto"></span>
            <span id="solic-rango-dias" class="ml-auto text-xs font-normal text-blue-400"></span>
        </div>

        {{-- Buscador --}}
        <div class="space-y-1.5">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                <input type="text" id="solicitar-search"
                    placeholder="Nombre o apellido..."
                    autocomplete="off"
                    onkeydown="if(event.key==='Enter'){event.preventDefault();buscarPersonas();}"
                    class="form-input w-full pl-10 pr-24" />
                <button type="button" onclick="buscarPersonas()"
                    class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1.5 bg-primary text-white text-xs font-medium rounded-md hover:bg-primary/80 transition-colors cursor-pointer">
                    Buscar
                </button>
            </div>
            <p class="text-xs text-gray-400">Filtra colaboradores con contrato vigente en el período · <kbd class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-[11px]">Enter</kbd> para buscar</p>
        </div>

        {{-- Resultados --}}
        <div id="solicitar-lista" class="hidden rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
            <div id="solicitar-cargando" class="hidden px-4 py-4 flex items-center gap-3 text-sm text-gray-400">
                <i class="fa-solid fa-spinner fa-spin text-primary"></i>
                <span>Buscando colaboradores...</span>
            </div>
            <div id="solicitar-sin-resultados" class="hidden px-4 py-6 text-center">
                <i class="fa-solid fa-user-slash text-2xl text-gray-300 dark:text-gray-600 mb-2"></i>
                <p class="text-sm text-gray-400">Sin resultados con contrato vigente para este período</p>
            </div>
            <div id="solicitar-items" class="divide-y divide-gray-100 dark:divide-dark-border max-h-52 overflow-y-auto"></div>
        </div>

        {{-- Persona seleccionada --}}
        <div id="solicitar-seleccionado" class="hidden">
            <div class="rounded-xl bg-primary/5 border border-primary/20 p-4 flex items-center gap-3">
                <div id="solic-avatar" class="w-11 h-11 rounded-full bg-primary/15 text-primary font-bold text-base flex items-center justify-center flex-shrink-0"></div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 dark:text-white text-sm" id="solicitar-nombre-display"></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate" id="solicitar-info-display"></p>
                </div>
                <button type="button" onclick="limpiarSeleccionPersona()"
                    class="text-gray-300 hover:text-red-500 cursor-pointer flex-shrink-0 transition-colors p-1" title="Cambiar">
                    <i class="fa-solid fa-rotate-left text-sm"></i>
                </button>
            </div>
        </div>

        {{-- Motivo --}}
        <div>
            <p class="text-[11px] font-bold uppercase tracking-widest text-light-muted dark:text-dark-muted mb-2 flex items-center gap-1.5">
                <i class="fa-solid fa-comment-dots text-primary text-[13px]"></i> Motivo <span class="normal-case font-normal tracking-normal text-gray-400">(opcional)</span>
            </p>
            <textarea id="solicitar-motivo" rows="2"
                placeholder="¿Por qué necesitas este colaborador?"
                class="form-input w-full resize-none text-sm"></textarea>
        </div>

        <input type="hidden" id="solicitar-empleado-id" />
        <input type="hidden" id="solicitar-supervisor-id" />
    </div>

    {{-- Error --}}
    <div id="solicitar-error" class="hidden mx-8 mb-2 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2 flex items-center gap-2">
        <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
        <span id="solicitar-error-texto"></span>
    </div>

    {{-- Footer paso 1 --}}
    <div id="solic-footer-1" class="px-8 py-4 border-t border-light-border dark:border-dark-border flex justify-between items-center bg-light-bg dark:bg-dark-bg">
        <button type="button" onclick="closeModal('modal-solicitar')"
            class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors cursor-pointer">
            Cancelar
        </button>
        <button type="button" id="btn-solic-siguiente" onclick="solicIrPaso2()"
            class="btn btn-primary flex items-center gap-2 cursor-pointer">
            Siguiente <i class="fa-solid fa-arrow-right text-xs"></i>
        </button>
    </div>

    {{-- Footer paso 2 --}}
    <div id="solic-footer-2" class="hidden px-8 py-4 border-t border-light-border dark:border-dark-border flex justify-between items-center bg-light-bg dark:bg-dark-bg">
        <button type="button" onclick="solicIrPaso1()"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors cursor-pointer">
            <i class="fa-solid fa-arrow-left text-xs"></i> Atrás
        </button>
        <button type="button" id="btn-solicitar-submit" onclick="submitSolicitar()"
            class="btn btn-primary flex items-center gap-2 cursor-pointer">
            <i class="fa-solid fa-paper-plane text-xs"></i> Enviar solicitud
        </button>
    </div>

</x-ui.modal-shell>
