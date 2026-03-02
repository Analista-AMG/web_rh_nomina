<!-- MODAL VER CONTRATO -->
<x-ui.modal-shell id="view-modal" max-width="900px">

    <!-- ══ HERO ══ -->
    <div id="view-hero" class="relative px-8 pt-8 pb-7 transition-all duration-300">

        <button onclick="closeModal('view-modal')"
            class="absolute top-4 right-4 w-8 h-8 rounded-full flex items-center justify-center text-light-muted hover:text-light-text dark:hover:text-dark-text hover:bg-black/8 dark:hover:bg-white/10 transition-colors z-10">
            <i class="fa-solid fa-times"></i>
        </button>

        <div class="flex items-end gap-6">
            <!-- Avatar -->
            <div id="view-avatar"
                class="w-20 h-20 rounded-2xl flex-shrink-0 flex items-center justify-center text-white text-2xl font-black shadow-lg ring-4 ring-white dark:ring-dark-card transition-colors duration-300">
                <span id="view-avatar-initials">?</span>
            </div>

            <!-- Info principal -->
            <div class="flex-1 min-w-0 pb-0.5">
                <!-- Badge estado -->
                <div class="mb-2">
                    <span id="view-estado-badge"
                        class="inline-flex items-center gap-1.5 text-[14px] font-bold uppercase tracking-wide transition-colors duration-300">
                        <span class="w-2 h-2 rounded-full bg-current"></span>
                        <span id="view-estado-label">—</span>
                    </span>
                </div>

                <!-- Nombre -->
                <p id="view-nombre" class="text-2xl font-extrabold text-light-text dark:text-dark-text leading-tight truncate">—</p>

                <!-- Chips -->
                <div class="flex items-center gap-3 mt-2 flex-wrap text-sm text-light-muted dark:text-dark-muted">
                    <span class="font-mono font-bold text-light-text dark:text-dark-text tracking-widest">
                        <span id="view-documento-chip">—</span>
                    </span>
                    <span class="opacity-30 hidden sm:inline">|</span>
                    <span class="hidden sm:flex items-center gap-1.5">
                        <i class="fa-solid fa-calendar text-xs opacity-60"></i>
                        <span id="view-inicio-chip">—</span>
                        <span class="opacity-40 mx-0.5">→</span>
                        <span id="view-fin-chip">—</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ BODY — 2 columnas ══ -->
    <div class="border-t border-light-border dark:border-dark-border grid grid-cols-1 sm:grid-cols-11 sm:divide-x divide-light-border dark:divide-dark-border">

        <!-- Izquierda: Contrato + Fechas (6/11) -->
        <div class="sm:col-span-6 px-6 py-5 space-y-4">

            <p class="text-[12px] font-bold uppercase tracking-widest text-light-muted dark:text-dark-muted flex items-center gap-1.5">
                <i class="fa-solid fa-file-contract view-status-icon text-[15px]"></i>
                Contrato
            </p>

            <div class="grid grid-cols-2 gap-x-5 gap-y-3">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Cargo</p>
                    <p id="view-cargo" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Planilla</p>
                    <p id="view-planilla" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Condición</p>
                    <p id="view-condicion" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Centro de Costo</p>
                    <p id="view-centro-costo" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Familia</p>
                    <p id="view-familia" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Período de Prueba</p>
                    <p id="view-periodo-prueba" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                </div>
            </div>

            <!-- Fechas card -->
            <p class="text-[12px] font-bold uppercase tracking-widest text-light-muted dark:text-dark-muted flex items-center gap-1.5">
                <i class="fa-solid fa-calendar-days view-status-icon text-[15px]"></i>
                Vigencia
            </p>
            <div class="flex items-start gap-2.5 bg-light-bg dark:bg-dark-bg rounded-lg border border-light-border dark:border-dark-border px-3.5 py-2.5">
                <div class="grid grid-cols-3 gap-4 flex-1">
                    <div>
                        <p class="text-[9px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Inicio</p>
                        <p id="view-inicio" class="text-xs font-bold font-mono text-light-text dark:text-dark-text">—</p>
                    </div>
                    <div>
                        <p class="text-[9px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Fin</p>
                        <p id="view-fin" class="text-xs font-bold font-mono text-light-text dark:text-dark-text">—</p>
                    </div>
                    <div>
                        <p class="text-[9px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Baja</p>
                        <p id="view-fecha-renuncia" class="text-xs font-bold font-mono text-light-text dark:text-dark-text">—</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Derecha: Remuneración + Banco (5/11) -->
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
                        <p id="view-haber" class="text-sm font-bold font-mono text-light-text dark:text-dark-text">—</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Asig. Familiar</p>
                        <p id="view-asignacion" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Movilidad</p>
                        <p id="view-movilidad" class="text-sm font-semibold font-mono text-light-text dark:text-dark-text">—</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Moneda</p>
                        <p id="view-moneda" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                    </div>
                </div>
            </div>

            <!-- Banco / Previsional -->
            <div>
                <p class="text-[12px] font-bold uppercase tracking-widest text-light-muted dark:text-dark-muted mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-building-columns view-status-icon text-[15px]"></i>
                    DATOS BANCARIOS
                </p>
                <div class="space-y-2.5">
                    <div class="grid grid-cols-2 gap-x-5">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Fondo Pensiones</p>
                            <p id="view-fp" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Banco</p>
                            <p id="view-banco" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                        </div>
                    </div>
                    <div class="bg-light-bg dark:bg-dark-bg rounded-lg border border-light-border dark:border-dark-border px-3 py-2 space-y-1.5">
                        <div class="flex justify-between items-center gap-2">
                            <p class="text-[9px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted flex-shrink-0">Cuenta</p>
                            <p id="view-numero-cuenta" class="text-xs font-mono font-bold text-light-text dark:text-dark-text truncate">—</p>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <p class="text-[9px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted flex-shrink-0">CCI</p>
                            <p id="view-codigo-interbancario" class="text-xs font-mono font-bold text-light-text dark:text-dark-text truncate">—</p>
                        </div>
                        <div class="border-t border-light-border dark:border-dark-border pt-1.5 flex justify-between items-center gap-2">
                            <p class="text-[9px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted flex-shrink-0">Cuenta CTS</p>
                            <p id="view-numero-cuenta-cts" class="text-xs font-mono font-bold text-light-text dark:text-dark-text truncate">—</p>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <p class="text-[9px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted flex-shrink-0">CCI CTS</p>
                            <p id="view-codigo-interbancario-cts" class="text-xs font-mono font-bold text-light-text dark:text-dark-text truncate">—</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ══ FOOTER ══ -->
    <x-ui.modal-footer modal-id="view-modal" cancel-label="Cerrar" />

</x-ui.modal-shell>
