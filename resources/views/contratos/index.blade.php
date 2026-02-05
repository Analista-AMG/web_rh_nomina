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
                        $salario = 'S/ ' . number_format($contrato->haber_basico, 2);
                    @endphp
                    
                    <!-- Celdas de la fila principal -->
                    <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-left rounded-l-xl border-y border-l border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold">
                                {{ mb_substr($contrato->persona->apellido_paterno ?? '?', 0, 1, 'UTF-8') }}{{ mb_substr($contrato->persona->nombres ?? '?', 0, 1, 'UTF-8') }}
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-800 dark:text-white leading-tight">
                                    {{ $contrato->persona->apellido_paterno ?? '' }} {{ $contrato->persona->nombres ?? 'Sin Asignar' }}
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
                                data-colaborador="{{ ($contrato->persona->apellido_paterno ?? '') . ' ' . ($contrato->persona->apellido_materno ?? '') . ', ' . ($contrato->persona->nombres ?? 'Sin Asignar') }}"
                                data-documento="{{ ($contrato->persona->tipo_documento ?? 'DOC') . ': ' . ($contrato->persona->numero_documento ?? '---') }}"
                                data-cargo="{{ $contrato->cargo->nombre_cargo ?? 'Sin Cargo' }}"
                                data-planilla="{{ $contrato->planilla->nombre_planilla ?? 'N/A' }}"
                                data-fp="{{ $contrato->fondoPensiones->fondo_pension ?? 'N/A' }}"
                                data-condicion="{{ $contrato->condicion->nombre_condicion ?? 'N/A' }}"
                                data-banco="{{ $contrato->banco->nombre_banco ?? 'N/A' }}"
                                data-moneda="{{ $contrato->moneda->nombre_moneda ?? 'N/A' }}"
                                data-centro-costo="{{ $contrato->centroCosto->nombre_centro_costo ?? 'N/A' }}"
                                data-familia="{{ $contrato->familia->nombre_familia ?? 'N/A' }}" 
                                data-inicio="{{ $inicio }}"
                                data-fin="{{ $fin }}"
                                data-fecha-renuncia="{{ $contrato->fecha_renuncia ? \Carbon\Carbon::parse($contrato->fecha_renuncia)->format('d/m/Y') : 'No registrada' }}"
                                data-haber="{{ number_format($contrato->haber_basico, 2) }}"
                                data-asignacion="{{ $contrato->asignacion_familiar ? 'Sí' : 'No' }}"
                                data-movilidad="{{ number_format($contrato->movilidad ?? 0, 2) }}"
                                data-numero-cuenta="{{ $contrato->numero_cuenta ?? 'N/A' }}"
                                data-codigo-interbancario="{{ $contrato->codigo_interbancario ?? 'N/A' }}"
                                data-numero-cuenta-cts="{{ $contrato->numero_cuenta_cts ?? 'N/A' }}"
                                data-codigo-interbancario-cts="{{ $contrato->codigo_interbancario_cts ?? 'N/A' }}"
                                data-periodo-prueba="{{ $contrato->periodo_prueba ? 'Sí' : 'No' }}"
                                data-estado="{{ $estadoTexto }}" />

                            {{-- Botón Añadir Movimiento --}}
                            @can('contratos.create')
                            @php
                                $ultimoMov = $contrato->movimientos->sortByDesc('inicio')->first();
                                $lastMovJson = $ultimoMov ? json_encode([
                                    'cargo_id' => $ultimoMov->id_cargo,
                                    'planilla_id' => $ultimoMov->id_planilla,
                                    'fp_id' => $ultimoMov->id_fp,
                                    'condicion_id' => $ultimoMov->id_condicion,
                                    'banco_id' => $ultimoMov->id_banco,
                                    'centro_costo_id' => $ultimoMov->id_centro_costo,
                                    'familia_id' => $ultimoMov->id_familia,
                                    'moneda_id' => $ultimoMov->id_moneda,
                                    'haber' => $ultimoMov->haber_basico,
                                    'movilidad' => $ultimoMov->movilidad ?? 0,
                                    'asignacion' => $ultimoMov->asignacion_familiar ? 1 : 0,
                                    'inicio' => $ultimoMov->inicio ? $ultimoMov->inicio->format('Y-m-d') : '',
                                    'fin' => $ultimoMov->fin ? $ultimoMov->fin->format('Y-m-d') : '',
                                ]) : '{}';
                            @endphp
                            <x-ui.action-button type="add" title="Añadir Movimiento" class="btn-add-movimiento-main"
                                data-contrato-id="{{ $contrato->id_contrato }}"
                                data-last-mov="{{ $lastMovJson }}"
                                data-contrato-inicio="{{ \Carbon\Carbon::parse($contrato->inicio_contrato)->addDay()->format('Y-m-d') }}"
                                data-contrato-fin="{{ $contrato->fin_contrato ? \Carbon\Carbon::parse($contrato->fin_contrato)->format('Y-m-d') : '' }}" />
                            @endcan
                            
                            {{-- Botón Baja --}}
                            @can('contratos.baja')
                            @php
                                $bajaData = $contrato->baja ? json_encode([
                                    'id_baja' => $contrato->baja->id_baja,
                                    'fecha_baja' => $contrato->baja->fecha_baja ? $contrato->baja->fecha_baja->format('Y-m-d') : '',
                                    'motivo_baja' => $contrato->baja->motivo_baja ?? '',
                                    'aviso_con_15_dias' => $contrato->baja->aviso_con_15_dias ? '1' : '0',
                                    'recomienda_reingreso' => $contrato->baja->recomienda_reingreso ? '1' : '0',
                                    'observacion' => $contrato->baja->observacion ?? '',
                                ]) : '{}';
                            @endphp
                            <x-ui.action-button type="baja" class="btn-baja-contrato"
                                data-contrato-id="{{ $contrato->id_contrato }}"
                                data-colaborador-nombre="{{ ($contrato->persona->apellido_paterno ?? '') . ' ' . ($contrato->persona->apellido_materno ?? '') . ', ' . ($contrato->persona->nombres ?? '') }}"
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
                                        $inicioMov = $mov->inicio ? \Carbon\Carbon::parse($mov->inicio)->format('d/m/Y') : '-';
                                        $finMov = $mov->fin ? \Carbon\Carbon::parse($mov->fin)->format('d/m/Y') : 'Indefinido';
                                        $estadoMov = $mov->estado ? 'Activo' : 'Inactivo';
                                        $asignacionMov = $mov->asignacion_familiar ? 'Sí' : 'No';
                                    @endphp
                                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-700/50"
                                        data-mov-id="{{ $mov->id_movimiento }}"
                                        data-mov-tipo="{{ $mov->tipo_movimiento ?? '' }}"
                                        data-mov-cargo="{{ $mov->cargo->nombre_cargo ?? 'N/A' }}"
                                        data-mov-cargo-id="{{ $mov->id_cargo ?? '' }}"
                                        data-mov-planilla="{{ $mov->planilla->nombre_planilla ?? 'N/A' }}"
                                        data-mov-planilla-id="{{ $mov->id_planilla ?? '' }}"
                                        data-mov-inicio="{{ $inicioMov }}"
                                        data-mov-inicio-raw="{{ $mov->inicio ? \Carbon\Carbon::parse($mov->inicio)->format('Y-m-d') : '' }}"
                                        data-mov-fin="{{ $finMov }}"
                                        data-mov-fin-raw="{{ $mov->fin ? \Carbon\Carbon::parse($mov->fin)->format('Y-m-d') : '' }}"
                                        data-mov-haber="{{ number_format($mov->haber_basico, 2) }}"
                                        data-mov-haber-raw="{{ $mov->haber_basico }}"
                                        data-mov-asignacion="{{ $asignacionMov }}"
                                        data-mov-asignacion-raw="{{ $mov->asignacion_familiar ? 1 : 0 }}"
                                        data-mov-movilidad="{{ number_format($mov->movilidad ?? 0, 2) }}"
                                        data-mov-movilidad-raw="{{ $mov->movilidad ?? 0 }}"
                                        data-mov-fp="{{ $mov->fondoPensiones->fondo_pension ?? 'N/A' }}"
                                        data-mov-fp-id="{{ $mov->id_fp ?? '' }}"
                                        data-mov-condicion="{{ $mov->condicion->nombre_condicion ?? 'N/A' }}"
                                        data-mov-condicion-id="{{ $mov->id_condicion ?? '' }}"
                                        data-mov-banco="{{ $mov->banco->nombre_banco ?? 'N/A' }}"
                                        data-mov-banco-id="{{ $mov->id_banco ?? '' }}"
                                        data-mov-centro-costo="{{ $mov->centroCosto->nombre_centro_costo ?? 'N/A' }}"
                                        data-mov-centro-costo-id="{{ $mov->id_centro_costo ?? '' }}"
                                        data-mov-familia="{{ $mov->familia->nombre_familia ?? 'N/A' }}"
                                        data-mov-familia-id="{{ $mov->id_familia ?? '' }}"
                                        data-mov-moneda="{{ $mov->moneda->nombre_moneda ?? 'N/A' }}"
                                        data-mov-moneda-id="{{ $mov->id_moneda ?? '' }}"
                                        data-mov-estado="{{ $estadoMov }}"
                                        data-mov-estado-raw="{{ $mov->estado ? 1 : 0 }}"
                                        data-mov-fecha-registro="{{ $mov->fecha_insercion ? \Carbon\Carbon::parse($mov->fecha_insercion)->format('d/m/Y H:i') : '-' }}"
                                        data-contrato-inicio="{{ \Carbon\Carbon::parse($contrato->inicio_contrato)->addDay()->format('Y-m-d') }}"
                                        data-contrato-fin="{{ $contrato->fin_contrato ? \Carbon\Carbon::parse($contrato->fin_contrato)->format('Y-m-d') : '' }}">
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $mov->tipo_movimiento ?? '-' }}</td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $inicioMov }}</td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300 font-mono">S/ {{ number_format($mov->haber_basico, 2) }}</td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $mov->cargo->nombre_cargo ?? 'N/A' }}</td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $mov->planilla->nombre_planilla ?? 'N/A' }}</td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $mov->fondoPensiones->fondo_pension ?? 'N/A' }}</td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $mov->fecha_insercion ? \Carbon\Carbon::parse($mov->fecha_insercion)->format('d/m/Y H:i') : '-' }}</td>
                                        <td class="py-2 px-3 text-center">
                                            <div class="flex justify-center gap-2">
                                                <x-ui.action-button type="view" class="btn-view-movimiento" />
                                                <x-ui.action-button type="edit" class="btn-edit-movimiento" />
                                                <x-ui.action-button type="delete" class="btn-delete-movimiento" />
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
    @include('contratos.partials.modals.create')
    @include('contratos.partials.modals.edit')
    @include('contratos.partials.modals.view')
    @include('contratos.partials.modals.view-movimiento')
    @include('contratos.partials.modals.edit-movimiento')
    @include('contratos.partials.modals.add-movimiento')
    @include('contratos.partials.modals.baja-contrato')

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const expandableRows = document.querySelectorAll('.expandable-row');

            expandableRows.forEach(row => {
                row.addEventListener('click', function (event) {
                    // Solo expandir si el clic no fue en un botón dentro de la fila
                    if (event.target.closest('button, a, form')) {
                        return;
                    }
                    
                    const subRow = this.nextElementSibling;
                    const icon = this.querySelector('.fa-chevron-down');

                    if (subRow && subRow.classList.contains('sub-row')) {
                        if (subRow.style.display === 'none' || subRow.style.display === '') {
                            subRow.style.display = 'table-row';
                            icon.classList.add('rotate-180');
                        } else {
                            subRow.style.display = 'none';
                            icon.classList.remove('rotate-180');
                        }
                    }
                });

                // Detener la propagación del clic en los botones para que no colapsen la fila
                const actionButtons = row.querySelectorAll('.btn-view-contrato, .btn-add-movimiento-main, .btn-baja-contrato');
                actionButtons.forEach(button => {
                    button.addEventListener('click', function (event) {
                        event.stopPropagation();

                        // Abrir modal Ver Contrato
                        if (this.classList.contains('btn-view-contrato')) {
                            const d = this.dataset;
                            document.getElementById('view-colaborador').value = d.colaborador || '';
                            document.getElementById('view-documento').value = d.documento || '';
                            document.getElementById('view-cargo').value = d.cargo || '';
                            document.getElementById('view-planilla').value = d.planilla || '';
                            document.getElementById('view-inicio').value = d.inicio || '';
                            document.getElementById('view-fin').value = d.fin || '';
                            document.getElementById('view-fecha-renuncia').value = d.fechaRenuncia || '';
                            document.getElementById('view-estado').value = d.estado || '';
                            document.getElementById('view-haber').value = 'S/ ' + (d.haber || '0.00');
                            document.getElementById('view-asignacion').value = d.asignacion || '';
                            document.getElementById('view-movilidad').value = 'S/ ' + (d.movilidad || '0.00');
                            document.getElementById('view-fp').value = d.fp || '';
                            document.getElementById('view-condicion').value = d.condicion || '';
                            document.getElementById('view-banco').value = d.banco || '';
                            document.getElementById('view-numero-cuenta').value = d.numeroCuenta || '';
                            document.getElementById('view-codigo-interbancario').value = d.codigoInterbancario || '';
                            document.getElementById('view-numero-cuenta-cts').value = d.numeroCuentaCts || '';
                            document.getElementById('view-codigo-interbancario-cts').value = d.codigoInterbancarioCts || '';
                            document.getElementById('view-moneda').value = d.moneda || '';
                            document.getElementById('view-centro-costo').value = d.centroCosto || '';
                            document.getElementById('view-familia').value = d.familia || '';
                            document.getElementById('view-periodo-prueba').value = d.periodoPrueba || '';
                            openModal('view-modal');
                        }

                        // Abrir modal Añadir Movimiento
                        if (this.classList.contains('btn-add-movimiento-main')) {
                            const contratoId = this.dataset.contratoId;
                            document.getElementById('form-add-movimiento').reset();
                            document.getElementById('add-mov-contrato-id').value = contratoId;
                            document.getElementById('add-mov-tipo').value = 'Movimiento Regular';

                            // Delimitar fechas: mínimo = inicio_contrato + 1 día, máximo = fin_contrato
                            const minFecha = this.dataset.contratoInicio || '';
                            const maxFecha = this.dataset.contratoFin || '';
                            const inputInicio = document.getElementById('add-mov-inicio');
                            const inputFin = document.getElementById('add-mov-fin');

                            inputInicio.min = minFecha;
                            inputInicio.max = maxFecha;
                            inputFin.min = minFecha;
                            inputFin.max = maxFecha;

                            // Pre-llenar con datos del último movimiento (JSON en data-last-mov)
                            try {
                                const lastMov = JSON.parse(this.dataset.lastMov || '{}');
                                if (lastMov.haber !== undefined) {
                                    document.getElementById('add-mov-haber').value = parseFloat(lastMov.haber || 0).toFixed(2);
                                    document.getElementById('add-mov-movilidad').value = parseFloat(lastMov.movilidad || 0).toFixed(2);
                                    document.getElementById('add-mov-asignacion').value = lastMov.asignacion || '0';
                                    document.getElementById('add-mov-inicio').value = '';
                                    document.getElementById('add-mov-fin').value = '';

                                    setTimeout(() => {
                                        document.getElementById('add-mov-cargo-id').value = lastMov.cargo_id || '';
                                        document.getElementById('add-mov-planilla-id').value = lastMov.planilla_id || '';
                                        document.getElementById('add-mov-fp-id').value = lastMov.fp_id || '';
                                        document.getElementById('add-mov-condicion-id').value = lastMov.condicion_id || '';
                                        document.getElementById('add-mov-banco-id').value = lastMov.banco_id || '';
                                        document.getElementById('add-mov-centro-costo-id').value = lastMov.centro_costo_id || '';
                                        document.getElementById('add-mov-familia-id').value = lastMov.familia_id || '';
                                        document.getElementById('add-mov-moneda-id').value = lastMov.moneda_id || '';
                                    }, 200);
                                }
                            } catch (e) {
                                console.error('Error parseando datos del último movimiento:', e);
                            }

                            openModal('add-movimiento-modal');
                        }

                        // Abrir modal Dar de Baja
                        if (this.classList.contains('btn-baja-contrato')) {
                            const contratoId = this.dataset.contratoId;

                            // Parsear datos de baja existente
                            let bajaData = {};
                            try {
                                bajaData = JSON.parse(this.dataset.baja || '{}');
                            } catch (e) {
                                console.error('Error parseando datos de baja:', e);
                            }

                            const esActualizacion = bajaData.id_baja !== undefined;

                            document.getElementById('form-baja-contrato').reset();
                            document.getElementById('baja-contrato-id').value = contratoId;
                            document.getElementById('baja-id').value = bajaData.id_baja || '';
                            document.getElementById('baja-colaborador-nombre').textContent = this.dataset.colaboradorNombre || '';
                            document.getElementById('baja-colaborador-doc').textContent = this.dataset.colaboradorDoc || '';

                            // Delimitar fecha: min = inicio_contrato, max = fin_contrato
                            const inputBajaFecha = document.getElementById('baja-fecha');
                            inputBajaFecha.min = this.dataset.contratoInicio || '';
                            inputBajaFecha.max = this.dataset.contratoFin || '';

                            // Adaptar modal según si es nueva baja o actualización
                            const titulo = document.getElementById('baja-modal-titulo');
                            const btnConfirmar = document.getElementById('btn-confirmar-baja');
                            const advertencia = document.getElementById('baja-advertencia-texto');

                            // Mostrar/ocultar botón eliminar
                            const eliminarContainer = document.getElementById('baja-eliminar-container');
                            const spacer = document.getElementById('baja-spacer');

                            if (esActualizacion) {
                                // Pre-llenar campos con datos existentes
                                inputBajaFecha.value = bajaData.fecha_baja || '';
                                document.getElementById('baja-motivo').value = bajaData.motivo_baja || '';
                                document.getElementById('baja-aviso-15-dias').value = bajaData.aviso_con_15_dias || '0';
                                document.getElementById('baja-recomienda-reingreso').value = bajaData.recomienda_reingreso || '1';
                                document.getElementById('baja-observacion').value = bajaData.observacion || '';

                                titulo.textContent = 'Actualizar Baja';
                                btnConfirmar.textContent = 'Actualizar Baja';
                                advertencia.innerHTML = 'Este contrato ya tiene una baja registrada. Puede modificar los datos o eliminarla.';

                                eliminarContainer.classList.remove('hidden');
                                spacer.classList.add('hidden');
                            } else {
                                titulo.textContent = 'Dar de Baja';
                                btnConfirmar.textContent = 'Confirmar Baja';
                                advertencia.innerHTML = 'Esta acción registrará la baja del colaborador. El contrato pasará a estado <strong>Finalizado</strong> una vez cumplida la fecha.';

                                eliminarContainer.classList.add('hidden');
                                spacer.classList.remove('hidden');
                            }

                            openModal('baja-contrato-modal');
                        }
                    });
                });

                // Detener la propagación para los botones de la cabecera de la sub-fila
                const subRowHeaderDiv = row.nextElementSibling.querySelector('.flex.justify-end.items-center.mb-3');
                if (subRowHeaderDiv) {
                    const subRowButtons = subRowHeaderDiv.querySelectorAll('.btn-view, .btn-edit, .btn-delete'); // Seleccionar por las clases de botón
                    subRowButtons.forEach(button => {
                        button.addEventListener('click', function (event) {
                            event.stopPropagation();
                        });
                    });
                }
                
                // Detener la propagación para los botones de movimiento
                const subRowMovimientoButtons = row.nextElementSibling.querySelectorAll('.btn-view-movimiento, .btn-edit-movimiento, .btn-delete-movimiento');
                subRowMovimientoButtons.forEach(button => {
                    button.addEventListener('click', function(event) {
                        event.stopPropagation();

                        // Manejar botón Ver Movimiento
                        if (this.classList.contains('btn-view-movimiento')) {
                            const tr = this.closest('tr');
                            const data = tr.dataset;

                            document.getElementById('view-mov-tipo').value = data.movTipo || '-';
                            document.getElementById('view-mov-cargo').value = data.movCargo || 'N/A';
                            document.getElementById('view-mov-planilla').value = data.movPlanilla || 'N/A';
                            document.getElementById('view-mov-inicio').value = data.movInicio || '-';
                            document.getElementById('view-mov-fin').value = data.movFin || 'Indefinido';
                            document.getElementById('view-mov-haber').value = 'S/ ' + (data.movHaber || '0.00');
                            document.getElementById('view-mov-asignacion').value = data.movAsignacion || 'No';
                            document.getElementById('view-mov-movilidad').value = 'S/ ' + (data.movMovilidad || '0.00');
                            document.getElementById('view-mov-fp').value = data.movFp || 'N/A';
                            document.getElementById('view-mov-condicion').value = data.movCondicion || 'N/A';
                            document.getElementById('view-mov-banco').value = data.movBanco || 'N/A';
                            document.getElementById('view-mov-centro-costo').value = data.movCentroCosto || 'N/A';
                            document.getElementById('view-mov-familia').value = data.movFamilia || 'N/A';
                            document.getElementById('view-mov-moneda').value = data.movMoneda || 'N/A';
                            document.getElementById('view-mov-estado').value = data.movEstado || 'N/A';
                            document.getElementById('view-mov-fecha-registro').value = data.movFechaRegistro || '-';

                            openModal('view-movimiento-modal');
                        }

                        // Manejar botón Eliminar Movimiento
                        if (this.classList.contains('btn-delete-movimiento')) {
                            const tr = this.closest('tr');
                            const data = tr.dataset;
                            const tipo = data.movTipo || '';
                            const esRegular = tipo === 'Movimiento Regular';

                            const mensaje = esRegular
                                ? '¿Estás seguro de eliminar este movimiento?'
                                : '¿Estás seguro? Esto eliminará el CONTRATO COMPLETO y todos sus movimientos.';

                            if (!confirm(mensaje)) return;

                            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                            fetch(`/contratos/movimientos/${data.movId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                }
                            })
                            .then(res => res.json())
                            .then(result => {
                                if (result.success) {
                                    alert(result.message);
                                    location.reload();
                                } else {
                                    alert(result.error || 'Error al eliminar');
                                }
                            })
                            .catch(() => alert('Error de conexión'));
                        }

                        // Manejar botón Editar Movimiento
                        if (this.classList.contains('btn-edit-movimiento')) {
                            const tr = this.closest('tr');
                            const data = tr.dataset;

                            // Delimitar fechas: min = inicio_contrato + 1, max = fin_contrato
                            const minFecha = data.contratoInicio || '';
                            const maxFecha = data.contratoFin || '';
                            const editInicio = document.getElementById('edit-mov-inicio');
                            const editFin = document.getElementById('edit-mov-fin');
                            editInicio.min = minFecha;
                            editInicio.max = maxFecha;
                            editFin.min = minFecha;
                            editFin.max = maxFecha;

                            // Llenar campos simples inmediatamente
                            document.getElementById('edit-mov-id').value = data.movId || '';
                            document.getElementById('edit-mov-tipo').value = data.movTipo || '';
                            editInicio.value = data.movInicioRaw || '';
                            editFin.value = data.movFinRaw || '';
                            document.getElementById('edit-mov-haber').value = parseFloat(data.movHaberRaw || 0).toFixed(2);
                            document.getElementById('edit-mov-movilidad').value = parseFloat(data.movMovilidadRaw || 0).toFixed(2);
                            document.getElementById('edit-mov-asignacion').value = data.movAsignacionRaw || '0';

                            // Abrir el modal primero
                            openModal('edit-movimiento-modal');

                            // Llenar selects después de abrir el modal (con un pequeño delay)
                            setTimeout(() => {
                                const cargoSelect = document.getElementById('edit-mov-cargo-id');
                                const planillaSelect = document.getElementById('edit-mov-planilla-id');

                                console.log('Opciones en select cargo:', cargoSelect.options.length);
                                console.log('Opciones en select planilla:', planillaSelect.options.length);

                                document.getElementById('edit-mov-cargo-id').value = data.movCargoId || '';
                                document.getElementById('edit-mov-planilla-id').value = data.movPlanillaId || '';
                                document.getElementById('edit-mov-fp-id').value = data.movFpId || '';
                                document.getElementById('edit-mov-condicion-id').value = data.movCondicionId || '';
                                document.getElementById('edit-mov-banco-id').value = data.movBancoId || '';
                                document.getElementById('edit-mov-centro-costo-id').value = data.movCentroCostoId || '';
                                document.getElementById('edit-mov-familia-id').value = data.movFamiliaId || '';
                                document.getElementById('edit-mov-moneda-id').value = data.movMonedaId || '';

                                console.log('Después de asignar - Cargo value:', document.getElementById('edit-mov-cargo-id').value);
                                console.log('Después de asignar - Planilla value:', document.getElementById('edit-mov-planilla-id').value);
                            }, 200);
                        }
                    });
                });

            });
        });
    </script>
    @endpush

    <!-- Lógica JS -->
    @include('contratos.partials.scripts')

</x-app-layout>
