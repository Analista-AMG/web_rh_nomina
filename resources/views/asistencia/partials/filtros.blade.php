{{-- ── Filtros ──────────────────────────────────────────────────────────── --}}
<div class="mb-6 bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border overflow-hidden shadow-sm">
    <div class="px-5 py-3 border-b border-light-border dark:border-dark-border flex items-center gap-2">
        <i class="fa-solid fa-sliders text-primary text-sm"></i>
        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Filtros</span>
    </div>
    <div class="px-5 py-4">
        @php
            // Solo los 2 períodos más recientes (mes actual y anterior)
            $pagosRecientes = $pagos->groupBy('periodo')->take(2)->reverse();
        @endphp
        <form method="GET" action="{{ route('asistencia.index') }}" id="form-filtro" class="flex flex-wrap items-end gap-4">
            <input type="hidden" name="pago_id" id="pago_id" value="{{ $pagoSeleccionado?->id }}">

            {{-- Período: solo mes actual y anterior --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                    <i class="fa-regular fa-calendar mr-1"></i>Período
                </label>
                <div class="flex items-center gap-2">
                    @foreach($pagosRecientes as $periodo => $grupo)
                        @php
                            $dt       = \Carbon\Carbon::createFromFormat('Y-m', $periodo)->locale('es');
                            $mesLabel = mb_strtoupper($dt->isoFormat('MMM')) . '-' . $dt->format('Y');
                        @endphp
                        <div class="flex items-center gap-1 bg-gray-50 dark:bg-[#1b2431] rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                            <span class="text-[11px] font-bold text-gray-400 dark:text-gray-500 tracking-wide mr-1">{{ $mesLabel }}</span>
                            @foreach($grupo->sortBy('quincena') as $pago)
                                @php
                                    $sel = $pagoSeleccionado && $pagoSeleccionado->id == $pago->id;
                                    $tip = \Carbon\Carbon::parse($pago->inicio)->format('d/m') . ' – ' . \Carbon\Carbon::parse($pago->fin)->format('d/m');
                                @endphp
                                <button type="button"
                                    onclick="seleccionarPago({{ $pago->id }})"
                                    title="{{ $tip }}"
                                    class="px-2.5 py-1 rounded-md text-xs font-semibold transition-colors cursor-pointer
                                        {{ $sel ? 'bg-primary text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 hover:text-gray-800 dark:hover:text-white' }}">
                                    Q{{ $pago->quincena }}
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Filtro por rango de fechas (client-side, limitado al período seleccionado) --}}
            @if($pagoSeleccionado)
            @php
                $periodoMin = \Carbon\Carbon::parse($pagoSeleccionado->inicio)->format('Y-m-d');
                $periodoMax = \Carbon\Carbon::parse($pagoSeleccionado->fin)->format('Y-m-d');
            @endphp
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                    <i class="fa-regular fa-calendar-day mr-1"></i>Rango de fechas
                </label>
                <div class="flex items-center gap-2">
                    <input type="date" id="filtro-fecha-desde"
                        min="{{ $periodoMin }}" max="{{ $periodoMax }}"
                        class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#1b2431] text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <span class="text-gray-400 dark:text-gray-500 text-xs font-medium">—</span>
                    <input type="date" id="filtro-fecha-hasta"
                        min="{{ $periodoMin }}" max="{{ $periodoMax }}"
                        class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#1b2431] text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                </div>
            </div>
            @endif

            {{-- Campaña (client-side) --}}
            @if($pagoSeleccionado && $filas->isNotEmpty())
            @php $campanas = $filas->pluck('campana')->unique()->sort()->filter(fn($c) => $c && $c !== '-')->values(); @endphp
            @if($campanas->isNotEmpty())
            <div class="min-w-[180px]">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                    <i class="fa-solid fa-headset mr-1"></i>Campaña
                </label>
                <select id="filtro-campana"
                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#1b2431] text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <option value="">Todas</option>
                    @foreach($campanas as $campana)
                        <option value="{{ mb_strtolower($campana) }}">{{ $campana }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Centro de Costo (client-side) --}}
            @php $centros = $filas->map(fn($f) => $f['contrato']->centroCosto?->nombre_centro_costo)->filter()->unique()->sort()->values(); @endphp
            @if($centros->isNotEmpty())
            <div class="min-w-[160px]">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                    <i class="fa-solid fa-building mr-1"></i>Centro de Costo
                </label>
                <select id="filtro-centro"
                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#1b2431] text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <option value="">Todos</option>
                    @foreach($centros as $centro)
                        <option value="{{ mb_strtolower($centro) }}">{{ $centro }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Familia (client-side) --}}
            @php $familias = $filas->map(fn($f) => $f['contrato']->familia?->nombre_familia)->filter()->unique()->sort()->values(); @endphp
            @if($familias->isNotEmpty())
            <div class="min-w-[160px]">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                    <i class="fa-solid fa-layer-group mr-1"></i>Familia
                </label>
                <select id="filtro-familia"
                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#1b2431] text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <option value="">Todas</option>
                    @foreach($familias as $familia)
                        <option value="{{ mb_strtolower($familia) }}">{{ $familia }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @endif

            {{-- Buscar colaborador (client-side) --}}
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                    <i class="fa-solid fa-magnifying-glass mr-1"></i>Colaborador
                </label>
                <div class="relative">
                    <input type="text" id="filtro-nombre" placeholder="Búsqueda por nombre"
                        autocomplete="off"
                        class="w-full pl-3 pr-8 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#1b2431] text-sm text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <button type="button" id="btn-limpiar-nombre"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400 hidden"
                        title="Limpiar">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>
