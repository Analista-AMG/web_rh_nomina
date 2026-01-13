<x-app-layout>
    @section('title', 'AMG International')

    <header class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Gestión de Personas</h1>
    </header>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-ui.kpi-card title="Total Personas" :value="$kpis['total']" />
        <x-ui.kpi-card title="Nuevas este mes" :value="$kpis['nuevas']" color="text-primary" />
        <x-ui.kpi-card title="Activas" :value="$kpis['activas']" color="text-success" />
    </div>

    <!-- Header & Search -->
    <div class="mb-4 flex flex-col md:flex-row justify-between items-center gap-4">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Últimas personas registradas</h2>
        <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
            <div class="relative w-full md:w-64">
                <i class="fa-solid fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="server-search-name" value="{{ request('search_name') }}" 
                       placeholder="Buscar por Nombre" 
                       class="w-full pl-10 pr-4 py-2 rounded-lg border border-[#ffffff] dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all">
            </div>
            <div class="relative w-full md:w-58">
                <i class="fa-solid fa-id-card absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="server-search-doc" value="{{ request('search_doc') }}"
                       placeholder="Buscar por N° Documento" 
                       class="w-full pl-10 pr-4 py-2 rounded-lg border border-[#ffffff] dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all">
            </div>
            
            @include('personas.partials.add-button')
        </div>
    </div>

    <!-- Tabla Unificada con Alineación Centrada -->
    <div class="overflow-x-auto px-4 pb-4">
        <table class="w-full text-center" style="border-collapse: separate; border-spacing: 0 4px;">
            <thead>
                <tr>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-left">Colaborador</th>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">F. Nac.</th>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nacionalidad</th>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Correo personal</th>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($personas as $persona)
                @php
                    $fechaNac = $persona->fecha_nacimiento ? \Carbon\Carbon::parse($persona->fecha_nacimiento)->format('d/m/Y') : '-';
                    $fechaNacIso = $persona->fecha_nacimiento ? \Carbon\Carbon::parse($persona->fecha_nacimiento)->format('Y-m-d') : '';
                    $estado = $persona->estado;
                    $badgeClass = ($estado == 1) ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 
                                 (($estado == 2) ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 
                                 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400');
                    $estadoTexto = ($estado == 1) ? 'Activo' : (($estado == 2) ? 'Pendiente' : 'Inactivo');
                @endphp
                <tr class="group transition-all duration-300 transform hover:scale-[1.01] hover:shadow-xl hover:z-10"
                    data-id="{{ $persona->id_persona }}"
                    data-doc="{{ $persona->numero_documento }}"
                    data-tdoc="{{ $persona->tipo_documento }}"
                    data-nombres="{{ $persona->nombres }}"
                    data-paterno="{{ $persona->apellido_paterno }}"
                    data-materno="{{ $persona->apellido_materno }}"
                    data-nac="{{ $fechaNacIso }}"
                    data-genero="{{ $persona->genero }}"
                    data-nacionalidad="{{ $persona->nacionalidad }}"
                    data-correo-pers="{{ $persona->correo_electronico_personal }}"
                    data-correo-corp="{{ $persona->correo_electronico_corporativo }}"
                    data-direccion="{{ $persona->direccion }}"
                >
                    <!-- Columna Colaborador (Izquierda) -->
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-left rounded-l-xl border-y border-l border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center text-primary text-sm font-bold">
                                {{ substr($persona->nombres ?? '?', 0, 1) }}{{ substr($persona->apellido_paterno ?? '?', 0, 1) }}
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-800 dark:text-white leading-tight">
                                    {{ $persona->apellido_paterno ?? '' }} {{ $persona->apellido_materno ?? '' }} {{ $persona->nombres ?? 'Sin Asignar' }}
                                </span>
                                <span class="text-[12px] text-gray-500 font-medium mt-0.5">
                                    {{ $persona->tipo_documento ?? 'DOC' }}: {{ $persona->numero_documento ?? '---' }}
                                </span>
                            </div>
                        </div>
                    </td>
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-500 dark:text-[#ffffff] border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">{{ $fechaNac }}</td>
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-500 dark:text-[#ffffff] border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm truncate">{{ $persona->nacionalidad }}</td>
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-500 dark:text-[#ffffff] border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm truncate">{{ $persona->correo_electronico_personal }}</td>
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }} ">
                            {{ $estadoTexto }}
                        </span>
                    </td>
                    <td class="bg-white dark:bg-[#273142] px-6 py-1 text-center rounded-r-xl border-y border-r border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                        <div class="flex justify-center gap-2">
                            <!-- Botón Ver -->
                            <div class="relative group/tooltip">
                                <button type="button" class="btn-view w-7 h-7 rounded-full bg-white dark:bg-black flex items-center justify-center shadow-md text-black dark:text-white hover:bg-[#3498db] hover:text-white transition-colors duration-300 cursor-pointer">
                                    <i class="fa-solid fa-eye text-sm pointer-events-none"></i>
                                </button>
                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 invisible group-hover/tooltip:visible opacity-0 group-hover/tooltip:opacity-100 transition-opacity bg-gray-900 text-white text-[10px] px-2 py-1 rounded shadow-lg whitespace-nowrap z-50">
                                    Ver
                                    <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900"></span>
                                </span>
                            </div>

                            <!-- Botón Editar -->
                            <div class="relative group/tooltip">
                                <button type="button" class="btn-edit w-7 h-7 rounded-full bg-white dark:bg-black flex items-center justify-center shadow-md text-black dark:text-white hover:bg-[#e67e22] hover:text-white transition-colors duration-300 cursor-pointer">
                                    <i class="fa-solid fa-pen text-sm pointer-events-none"></i>
                                </button>
                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 invisible group-hover/tooltip:visible opacity-0 group-hover/tooltip:opacity-100 transition-opacity bg-gray-900 text-white text-[10px] px-2 py-1 rounded shadow-lg whitespace-nowrap z-50">
                                    Editar
                                    <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900"></span>
                                </span>
                            </div>

                            <!-- Botón Eliminar -->
                            <div class="relative group/tooltip">
                                <button type="button" class="btn-delete w-7 h-7 rounded-full bg-white dark:bg-black flex items-center justify-center shadow-md text-black dark:text-white hover:bg-[#e74c3c] hover:text-white transition-colors duration-300 cursor-pointer">
                                    <i class="fa-solid fa-trash text-sm pointer-events-none"></i>
                                </button>
                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 invisible group-hover/tooltip:visible opacity-0 group-hover/tooltip:opacity-100 transition-opacity bg-gray-900 text-white text-[10px] px-2 py-1 rounded shadow-lg whitespace-nowrap z-50">
                                    Eliminar
                                    <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900"></span>
                                </span>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-6 py-12 text-center text-gray-500">No se encontraron resultados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($personas->hasPages())
    <div class="mt-4 px-4 pb-4">
        {{ $personas->links('vendor.pagination.tailwind') }}
    </div>
    @endif

    <!-- Inclusión de Modales -->
    @include('personas.partials.modals.create')
    @include('personas.partials.modals.edit')
    @include('personas.partials.modals.view')

    <!-- Lógica JS -->
    @include('personas.partials.scripts')

</x-app-layout>