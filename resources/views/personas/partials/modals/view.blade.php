<!-- MODAL VER — Profile Card -->
<x-ui.modal-shell id="view-modal" max-width="820px">

            <!-- ══ HERO ══ -->
            <div id="view-hero" class="relative px-8 pt-8 pb-7 transition-all duration-300">

                <!-- Botón cerrar -->
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
                        <p id="view-nombre-completo"
                            class="text-2xl font-extrabold text-light-text dark:text-dark-text leading-tight truncate">—</p>

                        <!-- Doc + Fecha + Género -->
                        <div class="flex items-center gap-3 mt-2 flex-wrap text-sm text-light-muted dark:text-dark-muted">
                            <span class="font-mono">
                                <span id="view-tdoc" class="font-medium">—</span>
                                <span class="mx-1 opacity-40">·</span>
                                <span id="view-doc" class="font-bold text-light-text dark:text-dark-text tracking-widest">—</span>
                            </span>
                            <span class="opacity-30 hidden sm:inline">|</span>
                            <span class="relative group/tooltip hidden sm:flex items-center gap-1.5 cursor-default">
                                <i class="fa-solid fa-calendar text-xs opacity-60"></i>
                                <span id="view-nac">—</span>
                                <span class="absolute top-full left-1/2 -translate-x-1/2 mt-2 invisible group-hover/tooltip:visible opacity-0 group-hover/tooltip:opacity-100 transition-opacity bg-gray-900 text-white text-[10px] px-2 py-1 rounded shadow-lg whitespace-nowrap z-50 pointer-events-none">
                                    Fecha de Nacimiento
                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 border-4 border-transparent border-b-gray-900"></span>
                                </span>
                            </span>
                            <span class="opacity-30 hidden sm:inline">·</span>
                            <span class="hidden sm:flex items-center gap-1.5">
                                <i class="fa-solid fa-venus-mars text-xs opacity-60"></i>
                                <span id="view-genero">—</span>
                            </span>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Selects ocultos — cascade de geo -->
            <div class="hidden">
                <select id="view-pais">
                    <option value=""></option>
                    @foreach ($paises as $pais)
                        <option value="{{ $pais->id }}">{{ $pais->nombre }} ({{ $pais->codigo_pais }})</option>
                    @endforeach
                </select>
                <select id="view-departamento">
                    <option value=""></option>
                    @foreach ($departamentos as $departamento)
                        <option value="{{ $departamento->id }}" data-pais="{{ $departamento->pais_id }}">{{ $departamento->nombre }}</option>
                    @endforeach
                </select>
                <select id="view-provincia">
                    <option value=""></option>
                    @foreach ($provincias as $provincia)
                        <option value="{{ $provincia->id }}" data-departamento="{{ $provincia->departamento_id }}">{{ $provincia->nombre }}</option>
                    @endforeach
                </select>
                <select id="view-distrito"><option value=""></option></select>
            </div>

            <!-- ══ BODY — 2 columnas ══ -->
            <div class="border-t border-light-border dark:border-dark-border grid grid-cols-1 sm:grid-cols-11 sm:divide-x divide-light-border dark:divide-dark-border">

                <!-- UBICACIÓN (55% → 6/11) -->
                <div class="sm:col-span-6 px-6 py-5">
                    <p class="text-[12px] font-bold uppercase tracking-widest text-light-muted dark:text-dark-muted mb-3 flex items-center gap-1.5">
                        <i class="fa-solid fa-location-dot view-status-icon text-[16px]"></i>
                        Ubicación
                    </p>

                    <!-- Geo 2×2 -->
                    <div class="grid grid-cols-2 gap-x-5 gap-y-3 mb-4">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">País</p>
                            <p id="view-pais-text" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Departamento</p>
                            <p id="view-departamento-text" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Provincia</p>
                            <p id="view-provincia-text" class="text-sm font-semibold text-light-text dark:text-dark-text">—</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Distrito</p>
                            <p id="view-distrito-text" class="text-sm font-bold text-light-text dark:text-dark-text">—</p>
                        </div>
                    </div>

                    <!-- Dirección -->
                    <div class="flex items-start gap-2.5 bg-light-bg dark:bg-dark-bg rounded-lg border border-light-border dark:border-dark-border px-3.5 py-2.5">
                        <i class="fa-solid fa-map-pin view-status-icon text-xs mt-0.5 flex-shrink-0"></i>
                        <p id="view-direccion" class="text-sm text-light-text dark:text-dark-text leading-snug">—</p>
                    </div>
                </div>

                <!-- CONTACTO (45% → 5/11) -->
                <div class="sm:col-span-5 px-6 py-5 border-t sm:border-t-0 border-light-border dark:border-dark-border">
                    <p class="text-[12px] font-bold uppercase tracking-widest text-light-muted dark:text-dark-muted mb-3 flex items-center gap-1.5">
                        <i class="fa-solid fa-address-book view-status-icon text-[16px]"></i>
                        Contacto
                    </p>

                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-light-bg dark:bg-dark-bg border border-light-border dark:border-dark-border flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-phone view-status-icon text-[11px]"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Teléfono</p>
                                <p id="view-telefono" class="text-sm font-bold text-light-text dark:text-dark-text font-mono tracking-widest">—</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-light-bg dark:bg-dark-bg border border-light-border dark:border-dark-border flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-envelope view-status-icon text-[11px]"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Personal</p>
                                <p id="view-correo-pers" class="text-sm font-medium text-light-text dark:text-dark-text truncate">—</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-light-bg dark:bg-dark-bg border border-light-border dark:border-dark-border flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-building view-status-icon text-[11px]"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-dark-muted mb-0.5">Corporativo</p>
                                <p id="view-correo-corp" class="text-sm font-medium text-light-text dark:text-dark-text truncate">—</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ══ FOOTER ══ -->
            <x-ui.modal-footer modal-id="view-modal" cancel-label="Cerrar" />

</x-ui.modal-shell>
