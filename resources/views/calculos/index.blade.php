<x-app-layout>
    @section('title', 'Cálculos - AMG International')

    <header class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Cálculos de Remuneraciones</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Dashboard analítico de nómina por periodo</p>
        </div>
        <span id="badge-periodo" class="hidden items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
            <i class="fa-solid fa-calendar-check"></i>
            <span id="badge-periodo-text"></span>
        </span>
    </header>

    {{-- Filtros --}}
    <div class="mb-6 bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border overflow-hidden shadow-sm">
        <div class="px-5 py-3 border-b border-light-border dark:border-dark-border flex items-center gap-2">
            <i class="fa-solid fa-sliders text-primary text-sm"></i>
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Filtros</span>
        </div>
        <div class="px-5 py-4">
            <div class="flex flex-wrap items-end gap-4">

                <div class="min-w-[160px]">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Periodo <span class="text-red-500">*</span></label>
                    <select id="f-periodo" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                        <option value="">Seleccione</option>
                        @foreach($periodos as $p)
                            <option value="{{ $p }}">{{ $p }}</option>
                        @endforeach
                    </select>
                </div>

                @if($empresas->isNotEmpty())
                <div class="min-w-[150px]">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Empresa</label>
                    <select id="f-empresa" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                        <option value="">Todas</option>
                        @foreach($empresas as $e)
                            <option value="{{ $e }}">{{ $e }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if($regimenes->isNotEmpty())
                <div class="min-w-[140px]">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Régimen</label>
                    <select id="f-regimen" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                        <option value="">Todos</option>
                        @foreach($regimenes as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if($centrosCosto->isNotEmpty())
                <div class="min-w-[150px]">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Centro Costo</label>
                    <select id="f-centro" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                        <option value="">Todos</option>
                        @foreach($centrosCosto as $cc)
                            <option value="{{ $cc }}">{{ $cc }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if($familias->isNotEmpty())
                <div class="min-w-[150px]">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Familia</label>
                    <select id="f-familia" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                        <option value="">Todas</option>
                        @foreach($familias as $f)
                            <option value="{{ $f }}">{{ $f }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="flex gap-2 flex-wrap ml-auto">
                    @can('calculos.execute')
                    <button type="button" id="btn-ejecutar" disabled
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                        <i class="fa-solid fa-play mr-1.5"></i> Procesar
                    </button>
                    @endcan
                    <button type="button" id="btn-consultar" disabled
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                        <i class="fa-solid fa-magnifying-glass mr-1.5"></i> Consultar
                    </button>
                    <button type="button" id="btn-exportar"
                        class="hidden px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium cursor-pointer">
                        <i class="fa-solid fa-file-excel mr-1.5"></i> Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Status --}}
    <div id="status-container" class="mb-5 hidden">
        <div id="status-box" class="rounded-xl p-4 shadow-sm border flex items-center gap-3">
            <div id="status-icon"></div>
            <div>
                <p id="status-text" class="text-sm font-medium"></p>
                <p id="status-detail" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"></p>
            </div>
        </div>
    </div>

    {{-- Dashboard --}}
    <div id="dashboard" class="hidden space-y-6">

        {{-- KPIs resumen --}}
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-users text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Contratos</span>
                </div>
                <p class="text-2xl font-bold text-gray-800 dark:text-white" id="k-contratos">—</p>
                <p class="text-xs text-gray-400 mt-0.5" id="k-contratos-sub">procesados</p>
            </div>
            <div class="bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 rounded-lg bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-arrow-up text-teal-600 dark:text-teal-400"></i>
                    </div>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">T. Haberes</span>
                </div>
                <p class="text-xl font-bold text-gray-800 dark:text-white font-mono" id="k-haberes">—</p>
                <p class="text-xs text-gray-400 mt-0.5">total periodo</p>
            </div>
            <div class="bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-arrow-down text-red-600 dark:text-red-400"></i>
                    </div>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">T. Descuentos</span>
                </div>
                <p class="text-xl font-bold text-gray-800 dark:text-white font-mono" id="k-descuentos">—</p>
                <p class="text-xs text-gray-400 mt-0.5">total periodo</p>
            </div>

            {{-- Desembolsos (quincena + neto) --}}
            <div class="bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border-2 border-green-400 dark:border-green-600">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-circle-dollar-to-slot text-green-600 dark:text-green-400"></i>
                    </div>
                    <span class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wide">Desembolsos</span>
                </div>
                <div class="flex justify-between items-baseline mb-1">
                    <span class="text-[11px] text-gray-400 dark:text-gray-500">1er · Quincena</span>
                    <span class="text-sm font-semibold font-mono text-amber-600 dark:text-amber-400" id="k-quincena">—</span>
                </div>
                <div class="flex justify-between items-baseline">
                    <span class="text-[11px] text-gray-400 dark:text-gray-500">2do · Neto</span>
                    <span class="text-base font-bold font-mono text-green-600 dark:text-green-400" id="k-neto">—</span>
                </div>
            </div>

            <div class="bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-building text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Costo Total</span>
                </div>
                <p class="text-xl font-bold text-gray-800 dark:text-white font-mono" id="k-costo">—</p>
                <p class="text-xs text-gray-400 mt-0.5">costo empleador</p>
            </div>
        </div>

        {{-- Tabs FT / RHE --}}
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-[#1e2836] rounded-lg p-1 w-fit">
            <button type="button" data-regime="" id="tab-todos"
                class="regime-tab px-4 py-1.5 rounded-md text-sm font-semibold transition-colors bg-white dark:bg-[#273142] text-gray-800 dark:text-white shadow-sm">
                Todos
            </button>
            <button type="button" data-regime="planilla" id="tab-planilla"
                class="regime-tab px-4 py-1.5 rounded-md text-sm font-semibold transition-colors text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                General/MYPE <span class="text-xs font-normal" id="tab-planilla-cnt"></span>
            </button>
            <button type="button" data-regime="RHE" id="tab-rhe"
                class="regime-tab px-4 py-1.5 rounded-md text-sm font-semibold transition-colors text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                RHE <span class="text-xs font-normal" id="tab-rhe-cnt"></span>
            </button>
        </div>

        {{-- ── SECCIÓN HABERES ── --}}
        <div>
            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Haberes</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- Asignación familiar --}}
                <div class="bg-white dark:bg-[#273142] rounded-xl p-5 shadow-sm border border-light-border dark:border-dark-border">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                            <i class="fa-solid fa-baby text-purple-600 dark:text-purple-400 text-sm"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Asignación Familiar</h3>
                        <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">General/MYPE</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Total pagado</span>
                            <span class="text-sm font-bold font-mono text-gray-800 dark:text-white" id="af-total">—</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Beneficiarios</span>
                            <span class="text-sm font-semibold text-purple-600 dark:text-purple-400" id="af-benef">—</span>
                        </div>
                    </div>
                </div>

                {{-- Feriados --}}
                <div class="bg-white dark:bg-[#273142] rounded-xl p-5 shadow-sm border border-light-border dark:border-dark-border">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <i class="fa-solid fa-calendar-day text-amber-600 dark:text-amber-400 text-sm"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Feriados</h3>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Días acumulados</span>
                            <span class="text-sm font-semibold text-amber-600 dark:text-amber-400" id="fer-dias">—</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Monto pagado</span>
                            <span class="text-sm font-bold font-mono text-gray-800 dark:text-white" id="fer-monto">—</span>
                        </div>
                    </div>
                </div>

                {{-- Movilidad --}}
                <div class="bg-white dark:bg-[#273142] rounded-xl p-5 shadow-sm border border-light-border dark:border-dark-border">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                            <i class="fa-solid fa-bus text-teal-600 dark:text-teal-400 text-sm"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Movilidad</h3>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Movilidad bruta</span>
                            <span class="text-sm font-mono text-gray-700 dark:text-gray-300" id="mov-bruto">—</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Adelanto (ef. neto S/0)</span>
                            <span class="text-sm font-mono text-red-500 dark:text-red-400" id="mov-adelanto">—</span>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-2 flex justify-between items-center">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Movilidad real</span>
                            <span class="text-sm font-bold font-mono text-teal-600 dark:text-teal-400" id="mov-real">—</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── SECCIÓN DESCUENTOS ── --}}
        <div>
            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Descuentos y Retenciones</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- Fondo de pensiones --}}
                <div class="bg-white dark:bg-[#273142] rounded-xl p-5 shadow-sm border border-light-border dark:border-dark-border">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                            <i class="fa-solid fa-piggy-bank text-orange-600 dark:text-orange-400 text-sm"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Fondo de Pensiones</h3>
                        <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">General/MYPE</span>
                    </div>
                    <div id="fp-detalle" class="space-y-2"></div>
                </div>

                {{-- Adelantos --}}
                <div class="bg-white dark:bg-[#273142] rounded-xl p-5 shadow-sm border border-light-border dark:border-dark-border">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <i class="fa-solid fa-money-bill-transfer text-amber-600 dark:text-amber-400 text-sm"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Adelantos</h3>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Quincena</span>
                            <span class="text-sm font-mono text-gray-700 dark:text-gray-300" id="adel-quin">—</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Comisión</span>
                            <span class="text-sm font-mono text-gray-700 dark:text-gray-300" id="adel-com">—</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Movilidad</span>
                            <span class="text-sm font-mono text-gray-700 dark:text-gray-300" id="adel-mov">—</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Gratificación</span>
                            <span class="text-sm font-mono text-gray-700 dark:text-gray-300" id="adel-grat">—</span>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-2 flex justify-between items-center">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Total</span>
                            <span class="text-sm font-bold font-mono text-red-600 dark:text-red-400" id="adel-total">—</span>
                        </div>
                    </div>
                </div>

                {{-- IR 4ta / Bonos --}}
                <div id="bloque-ir4ta" class="bg-white dark:bg-[#273142] rounded-xl p-5 shadow-sm border border-light-border dark:border-dark-border">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <i class="fa-solid fa-receipt text-red-600 dark:text-red-400 text-sm"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">IR 4ta Categoría</h3>
                        <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">Solo RHE</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Total retenido (8%)</span>
                            <span class="text-sm font-bold font-mono text-red-600 dark:text-red-400" id="ir-total">—</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Personas afectas</span>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300" id="ir-afectos">—</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── SECCIÓN BONOS ── --}}
        <div>
            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Bonos y Conceptos Variables</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Bonos afectos --}}
                <div class="bg-white dark:bg-[#273142] rounded-xl p-5 shadow-sm border border-light-border dark:border-dark-border">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                            <i class="fa-solid fa-star text-indigo-600 dark:text-indigo-400 text-sm"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Bonos Afectos AFP/EsSalud</h3>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Rendimiento</span>
                            <span class="text-sm font-mono text-gray-700 dark:text-gray-300" id="bono-rend">—</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Nocturnidad</span>
                            <span class="text-sm font-mono text-gray-700 dark:text-gray-300" id="bono-noct">—</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Mensual</span>
                            <span class="text-sm font-mono text-gray-700 dark:text-gray-300" id="bono-mens">—</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Reintegro</span>
                            <span class="text-sm font-mono text-gray-700 dark:text-gray-300" id="bono-rein">—</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Extra 9%</span>
                            <span class="text-sm font-mono text-gray-700 dark:text-gray-300" id="bono-9">—</span>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-2 flex justify-between items-center">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Total afectos</span>
                            <span class="text-sm font-bold font-mono text-indigo-600 dark:text-indigo-400" id="bono-total-afecto">—</span>
                        </div>
                    </div>
                </div>

                {{-- Maqueta inafecto --}}
                <div class="bg-white dark:bg-[#273142] rounded-xl p-5 shadow-sm border border-light-border dark:border-dark-border">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <i class="fa-solid fa-file-invoice text-gray-600 dark:text-gray-400 text-sm"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Conceptos Inafectos</h3>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Maqueta Inafecto</span>
                            <span class="text-sm font-bold font-mono text-gray-800 dark:text-white" id="maq-total">—</span>
                        </div>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-3">No afectan base AFP ni EsSalud. Corresponde principalmente a NEUSOFT ALIEXPRESS.</p>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── SECCIÓN COSTO EMPLEADOR FT ── --}}
        <div id="seccion-provisiones">
            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Costo Empleador — General / MYPE</p>
            <div class="bg-white dark:bg-[#273142] rounded-xl p-5 shadow-sm border border-blue-200 dark:border-blue-800/50">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-calculator text-blue-600 dark:text-blue-400 text-sm"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Provisiones del Empleador</h3>
                    <span class="ml-2 text-[10px] px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">No se descuentan al trabajador</span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-4">
                    <div class="text-center">
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">B. Comp.</p>
                        <p class="text-sm font-bold font-mono text-gray-800 dark:text-white" id="prov-bcomp">—</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Movilidad</p>
                        <p class="text-sm font-bold font-mono text-gray-800 dark:text-white" id="prov-movilidad">—</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">EsSalud</p>
                        <p class="text-sm font-bold font-mono text-gray-800 dark:text-white" id="prov-essalud">—</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Prov. Grat.</p>
                        <p class="text-sm font-bold font-mono text-gray-800 dark:text-white" id="prov-grat">—</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Bono 9%</p>
                        <p class="text-sm font-bold font-mono text-gray-800 dark:text-white" id="prov-9pct">—</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Prov. CTS</p>
                        <p class="text-sm font-bold font-mono text-gray-800 dark:text-white" id="prov-cts">—</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Prov. Vac.</p>
                        <p class="text-sm font-bold font-mono text-gray-800 dark:text-white" id="prov-vac">—</p>
                    </div>
                    <div class="text-center bg-blue-50 dark:bg-blue-900/20 rounded-lg p-2">
                        <p class="text-[10px] text-blue-600 dark:text-blue-400 uppercase font-semibold mb-1">T. Provisiones</p>
                        <p class="text-sm font-bold font-mono text-blue-700 dark:text-blue-300" id="prov-total">—</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Distribución por empresa --}}
        <div class="bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border overflow-hidden">
            <div class="px-5 py-3 border-b border-light-border dark:border-dark-border flex items-center gap-2">
                <i class="fa-solid fa-table text-primary text-sm"></i>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Distribución por Empresa</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-[#1e2836]">
                        <tr>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Empresa</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Régimen</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ctos.</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">T. Haberes</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">T. Desc.</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Neto S/</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">EsSalud</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Provisiones</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">IR 4ta</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Costo Total</th>
                        </tr>
                    </thead>
                    <tbody id="dist-tbody" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Estado vacío --}}
    <div id="empty-state" class="bg-white dark:bg-[#273142] rounded-xl p-12 text-center shadow-sm border border-light-border dark:border-dark-border">
        <i class="fa-solid fa-calculator text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <p class="text-gray-500 dark:text-gray-400">Selecciona un periodo y pulsa <strong>Consultar</strong> para ver el dashboard de nómina.</p>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const elPeriodo  = document.getElementById('f-periodo');
        const elEmpresa  = document.getElementById('f-empresa');
        const elRegimen  = document.getElementById('f-regimen');
        const elCentro   = document.getElementById('f-centro');
        const elFamilia  = document.getElementById('f-familia');
        const btnEjecuta = document.getElementById('btn-ejecutar');
        const btnConsult = document.getElementById('btn-consultar');
        const btnExport  = document.getElementById('btn-exportar');

        let activeRegime = '';

        const fmt  = v => 'S/ ' + parseFloat(v || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const set  = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

        function updateButtons() {
            const ok = elPeriodo.value !== '';
            if (btnEjecuta) btnEjecuta.disabled = !ok;
            btnConsult.disabled = !ok;
        }
        elPeriodo.addEventListener('change', updateButtons);
        updateButtons();

        function getParams(regime) {
            const p = new URLSearchParams({ periodo: elPeriodo.value });
            if (elEmpresa  && elEmpresa.value)  p.append('empresa',      elEmpresa.value);
            if (elRegimen  && elRegimen.value)  p.append('regimen',      elRegimen.value);
            if (elCentro   && elCentro.value)   p.append('centro_costo', elCentro.value);
            if (elFamilia  && elFamilia.value)  p.append('familia',      elFamilia.value);
            if (regime) p.append('regime_tipo', regime);
            return p;
        }

        // ── Tabs FT / RHE ──────────────────────────────────────────────────
        document.querySelectorAll('.regime-tab').forEach(btn => {
            btn.addEventListener('click', function () {
                activeRegime = this.dataset.regime;
                document.querySelectorAll('.regime-tab').forEach(b => {
                    b.className = 'regime-tab px-4 py-1.5 rounded-md text-sm font-semibold transition-colors text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200';
                });
                this.className = 'regime-tab px-4 py-1.5 rounded-md text-sm font-semibold transition-colors bg-white dark:bg-[#273142] text-gray-800 dark:text-white shadow-sm';
                if (document.getElementById('dashboard').classList.contains('hidden')) return;
                fetchStats(activeRegime);
            });
        });

        // ── Status ─────────────────────────────────────────────────────────
        function showStatus(type, text, detail) {
            const box  = document.getElementById('status-box');
            const icon = document.getElementById('status-icon');
            const txt  = document.getElementById('status-text');
            const det  = document.getElementById('status-detail');
            document.getElementById('status-container').classList.remove('hidden');
            box.className = 'rounded-xl p-4 shadow-sm border flex items-center gap-3 ';
            const map = {
                loading: { cls: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800', icon: '<div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>', tcls: 'text-sm font-medium text-blue-700 dark:text-blue-300' },
                success: { cls: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800', icon: '<i class="fa-solid fa-circle-check text-xl text-green-600"></i>', tcls: 'text-sm font-medium text-green-700 dark:text-green-300' },
                error:   { cls: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800', icon: '<i class="fa-solid fa-circle-xmark text-xl text-red-600"></i>', tcls: 'text-sm font-medium text-red-700 dark:text-red-300' },
            };
            box.classList.add(...map[type].cls.split(' '));
            icon.innerHTML  = map[type].icon;
            txt.className   = map[type].tcls;
            txt.textContent  = text;
            det.textContent  = detail || '';
        }
        function hideStatus() { document.getElementById('status-container').classList.add('hidden'); }

        // ── Render dashboard ───────────────────────────────────────────────
        function renderDashboard(data) {
            const k = data.kpis;

            // KPIs
            set('k-contratos', k.contratos.toLocaleString('es-PE'));
            set('k-contratos-sub', `Gral/MYPE: ${k.contratos_ft} · RHE: ${k.contratos_rhe}`);
            set('k-haberes',    fmt(k.total_haberes));
            set('k-descuentos', fmt(k.total_descuentos));
            set('k-quincena',   fmt(k.adelanto_quincena));
            set('k-neto',       fmt(k.neto_soles));
            set('k-costo',      fmt(k.costo_total));

            // Tab counts
            set('tab-planilla-cnt', `(${k.contratos_ft})`);
            set('tab-rhe-cnt', `(${k.contratos_rhe})`);

            // Badge periodo
            const badge = document.getElementById('badge-periodo');
            badge.classList.remove('hidden');
            badge.classList.add('inline-flex');
            document.getElementById('badge-periodo-text').textContent = elPeriodo.value;

            // Haberes
            set('af-total',  fmt(data.asig_familiar.total));
            set('af-benef',  data.asig_familiar.beneficiarios + ' personas');
            set('fer-dias',  data.feriados.dias_total + ' días');
            set('fer-monto', fmt(data.feriados.monto_total));
            set('mov-bruto',   fmt(data.movilidad.bruto));
            set('mov-adelanto', '−' + fmt(data.movilidad.adelanto));
            set('mov-real',    fmt(data.movilidad.real));

            // Fondo pensiones
            const fpEl = document.getElementById('fp-detalle');
            fpEl.innerHTML = '';
            data.fp.detalle.forEach(d => {
                const total = (d.aporte || 0) + (d.prima || 0) + (d.comision || 0);
                fpEl.innerHTML += `
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">${d.fondo_pensiones} <span class="text-gray-400 dark:text-gray-500">(${d.contratos})</span></span>
                        <span class="text-sm font-mono text-gray-700 dark:text-gray-300">${fmt(total)}</span>
                    </div>`;
            });

            // Adelantos
            set('adel-quin',  fmt(data.adelantos.quincena));
            set('adel-com',   fmt(data.adelantos.comision));
            set('adel-mov',   fmt(data.adelantos.movilidad));
            set('adel-grat',  fmt(data.adelantos.gratificacion));
            set('adel-total', fmt(data.adelantos.total));

            // IR 4ta
            set('ir-total',   fmt(data.ir_4ta.total));
            set('ir-afectos', data.ir_4ta.personas_afectas + ' personas');
            const bloqueIR = document.getElementById('bloque-ir4ta');
            if (data.ir_4ta.total > 0) bloqueIR.classList.remove('opacity-40');
            else bloqueIR.classList.add('opacity-40');

            // Bonos
            set('bono-rend',         fmt(data.bonos.rendimiento));
            set('bono-noct',         fmt(data.bonos.nocturnidad));
            set('bono-mens',         fmt(data.bonos.mensual));
            set('bono-rein',         fmt(data.bonos.reintegro));
            set('bono-9',            fmt(data.bonos.extra_9pct));
            set('bono-total-afecto', fmt(data.bonos.total_afectos));
            set('maq-total',         fmt(data.bonos.maqueta_inafecto));

            // Provisiones (FT)
            const secProv = document.getElementById('seccion-provisiones');
            if (data.provisiones.contratos_ft > 0) {
                secProv.classList.remove('hidden');
                set('prov-bcomp',     fmt(data.provisiones.basico_comp));
                set('prov-movilidad', fmt(data.provisiones.movilidad));
                set('prov-essalud',   fmt(data.provisiones.essalud));
                set('prov-grat',      fmt(data.provisiones.prov_grat));
                set('prov-9pct',      fmt(data.provisiones.prov_9pct));
                set('prov-cts',       fmt(data.provisiones.prov_cts));
                set('prov-vac',       fmt(data.provisiones.prov_vac));
                set('prov-total',     fmt(data.provisiones.total_provisiones));
            } else {
                secProv.classList.add('hidden');
            }

            // Distribución
            const tbody = document.getElementById('dist-tbody');
            tbody.innerHTML = '';
            data.distribucion.forEach(row => {
                const esFT = !row.es_rhe;
                tbody.innerHTML += `
                    <tr class="hover:bg-gray-50 dark:hover:bg-[#323d4d] transition-colors">
                        <td class="px-4 py-2.5 text-sm font-medium text-gray-800 dark:text-white">${row.empresa}</td>
                        <td class="px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold ${row.es_rhe ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'}">
                                ${row.regimen}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-center text-sm text-gray-700 dark:text-gray-300">${row.contratos}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-xs text-gray-700 dark:text-gray-300">${fmt(row.total_haberes)}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-xs text-red-600 dark:text-red-400">${fmt(row.total_descuentos)}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-xs font-bold text-green-600 dark:text-green-400">${fmt(row.neto_soles)}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-xs ${esFT ? 'text-gray-700 dark:text-gray-300' : 'text-gray-300 dark:text-gray-600'}">${esFT ? fmt(row.essalud) : '—'}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-xs ${esFT ? 'text-blue-600 dark:text-blue-400' : 'text-gray-300 dark:text-gray-600'}">${esFT ? fmt(row.prov_total) : '—'}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-xs ${row.es_rhe ? 'text-amber-600 dark:text-amber-400' : 'text-gray-300 dark:text-gray-600'}">${row.es_rhe ? fmt(row.ir_8pct) : '—'}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-xs font-semibold text-blue-700 dark:text-blue-300">${fmt(row.costo_total)}</td>
                    </tr>`;
            });

            document.getElementById('dashboard').classList.remove('hidden');
            document.getElementById('empty-state').classList.add('hidden');
            btnExport.classList.remove('hidden');
        }

        // ── Fetch stats ────────────────────────────────────────────────────
        function fetchStats(regime) {
            showStatus('loading', 'Consultando resultados...', '');
            fetch('{{ route("calculos.stats") }}?' + getParams(regime), { headers: { Accept: 'application/json' } })
                .then(r => r.json())
                .then(res => {
                    updateButtons();
                    if (res.success && res.data) {
                        hideStatus();
                        renderDashboard(res.data);
                    } else if (res.success && !res.data) {
                        showStatus('error', 'Sin resultados', 'No hay datos para el periodo con los filtros aplicados.');
                        document.getElementById('dashboard').classList.add('hidden');
                        document.getElementById('empty-state').classList.remove('hidden');
                        btnExport.classList.add('hidden');
                    } else {
                        showStatus('error', 'Error al consultar', res.error || 'Error desconocido');
                    }
                })
                .catch(() => { updateButtons(); showStatus('error', 'Error de conexión', ''); });
        }

        function consultar() {
            if (!elPeriodo.value) return;
            btnConsult.disabled = true;
            if (btnEjecuta) btnEjecuta.disabled = true;
            fetchStats(activeRegime);
        }

        btnConsult.addEventListener('click', consultar);

        // ── Procesar ───────────────────────────────────────────────────────
        if (btnEjecuta) {
            btnEjecuta.addEventListener('click', function () {
                if (!elPeriodo.value) return;
                if (!confirm('¿Procesar nómina del periodo ' + elPeriodo.value + '?')) return;
                btnEjecuta.disabled = true;
                btnConsult.disabled = true;
                showStatus('loading', 'Procesando nómina...', 'Esto puede tomar varios segundos.');

                fetch('{{ route("calculos.ejecutar") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json' },
                    body: JSON.stringify({ periodo: elPeriodo.value })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const info = res.data;
                        showStatus('success', 'Nómina procesada correctamente',
                            (info.contratos_procesados || '') + (info.modo ? ' · Modo: ' + info.modo : '') + (info.mensaje ? ' · ' + info.mensaje : ''));
                        consultar();
                    } else {
                        updateButtons();
                        showStatus('error', 'Error al procesar', res.error || 'Error desconocido');
                    }
                })
                .catch(() => { updateButtons(); showStatus('error', 'Error de conexión', 'No se pudo conectar con FastAPI.'); });
            });
        }

        // ── Exportar ───────────────────────────────────────────────────────
        btnExport.addEventListener('click', function () {
            window.location.href = '{{ route("calculos.exportar") }}?' + getParams(activeRegime);
        });
    });
    </script>
    @endpush
</x-app-layout>
