<x-app-layout>
    @section('title', 'Cálculos - AMG International')

    <header class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Cálculos de Remuneraciones</h1>
    </header>

    <!-- Filtros y Acciones -->
    <div class="mb-6 bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
        <div class="flex flex-wrap items-end gap-4">
            <div class="min-w-[180px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Periodo</label>
                <select id="periodo" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <option value="">Seleccione</option>
                    @foreach($periodos as $periodo)
                        <option value="{{ $periodo }}">{{ $periodo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[180px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Planilla</label>
                <select id="id_planilla" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <option value="">Todas</option>
                    @foreach($planillas as $planilla)
                        <option value="{{ $planilla->id_planilla }}">{{ $planilla->nombre_planilla }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                @can('calculos.execute')
                <button type="button" id="btn-ejecutar" class="px-5 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer" disabled>
                    <i class="fa-solid fa-play mr-1"></i> Procesar Nómina
                </button>
                @endcan
                <button type="button" id="btn-consultar" class="px-5 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer" disabled>
                    <i class="fa-solid fa-search mr-1"></i> Consultar Resultados
                </button>
            </div>
        </div>
    </div>

    <!-- Estado del proceso -->
    <div id="status-container" class="mb-4 hidden">
        <div id="status-box" class="rounded-xl p-4 shadow-sm border flex items-center gap-3">
            <div id="status-icon"></div>
            <div>
                <p id="status-text" class="text-sm font-medium"></p>
                <p id="status-detail" class="text-xs text-gray-500 dark:text-gray-400"></p>
            </div>
        </div>
    </div>

    <!-- Tabla de Resultados -->
    <div id="resultados-container" class="hidden">
        <div class="mb-3 flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
            <span id="info-count"><i class="fa-solid fa-users mr-1"></i></span>
        </div>
        <div class="overflow-x-auto bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-[#1e2836]" id="resultados-thead">
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700" id="resultados-tbody">
                </tbody>
            </table>
        </div>
    </div>

    <!-- Estado vacío -->
    <div id="empty-state" class="bg-white dark:bg-[#273142] rounded-xl p-12 text-center shadow-sm border border-light-border dark:border-dark-border">
        <i class="fa-solid fa-calculator text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <p class="text-gray-500 dark:text-gray-400">Seleccione un periodo y consulte o procese la nómina.</p>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const periodo = document.getElementById('periodo');
            const planilla = document.getElementById('id_planilla');
            const btnEjecutar = document.getElementById('btn-ejecutar');
            const btnConsultar = document.getElementById('btn-consultar');
            const statusContainer = document.getElementById('status-container');
            const statusBox = document.getElementById('status-box');
            const statusIcon = document.getElementById('status-icon');
            const statusText = document.getElementById('status-text');
            const statusDetail = document.getElementById('status-detail');
            const resultadosContainer = document.getElementById('resultados-container');
            const resultadosThead = document.getElementById('resultados-thead');
            const resultadosTbody = document.getElementById('resultados-tbody');
            const infoCount = document.getElementById('info-count');
            const emptyState = document.getElementById('empty-state');

            function updateButtons() {
                const hasPeriodo = periodo.value !== '';
                if (btnEjecutar) btnEjecutar.disabled = !hasPeriodo;
                btnConsultar.disabled = !hasPeriodo;
            }

            periodo.addEventListener('change', updateButtons);
            updateButtons();

            function showStatus(type, text, detail) {
                statusContainer.classList.remove('hidden');
                statusBox.className = 'rounded-xl p-4 shadow-sm border flex items-center gap-3 ';

                if (type === 'loading') {
                    statusBox.classList.add('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-200', 'dark:border-blue-800');
                    statusIcon.innerHTML = '<div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>';
                    statusText.className = 'text-sm font-medium text-blue-700 dark:text-blue-300';
                } else if (type === 'success') {
                    statusBox.classList.add('bg-green-50', 'dark:bg-green-900/20', 'border-green-200', 'dark:border-green-800');
                    statusIcon.innerHTML = '<i class="fa-solid fa-circle-check text-xl text-green-600"></i>';
                    statusText.className = 'text-sm font-medium text-green-700 dark:text-green-300';
                } else if (type === 'error') {
                    statusBox.classList.add('bg-red-50', 'dark:bg-red-900/20', 'border-red-200', 'dark:border-red-800');
                    statusIcon.innerHTML = '<i class="fa-solid fa-circle-xmark text-xl text-red-600"></i>';
                    statusText.className = 'text-sm font-medium text-red-700 dark:text-red-300';
                }

                statusText.textContent = text;
                statusDetail.textContent = detail || '';
            }

            // Columnas monetarias para formatear
            const columnasMonetarias = [
                'haber_basico', 'asig_familiar', 'movilidad', 'comision', 'bono',
                'total_haberes', 'base_imponible', 'aporte', 'prima', 'comision_afp',
                'essalud', 'total_descuentos', 'neto'
            ];

            const labelsColumnas = {
                'id_contrato': 'Contrato',
                'colaborador': 'Colaborador',
                'numero_documento': 'Documento',
                'planilla': 'Planilla',
                'tipo_calculo': 'Modo',
                'dias_calculo': 'D. Cálculo',
                'dias_trabajados': 'D. Trabajados',
                'haber_basico': 'HBR',
                'asig_familiar': 'Asig. Fam.',
                'movilidad': 'Movilidad',
                'comision': 'Comisión',
                'bono': 'Bono',
                'total_haberes': 'Total Hab.',
                'base_imponible': 'Base Imp.',
                'aporte': 'Aporte',
                'prima': 'Prima',
                'comision_afp': 'Com. AFP',
                'essalud': 'EsSalud',
                'total_descuentos': 'Total Desc.',
                'neto': 'Neto'
            };

            function renderResultados(data) {
                resultadosThead.innerHTML = '';
                resultadosTbody.innerHTML = '';

                if (!data || !Array.isArray(data) || data.length === 0) {
                    emptyState.classList.remove('hidden');
                    resultadosContainer.classList.add('hidden');
                    emptyState.querySelector('p').textContent = 'No se encontraron resultados para este periodo.';
                    return;
                }

                emptyState.classList.add('hidden');
                resultadosContainer.classList.remove('hidden');
                infoCount.innerHTML = '<i class="fa-solid fa-users mr-1"></i> ' + data.length + ' registros';

                const keys = Object.keys(data[0]);
                const headerRow = document.createElement('tr');
                keys.forEach(key => {
                    const th = document.createElement('th');
                    th.className = 'px-3 py-3 text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 whitespace-nowrap';
                    if (key === 'colaborador') {
                        th.className = 'sticky left-0 z-10 bg-gray-50 dark:bg-[#1e2836] px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 min-w-[220px]';
                    }
                    th.textContent = labelsColumnas[key] || key.replace(/_/g, ' ');
                    headerRow.appendChild(th);
                });
                resultadosThead.appendChild(headerRow);

                data.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-gray-50 dark:hover:bg-[#323d4d] transition-colors';
                    keys.forEach(key => {
                        const td = document.createElement('td');
                        td.className = 'px-3 py-2 text-sm text-gray-700 dark:text-gray-300 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap';
                        const val = row[key];

                        if (key === 'colaborador') {
                            td.className = 'sticky left-0 z-10 bg-white dark:bg-[#273142] px-4 py-2 text-sm font-medium text-gray-800 dark:text-white border-r border-gray-200 dark:border-gray-700 whitespace-nowrap';
                            td.textContent = val ?? '-';
                        } else if (key === 'tipo_calculo') {
                            const badge = document.createElement('span');
                            badge.className = val === 'REGULAR'
                                ? 'px-2 py-0.5 rounded-full text-[11px] font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                : 'px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                            badge.textContent = val;
                            td.classList.add('text-center');
                            td.appendChild(badge);
                        } else if (columnasMonetarias.includes(key)) {
                            td.textContent = val !== null ? parseFloat(val).toFixed(2) : '0.00';
                            td.classList.add('text-right', 'font-mono', 'text-xs');
                            if (key === 'neto') td.classList.add('font-bold', 'text-green-600', 'dark:text-green-400');
                            if (key === 'total_descuentos') td.classList.add('text-red-600', 'dark:text-red-400');
                        } else if (typeof val === 'number') {
                            td.textContent = val;
                            td.classList.add('text-center');
                        } else {
                            td.textContent = val ?? '-';
                        }
                        tr.appendChild(td);
                    });
                    resultadosTbody.appendChild(tr);
                });
            }

            // Ejecutar nómina
            if (btnEjecutar) {
                btnEjecutar.addEventListener('click', function() {
                    if (!periodo.value) return;
                    if (!confirm('¿Desea procesar la nómina del periodo ' + periodo.value + '? Esto puede tomar unos momentos.')) return;

                    btnEjecutar.disabled = true;
                    btnConsultar.disabled = true;
                    showStatus('loading', 'Procesando nómina...', 'Esto puede tomar varios segundos dependiendo de la cantidad de contratos.');

                    fetch('{{ route("calculos.ejecutar") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ periodo: periodo.value })
                    })
                    .then(response => response.json())
                    .then(data => {
                        updateButtons();
                        if (data.success) {
                            const info = data.data;
                            const modo = info.modo || '';
                            const contratos = info.contratos_procesados || 0;
                            showStatus('success',
                                'Nómina procesada correctamente',
                                contratos + ' contratos procesados | Modo: ' + modo + (info.mensaje ? ' | ' + info.mensaje : '')
                            );
                            // Cargar resultados automáticamente
                            consultarResultados();
                        } else {
                            showStatus('error', 'Error al procesar', data.error || 'Error desconocido');
                        }
                    })
                    .catch(error => {
                        updateButtons();
                        showStatus('error', 'Error de conexión', 'No se pudo conectar con el servicio FastAPI.');
                        console.error('Error:', error);
                    });
                });
            }

            // Consultar resultados
            function consultarResultados() {
                if (!periodo.value) return;

                btnConsultar.disabled = true;
                if (btnEjecutar) btnEjecutar.disabled = true;

                const params = new URLSearchParams({ periodo: periodo.value });
                if (planilla.value) params.append('id_planilla', planilla.value);

                fetch('{{ route("calculos.resultados") }}?' + params.toString(), {
                    headers: { 'Accept': 'application/json' }
                })
                .then(response => response.json())
                .then(data => {
                    updateButtons();
                    if (data.success) {
                        if (!statusContainer.classList.contains('hidden') === false) {
                            showStatus('success', 'Resultados cargados', '');
                        }
                        renderResultados(data.data);
                    } else {
                        showStatus('error', 'Error al consultar', data.error || 'Error desconocido');
                    }
                })
                .catch(error => {
                    updateButtons();
                    showStatus('error', 'Error de conexión', 'No se pudo consultar los resultados.');
                    console.error('Error:', error);
                });
            }

            btnConsultar.addEventListener('click', function() {
                showStatus('loading', 'Consultando resultados...', '');
                consultarResultados();
            });
        });
    </script>
    @endpush
</x-app-layout>
