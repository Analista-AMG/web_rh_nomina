<x-app-layout>
    @section('title', 'Asistencia - AMG International')

    <header class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Registro de Asistencia</h1>
        @if(!$esAdmin && $diaActual <= 1 && $pagoSeleccionado)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                <i class="fa-solid fa-clock"></i> Puedes editar el período anterior hasta el día 1
            </span>
        @endif
    </header>

    @include('asistencia.partials.filtros')

    @if($pagoSeleccionado)
        {{-- Stats bar --}}
        <div class="mb-4 flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
            <span>
                <i class="fa-solid fa-calendar mr-1"></i>
                <span id="stats-rango">{{ \Carbon\Carbon::parse($pagoSeleccionado->inicio)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($pagoSeleccionado->fin)->format('d/m/Y') }}</span>
            </span>
            <span><i class="fa-solid fa-users mr-1"></i> <span id="contador-colaboradores">{{ $filas->count() }}</span> colaborador(es)</span>
            @if(!$esAdmin && $bloquearAntesDe)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                    <i class="fa-solid fa-lock text-[10px]"></i> Períodos anteriores bloqueados
                </span>
            @endif
        </div>

        {{-- Legend --}}
        <div class="mb-4 bg-white dark:bg-[#273142] rounded-xl p-3 shadow-sm border border-light-border dark:border-dark-border flex flex-wrap items-center gap-x-4 gap-y-1">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Leyenda:</span>
            @foreach($itemsAsistencia as $item)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                    <strong class="mr-1">{{ $item->codigo_asistencia }}</strong> {{ $item->descripcion }}
                </span>
            @endforeach
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-400"><i class="fa-solid fa-lock text-[9px]"></i> Sin contrato</span>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-amber-100 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400"><i class="fa-solid fa-lock text-[9px]"></i> Periodo cerrado</span>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-blue-50 dark:bg-blue-900/20 text-blue-500"><i class="fa-solid fa-lock text-[9px]"></i> Otro supervisor</span>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"><strong class="mr-1">R/P</strong> Remoto / Presencial</span>
        </div>

        @if($filas->isEmpty())
            <div class="bg-white dark:bg-[#273142] rounded-xl p-12 text-center shadow-sm border border-light-border dark:border-dark-border">
                <i class="fa-solid fa-user-slash text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">No tienes colaboradores asignados para este periodo.</p>
                @if(!$esAdmin)
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Los colaboradores se determinan por la jerarquía de asignaciones.</p>
                @endif
            </div>
        @else
            @include('asistencia.partials.tabla')
        <div id="paginacion-asistencia"></div>
        @endif
    @else
        <div class="bg-white dark:bg-[#273142] rounded-xl p-12 text-center shadow-sm border border-light-border dark:border-dark-border">
            <i class="fa-solid fa-calendar-days text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">Seleccione un periodo para registrar asistencia.</p>
        </div>
    @endif

    @include('asistencia.partials.scripts')

</x-app-layout>
