<x-app-layout>
    @section('title', 'Reporte de Asistencia - AMG International')

    <header class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Reporte de Asistencia</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Días sin registrar por responsable</p>
        </div>
    </header>

    {{-- Filtros (período + rol + campaña + nombre) --}}
    @php
        $periodoRef     = $pagoSeleccionado?->periodo ?? $pagos->first()?->periodo;
        $pagosRecientes = $pagos->filter(fn($p) => $p->periodo <= $periodoRef)
            ->groupBy('periodo')->take(2)->reverse();
        $rolesDisponibles    = $filas->pluck('rol')->unique()->filter()->sort()->values();
        $campanasDisponibles = $filas->pluck('campana')->unique()->filter(fn($c) => $c !== '—')->sort()->values();
    @endphp
    <div class="mb-6 bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border shadow-sm">
        <div class="px-5 py-3 border-b border-light-border dark:border-dark-border flex items-center gap-2 rounded-t-xl">
            <i class="fa-solid fa-sliders text-primary text-sm"></i>
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Filtros</span>
        </div>
        <div class="px-5 py-4 flex flex-wrap items-center gap-4 rounded-b-xl overflow-visible">

            {{-- Período --}}
            <form method="GET" action="{{ route('reportes.asistencia') }}" id="form-periodo" class="flex items-center gap-2 flex-shrink-0">
                <input type="hidden" name="pago_id" id="pago_id" value="{{ $pagoSeleccionado?->id }}">
                <span class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">Período</span>
                @foreach($pagosRecientes as $periodo => $grupo)
                    @php
                        $dt = \Carbon\Carbon::parse($periodo . '-01')->locale('es');
                        $mesLabel = rtrim(mb_strtoupper($dt->isoFormat('MMM')), '.') . '-' . $dt->format('Y');
                    @endphp
                    <div class="flex items-center gap-1 bg-gray-50 dark:bg-[#1b2431] rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                        <span class="text-[11px] font-bold text-gray-400 dark:text-gray-500 tracking-wide mr-1">{{ $mesLabel }}</span>
                        @foreach($grupo->sortBy('quincena') as $pago)
                            @php $sel = $pagoSeleccionado && $pagoSeleccionado->id == $pago->id; @endphp
                            <button type="button"
                                onclick="document.getElementById('pago_id').value='{{ $pago->id }}'; document.getElementById('form-periodo').submit();"
                                title="{{ \Carbon\Carbon::parse($pago->inicio)->format('d/m') }} – {{ \Carbon\Carbon::parse($pago->fin)->format('d/m') }}"
                                class="px-2.5 py-1 rounded-md text-xs font-semibold transition-colors cursor-pointer
                                    {{ $sel ? 'bg-primary text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                Q{{ $pago->quincena }}
                            </button>
                        @endforeach
                    </div>
                @endforeach
            </form>

            @if($filas->isNotEmpty())
                <div class="w-px h-7 bg-gray-200 dark:bg-gray-700 flex-shrink-0"></div>

                {{-- Rol (multi-select dropdown) --}}
                <div class="relative flex-shrink-0"
                     x-data="{ open: false, selected: [] }"
                     @click.outside="open = false">
                    <button type="button" @click="open = !open"
                        class="flex items-center gap-2 py-1.5 pl-3 pr-2.5 text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-[#1b2431] text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/40 min-w-36 cursor-pointer">
                        <span x-text="selected.length === 0 ? 'Todos los roles' : selected.length + ' rol(es)'">Todos los roles</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 ml-auto transition-transform duration-150" :class="open && 'rotate-180'"></i>
                    </button>
                    <div x-show="open" x-transition.opacity style="display:none"
                        class="absolute left-0 top-full mt-1 z-20 bg-white dark:bg-[#273142] rounded-lg border border-gray-200 dark:border-gray-700 shadow-lg py-1 min-w-44">
                        @foreach($rolesDisponibles as $r)
                            <label class="flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#323d4d] cursor-pointer select-none">
                                <input type="checkbox" value="{{ $r }}"
                                    class="rounded text-primary focus:ring-primary/40 cursor-pointer"
                                    @change="
                                        selected = $event.target.checked
                                            ? [...selected, $event.target.value]
                                            : selected.filter(v => v !== $event.target.value);
                                        window.rptRolesSeleccionados = selected;
                                        rptAplicar();
                                    ">
                                <span>{{ $r }}</span>
                            </label>
                        @endforeach
                        <template x-if="selected.length > 0">
                            <div class="border-t border-gray-100 dark:border-gray-700 mt-1 pt-1 px-3 pb-1">
                                <button type="button"
                                    @click="selected = []; window.rptRolesSeleccionados = []; $el.closest('[x-data]').querySelectorAll('input[type=checkbox]').forEach(c => c.checked = false); rptAplicar();"
                                    class="text-xs text-primary hover:underline cursor-pointer">Limpiar selección</button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Campaña --}}
                <select id="rpt-filtro-campana"
                    class="py-1.5 pl-3 pr-7 text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-[#1b2431] text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/40 min-w-40">
                    <option value="">Todas las campañas</option>
                    @foreach($campanasDisponibles as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>

                {{-- Nombre (al final) --}}
                <div class="relative flex-1 min-w-40">
                    <input id="rpt-filtro-nombre" type="text" placeholder="Buscar responsable…"
                        class="w-full pl-8 pr-7 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-[#1b2431] text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/40">
                    <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    <button id="rpt-btn-limpiar" class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        onclick="document.getElementById('rpt-filtro-nombre').value=''; rptAplicar();">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">
                    <span id="rpt-contador">{{ $filas->count() }}</span> responsable(s)
                </span>
            @endif

        </div>
    </div>

    @if(!$pagoSeleccionado)
        <div class="bg-white dark:bg-[#273142] rounded-xl p-12 text-center shadow-sm border border-light-border dark:border-dark-border">
            <i class="fa-solid fa-calendar-days text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">Seleccione un período para ver el reporte.</p>
        </div>
    @elseif($filas->isEmpty())
        <div class="bg-white dark:bg-[#273142] rounded-xl p-12 text-center shadow-sm border border-light-border dark:border-dark-border">
            <i class="fa-solid fa-circle-check text-4xl text-green-400 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400 font-medium">Todo completo para este período.</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">No hay días de asistencia sin registrar.</p>
        </div>
    @else
        {{-- KPIs --}}
        @php
            $totalVacios           = $filas->sum('vacios');
            $totalEsperados        = $filas->sum('esperados');
            $totalLlenados         = $filas->sum('llenados');
            $coberturaGlobal       = $totalEsperados > 0 ? round($totalLlenados / $totalEsperados * 100) : 100;
            $responsablesConVacios = $filas->where('vacios', '>', 0)->count();
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Responsables</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $filas->count() }}</p>
                <p class="text-xs text-amber-500 mt-0.5">{{ $responsablesConVacios }} con vacíos</p>
            </div>
            <div class="bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Días con contrato</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($totalEsperados) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $diasPeriodo->count() }} días en período</p>
            </div>
            <div class="bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Días vacíos</p>
                <p class="text-2xl font-bold {{ $totalVacios > 0 ? 'text-red-500' : 'text-green-500' }}">{{ number_format($totalVacios) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">sin asistencia registrada</p>
            </div>
            <div class="bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Cobertura global</p>
                <p class="text-2xl font-bold {{ $coberturaGlobal >= 90 ? 'text-green-500' : ($coberturaGlobal >= 70 ? 'text-yellow-500' : 'text-red-500') }}">
                    {{ $coberturaGlobal }}%
                </p>
                <p class="text-xs text-gray-400 mt-0.5">{{ number_format($totalLlenados) }} registrados</p>
            </div>
        </div>

        {{-- Tabla de supervisores --}}
        <div class="bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-[#1e2836]">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Responsable</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rol</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Campaña</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Colaboradores</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Días con contrato</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Vacíos</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cobertura</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($filas as $i => $fila)
                        @php
                            $pct = $fila['cobertura'];
                            $colorPct = $pct >= 90 ? 'text-green-600 dark:text-green-400'
                                      : ($pct >= 70 ? 'text-yellow-600 dark:text-yellow-400'
                                      : ($pct >= 50 ? 'text-orange-600 dark:text-orange-400'
                                      : 'text-red-600 dark:text-red-400'));
                            $bgBar = $pct >= 90 ? 'bg-green-500'
                                   : ($pct >= 70 ? 'bg-yellow-500'
                                   : ($pct >= 50 ? 'bg-orange-500'
                                   : 'bg-red-500'));
                            $tieneDetalle = !empty($fila['detalle']);
                            $rolNombre = $fila['rol'] ?? '—';
                            [$rolBg, $rolText] = match($rolNombre) {
                                'Jefe Operaciones' => ['bg-purple-100 dark:bg-purple-900/30', 'text-purple-700 dark:text-purple-300'],
                                'Coordinador'      => ['bg-blue-100 dark:bg-blue-900/30',   'text-blue-700 dark:text-blue-300'],
                                'Supervisor'       => ['bg-sky-100 dark:bg-sky-900/30',     'text-sky-700 dark:text-sky-300'],
                                default            => ['bg-gray-100 dark:bg-gray-700',       'text-gray-600 dark:text-gray-400'],
                            };
                        @endphp
                        {{-- Fila principal --}}
                        <tr class="rpt-fila group hover:bg-gray-50 dark:hover:bg-[#323d4d] transition-colors {{ $tieneDetalle ? 'cursor-pointer' : '' }}"
                            data-nombre="{{ mb_strtolower($fila['supervisor']) }}"
                            data-rol="{{ $fila['rol'] ?? '' }}"
                            data-campana="{{ $fila['campana'] ?? '' }}"
                            @if($tieneDetalle) onclick="toggleDetalle({{ $i }})" @endif>
                            <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">
                                <div class="flex items-center gap-2">
                                    <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold flex-shrink-0">
                                        {{ mb_strtoupper(mb_substr($fila['supervisor'], 0, 2)) }}
                                    </div>
                                    {{ $fila['supervisor'] }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $rolBg }} {{ $rolText }}">
                                    {{ $rolNombre }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $fila['campana'] }}</td>
                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $fila['colaboradores'] }}</td>
                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $fila['esperados'] }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($fila['vacios'] > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">
                                        <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> {{ $fila['vacios'] }}
                                    </span>
                                @else
                                    <span class="text-green-500 text-xs"><i class="fa-solid fa-check"></i></span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 justify-center">
                                    <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                        <div class="{{ $bgBar }} h-1.5 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold {{ $colorPct }} w-10 text-right">{{ $pct }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-400">
                                @if($tieneDetalle)
                                    <i id="chevron-{{ $i }}" class="fa-solid fa-chevron-down text-xs transition-transform duration-200"></i>
                                @endif
                            </td>
                        </tr>

                        {{-- Detalle expandible --}}
                        @if($tieneDetalle)
                            <tr id="detalle-{{ $i }}" class="hidden">
                                <td colspan="8" class="px-0 py-0">
                                    <div class="bg-gray-50 dark:bg-[#1e2836] border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                                            Colaboradores con días sin registrar
                                        </p>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                            @foreach($fila['detalle'] as $colab)
                                                <div class="bg-white dark:bg-[#273142] rounded-lg p-3 border border-gray-200 dark:border-gray-700 {{ ($colab['es_propio'] ?? false) ? 'border-l-2 border-l-primary' : '' }}">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <div class="flex items-center gap-1.5 min-w-0">
                                                            <span class="text-sm font-medium text-gray-800 dark:text-white truncate">{{ $colab['nombre'] }}</span>
                                                            @if($colab['es_propio'] ?? false)
                                                                <span class="flex-shrink-0 text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-primary/10 text-primary">propio</span>
                                                            @endif
                                                        </div>
                                                        <span class="flex-shrink-0 text-xs font-semibold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-2 py-0.5 rounded-full ml-2">
                                                            {{ $colab['vacios'] }}/{{ $colab['esperados'] }}
                                                        </span>
                                                    </div>
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach(array_slice($colab['fechas'], 0, 15) as $fecha)
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 font-medium">
                                                                {{ \Carbon\Carbon::parse($fecha)->format('d/m') }}
                                                            </span>
                                                        @endforeach
                                                        @if(count($colab['fechas']) > 15)
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500">
                                                                +{{ count($colab['fechas']) - 15 }} más
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @push('scripts')
    <script>
        function toggleDetalle(i) {
            const row     = document.getElementById('detalle-' + i);
            const chevron = document.getElementById('chevron-' + i);
            if (!row) return;
            const hidden = row.classList.toggle('hidden');
            if (chevron) chevron.style.transform = hidden ? '' : 'rotate(180deg)';
        }

        window.rptRolesSeleccionados = [];

        document.addEventListener('DOMContentLoaded', function () {
            const inputNombre = document.getElementById('rpt-filtro-nombre');
            const selCampana  = document.getElementById('rpt-filtro-campana');
            const btnLimpiar  = document.getElementById('rpt-btn-limpiar');
            const contador    = document.getElementById('rpt-contador');
            const filas       = [...document.querySelectorAll('tr.rpt-fila')];

            function rptAplicar() {
                const nombre  = (inputNombre?.value ?? '').toLowerCase().trim();
                const roles   = window.rptRolesSeleccionados ?? [];
                const campana = selCampana?.value ?? '';

                let visible = 0;
                filas.forEach(tr => {
                    const match = (!nombre          || tr.dataset.nombre.includes(nombre))
                               && (roles.length === 0 || roles.includes(tr.dataset.rol))
                               && (!campana         || tr.dataset.campana === campana);

                    tr.style.display = match ? '' : 'none';

                    // Ocultar la fila de detalle si el padre se oculta
                    const next = tr.nextElementSibling;
                    if (next?.id?.startsWith('detalle-')) next.style.display = match ? '' : 'none';

                    if (match) visible++;
                });

                if (contador) contador.textContent = visible;
                if (btnLimpiar) btnLimpiar.classList.toggle('hidden', !nombre);
            }

            window.rptAplicar = rptAplicar;

            inputNombre?.addEventListener('input', () => {
                const v = inputNombre.value.trim();
                if (v.length === 0 || v.length >= 2) rptAplicar();
            });
            inputNombre?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); rptAplicar(); } });
            selCampana?.addEventListener('change', rptAplicar);
        });
    </script>
    @endpush

</x-app-layout>
