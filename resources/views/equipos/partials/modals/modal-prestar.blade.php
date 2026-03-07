{{-- ═══════════════════════════════════════════════════════════════════════
     MODAL: PRESTAR colaborador (2 pasos)
════════════════════════════════════════════════════════════════════════ --}}
<x-ui.modal-shell id="modal-prestar" max-width="520px">

    {{-- Header con step indicator --}}
    <div class="px-8 pt-6 pb-4 border-b border-light-border dark:border-dark-border">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                        <i class="fa-solid fa-arrow-up-from-bracket text-primary text-sm"></i>
                    </span>
                    Prestar colaborador
                </h2>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5" id="prestar-subtitulo">Selecciona el período del préstamo</p>
            </div>
            <button onclick="closeModal('modal-prestar')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <div class="flex items-center gap-2 mt-4">
            <div class="flex items-center gap-1.5">
                <div id="prestar-dot-1" class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-colors bg-primary text-white">1</div>
                <span id="prestar-label-1" class="text-xs font-medium text-primary">Período</span>
            </div>
            <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700 mx-1"></div>
            <div class="flex items-center gap-1.5">
                <div id="prestar-dot-2" class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-colors bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500">2</div>
                <span id="prestar-label-2" class="text-xs font-medium text-gray-400 dark:text-gray-500">Colaborador</span>
            </div>
        </div>
    </div>

    {{-- ── PASO 1: Período ── --}}
    <div id="prestar-step-1" class="px-8 py-6 space-y-5">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-widest text-light-muted dark:text-dark-muted mb-3 flex items-center gap-1.5">
                <i class="fa-solid fa-calendar-range text-primary text-[13px]"></i> Período del préstamo
            </p>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Desde</label>
                    <x-forms.text-input type="date" id="prestar-fecha-inicio" oninput="actualizarDuracion('prestar')" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Hasta</label>
                    <x-forms.text-input type="date" id="prestar-fecha-fin" oninput="actualizarDuracion('prestar')" />
                </div>
            </div>
            <div id="prestar-duracion-wrap" class="hidden mt-3 flex justify-center">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-300 rounded-full text-sm font-medium">
                    <i class="fa-solid fa-calendar-check text-xs"></i>
                    <span id="prestar-duracion-texto"></span>
                </span>
            </div>
        </div>
    </div>

    {{-- ── PASO 2: Colaborador + destino + motivo ── --}}
    <div id="prestar-step-2" class="hidden px-8 py-5 space-y-4 overflow-y-auto" style="max-height:62vh">

        {{-- Resumen período --}}
        <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-300 text-sm font-medium">
            <i class="fa-solid fa-calendar-range text-xs"></i>
            <span id="prestar-rango-texto"></span>
            <span id="prestar-rango-dias" class="ml-auto text-xs font-normal text-blue-400 dark:text-blue-400"></span>
        </div>

        @if($misColaboradores->isEmpty())
            <div class="text-center py-6">
                <i class="fa-solid fa-user-slash text-3xl text-gray-300 dark:text-gray-600 mb-3"></i>
                <p class="text-sm text-gray-500 dark:text-gray-400">No tienes colaboradores asignados en tu jerarquía.</p>
            </div>
        @else
            {{-- Filtro + lista (multi-selección hasta 3) --}}
            <div class="space-y-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-sm"></i>
                    <input type="text" id="prestar-filtro"
                        placeholder="Filtrar colaboradores..."
                        oninput="filtrarColaboradores()"
                        autocomplete="off"
                        class="form-input w-full pl-10">
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
                    <div id="prestar-lista" class="divide-y divide-gray-100 dark:divide-dark-border max-h-40 overflow-y-auto">
                        @foreach($misColaboradores as $persona)
                        @php
                            $cont  = $contratosFecha[$persona->id] ?? null;
                            $cIni  = $cont?->inicio_contrato?->format('Y-m-d') ?? '';
                            $cFin  = $cont ? ($cont->fecha_renuncia ?? $cont->fin_contrato)?->format('Y-m-d') ?? '' : '';
                        @endphp
                        <div class="prestar-item flex items-center gap-3 px-4 py-2.5 cursor-pointer transition-colors"
                            data-id="{{ $persona->id }}"
                            data-nombre="{{ $persona->nombre_corto ?? $persona->nombres }}"
                            data-contrato-inicio="{{ $cIni }}"
                            data-contrato-fin="{{ $cFin }}"
                            onclick="prestarSeleccionar(this)">
                            <div class="prestar-item-avatar w-8 h-8 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center flex-shrink-0 transition-colors">
                                {{ mb_strtoupper(mb_substr($persona->apellido_paterno ?? '?', 0, 1) . mb_substr(explode(' ', trim($persona->nombres ?? '?'))[0], 0, 1)) }}
                            </div>
                            <span class="text-sm text-gray-800 dark:text-white flex-1">{{ $persona->nombre_corto ?? $persona->nombres }}</span>
                            <i class="prestar-check fa-solid fa-check text-primary text-xs hidden"></i>
                            <i class="prestar-plus  fa-solid fa-plus  text-gray-300 text-xs"></i>
                        </div>
                        @endforeach
                    </div>
                    <p id="prestar-sin-resultados" class="hidden px-4 py-5 text-center text-sm text-gray-400">Sin colaboradores con contrato vigente para este período</p>
                </div>
                <p id="prestar-contador" class="hidden text-right text-xs text-gray-400 dark:text-gray-500"></p>
            </div>

            {{-- Supervisor destino --}}
            <div class="space-y-2">
                <p class="text-[11px] font-bold uppercase tracking-widest text-light-muted dark:text-dark-muted flex items-center gap-1.5">
                    <i class="fa-solid fa-user-tie text-primary text-[13px]"></i> Supervisor destino
                </p>
                <div id="prestar-destino-seleccionado" class="hidden rounded-xl bg-primary/5 border border-primary/20 p-3 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-primary/15 text-primary font-bold text-xs flex items-center justify-center flex-shrink-0" id="prestar-destino-avatar"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white" id="prestar-destino-nombre"></p>
                        <p class="text-xs text-gray-400 mt-0.5" id="prestar-destino-info"></p>
                    </div>
                    <button type="button" onclick="limpiarDestinoPrestar()" class="text-gray-300 hover:text-red-500 cursor-pointer transition-colors p-1">
                        <i class="fa-solid fa-rotate-left text-sm"></i>
                    </button>
                </div>
                <div id="prestar-destino-lista" class="rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
                    <div class="divide-y divide-gray-100 dark:divide-dark-border max-h-40 overflow-y-auto">
                        @foreach($supervisores as $sup)
                        @php $asig = $sup->asignaciones->first(); @endphp
                        <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-primary/5 cursor-pointer transition-colors group"
                            data-id="{{ $sup->id }}" data-nombre="{{ $sup->name }}"
                            data-rol="{{ $asig?->rol ?? '—' }}" data-campana="{{ $asig?->campana?->nombre ?? '—' }}"
                            onclick="prestarSeleccionarDestino(this)">
                            <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 font-bold text-xs flex items-center justify-center flex-shrink-0 group-hover:bg-primary/20 group-hover:text-primary transition-colors">
                                {{ mb_strtoupper(mb_substr($sup->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $sup->name }}</p>
                                <p class="text-xs text-gray-400">[<strong class="text-gray-500 dark:text-gray-300">{{ $asig?->rol ?? '—' }}</strong>] [<strong class="text-gray-500 dark:text-gray-300">{{ $asig?->campana?->nombre ?? '—' }}</strong>]</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-gray-300 group-hover:text-primary text-xs flex-shrink-0"></i>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Motivo --}}
            <div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-light-muted dark:text-dark-muted mb-2 flex items-center gap-1.5">
                    <i class="fa-solid fa-comment-dots text-primary text-[13px]"></i> Motivo <span class="normal-case font-normal tracking-normal text-gray-400">(opcional)</span>
                </p>
                <textarea id="prestar-motivo" rows="2" placeholder="Describe brevemente el motivo del préstamo..."
                    class="form-input w-full resize-none text-sm"></textarea>
            </div>
        @endif
    </div>

    {{-- Error --}}
    <div id="prestar-error" class="hidden mx-8 mb-2 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2 flex items-center gap-2">
        <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
        <span id="prestar-error-texto"></span>
    </div>

    {{-- Footer paso 1 --}}
    <div id="prestar-footer-1" class="px-8 py-4 border-t border-light-border dark:border-dark-border flex justify-between items-center bg-light-bg dark:bg-dark-bg">
        <button type="button" onclick="closeModal('modal-prestar')"
            class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors cursor-pointer">
            Cancelar
        </button>
        <button type="button" onclick="prestarIrPaso2()"
            class="btn btn-primary flex items-center gap-2 cursor-pointer">
            Siguiente <i class="fa-solid fa-arrow-right text-xs"></i>
        </button>
    </div>

    {{-- Footer paso 2 --}}
    <div id="prestar-footer-2" class="hidden px-8 py-4 border-t border-light-border dark:border-dark-border flex justify-between items-center bg-light-bg dark:bg-dark-bg">
        <button type="button" onclick="prestarIrPaso1()"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors cursor-pointer">
            <i class="fa-solid fa-arrow-left text-xs"></i> Atrás
        </button>
        <button type="button" id="btn-prestar-submit" onclick="submitPrestar()"
            class="btn btn-primary flex items-center gap-2 cursor-pointer">
            <i class="fa-solid fa-arrow-up-from-bracket text-xs"></i> Confirmar préstamo
        </button>
    </div>

</x-ui.modal-shell>
