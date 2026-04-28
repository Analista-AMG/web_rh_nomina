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

            @can('contratos.create')
                @include('contratos.partials.add-button')
            @endcan
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
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Planilla</th>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Inicio</th>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fin</th>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contratos as $contrato)
                
                {{-- Fila Principal del Contrato --}}
                <tr class="group transition-all duration-300 transform hover:scale-[1.01] hover:shadow-xl hover:z-10 cursor-pointer expandable-row">
                    @php
                        $inicio = \Carbon\Carbon::parse($contrato->inicio_contrato)->format('d/m/Y');
                        $fin = $contrato->fin_contrato ? \Carbon\Carbon::parse($contrato->fin_contrato)->format('d/m/Y') : 'Indefinido';
                        
                        $estadoCalculado = $contrato->estado; // Obtener la cadena del accessor

                        if ($estadoCalculado == 'Activo') {
                            $badgeClass = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
                            $estadoTexto = 'Activo';
                        } elseif ($estadoCalculado == 'Pendiente') {
                            $badgeClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'; // Azul para Pendiente
                            $estadoTexto = 'Pendiente';
                        } else { // 'Finalizado'
                            $badgeClass = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
                            $estadoTexto = 'Finalizado';
                        }
                        $puedeVerSalario = Auth::user()->can('contratos.edit');
                        $salario = $puedeVerSalario
                            ? 'S/ ' . number_format($contrato->haber_basico, 2)
                            : 'S/ ••••••';
                    @endphp
                    
                    <!-- Celdas de la fila principal -->
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-left rounded-l-xl border-y border-l border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold">
                                {{ mb_substr($contrato->persona->apellido_paterno ?? '?', 0, 1, 'UTF-8') }}{{ mb_substr(explode(' ', trim($contrato->persona->nombres ?? '?'))[0], 0, 1, 'UTF-8') }}
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-800 dark:text-white leading-tight">
                                    {{ $contrato->persona->nombre_corto ?? 'Sin Asignar' }}
                                </span>
                                <span class="text-[14px] text-gray-500 font-bold mt-0.5">
                                    {{ $contrato->persona->tipo_documento ?? 'DOC' }}: {{ $contrato->persona->numero_documento ?? '---' }}
                                </span>
                            </div>
                        </div>
                    </td>
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-700 dark:text-[#ffffff] border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm font-medium">
                        {{ $contrato->cargo->nombre_cargo ?? 'Sin Cargo' }}
                    </td>
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-700 dark:text-[#ffffff] border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm font-mono">
                        {{ $salario }}
                    </td>
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-700 dark:text-[#ffffff] border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm font-mono">
                        {{ $contrato->planilla->nombre_planilla }}
                    </td>
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-500 dark:text-[#ffffff] border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                        {{ $inicio }}
                    </td>
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-500 dark:text-[#ffffff] border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                        {{ $fin }}
                    </td>
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                            {{ $estadoTexto }}
                        </span>
                    </td>
                    <td class="bg-white dark:bg-[#273142] px-6 py-1 text-center rounded-r-xl border-y border-r border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                        <div class="flex justify-center items-center gap-2">
                            {{-- Botón Ver Contrato --}}
                            <x-ui.action-button type="view" title="Ver Contrato" class="btn-view-contrato"
                                data-colaborador="{{ $contrato->persona->nombre_corto ?? 'Sin Asignar' }}"
                                data-documento="{{ ($contrato->persona->tipo_documento ?? 'DOC') . ': ' . ($contrato->persona->numero_documento ?? '---') }}"
                                data-cargo="{{ $contrato->cargo->nombre_cargo ?? 'Sin Cargo' }}"
                                data-planilla="{{ $contrato->planilla->nombre_planilla ?? 'N/A' }}"
                                data-fp="{{ $contrato->fondoPension->fondo_pension ?? 'N/A' }}"
                                data-condicion="{{ $contrato->condicion->nombre_condicion ?? 'N/A' }}"
                                data-banco="{{ $contrato->banco->nombre_banco ?? 'N/A' }}"
                                data-moneda="{{ $contrato->moneda->nombre_moneda ?? 'N/A' }}"
                                data-centro-costo="{{ $contrato->centroCosto->nombre_centro_costo ?? 'N/A' }}"
                                data-familia="{{ $contrato->familia->nombre_familia ?? 'N/A' }}" 
                                data-inicio="{{ $inicio }}"
                                data-fin="{{ $fin }}"
                                data-fecha-renuncia="{{ $contrato->fecha_renuncia ? \Carbon\Carbon::parse($contrato->fecha_renuncia)->format('d/m/Y') : 'No registrada' }}"
                                data-haber="{{ $puedeVerSalario ? number_format($contrato->haber_basico, 2) : '••••••' }}"
                                data-asignacion="{{ $contrato->asignacion_familiar ? 'Sí' : 'No' }}"
                                data-movilidad="{{ number_format($contrato->movilidad ?? 0, 2) }}"
                                data-numero-cuenta="{{ $contrato->numero_cuenta ?? 'N/A' }}"
                                data-codigo-interbancario="{{ $contrato->codigo_interbancario ?? 'N/A' }}"
                                data-numero-cuenta-cts="{{ $contrato->numero_cuenta_cts ?? 'N/A' }}"
                                data-codigo-interbancario-cts="{{ $contrato->codigo_interbancario_cts ?? 'N/A' }}"
                                data-periodo-prueba="{{ $contrato->periodo_prueba ? 'Sí' : 'No' }}"
                                data-suspension-renta="{{ $contrato->suspension_renta ? 'Sí' : 'No' }}"
                                data-estado="{{ $estadoTexto }}" />

                            {{-- Botón Añadir Movimiento --}}
                            @can('contratos.create')
                            @php
                                $ultimoMov = $contrato->movimientos->sortByDesc('inicio')->first();
                                $lastMovJson = $ultimoMov ? json_encode([
                                    'cargo_id'           => $ultimoMov->cargo_id,
                                    'planilla_id'        => $ultimoMov->planilla_id,
                                    'fondo_pensiones_id' => $ultimoMov->fondo_pensiones_id,
                                    'condicion_id'       => $ultimoMov->condicion_id,
                                    'banco_id'           => $ultimoMov->banco_id,
                                    'centro_costo_id'    => $ultimoMov->centro_costo_id,
                                    'familia_id'         => $ultimoMov->familia_id,
                                    'moneda_id'          => $ultimoMov->moneda_id,
                                    'haber'              => $ultimoMov->haber_basico,
                                    'asignacion'         => $ultimoMov->asignacion_familiar ? 1 : 0,
                                    'suspension_renta'   => $ultimoMov->suspension_renta ? 1 : 0,
                                ]) : '{}';
                            @endphp
                            <x-ui.action-button type="add" title="Añadir Movimiento" class="btn-add-movimiento-main"
                                data-contrato-id="{{ $contrato->id }}"
                                data-colaborador="{{ $contrato->persona->nombre_corto ?? 'Sin Asignar' }}"
                                data-documento="{{ ($contrato->persona->tipo_documento ?? 'DOC') . ': ' . ($contrato->persona->numero_documento ?? '---') }}"
                                data-last-mov="{{ $lastMovJson }}"
                                data-contrato-inicio="{{ \Carbon\Carbon::parse($contrato->inicio_contrato)->addDay()->format('Y-m-d') }}"
                                data-contrato-fin="{{ $contrato->fin_contrato ? \Carbon\Carbon::parse($contrato->fin_contrato)->format('Y-m-d') : '' }}" />
                            @endcan
                            
                            {{-- Botón Baja --}}
                            @can('contratos.baja')
                            @php
                                $bajaData = $contrato->baja ? json_encode([
                                    'id_baja' => $contrato->baja->id,
                                    'fecha_baja' => $contrato->baja->fecha_baja ? $contrato->baja->fecha_baja->format('Y-m-d') : '',
                                    'motivo_baja' => $contrato->baja->motivo_baja ?? '',
                                    'aviso_con_15_dias' => $contrato->baja->aviso_con_15_dias ? '1' : '0',
                                    'recomienda_reingreso' => $contrato->baja->recomienda_reingreso ? '1' : '0',
                                    'observacion' => $contrato->baja->observacion ?? '',
                                ]) : '{}';
                            @endphp
                            <x-ui.action-button type="baja" class="btn-baja-contrato"
                                data-contrato-id="{{ $contrato->id }}"
                                data-colaborador-nombre="{{ $contrato->persona->nombre_corto ?? '' }}"
                                data-colaborador-doc="{{ ($contrato->persona->tipo_documento ?? 'DOC') . ': ' . ($contrato->persona->numero_documento ?? '---') }}"
                                data-contrato-inicio="{{ \Carbon\Carbon::parse($contrato->inicio_contrato)->format('Y-m-d') }}"
                                data-contrato-fin="{{ $contrato->fin_contrato ? \Carbon\Carbon::parse($contrato->fin_contrato)->format('Y-m-d') : '' }}"
                                data-baja="{{ $bajaData }}" />
                            @endcan

                        </div>
                    </td>
                </tr>

                {{-- Sub-Fila de Movimientos (Oculta por defecto) --}}
                <tr class="sub-row" style="display: none;">
                    <td colspan="8" class="p-0">
                        <div class="bg-gray-50 dark:bg-[#1e2836] p-4">
                            {{-- Cabecera de la sub-fila: Limpia de botones de acción del contrato principal --}}
                            <div class="flex justify-end items-center mb-3">
                                {{-- Este espacio está limpio de botones de acción del contrato principal --}}
                            </div>
                            @if($contrato->movimientos->isNotEmpty())
                            <table class="min-w-full text-sm text-center">
                                <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                                    <tr>
                                        <th class="py-2 px-3 text-center">Tipo Movimiento</th>
                                        <th class="py-2 px-3 text-center">Fecha Efectiva</th>
                                        <th class="py-2 px-3 text-center">Salario</th>
                                        <th class="py-2 px-3 text-center">Cargo</th>
                                        <th class="py-2 px-3 text-center">Planilla</th>
                                        <th class="py-2 px-3 text-center">Fondo Pensiones</th>
                                        <th class="py-2 px-3 text-center">Registrado</th>
                                        <th class="py-2 px-3 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($contrato->movimientos->sortByDesc('inicio') as $mov)
                                    @php
                                        $inicioMov = $mov->inicio ? $mov->inicio->format('d/m/Y') : '-';
                                        $finMov = $mov->fin ? $mov->fin->format('d/m/Y') : 'Indefinido';
                                        $estadoMov = $mov->estado ? 'Activo' : 'Inactivo';
                                        $asignacionMov = $mov->asignacion_familiar ? 'Sí' : 'No';
                                    @endphp
                                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-700/50"
                                        data-mov-id="{{ $mov->id }}"
                                        data-mov-colaborador="{{ $contrato->persona->nombre_corto ?? '' }}"
                                        data-mov-documento="{{ ($contrato->persona->tipo_documento ?? 'DOC') . ': ' . ($contrato->persona->numero_documento ?? '---') }}"
                                        data-mov-tipo="{{ $mov->tipo_movimiento ?? '' }}"
                                        data-mov-cargo="{{ $mov->cargo->nombre_cargo ?? 'N/A' }}"
                                        data-mov-cargo-id="{{ $mov->cargo_id ?? '' }}"
                                        data-mov-planilla="{{ $mov->planilla->nombre_planilla ?? 'N/A' }}"
                                        data-mov-planilla-id="{{ $mov->planilla_id ?? '' }}"
                                        data-mov-inicio="{{ $inicioMov }}"
                                        data-mov-inicio-raw="{{ $mov->inicio ? $mov->inicio->format('Y-m-d') : '' }}"
                                        data-mov-fin="{{ $finMov }}"
                                        data-mov-fin-raw="{{ $mov->fin ? $mov->fin->format('Y-m-d') : '' }}"
                                        data-mov-haber="{{ $puedeVerSalario ? number_format($mov->haber_basico, 2) : '••••••' }}"
                                        data-mov-haber-raw="{{ $puedeVerSalario ? $mov->haber_basico : '' }}"
                                        data-mov-asignacion="{{ $asignacionMov }}"
                                        data-mov-asignacion-raw="{{ $mov->asignacion_familiar ? 1 : 0 }}"
                                        data-mov-fp="{{ $mov->fondoPension->fondo_pension ?? 'N/A' }}"
                                        data-mov-fp-id="{{ $mov->fondo_pensiones_id ?? '' }}"
                                        data-mov-condicion="{{ $mov->condicion->nombre_condicion ?? 'N/A' }}"
                                        data-mov-condicion-id="{{ $mov->condicion_id ?? '' }}"
                                        data-mov-banco="{{ $mov->banco->nombre_banco ?? 'N/A' }}"
                                        data-mov-banco-id="{{ $mov->banco_id ?? '' }}"
                                        data-mov-centro-costo="{{ $mov->centroCosto->nombre_centro_costo ?? 'N/A' }}"
                                        data-mov-centro-costo-id="{{ $mov->centro_costo_id ?? '' }}"
                                        data-mov-familia="{{ $mov->familia->nombre_familia ?? 'N/A' }}"
                                        data-mov-familia-id="{{ $mov->familia_id ?? '' }}"
                                        data-mov-moneda="{{ $mov->moneda->nombre_moneda ?? 'N/A' }}"
                                        data-mov-moneda-id="{{ $mov->moneda_id ?? '' }}"
                                        data-mov-numero-cuenta="{{ $mov->numero_cuenta ?? '' }}"
                                        data-mov-codigo-interbancario="{{ $mov->codigo_interbancario ?? '' }}"
                                        data-mov-suspension-renta-raw="{{ $mov->suspension_renta ? 1 : 0 }}"
                                        data-mov-suspension-renta="{{ $mov->suspension_renta ? 'Sí' : 'No' }}"
                                        data-mov-estado="{{ $estadoMov }}"
                                        data-mov-estado-raw="{{ $mov->estado ? 1 : 0 }}"
                                        data-contrato-estado="{{ $estadoTexto }}"
                                        data-mov-fecha-registro="{{ $mov->created_at ? $mov->created_at->format('d/m/Y H:i') : '-' }}"
                                        data-contrato-inicio="{{ \Carbon\Carbon::parse($contrato->inicio_contrato)->addDay()->format('Y-m-d') }}"
                                        data-contrato-fin="{{ $contrato->fin_contrato ? \Carbon\Carbon::parse($contrato->fin_contrato)->format('Y-m-d') : '' }}">
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $mov->tipo_movimiento ?? '-' }}</td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $inicioMov }}</td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300 font-mono">{{ $puedeVerSalario ? 'S/ ' . number_format($mov->haber_basico, 2) : 'S/ ••••••' }}</td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $mov->cargo->nombre_cargo ?? 'N/A' }}</td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $mov->planilla->nombre_planilla ?? 'N/A' }}</td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $mov->fondoPension->fondo_pension ?? 'N/A' }}</td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $mov->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td class="py-2 px-3 text-center">
                                            <div class="flex justify-center gap-2">
                                                <x-ui.action-button type="view" class="btn-view-movimiento" />
                                                @can('contratos.edit')
                                                    <x-ui.action-button type="edit" class="btn-edit-movimiento" />
                                                    <x-ui.action-button type="delete" class="btn-delete-movimiento" />
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @else
                            <p class="text-center text-gray-500 dark:text-gray-400 py-4">No hay movimientos registrados para este contrato.</p>
                            @endif
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

    <!-- Inclusion de Modales -->
    @include('contratos.partials.modals.evaluar-contrato')
    @include('contratos.partials.modals.historial-previa')
    @include('contratos.partials.modals.crear-contrato')
    @include('contratos.partials.modals.edit')
    @include('contratos.partials.modals.view')
    @include('contratos.partials.modals.view-movimiento')
    @include('contratos.partials.modals.edit-movimiento')
    @include('contratos.partials.modals.add-movimiento')
    @include('contratos.partials.modals.baja-contrato')

    @include('contratos.partials.scripts')

</x-app-layout>
