<x-app-layout>
    @section('title', 'Mi Equipo - AMG')

    <header class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Mi Equipo</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gestión de colaboradores asignados</p>
        </div>
        @can('equipos.approve')
        <a href="{{ route('equipos.pendientes') }}"
           class="relative flex items-center gap-2 px-4 py-2 bg-white dark:bg-[#273142] border border-light-border dark:border-dark-border rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-primary transition-colors shadow-sm">
            <i class="fa-solid fa-inbox text-lg"></i>
            <span>Cola de Aprobación</span>
            @if($totalPendientesAprobacion > 0)
                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                    {{ $totalPendientesAprobacion > 9 ? '9+' : $totalPendientesAprobacion }}
                </span>
            @endif
        </a>
        @endcan
    </header>

    {{-- Filtro de fecha --}}
    <div class="mb-6 bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
        <form method="GET" action="{{ route('equipos.index') }}" class="flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha</label>
                <input type="date" name="fecha" value="{{ $fecha }}"
                    class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
            </div>
            <button type="submit"
                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium cursor-pointer">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Buscar
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Panel izquierdo: Mi equipo del día --}}
        <div class="xl:col-span-2 space-y-6">

            {{-- ALERTA: intentos de quitar personas de mi equipo --}}
            @if($alertasEquipo->isNotEmpty())
            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl border-2 border-amber-400 dark:border-amber-600 shadow-sm">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-amber-300 dark:border-amber-700">
                    <span class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-400 dark:bg-amber-600 flex items-center justify-center animate-pulse">
                        <i class="fa-solid fa-triangle-exclamation text-white text-sm"></i>
                    </span>
                    <div>
                        <h2 class="font-bold text-amber-800 dark:text-amber-200 text-sm">
                            Están intentando transferir {{ $alertasEquipo->count() === 1 ? 'a una persona' : $alertasEquipo->count() . ' personas' }} de tu equipo
                        </h2>
                        <p class="text-xs text-amber-600 dark:text-amber-400">Pendiente de aprobación — aún no se ha tomado una decisión</p>
                    </div>
                </div>
                <div class="divide-y divide-amber-200 dark:divide-amber-800">
                    @foreach($alertasEquipo as $alerta)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">
                                {{ $alerta->contrato->persona?->nombre_corto ?? '—' }}
                            </p>
                            <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                                <i class="fa-solid fa-arrow-right mr-1"></i>
                                Lo solicita: <span class="font-medium">{{ $alerta->supervisor->name ?? '—' }}</span>
                            </p>
                        </div>
                        <span class="text-xs text-amber-500 dark:text-amber-400 whitespace-nowrap ml-4">
                            <i class="fa-regular fa-clock mr-1"></i>{{ $alerta->created_at->format('d/m/Y H:i') }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Equipo actual --}}
            <div class="bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border">
                <div class="px-5 py-4 border-b border-light-border dark:border-dark-border flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-users text-primary"></i>
                        Mi equipo — {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
                        <span class="ml-2 text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full font-medium">
                            {{ $equipo->count() }} colaboradores
                        </span>
                    </h2>
                </div>

                @if($equipo->isEmpty())
                    <div class="px-5 py-10 text-center text-gray-400 dark:text-gray-500">
                        <i class="fa-solid fa-users-slash text-3xl mb-3"></i>
                        <p class="text-sm">No tienes colaboradores asignados para este día.</p>
                        <p class="text-xs mt-1">Agrega desde la sección de Huérfanos.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase border-b border-light-border dark:border-dark-border">
                                    <th class="px-5 py-3 text-left font-medium">Colaborador</th>
                                    <th class="px-5 py-3 text-center font-medium">Documento</th>
                                    <th class="px-5 py-3 text-center font-medium">Condición</th>
                                    <th class="px-5 py-3 text-center font-medium">Cargo</th>
                                    <th class="px-5 py-3 text-center font-medium">Empresa</th>
                                    <th class="px-5 py-3 text-center font-medium">Familia</th>
                                    <th class="px-5 py-3 text-center font-medium">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-light-border dark:divide-dark-border">
                                @foreach($equipo as $asignacion)
                                <tr class="hover:bg-gray-50 dark:hover:bg-[#1b2431] transition-colors">
                                    <td class="px-5 py-3 font-medium text-gray-800 dark:text-white">
                                        {{ $asignacion->contrato->persona?->nombre_corto ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-center text-gray-600 dark:text-gray-300">
                                        {{ $asignacion->contrato->persona->numero_documento ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-center text-gray-600 dark:text-gray-300">
                                        {{ $asignacion->contrato->condicion->nombre_condicion ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-center text-gray-600 dark:text-gray-300">
                                        {{ $asignacion->contrato->cargo->nombre_cargo ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-center text-gray-600 dark:text-gray-300">
                                        {{ $asignacion->contrato->planilla->nombre_empresa ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-center text-gray-600 dark:text-gray-300">
                                        {{ $asignacion->contrato->familia->nombre_familia ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <button type="button"
                                            onclick="retirar({{ $asignacion->id_asignacion }}, '{{ addslashes($asignacion->contrato->persona?->nombre_corto ?? '') }}')"
                                            class="text-red-500 hover:text-red-700 transition-colors cursor-pointer"
                                            title="Retirar del equipo">
                                            <i class="fa-solid fa-user-minus"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Mis solicitudes procesadas (aprobadas / rechazadas) --}}
            @if($solicitudesProcesadas->isNotEmpty())
            <div class="bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border">
                <div class="px-5 py-4 border-b border-light-border dark:border-dark-border flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-gray-400"></i>
                    <h2 class="font-semibold text-gray-800 dark:text-white">
                        Mis solicitudes respondidas
                        <span class="ml-2 text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 px-2 py-0.5 rounded-full">
                            {{ $solicitudesProcesadas->count() }}
                        </span>
                    </h2>
                </div>
                <div class="divide-y divide-light-border dark:divide-dark-border">
                    @foreach($solicitudesProcesadas as $sol)
                    <div class="px-5 py-4">
                        <div class="flex items-start gap-3">
                            {{-- Badge estado --}}
                            <div class="flex-shrink-0 mt-0.5">
                                @if($sol->estado === 'aprobado')
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-2 py-1 rounded-full">
                                        <i class="fa-solid fa-check"></i> Aprobada
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 px-2 py-1 rounded-full">
                                        <i class="fa-solid fa-xmark"></i> Rechazada
                                    </span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                    {{ $sol->contrato->persona?->nombre_corto ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    <span class="font-medium text-gray-600 dark:text-gray-300">
                                        {{ $sol->estado === 'aprobado' ? 'Aprobado por:' : 'Rechazado por:' }}
                                    </span>
                                    {{ $sol->aprobadoPor->name ?? '—' }}
                                </p>
                                @if($sol->motivo_rechazo)
                                <p class="mt-1.5 text-xs bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 px-3 py-1.5 rounded-lg">
                                    <i class="fa-solid fa-quote-left text-red-300 mr-1 text-[10px]"></i>{{ $sol->motivo_rechazo }}
                                </p>
                                @endif

                                {{-- Timestamps --}}
                                <div class="flex items-center gap-3 mt-2">
                                    <span class="text-xs text-gray-400">
                                        <i class="fa-regular fa-clock mr-1"></i>
                                        Solicitado: {{ $sol->created_at->format('d/m/Y H:i') }}
                                    </span>
                                    <span class="text-gray-300 dark:text-gray-600">|</span>
                                    <span class="text-xs {{ $sol->estado === 'aprobado' ? 'text-green-500' : 'text-red-400' }}">
                                        <i class="fa-regular fa-circle-check mr-1"></i>
                                        Respondido: {{ $sol->updated_at->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Movimientos de mi equipo: personas que me quitaron o intentaron quitar --}}
            @if($movimientosEquipo->isNotEmpty())
            <div class="bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-blue-200 dark:border-blue-800">
                <div class="px-5 py-4 border-b border-blue-100 dark:border-blue-800 flex items-center gap-2">
                    <i class="fa-solid fa-right-left text-blue-500"></i>
                    <h2 class="font-semibold text-gray-800 dark:text-white">
                        Movimientos de mi equipo
                        <span class="ml-2 text-xs bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 px-2 py-0.5 rounded-full">
                            {{ $movimientosEquipo->count() }}
                        </span>
                    </h2>
                </div>
                <div class="divide-y divide-light-border dark:divide-dark-border">
                    @foreach($movimientosEquipo as $mov)
                    <div class="px-5 py-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                @if($mov->estado === 'aprobado')
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 px-2 py-1 rounded-full">
                                        <i class="fa-solid fa-arrow-right-from-bracket"></i> Transferido
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-1 rounded-full">
                                        <i class="fa-solid fa-shield-halved"></i> Intento rechazado
                                    </span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                    {{ $mov->contrato->persona?->nombre_corto ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    @if($mov->estado === 'aprobado')
                                        <span class="font-medium text-gray-600 dark:text-gray-300">Transferido a:</span>
                                        {{ $mov->supervisor->name ?? '—' }}
                                    @else
                                        <span class="font-medium text-gray-600 dark:text-gray-300">Intento de:</span>
                                        {{ $mov->supervisor->name ?? '—' }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    <span class="font-medium text-gray-600 dark:text-gray-300">
                                        {{ $mov->estado === 'aprobado' ? 'Aprobado por:' : 'Rechazado por:' }}
                                    </span>
                                    {{ $mov->aprobadoPor->name ?? '—' }}
                                </p>
                                <div class="flex items-center gap-3 mt-2">
                                    <span class="text-xs text-gray-400">
                                        <i class="fa-regular fa-clock mr-1"></i>Solicitado: {{ $mov->created_at->format('d/m/Y H:i') }}
                                    </span>
                                    <span class="text-gray-300 dark:text-gray-600">|</span>
                                    <span class="text-xs {{ $mov->estado === 'aprobado' ? 'text-orange-500' : 'text-gray-400' }}">
                                        <i class="fa-regular fa-circle-check mr-1"></i>Respondido: {{ $mov->updated_at->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Solicitudes pendientes hacia mi equipo (otros supervisores me los piden) --}}
            @if($solicitudesPendientes->isNotEmpty())
            <div class="bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-yellow-300 dark:border-yellow-700">
                <div class="px-5 py-4 border-b border-yellow-200 dark:border-yellow-700 flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-yellow-500"></i>
                    <h2 class="font-semibold text-gray-800 dark:text-white">
                        Solicitudes sobre tu equipo
                        <span class="ml-2 text-xs bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300 px-2 py-0.5 rounded-full">
                            {{ $solicitudesPendientes->count() }} pendientes
                        </span>
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase border-b border-light-border dark:border-dark-border">
                                <th class="px-5 py-3 text-left font-medium">Colaborador</th>
                                <th class="px-5 py-3 text-left font-medium">Solicitado a</th>
                                <th class="px-5 py-3 text-left font-medium">Solicitado el</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-light-border dark:divide-dark-border">
                            @foreach($solicitudesPendientes as $sol)
                            <tr class="hover:bg-yellow-50 dark:hover:bg-yellow-900/10 transition-colors">
                                <td class="px-5 py-3 font-medium text-gray-800 dark:text-white">
                                    {{ $sol->contrato->persona?->nombre_corto ?? '—' }}
                                </td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $sol->prevSupervisor->name ?? '(sin equipo)' }}
                                </td>
                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400 text-xs">
                                    {{ $sol->created_at->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>

        {{-- Panel derecho: Huérfanos / En otros equipos --}}
        <div class="xl:col-span-1">
            <div class="bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border sticky top-4">

                {{-- Tabs --}}
                <div class="flex border-b border-light-border dark:border-dark-border">
                    <button id="tab-huerfanos" onclick="switchTab('huerfanos')"
                        class="flex-1 px-4 py-3 text-sm font-medium text-primary border-b-2 border-primary bg-primary/5 transition-colors cursor-pointer">
                        <i class="fa-solid fa-user-clock mr-1"></i>
                        Sin equipo
                        @if($huerfanos->isNotEmpty())
                            <span class="ml-1 text-xs bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-300 px-1.5 py-0.5 rounded-full">{{ $huerfanos->count() }}</span>
                        @endif
                    </button>
                    <button id="tab-otros" onclick="switchTab('otros')"
                        class="flex-1 px-4 py-3 text-sm font-medium text-gray-500 dark:text-gray-400 border-b-2 border-transparent hover:text-primary transition-colors cursor-pointer">
                        <i class="fa-solid fa-people-arrows mr-1"></i>
                        Otros equipos
                        @if($enOtrosEquipos->isNotEmpty())
                            <span class="ml-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 px-1.5 py-0.5 rounded-full">{{ $enOtrosEquipos->count() }}</span>
                        @endif
                    </button>
                </div>

                {{-- Tab: Huérfanos --}}
                <div id="panel-huerfanos">
                    @if($huerfanos->isEmpty())
                        <div class="px-5 py-8 text-center text-gray-400 dark:text-gray-500">
                            <i class="fa-solid fa-check-circle text-2xl mb-2 text-green-400"></i>
                            <p class="text-sm">Todos asignados para este día.</p>
                        </div>
                    @else
                        <div class="px-3 pt-3">
                            <input type="text" id="search-huerfanos" placeholder="Buscar por nombre..."
                                oninput="filtrarLista('search-huerfanos', 'lista-huerfanos')"
                                class="w-full px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                        </div>
                        <ul id="lista-huerfanos" class="divide-y divide-light-border dark:divide-dark-border max-h-[55vh] overflow-y-auto mt-2">
                            @foreach($huerfanos as $contrato)
                            <li class="px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-[#1b2431] transition-colors">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-white truncate">
                                        {{ $contrato->persona?->nombre_corto ?? '—' }}
                                    </p>
                                    <p class="text-xs text-gray-400 truncate">
                                        {{ $contrato->condicion->nombre_condicion ?? '—' }}
                                        - {{ $contrato->familia->nombre_familia ?? '—' }}
                                    </p>
                                </div>
                                <button type="button"
                                    onclick="agregarHuerfano({{ $contrato->id_contrato }}, '{{ addslashes($contrato->persona?->nombre_corto ?? '') }}', '{{ $fecha }}')"
                                    class="ml-2 flex-shrink-0 p-1.5 text-primary hover:bg-primary/10 rounded-lg transition-colors cursor-pointer"
                                    title="Agregar a mi equipo">
                                    <i class="fa-solid fa-user-plus text-sm"></i>
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Tab: En otros equipos --}}
                <div id="panel-otros" class="hidden">
                    @if($enOtrosEquipos->isEmpty())
                        <div class="px-5 py-8 text-center text-gray-400 dark:text-gray-500">
                            <i class="fa-solid fa-people-group text-2xl mb-2"></i>
                            <p class="text-sm">No hay colaboradores en otros equipos.</p>
                        </div>
                    @else
                        <div class="px-3 pt-3">
                            <input type="text" id="search-otros" placeholder="Buscar por nombre..."
                                oninput="filtrarLista('search-otros', 'lista-otros')"
                                class="w-full px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                        </div>
                        <ul id="lista-otros" class="divide-y divide-light-border dark:divide-dark-border max-h-[55vh] overflow-y-auto mt-2">
                            @foreach($enOtrosEquipos as $asignacion)
                            <li class="px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-[#1b2431] transition-colors">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-white truncate">
                                        {{ $asignacion->contrato->persona?->nombre_corto ?? '—' }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        <i class="fa-solid fa-user text-gray-300 mr-1"></i>{{ $asignacion->supervisor->name ?? '—' }}
                                    </p>
                                </div>
                                <button type="button"
                                    onclick="solicitarTraslado({{ $asignacion->contrato->id_contrato }}, '{{ addslashes($asignacion->contrato->persona?->nombre_corto ?? '') }}', '{{ $fecha }}')"
                                    class="ml-2 flex-shrink-0 p-1.5 text-yellow-500 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 rounded-lg transition-colors cursor-pointer"
                                    title="Solicitar traslado">
                                    <i class="fa-solid fa-paper-plane text-sm"></i>
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

            </div>
        </div>

    </div>

    {{-- Modal de confirmación: retirar --}}
    <div id="modal-retirar" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#273142] rounded-xl shadow-xl w-full max-w-sm p-6">
            <h3 class="font-semibold text-gray-800 dark:text-white text-lg mb-2">Retirar colaborador</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                ¿Seguro que deseas retirar a <strong id="nombre-retirar"></strong> de tu equipo? Quedará sin supervisor para ese día.
            </p>
            <div class="flex gap-3 justify-end">
                <button onclick="cerrarModalRetirar()"
                    class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                    Cancelar
                </button>
                <button id="btn-confirmar-retirar"
                    class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 cursor-pointer">
                    Retirar
                </button>
            </div>
        </div>
    </div>

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── Buscador de listas ─────────────────────────────────────
function filtrarLista(inputId, listaId) {
    const q = document.getElementById(inputId).value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    document.querySelectorAll(`#${listaId} li`).forEach(li => {
        const texto = li.querySelector('p')?.textContent.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '') ?? '';
        li.style.display = texto.includes(q) ? '' : 'none';
    });
}

// ── Tabs ───────────────────────────────────────────────────
function switchTab(tab) {
    const tabs    = { huerfanos: 'tab-huerfanos', otros: 'tab-otros' };
    const panels  = { huerfanos: 'panel-huerfanos', otros: 'panel-otros' };

    Object.keys(tabs).forEach(key => {
        const isActive = key === tab;
        document.getElementById(tabs[key]).classList.toggle('text-primary', isActive);
        document.getElementById(tabs[key]).classList.toggle('border-primary', isActive);
        document.getElementById(tabs[key]).classList.toggle('bg-primary/5', isActive);
        document.getElementById(tabs[key]).classList.toggle('text-gray-500', !isActive);
        document.getElementById(tabs[key]).classList.toggle('dark:text-gray-400', !isActive);
        document.getElementById(tabs[key]).classList.toggle('border-transparent', !isActive);
        document.getElementById(panels[key]).classList.toggle('hidden', !isActive);
    });
}

// ── Retirar ────────────────────────────────────────────────
let idAsignacionPendiente = null;

function retirar(idAsignacion, nombre) {
    idAsignacionPendiente = idAsignacion;
    document.getElementById('nombre-retirar').textContent = nombre;
    document.getElementById('modal-retirar').classList.remove('hidden');
}

function cerrarModalRetirar() {
    document.getElementById('modal-retirar').classList.add('hidden');
    idAsignacionPendiente = null;
}

document.getElementById('btn-confirmar-retirar').addEventListener('click', async () => {
    if (!idAsignacionPendiente) return;
    try {
        const res = await fetch(`/equipos/${idAsignacionPendiente}/retirar`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.error ?? 'Error al retirar.');
            cerrarModalRetirar();
        }
    } catch (e) {
        alert('Error de conexión.');
        cerrarModalRetirar();
    }
});

// ── Agregar Huérfano ───────────────────────────────────────
async function agregarHuerfano(idContrato, nombre, fecha) {
    if (!confirm(`¿Agregar a ${nombre} a tu equipo del ${fecha}?`)) return;
    try {
        const res = await fetch('/equipos/agregar', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ id_contrato: idContrato, fecha })
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else if (data.requiere_solicitud) {
            if (confirm(data.error + '\n\n¿Deseas enviar una solicitud de traslado?')) {
                await solicitar(idContrato, fecha);
            }
        } else {
            alert(data.error ?? 'Error al agregar.');
        }
    } catch (e) {
        alert('Error de conexión.');
    }
}

// ── Solicitar traslado ─────────────────────────────────────
async function solicitarTraslado(idContrato, nombre, fecha) {
    if (!confirm(`¿Enviar solicitud para agregar a ${nombre} el ${fecha}? Un aprobador deberá autorizarlo.`)) return;
    await solicitar(idContrato, fecha);
}

async function solicitar(idContrato, fecha) {
    try {
        const res = await fetch('/equipos/solicitar', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ id_contrato: idContrato, fecha })
        });
        const data = await res.json();
        if (data.success) {
            alert('Solicitud enviada correctamente.');
            location.reload();
        } else {
            alert(data.error ?? 'Error al enviar solicitud.');
        }
    } catch (e) {
        alert('Error de conexión.');
    }
}
</script>
@endpush

</x-app-layout>
