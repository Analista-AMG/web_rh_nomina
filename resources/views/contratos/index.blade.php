<x-app-layout>
    @section('title', 'Gestión de Contratos - AMG')

    <header class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Gestión de Contratos</h1>
    </header>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-ui.kpi-card title="Contratos Activos" :value="$kpis['activos']" color="text-success" />
        <x-ui.kpi-card title="Por Vencer (30 días)" :value="$kpis['por_vencer']" color="text-warning" />
        <x-ui.kpi-card title="Histórico Total" :value="$kpis['total']" />
    </div>

    <!-- Header & Search -->
    <div class="mb-4 flex flex-col md:flex-row justify-between items-center gap-4">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Listado de Contratos</h2>
        <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
            <!-- Buscador por Nombre -->
            <div class="relative w-full md:w-64">
                <i class="fa-solid fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="server-search-name" value="{{ request('search_name') }}" 
                       placeholder="Buscar por Nombre" 
                       class="w-full pl-10 pr-4 py-2 rounded-lg border border-[#ffffff] dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all">
            </div>
            <!-- Buscador por Documento -->
            <div class="relative w-full md:w-58">
                <i class="fa-solid fa-id-card absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="server-search-doc" value="{{ request('search_doc') }}"
                       placeholder="Buscar por N° Documento" 
                       class="w-full pl-10 pr-4 py-2 rounded-lg border border-[#ffffff] dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all">
            </div>
            
            @include('contratos.partials.add-button')
        </div>
    </div>

    <!-- Tabla Unificada -->
    <div class="overflow-x-auto px-4 pb-4">
        <table class="w-full text-center" style="border-collapse: separate; border-spacing: 0 4px;">
            <thead>
                <tr>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-left">Colaborador</th>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cargo</th>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Salario</th>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Inicio</th>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fin</th>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contratos as $contrato)
                @php
                    $inicio = $contrato->inicio_contrato ? \Carbon\Carbon::parse($contrato->inicio_contrato)->format('d/m/Y') : '-';
                    $fin = $contrato->fin_contrato ? \Carbon\Carbon::parse($contrato->fin_contrato)->format('d/m/Y') : 'Indefinido';
                    
                    $estado = $contrato->estado;
                    // Lógica visual de estado
                    if ($estado == 1) {
                        $badgeClass = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
                        $estadoTexto = 'Activo';
                    } else {
                        $badgeClass = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
                        $estadoTexto = 'Inactivo';
                    }

                    // Formato moneda
                    $salario = 'S/ ' . number_format($contrato->haber_basico, 2);
                    $nombreCompleto = ($contrato->persona->nombres ?? '') . ' ' . ($contrato->persona->apellido_paterno ?? '');
                    $inicioIso = $contrato->inicio_contrato ? \Carbon\Carbon::parse($contrato->inicio_contrato)->format('Y-m-d') : '';
                    $finIso = $contrato->fin_contrato ? \Carbon\Carbon::parse($contrato->fin_contrato)->format('Y-m-d') : '';
                @endphp
                <tr class="group transition-all duration-300 transform hover:scale-[1.01] hover:shadow-xl hover:z-10"
                    data-id="{{ $contrato->id_contrato }}"
                    data-empleado="{{ $nombreCompleto }}"
                    data-cargo="{{ $contrato->cargo->nombre_cargo ?? '' }}"
                    data-salario="{{ $contrato->haber_basico }}"
                    data-inicio="{{ $inicioIso }}"
                    data-fin="{{ $finIso }}"
                    data-estado="{{ $estado }}"
                >
                    
                    <!-- Columna Colaborador (Izquierda) -->
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-left rounded-l-xl border-y border-l border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold">
                                {{ substr($contrato->persona->nombres ?? '?', 0, 1) }}{{ substr($contrato->persona->apellido_paterno ?? '?', 0, 1) }}
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-800 dark:text-white leading-tight">
                                    {{ $contrato->persona->apellido_paterno ?? '' }} {{ $contrato->persona->nombres ?? 'Sin Asignar' }}
                                </span>
                                <span class="text-[11px] text-gray-500 font-medium mt-0.5">
                                    {{ $contrato->persona->tipo_documento ?? 'DOC' }}: {{ $contrato->persona->numero_documento ?? '---' }}
                                </span>
                            </div>
                        </div>
                    </td>

                    <!-- Cargo -->
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-700 dark:text-[#ffffff] border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm font-medium">
                        {{ $contrato->cargo->nombre_cargo ?? 'Sin Cargo' }}
                    </td>

                    <!-- Salario -->
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-700 dark:text-[#ffffff] border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm font-mono">
                        {{ $salario }}
                    </td>

                    <!-- Inicio -->
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-500 dark:text-[#ffffff] border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                        {{ $inicio }}
                    </td>

                    <!-- Fin -->
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-500 dark:text-[#ffffff] border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                        {{ $fin }}
                    </td>

                    <!-- Estado -->
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                            {{ $estadoTexto }}
                        </span>
                    </td>

                    <!-- Acciones -->
                    <td class="bg-white dark:bg-[#273142] px-6 py-1 text-center rounded-r-xl border-y border-r border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                        <div class="flex justify-center gap-2">
                            <!-- Ver -->
                            <button type="button" class="btn-view w-7 h-7 rounded-full bg-white dark:bg-black flex items-center justify-center shadow-md text-black dark:text-white hover:bg-[#3498db] hover:text-white transition-colors duration-300 cursor-pointer" title="Ver Detalles">
                                <i class="fa-solid fa-eye text-sm pointer-events-none"></i>
                            </button>

                            <!-- Editar -->
                            <button type="button" class="btn-edit w-7 h-7 rounded-full bg-white dark:bg-black flex items-center justify-center shadow-md text-black dark:text-white hover:bg-[#e67e22] hover:text-white transition-colors duration-300 cursor-pointer" title="Editar">
                                <i class="fa-solid fa-pen text-sm pointer-events-none"></i>
                            </button>

                            <!-- Eliminar -->
                            <form action="{{ route('contratos.destroy', $contrato->id_contrato) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-delete w-7 h-7 rounded-full bg-white dark:bg-black flex items-center justify-center shadow-md text-black dark:text-white hover:bg-[#e74c3c] hover:text-white transition-colors duration-300 cursor-pointer" title="Eliminar">
                                    <i class="fa-solid fa-trash text-sm pointer-events-none"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">No se encontraron contratos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($contratos->hasPages())
    <div class="mt-4 px-4 pb-4">
        {{ $contratos->links('vendor.pagination.tailwind') }}
    </div>
    @endif

    <!-- Inclusión de Modales -->
    @include('contratos.partials.modals.create')
    @include('contratos.partials.modals.edit')
    @include('contratos.partials.modals.view')

    <!-- Lógica JS -->
    @include('contratos.partials.scripts')

</x-app-layout>
