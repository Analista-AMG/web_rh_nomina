<x-app-layout>
    @section('title', 'Reporte de Asistencia - AMG International')

    <header class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Reporte de Asistencia</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Días sin registrar por supervisor</p>
        </div>
    </header>

    {{-- Selector de período --}}
    <div class="mb-6 bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-light-border dark:border-dark-border flex items-center gap-2">
            <i class="fa-solid fa-calendar text-primary text-sm"></i>
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Período</span>
        </div>
        <div class="px-5 py-4">
            @php
                $pagosRecientes = $pagos->groupBy('periodo')->take(2)->reverse();
            @endphp
            <form method="GET" action="{{ route('reportes.asistencia') }}" id="form-periodo" class="flex flex-wrap items-end gap-4">
                <input type="hidden" name="pago_id" id="pago_id" value="{{ $pagoSeleccionado?->id }}">
                <div class="flex items-center gap-2">
                    @foreach($pagosRecientes as $periodo => $grupo)
                        @php
                            $dt = \Carbon\Carbon::createFromFormat('Y-m', $periodo)->locale('es');
                            $mesLabel = mb_strtoupper($dt->isoFormat('MMM')) . '-' . $dt->format('Y');
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
                </div>
            </form>
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
            $totalVacios    = $filas->sum('vacios');
            $totalEsperados = $filas->sum('esperados');
            $totalLlenados  = $filas->sum('llenados');
            $coberturaGlobal = $totalEsperados > 0 ? round($totalLlenados / $totalEsperados * 100) : 100;
            $supervisoresConVacios = $filas->where('vacios', '>', 0)->count();
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Supervisores</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $filas->count() }}</p>
                <p class="text-xs text-amber-500 mt-0.5">{{ $supervisoresConVacios }} con vacíos</p>
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supervisor</th>
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
                        @endphp
                        {{-- Fila principal --}}
                        <tr class="group hover:bg-gray-50 dark:hover:bg-[#323d4d] transition-colors {{ $tieneDetalle ? 'cursor-pointer' : '' }}"
                            @if($tieneDetalle) onclick="toggleDetalle({{ $i }})" @endif>
                            <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">
                                <div class="flex items-center gap-2">
                                    <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold flex-shrink-0">
                                        {{ mb_strtoupper(mb_substr($fila['supervisor'], 0, 2)) }}
                                    </div>
                                    {{ $fila['supervisor'] }}
                                </div>
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
                                <td colspan="7" class="px-0 py-0">
                                    <div class="bg-gray-50 dark:bg-[#1e2836] border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                                            Colaboradores con días sin registrar
                                        </p>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                            @foreach($fila['detalle'] as $colab)
                                                <div class="bg-white dark:bg-[#273142] rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-sm font-medium text-gray-800 dark:text-white">{{ $colab['nombre'] }}</span>
                                                        <span class="text-xs font-semibold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-2 py-0.5 rounded-full">
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
    </script>
    @endpush

</x-app-layout>
