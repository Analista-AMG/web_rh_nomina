<x-app-layout>
    @section('title', 'Confirmaciones de Contratos - AMG')

    <header class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Confirmación de Contratos</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Verifica los datos de tu equipo para el periodo seleccionado</p>
        </div>
        <div class="flex items-center gap-2">
            @can('contratos.confirmar.tablero')
            <a href="{{ route('contratos.confirmaciones.tablero', ['periodo' => $periodo]) }}"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium border border-light-border dark:border-dark-border text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] transition-colors">
                <i class="fa-solid fa-chart-bar text-xs"></i>
                <span>Tablero RRHH</span>
            </a>
            @endcan
            {{-- Selector de periodo --}}
            <form method="GET" action="{{ route('contratos.confirmaciones') }}" id="form-periodo">
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

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border p-4 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-check text-green-600 dark:text-green-400"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Confirmados</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['confirmados'] }} <span class="text-sm font-normal text-gray-400">/ {{ $stats['total'] }}</span></p>
            </div>
        </div>
        <div class="bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border p-4 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-triangle-exclamation text-amber-500 dark:text-amber-400"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Requieren actualiz.</p>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['requieren_actualizacion'] }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border p-4 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                <i class="fa-regular fa-clock text-gray-500 dark:text-gray-400"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Pendientes</p>
                <p class="text-2xl font-bold text-gray-600 dark:text-gray-300">{{ $stats['pendientes'] }}</p>
            </div>
        </div>
        @if($stats['mov_cambio'] > 0)
        <div class="bg-white dark:bg-[#273142] rounded-xl border border-blue-200 dark:border-blue-800 p-4 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-rotate text-blue-600 dark:text-blue-400"></i>
            </div>
            <div>
                <p class="text-xs text-blue-600 dark:text-blue-400 uppercase tracking-wide font-semibold">Datos actualizados</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['mov_cambio'] }}</p>
            </div>
        </div>
        @endif
    </div>

    {{-- Filtros de estado --}}
    @php
        $totalPendientes = $stats['pendientes'] + $stats['requieren_actualizacion'];
        $urlBase = route('contratos.confirmaciones', ['periodo' => $periodo]);
    @endphp
    <div class="flex items-center gap-2 mb-4 flex-wrap">
        <a href="{{ $urlBase }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-colors
                  {{ !$filtro ? 'bg-gray-700 dark:bg-gray-200 text-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
            Todos <span class="{{ !$filtro ? 'opacity-60' : 'opacity-70' }}">{{ $stats['total'] }}</span>
        </a>
        <a href="{{ route('contratos.confirmaciones', ['periodo' => $periodo, 'filtro' => 'pendiente']) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-colors
                  {{ $filtro === 'pendiente' ? 'bg-gray-600 text-white' : 'bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
            <i class="fa-regular fa-clock"></i> Pendiente
            <span class="{{ $filtro === 'pendiente' ? 'opacity-60' : 'opacity-70' }}">{{ $totalPendientes }}</span>
        </a>
        @if($stats['mov_cambio'] > 0)
        <a href="{{ route('contratos.confirmaciones', ['periodo' => $periodo, 'filtro' => 'datos_actualizados']) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-colors
                  {{ $filtro === 'datos_actualizados' ? 'bg-blue-600 text-white' : 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30' }}">
            <i class="fa-solid fa-rotate"></i> Datos actualizados
            <span class="{{ $filtro === 'datos_actualizados' ? 'opacity-60' : 'opacity-70' }}">{{ $stats['mov_cambio'] }}</span>
        </a>
        @endif
        <a href="{{ route('contratos.confirmaciones', ['periodo' => $periodo, 'filtro' => 'confirmado']) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-colors
                  {{ $filtro === 'confirmado' ? 'bg-green-600 text-white' : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/30' }}">
            <i class="fa-solid fa-check"></i> Confirmado
            <span class="{{ $filtro === 'confirmado' ? 'opacity-60' : 'opacity-70' }}">{{ $stats['confirmados'] }}</span>
        </a>
    </div>

    {{-- Tabla principal --}}
    <div class="bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border shadow-sm p-5">
        @include('contratos.confirmaciones.partials._tabla', [
            'filas'       => $filas,
            'periodo'     => $periodo,
            'esRrhh'      => $esAdminORrhh,
            'puedeActuar' => true,
            'filtro'      => $filtro,
        ])

        @if($filas->hasPages())
        <div class="mt-4 pt-4 border-t border-light-border dark:border-dark-border flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <span>Mostrando {{ $filas->firstItem() }}–{{ $filas->lastItem() }} de {{ $filas->total() }} colaboradores</span>
            <div class="flex items-center gap-1">
                @if($filas->onFirstPage())
                    <span class="px-3 py-1.5 rounded-lg text-gray-300 dark:text-gray-600 cursor-not-allowed">
                        <i class="fa-solid fa-chevron-left text-xs"></i>
                    </span>
                @else
                    <a href="{{ $filas->previousPageUrl() }}"
                       class="px-3 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-[#1b2431] transition-colors">
                        <i class="fa-solid fa-chevron-left text-xs"></i>
                    </a>
                @endif

                @foreach($filas->getUrlRange(1, $filas->lastPage()) as $pg => $url)
                    @if($pg === $filas->currentPage())
                        <span class="px-3 py-1.5 rounded-lg bg-primary/10 text-primary font-semibold">{{ $pg }}</span>
                    @elseif(abs($pg - $filas->currentPage()) <= 2 || $pg === 1 || $pg === $filas->lastPage())
                        <a href="{{ $url }}" class="px-3 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-[#1b2431] transition-colors">{{ $pg }}</a>
                    @elseif(abs($pg - $filas->currentPage()) === 3)
                        <span class="px-2">…</span>
                    @endif
                @endforeach

                @if($filas->hasMorePages())
                    <a href="{{ $filas->nextPageUrl() }}"
                       class="px-3 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-[#1b2431] transition-colors">
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </a>
                @else
                    <span class="px-3 py-1.5 rounded-lg text-gray-300 dark:text-gray-600 cursor-not-allowed">
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </span>
                @endif
            </div>
        </div>
        @endif
    </div>

</x-app-layout>
