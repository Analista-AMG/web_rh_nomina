<x-app-layout>
    @section('title', 'Equipos — Préstamos')

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <header class="mb-6 flex items-center justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Equipos — Préstamos</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gestión de préstamos temporales entre supervisores</p>
        </div>
        <div class="flex items-center gap-3">
            @if($totalPendientes > 0)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 text-sm font-medium rounded-lg">
                <i class="fa-solid fa-clock-rotate-left"></i>
                {{ $totalPendientes }} pendiente{{ $totalPendientes > 1 ? 's' : '' }} de aprobación
            </span>
            @endif
            <button type="button" onclick="abrirModalSolicitar()"
                class="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-300 hover:border-primary hover:text-primary transition-colors cursor-pointer bg-white dark:bg-[#273142]">
                <i class="fa-solid fa-arrow-down-to-bracket"></i>
                Solicitar colaborador
            </button>
            <button type="button" onclick="abrirModalPrestar()"
                class="btn btn-primary flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-arrow-up-from-bracket"></i>
                Prestar colaborador
            </button>
        </div>
    </header>

    {{-- ── Tabs ─────────────────────────────────────────────────────────────── --}}
    <div class="mb-4 border-b border-light-border dark:border-dark-border">
        <nav class="flex gap-1" id="tabs-nav">
            @php
                $tabs = [
                    ['key' => 'activos',    'label' => 'Activos',    'count' => $activos->count(),    'color' => 'green'],
                    ['key' => 'pendientes', 'label' => 'Pendientes', 'count' => $pendientes->count(), 'color' => 'amber'],
                    ['key' => 'proximos',   'label' => 'Próximos',   'count' => $proximos->count(),   'color' => 'blue'],
                    ['key' => 'historial',  'label' => 'Historial',  'count' => $historial->count(),  'color' => 'gray'],
                ];
                $firstTab = 'pendientes';
                if ($pendientes->isEmpty()) $firstTab = 'activos';
                if ($activos->isEmpty() && $pendientes->isEmpty()) $firstTab = 'proximos';
            @endphp
            @foreach($tabs as $tab)
            <button type="button"
                id="tab-btn-{{ $tab['key'] }}"
                onclick="switchTab('{{ $tab['key'] }}')"
                class="tab-btn flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer
                       {{ $tab['key'] === $firstTab
                          ? 'border-primary text-primary bg-primary/5'
                          : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-primary' }}">
                {{ $tab['label'] }}
                @if($tab['count'] > 0)
                <span class="text-xs px-1.5 py-0.5 rounded-full font-semibold
                    {{ $tab['key'] === 'pendientes' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300'
                      : ($tab['key'] === 'activos' ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300'
                      : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400') }}">
                    {{ $tab['count'] }}
                </span>
                @endif
            </button>
            @endforeach
        </nav>
    </div>

    {{-- ── Panel ACTIVOS ───────────────────────────────────────────────────── --}}
    <div id="panel-activos" class="{{ $firstTab !== 'activos' ? 'hidden' : '' }}">
        @if($activos->isEmpty())
            @include('equipos.partials.empty', ['mensaje' => 'No hay préstamos activos hoy.', 'icon' => 'fa-people-arrows'])
        @else
            @include('equipos.partials.tabla-prestamos', ['coleccion' => $activos, 'modo' => 'activos', 'userId' => $userId, 'puedeAprobar' => $puedeAprobar])
        @endif
    </div>

    {{-- ── Panel PENDIENTES ─────────────────────────────────────────────────── --}}
    <div id="panel-pendientes" class="{{ $firstTab !== 'pendientes' ? 'hidden' : '' }}">
        @if($pendientes->isEmpty())
            @include('equipos.partials.empty', ['mensaje' => 'No hay préstamos pendientes de aprobación.', 'icon' => 'fa-inbox'])
        @else
            @include('equipos.partials.tabla-prestamos', ['coleccion' => $pendientes, 'modo' => 'pendientes', 'userId' => $userId, 'puedeAprobar' => $puedeAprobar])
        @endif
    </div>

    {{-- ── Panel PRÓXIMOS ───────────────────────────────────────────────────── --}}
    <div id="panel-proximos" class="{{ $firstTab !== 'proximos' ? 'hidden' : '' }}">
        @if($proximos->isEmpty())
            @include('equipos.partials.empty', ['mensaje' => 'No hay préstamos próximos programados.', 'icon' => 'fa-calendar-days'])
        @else
            @include('equipos.partials.tabla-prestamos', ['coleccion' => $proximos, 'modo' => 'proximos', 'userId' => $userId, 'puedeAprobar' => $puedeAprobar])
        @endif
    </div>

    {{-- ── Panel HISTORIAL ─────────────────────────────────────────────────── --}}
    <div id="panel-historial" class="{{ $firstTab !== 'historial' ? 'hidden' : '' }}">
        @if($historial->isEmpty())
            @include('equipos.partials.empty', ['mensaje' => 'Sin historial de préstamos.', 'icon' => 'fa-clock-rotate-left'])
        @else
            @include('equipos.partials.tabla-prestamos', ['coleccion' => $historial, 'modo' => 'historial', 'userId' => $userId, 'puedeAprobar' => $puedeAprobar])
        @endif
    </div>


    {{-- ── Modales ──────────────────────────────────────────────────────────── --}}
    @include('equipos.partials.modals.modal-prestar')
    @include('equipos.partials.modals.modal-solicitar')
    @include('equipos.partials.modals.modal-acciones')

    {{-- ── Scripts ─────────────────────────────────────────────────────────── --}}
    @include('equipos.partials.scripts')

</x-app-layout>
