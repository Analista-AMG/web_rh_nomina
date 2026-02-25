<x-app-layout>
    @section('title', 'Cálculos - AMG International')

    <header class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Cálculos de Remuneraciones</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Procesa y consulta los resultados de nómina por periodo</p>
    </header>

    {{-- Filtros --}}
    <div class="mb-6 bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
        <div class="flex flex-wrap items-end gap-4">
            <div class="min-w-[180px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Periodo</label>
                <select id="periodo" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <option value="">Seleccione</option>
                    @foreach($periodos as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
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
            <div class="flex gap-2 flex-wrap">
                @can('calculos.execute')
                <button type="button" id="btn-ejecutar"
                    class="px-5 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer" disabled>
                    <i class="fa-solid fa-play mr-1"></i> Procesar Nómina
                </button>
                @endcan
                <button type="button" id="btn-consultar"
                    class="px-5 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer" disabled>
                    <i class="fa-solid fa-magnifying-glass mr-1"></i> Consultar
                </button>
                <button type="button" id="btn-exportar"
                    class="hidden px-5 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium cursor-pointer">
                    <i class="fa-solid fa-file-excel mr-1"></i> Descargar Excel
                </button>
            </div>
        </div>
    </div>

    {{-- Estado del proceso --}}
    <div id="status-container" class="mb-5 hidden">
        <div id="status-box" class="rounded-xl p-4 shadow-sm border flex items-center gap-3">
            <div id="status-icon"></div>
            <div>
                <p id="status-text" class="text-sm font-medium"></p>
                <p id="status-detail" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"></p>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div id="kpi-container" class="hidden mb-6">
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">

            <div class="bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-users text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Contratos</span>
                </div>
                <p class="text-2xl font-bold text-gray-800 dark:text-white" id="kpi-contratos">—</p>
                <p class="text-xs text-gray-400 mt-0.5">procesados</p>
            </div>

            <div class="bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-baby text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Asig. Fam.</span>
                </div>
                <p class="text-xl font-bold text-gray-800 dark:text-white font-mono" id="kpi-asig-fam">—</p>
                <p class="text-xs text-gray-400 mt-0.5">total periodo</p>
            </div>

            <div class="bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 rounded-lg bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-bus text-teal-600 dark:text-teal-400"></i>
                    </div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Movilidad</span>
                </div>
                <p class="text-xl font-bold text-gray-800 dark:text-white font-mono" id="kpi-movilidad">—</p>
                <p class="text-xs text-gray-400 mt-0.5">total periodo</p>
            </div>

            <div class="bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-piggy-bank text-orange-600 dark:text-orange-400"></i>
                    </div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">AFP</span>
                </div>
                <p class="text-xl font-bold text-gray-800 dark:text-white font-mono" id="kpi-afp">—</p>
                <p class="text-xs text-gray-400 mt-0.5">aporte + prima + com.</p>
            </div>

            <div class="bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-money-bill-transfer text-amber-600 dark:text-amber-400"></i>
                    </div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Adelanto</span>
                </div>
                <p class="text-xl font-bold text-gray-800 dark:text-white font-mono" id="kpi-adelanto">—</p>
                <p class="text-xs text-gray-400 mt-0.5">total periodo</p>
            </div>

            <div class="bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border-2 border-green-400 dark:border-green-600">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                        <i class="fa-solid fa-circle-dollar-to-slot text-green-600 dark:text-green-400"></i>
                    </div>
                    <span class="text-xs font-medium text-green-600 dark:text-green-400 uppercase tracking-wide font-semibold">Neto</span>
                </div>
                <p class="text-xl font-bold text-green-600 dark:text-green-400 font-mono" id="kpi-neto">—</p>
                <p class="text-xs text-green-400 mt-0.5">total a pagar</p>
            </div>

        </div>
    </div>

    {{-- Tabla de resultados --}}
    <div id="resultados-container" class="hidden">
        <div class="mb-3 flex items-center justify-between">
            <span id="info-count" class="text-sm text-gray-500 dark:text-gray-400">
                <i class="fa-solid fa-table mr-1"></i>
            </span>
        </div>
        <div class="overflow-x-auto bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-[#1e2836]" id="resultados-thead"></thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700" id="resultados-tbody"></tbody>
            </table>
        </div>
    </div>

    {{-- Estado vacío --}}
    <div id="empty-state" class="bg-white dark:bg-[#273142] rounded-xl p-12 text-center shadow-sm border border-light-border dark:border-dark-border">
        <i class="fa-solid fa-calculator text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <p class="text-gray-500 dark:text-gray-400">Selecciona un periodo y consulta o procesa la nómina.</p>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const elPeriodo    = document.getElementById('periodo');
        const elPlanilla   = document.getElementById('id_planilla');
        const btnEjecutar  = document.getElementById('btn-ejecutar');
        const btnConsultar = document.getElementById('btn-consultar');
        const btnExportar  = document.getElementById('btn-exportar');

        function updateButtons() {
            const ok = elPeriodo.value !== '';
            if (btnEjecutar) btnEjecutar.disabled = !ok;
            btnConsultar.disabled = !ok;
        }
        elPeriodo.addEventListener('change', updateButtons);
        updateButtons();

        // ── Exportar ────────────────────────────────────────────
        btnExportar.addEventListener('click', function () {
            const params = new URLSearchParams({ periodo: elPeriodo.value });
            if (elPlanilla.value) params.append('id_planilla', elPlanilla.value);
            window.location.href = '{{ route("calculos.exportar") }}?' + params.toString();
        });

        // ── Status helper ────────────────────────────────────────
        function showStatus(type, text, detail) {
            const box  = document.getElementById('status-box');
            const icon = document.getElementById('status-icon');
            const txt  = document.getElementById('status-text');
            const det  = document.getElementById('status-detail');

            document.getElementById('status-container').classList.remove('hidden');
            box.className = 'rounded-xl p-4 shadow-sm border flex items-center gap-3 ';

            const map = {
                loading: { cls: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800', icon: '<div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>', tcls: 'text-sm font-medium text-blue-700 dark:text-blue-300' },
                success: { cls: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800', icon: '<i class="fa-solid fa-circle-check text-xl text-green-600"></i>', tcls: 'text-sm font-medium text-green-700 dark:text-green-300' },
                error:   { cls: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800', icon: '<i class="fa-solid fa-circle-xmark text-xl text-red-600"></i>', tcls: 'text-sm font-medium text-red-700 dark:text-red-300' },
            };
            box.classList.add(...map[type].cls.split(' '));
            icon.innerHTML = map[type].icon;
            txt.className  = map[type].tcls;
            txt.textContent  = text;
            det.textContent  = detail || '';
        }

        // ── KPI helper ───────────────────────────────────────────
        const fmt = v => 'S/ ' + parseFloat(v || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        function renderKPIs(data) {
            if (!data.length) return;
            const sum = k => data.reduce((a, r) => a + parseFloat(r[k] || 0), 0);

            document.getElementById('kpi-contratos').textContent  = data.length.toLocaleString('es-PE');
            document.getElementById('kpi-asig-fam').textContent   = fmt(sum('asig_familiar'));
            document.getElementById('kpi-movilidad').textContent  = fmt(sum('movilidad'));
            document.getElementById('kpi-afp').textContent        = fmt(sum('aporte') + sum('prima') + sum('comision_afp'));
            document.getElementById('kpi-adelanto').textContent   = fmt(sum('adelanto_sueldo'));
            document.getElementById('kpi-neto').textContent       = fmt(sum('neto'));

            document.getElementById('kpi-container').classList.remove('hidden');
        }

        // ── Tabla helper ─────────────────────────────────────────
        const monetarias = [
            'haber_basico','asig_familiar','movilidad','comision','bono','rv',
            'pago_descanso_medico','pago_feriado','reintegro_afecto','tardanza',
            'total_haberes','base_imponible','aporte','prima','comision_afp',
            'essalud','adelanto_sueldo','total_descuentos','neto',
            'basico_compensatorio','prov_gratificacion','bono_extra_9','prov_cts','prov_vacaciones','costo_total',
        ];

        const labels = {
            id_contrato:'Ctto.', colaborador:'Colaborador', numero_documento:'Documento',
            planilla:'Planilla', tipo_calculo:'Tipo', dias_calculo:'D.Cal', dias_trabajados:'D.Trab',
            haber_basico:'HBR', asig_familiar:'AFR', movilidad:'Movil.', comision:'Com.', bono:'Bono',
            rv:'RV', pago_descanso_medico:'P.DM', pago_feriado:'P.Fer', reintegro_afecto:'Reint.',
            tardanza:'Tard.', total_haberes:'T.Hab', base_imponible:'B.Imp',
            aporte:'Aporte', prima:'Prima', comision_afp:'C.AFP', essalud:'EsSalud',
            adelanto_sueldo:'Adelanto', total_descuentos:'T.Desc', neto:'Neto',
            basico_compensatorio:'B.Comp', prov_gratificacion:'P.Grat',
            bono_extra_9:'Bon.9%', prov_cts:'P.CTS', prov_vacaciones:'P.Vac', costo_total:'C.Total',
        };

        function renderTabla(data) {
            const thead = document.getElementById('resultados-thead');
            const tbody = document.getElementById('resultados-tbody');
            thead.innerHTML = '';
            tbody.innerHTML = '';

            if (!data.length) {
                document.getElementById('empty-state').classList.remove('hidden');
                document.getElementById('resultados-container').classList.add('hidden');
                document.getElementById('kpi-container').classList.add('hidden');
                document.getElementById('empty-state').querySelector('p').textContent = 'No se encontraron resultados para este periodo.';
                return;
            }

            document.getElementById('empty-state').classList.add('hidden');
            document.getElementById('resultados-container').classList.remove('hidden');
            document.getElementById('info-count').innerHTML = '<i class="fa-solid fa-table mr-1"></i> ' + data.length.toLocaleString('es-PE') + ' registros';
            btnExportar.classList.remove('hidden');

            const keys = Object.keys(data[0]);
            const tr   = document.createElement('tr');
            keys.forEach(k => {
                const th = document.createElement('th');
                th.className = k === 'colaborador'
                    ? 'sticky left-0 z-10 bg-gray-50 dark:bg-[#1e2836] px-4 py-3 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 min-w-[200px]'
                    : 'px-3 py-3 text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 whitespace-nowrap';
                th.textContent = labels[k] || k;
                tr.appendChild(th);
            });
            thead.appendChild(tr);

            data.forEach((row, i) => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50 dark:hover:bg-[#323d4d] transition-colors';
                keys.forEach(k => {
                    const td  = document.createElement('td');
                    const val = row[k];
                    if (k === 'colaborador') {
                        td.className = 'sticky left-0 z-10 bg-white dark:bg-[#273142] px-4 py-2 text-sm font-medium text-gray-800 dark:text-white border-r border-gray-200 dark:border-gray-700 whitespace-nowrap';
                        td.textContent = val ?? '—';
                    } else if (k === 'tipo_calculo') {
                        td.className = 'px-3 py-2 text-center border-r border-gray-200 dark:border-gray-700';
                        const badge = document.createElement('span');
                        badge.className = val === 'REGULAR'
                            ? 'px-2 py-0.5 rounded-full text-[11px] font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                            : 'px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                        badge.textContent = val ?? '—';
                        td.appendChild(badge);
                    } else if (monetarias.includes(k)) {
                        const n = parseFloat(val || 0);
                        td.className = 'px-3 py-2 text-right font-mono text-xs border-r border-gray-200 dark:border-gray-700 whitespace-nowrap';
                        td.textContent = n.toFixed(2);
                        if (k === 'neto')             td.classList.add('font-bold', 'text-green-600', 'dark:text-green-400');
                        if (k === 'total_descuentos') td.classList.add('text-red-600', 'dark:text-red-400');
                        if (k === 'costo_total')      td.classList.add('font-semibold', 'text-blue-600', 'dark:text-blue-400');
                    } else if (typeof val === 'number') {
                        td.className = 'px-3 py-2 text-center text-sm text-gray-700 dark:text-gray-300 border-r border-gray-200 dark:border-gray-700';
                        td.textContent = val;
                    } else {
                        td.className = 'px-3 py-2 text-sm text-gray-700 dark:text-gray-300 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap';
                        td.textContent = val ?? '—';
                    }
                    tr.appendChild(td);
                });
                tbody.appendChild(tr);
            });
        }

        // ── Consultar ────────────────────────────────────────────
        function consultarResultados() {
            if (!elPeriodo.value) return;
            btnConsultar.disabled = true;
            if (btnEjecutar) btnEjecutar.disabled = true;

            const params = new URLSearchParams({ periodo: elPeriodo.value });
            if (elPlanilla.value) params.append('id_planilla', elPlanilla.value);

            fetch('{{ route("calculos.resultados") }}?' + params, { headers: { Accept: 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    updateButtons();
                    if (data.success) {
                        renderKPIs(data.data);
                        renderTabla(data.data);
                    } else {
                        showStatus('error', 'Error al consultar', data.error || 'Error desconocido');
                    }
                })
                .catch(() => { updateButtons(); showStatus('error', 'Error de conexión', ''); });
        }

        btnConsultar.addEventListener('click', function () {
            showStatus('loading', 'Consultando resultados...', '');
            consultarResultados();
        });

        // ── Procesar ─────────────────────────────────────────────
        if (btnEjecutar) {
            btnEjecutar.addEventListener('click', function () {
                if (!elPeriodo.value) return;
                if (!confirm('¿Procesar nómina del periodo ' + elPeriodo.value + '?')) return;
                btnEjecutar.disabled = true;
                btnConsultar.disabled = true;
                showStatus('loading', 'Procesando nómina...', 'Esto puede tomar varios segundos.');

                fetch('{{ route("calculos.ejecutar") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json' },
                    body: JSON.stringify({ periodo: elPeriodo.value })
                })
                .then(r => r.json())
                .then(data => {
                    updateButtons();
                    if (data.success) {
                        const info = data.data;
                        showStatus('success', 'Nómina procesada correctamente',
                            (info.contratos_procesados || 0) + ' contratos | Modo: ' + (info.modo || '') + (info.mensaje ? ' | ' + info.mensaje : ''));
                        consultarResultados();
                    } else {
                        showStatus('error', 'Error al procesar', data.error || 'Error desconocido');
                    }
                })
                .catch(() => { updateButtons(); showStatus('error', 'Error de conexión', 'No se pudo conectar con FastAPI.'); });
            });
        }
    });
    </script>
    @endpush
</x-app-layout>
