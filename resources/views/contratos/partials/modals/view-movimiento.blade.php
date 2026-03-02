{{-- MODAL VER MOVIMIENTO --}}
<x-ui.modal-shell id="view-movimiento-modal" max-width="860px">

    <!-- ══ HERO ══ -->
    <div id="view-mov-hero" class="relative px-8 pt-8 pb-7 transition-all duration-300">

        <button onclick="closeModal('view-movimiento-modal')"
            class="absolute top-4 right-4 w-8 h-8 rounded-full flex items-center justify-center text-light-muted hover:text-light-text dark:hover:text-dark-text hover:bg-black/8 dark:hover:bg-white/10 transition-colors z-10">
            <i class="fa-solid fa-times"></i>
        </button>

        <div class="flex items-end gap-6">
            <!-- Avatar con iniciales -->
            <div id="view-mov-avatar"
                class="w-20 h-20 rounded-2xl flex-shrink-0 flex items-center justify-center text-white text-2xl font-black shadow-lg ring-4 ring-white dark:ring-dark-card transition-colors duration-300">
                <span id="view-mov-avatar-initials">?</span>
            </div>

            <!-- Info principal -->
            <div class="flex-1 min-w-0 pb-0.5">
                <!-- Badge estado -->
                <div class="mb-2">
                    <span id="view-mov-estado-badge"
                        class="inline-flex items-center gap-1.5 text-[14px] font-bold uppercase tracking-wide transition-colors duration-300">
                        <span class="w-2 h-2 rounded-full bg-current"></span>
                        <span id="view-mov-estado-label">—</span>
                    </span>
                </div>

                <!-- Nombre colaborador -->
                <p id="view-mov-nombre" class="text-2xl font-extrabold text-light-text dark:text-dark-text leading-tight truncate">—</p>

                <!-- Chips: tipo | fechas -->
                <div class="flex items-center gap-3 mt-2 flex-wrap text-sm text-light-muted dark:text-dark-muted">
                    <span id="view-mov-tipo-chip" class="font-medium text-light-text dark:text-dark-text">—</span>
                    <span class="opacity-30 hidden sm:inline">|</span>
                    <span class="hidden sm:flex items-center gap-1.5">
                        <i class="fa-solid fa-calendar text-xs opacity-60"></i>
                        <span id="view-mov-inicio-chip">—</span>
                        <span class="opacity-40 mx-0.5">→</span>
                        <span id="view-mov-fin-chip">—</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ BODY — 2 columnas ══ -->
    <div class="border-t border-light-border dark:border-dark-border grid grid-cols-1 sm:grid-cols-11 sm:divide-x divide-light-border dark:divide-dark-border">

        <!-- Izquierda: Datos del Movimiento + Vigencia (6/11) -->
        <div class="sm:col-span-6 px-6 py-5 space-y-4">

            <p class="text-[12px] font-bold uppercase tracking-widest text-light-muted dark:text-dark-muted flex items-center gap-1.5">
                <i class="fa-solid fa-file-contract view-status-icon text-[15px]"></i>
                Datos del Movimiento
            </p>

            <div class="grid grid-cols-2 gap-x-5 gap-y-3">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Cargo</p>
                    <p id="view-mov-cargo" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Planilla</p>
                    <p id="view-mov-planilla" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Condición</p>
                    <p id="view-mov-condicion" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Centro de Costo</p>
                    <p id="view-mov-centro-costo" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Familia</p>
                    <p id="view-mov-familia" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Asig. Familiar</p>
                    <p id="view-mov-asignacion" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                </div>
            </div>

            <!-- Vigencia card -->
            <p class="text-[12px] font-bold uppercase tracking-widest text-light-muted dark:text-dark-muted flex items-center gap-1.5">
                <i class="fa-solid fa-calendar-days view-status-icon text-[15px]"></i>
                Vigencia
            </p>
            <div class="bg-light-bg dark:bg-dark-bg rounded-lg border border-light-border dark:border-dark-border px-3.5 py-2.5">
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-[9px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Inicio</p>
                        <p id="view-mov-inicio" class="text-xs font-bold font-mono text-light-text dark:text-dark-text">—</p>
                    </div>
                    <div>
                        <p class="text-[9px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Fin</p>
                        <p id="view-mov-fin" class="text-xs font-bold font-mono text-light-text dark:text-dark-text">—</p>
                    </div>
                    <div>
                        <p class="text-[9px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Registrado</p>
                        <p id="view-mov-fecha-registro" class="text-xs font-bold text-light-text dark:text-dark-text">—</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Derecha: Remuneración + Previsional (5/11) -->
        <div class="sm:col-span-5 px-6 py-5 space-y-5 border-t sm:border-t-0 border-light-border dark:border-dark-border">

            <!-- Remuneración -->
            <div>
                <p class="text-[12px] font-bold uppercase tracking-widest text-light-muted dark:text-dark-muted mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-money-bill-wave view-status-icon text-[15px]"></i>
                    Remuneración
                </p>
                <div class="grid grid-cols-2 gap-x-5 gap-y-3">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Haber Básico</p>
                        <p id="view-mov-haber" class="text-sm font-bold font-mono text-light-text dark:text-dark-text">—</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Movilidad</p>
                        <p id="view-mov-movilidad" class="text-sm font-semibold font-mono text-light-text dark:text-dark-text">—</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Moneda</p>
                        <p id="view-mov-moneda" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                    </div>
                </div>
            </div>

            <!-- Previsional / Banco -->
            <div>
                <p class="text-[12px] font-bold uppercase tracking-widest text-light-muted dark:text-dark-muted mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-building-columns view-status-icon text-[15px]"></i>
                    Previsional
                </p>
                <div class="grid grid-cols-2 gap-x-5 gap-y-3 mb-2.5">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Fondo de Pensiones</p>
                        <p id="view-mov-fp" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Banco</p>
                        <p id="view-mov-banco" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                    </div>
                </div>
                <div class="bg-light-bg dark:bg-dark-bg rounded-lg border border-light-border dark:border-dark-border px-3 py-2 space-y-1.5">
                    <div class="flex justify-between items-center gap-2">
                        <p class="text-[9px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted flex-shrink-0">Cuenta</p>
                        <p id="view-mov-numero-cuenta" class="text-xs font-mono font-bold text-light-text dark:text-dark-text truncate">—</p>
                    </div>
                    <div class="flex justify-between items-center gap-2">
                        <p class="text-[9px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted flex-shrink-0">CCI</p>
                        <p id="view-mov-codigo-interbancario" class="text-xs font-mono font-bold text-light-text dark:text-dark-text truncate">—</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ══ FOOTER ══ -->
    <x-ui.modal-footer modal-id="view-movimiento-modal" cancel-label="Cerrar" />

</x-ui.modal-shell>
