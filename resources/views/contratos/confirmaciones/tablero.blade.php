<x-app-layout>
    @section('title', 'Tablero de Confirmaciones - AMG')

    <header class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Tablero de Confirmaciones</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Estado de confirmación por supervisor</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('contratos.confirmaciones', ['periodo' => $periodo]) }}"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium border border-light-border dark:border-dark-border text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] transition-colors">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Mi equipo</span>
            </a>
            <form method="GET" action="{{ route('contratos.confirmaciones.tablero') }}" id="form-periodo">
                @if($filtro)
                    <input type="hidden" name="filtro" value="{{ $filtro }}">
                @endif
                <div class="relative">
                    <i class="fa-solid fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    <select name="periodo" onchange="document.getElementById('form-periodo').submit()"
                            class="pl-8 pr-8 py-2 text-sm rounded-lg border border-light-border dark:border-dark-border bg-white dark:bg-[#273142] text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all appearance-none cursor-pointer">
                        @foreach($periodos as $p)
                            <option value="{{ $p['value'] }}" @selected($p['value'] === $periodo)>{{ $p['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </header>

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 text-sm flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 text-sm">
        <i class="fa-solid fa-circle-xmark"></i> {{ $errors->first() }}
    </div>
    @endif

    {{-- KPI resumen global (siempre sobre el total, no afectado por filtro ni búsqueda) --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
        <div class="bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border p-4 shadow-sm text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Total contratos</p>
            <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border p-4 shadow-sm text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Confirmados</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['confirmados'] }}</p>
        </div>
        <div class="bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border p-4 shadow-sm text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Requieren actualiz.</p>
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['requieren'] }}</p>
        </div>
        <div class="bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border p-4 shadow-sm text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">% completado</p>
            <p class="text-2xl font-bold {{ $stats['pct'] === 100 ? 'text-green-600 dark:text-green-400' : 'text-primary' }}">{{ $stats['pct'] }}%</p>
        </div>
    </div>

    {{-- Filtros por estado + buscador --}}
    @php
        $baseUrl = route('contratos.confirmaciones.tablero') . '?periodo=' . $periodo . ($buscar ? '&buscar=' . urlencode($buscar) : '');
    @endphp
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <div class="flex flex-wrap gap-2">
            <a href="{{ $baseUrl }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium border transition
                      {{ !$filtro ? 'bg-primary text-white border-primary' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-primary hover:text-primary' }}">
                Todos
            </a>
            <a href="{{ $baseUrl }}&filtro=pendiente"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium border transition
                      {{ $filtro === 'pendiente' ? 'bg-gray-700 text-white border-gray-700' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-gray-500 hover:text-gray-700 dark:hover:text-gray-200' }}">
                <span class="w-2 h-2 rounded-full bg-gray-400 inline-block"></span> Pendiente
                <span class="ml-1 text-xs opacity-70">{{ $stats['pendientes'] }}</span>
            </a>
            <a href="{{ $baseUrl }}&filtro=confirmado"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium border transition
                      {{ $filtro === 'confirmado' ? 'bg-green-600 text-white border-green-600' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-green-500 hover:text-green-700 dark:hover:text-green-400' }}">
                <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span> Confirmado
                <span class="ml-1 text-xs opacity-70">{{ $stats['confirmados'] }}</span>
            </a>
            <a href="{{ $baseUrl }}&filtro=requiere_actualizacion"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium border transition
                      {{ $filtro === 'requiere_actualizacion' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-amber-400 hover:text-amber-600 dark:hover:text-amber-400' }}">
                <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span> Requiere actualiz.
                <span class="ml-1 text-xs opacity-70">{{ $stats['requieren'] }}</span>
            </a>
        </div>

        {{-- Buscador por nombre --}}
        <form method="GET" action="{{ route('contratos.confirmaciones.tablero') }}" class="flex items-center gap-2">
            <input type="hidden" name="periodo" value="{{ $periodo }}">
            @if($filtro)
                <input type="hidden" name="filtro" value="{{ $filtro }}">
            @endif
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                <input type="text" name="buscar" value="{{ $buscar }}"
                       placeholder="Buscar colaborador..."
                       class="pl-8 pr-8 py-1.5 text-sm rounded-lg border border-light-border dark:border-dark-border bg-white dark:bg-[#273142] text-gray-800 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all w-80">
                @if($buscar)
                <a href="{{ route('contratos.confirmaciones.tablero', array_filter(['periodo' => $periodo, 'filtro' => $filtro])) }}"
                   class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                   title="Limpiar búsqueda">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tarjetas por supervisor --}}
    @if($tablero->isEmpty())
        <div class="bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border shadow-sm p-12 text-center text-gray-400 dark:text-gray-500">
            @if($buscar)
                <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block"></i>
                <p class="text-sm">Sin resultados para <span class="font-medium text-gray-600 dark:text-gray-300">"{{ $buscar }}"</span>.</p>
            @else
                <i class="fa-solid fa-users text-3xl mb-3 block"></i>
                <p class="text-sm">No hay supervisores con colaboradores asignados.</p>
            @endif
        </div>
    @else
    <div class="space-y-3">
        @foreach($tablero as $row)
        <div class="bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border shadow-sm overflow-hidden">

            {{-- Header del supervisor --}}
            <button type="button"
                    onclick="toggleDetalle('sup-{{ $loop->index }}')"
                    class="w-full flex items-center gap-4 px-5 py-4 hover:bg-gray-50 dark:hover:bg-[#1e2a38]/40 transition-colors text-left">

                <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0 text-sm font-bold text-primary">
                    {{ strtoupper(substr($row->supervisor->name, 0, 1)) }}
                </div>

                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-800 dark:text-gray-100 text-sm truncate">{{ $row->supervisor->name }}</p>
                    <p class="text-xs text-gray-400">{{ $row->total }} colaborador{{ $row->total !== 1 ? 'es' : '' }}</p>
                </div>

                {{-- Barra de progreso --}}
                <div class="flex-1 max-w-xs hidden sm:block">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500 rounded-full transition-all duration-500"
                                 style="width: {{ $row->pct }}%"></div>
                        </div>
                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 w-10 text-right">{{ $row->pct }}%</span>
                    </div>
                </div>

                {{-- Badges --}}
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($row->confirmados > 0)
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                        ✓ {{ $row->confirmados }}
                    </span>
                    @endif
                    @if($row->requieren > 0)
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">
                        ⚠ {{ $row->requieren }}
                    </span>
                    @endif
                    @if($row->pendientes > 0)
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                        {{ $row->pendientes }} pend.
                    </span>
                    @endif
                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200" id="chev-sup-{{ $loop->index }}"></i>
                </div>
            </button>

            {{-- Detalle expandible --}}
            <div id="sup-{{ $loop->index }}" class="hidden border-t border-light-border dark:border-dark-border px-5 py-4">
                @include('contratos.confirmaciones.partials._tabla', [
                    'filas'       => $row->filas,
                    'periodo'     => $periodo,
                    'esRrhh'      => $esRrhh,
                    'puedeActuar' => $puedeActuar,
                    'grupos'      => $grupos,
                ])
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <script>
    function toggleDetalle(id) {
        const el   = document.getElementById(id);
        const chev = document.getElementById('chev-' + id);
        if (!el) return;
        el.classList.toggle('hidden');
        if (chev) chev.classList.toggle('rotate-180');
    }
    </script>

</x-app-layout>
