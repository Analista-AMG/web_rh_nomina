<x-app-layout>
    @section('title', 'Adicionales - AMG International')

    <style>
        /* Ocultar spinners de inputs numéricos */
        .adicional-input::-webkit-outer-spin-button,
        .adicional-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .adicional-input[type=number] { -moz-appearance: textbox; }
    </style>

    <header class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Gestión de Adicionales</h1>
    </header>

    <!-- Filtros -->
    <div class="mb-6 bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
        <form method="GET" action="{{ route('adicionales.index') }}" class="flex flex-wrap items-end gap-3" id="adicionales-filtros">
            <div class="min-w-[150px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Periodo</label>
                <select name="periodo" id="periodo" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <option value="">Seleccione</option>
                    @foreach($periodos as $periodo)
                        <option value="{{ $periodo }}" {{ $periodoSeleccionado == $periodo ? 'selected' : '' }}>
                            {{ $periodo }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[160px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Empresa</label>
                <select name="nombre_empresa" id="nombre_empresa" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <option value="">Todas</option>
                    @foreach($empresas as $emp)
                        <option value="{{ $emp }}" {{ request('nombre_empresa') == $emp ? 'selected' : '' }}>
                            {{ $emp }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[160px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Planilla</label>
                <select name="id_planilla" id="id_planilla" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <option value="">Todas</option>
                    @foreach($planillas as $planilla)
                        <option value="{{ $planilla->id_planilla }}" {{ request('id_planilla') == $planilla->id_planilla ? 'selected' : '' }}>
                            {{ $planilla->nombre_planilla }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[160px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Familia</label>
                <select name="id_familia" id="id_familia" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <option value="">Todas</option>
                    @foreach($familias as $familia)
                        <option value="{{ $familia->id_familia }}" {{ request('id_familia') == $familia->id_familia ? 'selected' : '' }}>
                            {{ $familia->nombre_familia }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[180px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">N° Documento</label>
                <div class="relative">
                    <i class="fa-solid fa-id-card absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="numero_documento" id="numero_documento" value="{{ request('numero_documento') }}" placeholder="Buscar documento"
                        class="w-full pl-10 pr-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                </div>
            </div>
            @can('adicionales.edit')
            <div class="ml-auto flex items-end">
                <button type="button" onclick="document.getElementById('modal-importar').classList.remove('hidden')"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium cursor-pointer whitespace-nowrap">
                    <i class="fa-solid fa-file-excel mr-1"></i> Cargar Movilidad
                </button>
            </div>
            @endcan
        </form>
    </div>

    @if($periodoSeleccionado)
        <!-- KPIs -->
        <div class="mb-4 flex flex-wrap gap-3">
            <div class="bg-white dark:bg-[#273142] rounded-xl px-4 py-2 shadow-sm border border-light-border dark:border-dark-border text-center min-w-[110px]">
                <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-semibold">Registros</p>
                <p class="text-lg font-bold text-gray-800 dark:text-white">{{ $totalRegistros }}</p>
            </div>
            @foreach(\App\Models\Adicional::TIPOS as $tipo)
                @php
                    $esNegativo = in_array($tipo, \App\Models\Adicional::TIPOS_NEGATIVOS);
                    $color = $esNegativo ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
                @endphp
                <div class="bg-white dark:bg-[#273142] rounded-xl px-4 py-2 shadow-sm border border-light-border dark:border-dark-border text-center min-w-[110px]">
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-semibold">{{ \App\Models\Adicional::LABELS[$tipo] }}</p>
                    <p class="text-lg font-bold {{ $color }}">S/ {{ number_format($kpis[$tipo], 2) }}</p>
                </div>
            @endforeach
        </div>

        <!-- Info -->
        <div class="mb-4 flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
            <span><i class="fa-solid fa-calendar mr-1"></i> Periodo: {{ $periodoSeleccionado }}</span>
            <span><i class="fa-solid fa-users mr-1"></i> {{ $contratos->count() }} contratos</span>
        </div>

        <!-- Tabla -->
        <div class="overflow-x-auto bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-[#1e2836]">
                    <tr>
                        <th class="sticky left-0 z-10 bg-gray-50 dark:bg-[#1e2836] px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 min-w-[300px]">
                            Colaborador
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 min-w-[80px]">
                            Condición
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 min-w-[100px]">
                            Planilla
                        </th>
                        @foreach(\App\Models\Adicional::TIPOS as $tipo)
                            @php
                                $esNegativo = in_array($tipo, \App\Models\Adicional::TIPOS_NEGATIVOS);
                                $thColor = $esNegativo ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
                            @endphp
                            <th class="px-2 py-3 text-center text-[11px] font-semibold {{ $thColor }} uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 min-w-[110px]">
                                {{ \App\Models\Adicional::LABELS[$tipo] }}
                            </th>
                        @endforeach
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider min-w-[100px]">
                            Total
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($contratos as $contrato)
                        @php
                            $adic = $contrato->adicionales_periodo;
                            $total = 0;
                            foreach (\App\Models\Adicional::TIPOS as $t) {
                                $m = floatval($adic[$t]['monto'] ?? 0);
                                $total += in_array($t, \App\Models\Adicional::TIPOS_NEGATIVOS) ? -$m : $m;
                            }
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#323d4d] transition-colors">
                            <td class="sticky left-0 z-10 bg-white dark:bg-[#273142] px-4 py-2 border-r border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center text-primary text-sm font-bold flex-shrink-0">
                                        {{ mb_substr($contrato->persona->nombres ?? '?', 0, 1, 'UTF-8') }}{{ mb_substr($contrato->persona->apellido_paterno ?? '?', 0, 1, 'UTF-8') }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-800 dark:text-white leading-tight">
                                            {{ $contrato->persona->apellido_paterno ?? '' }} {{ $contrato->persona->apellido_materno ?? '' }} {{ explode(' ', $contrato->persona->nombres ?? '')[0] ?: 'Sin Asignar' }}
                                        </span>
                                        <span class="text-[12px] text-gray-500 font-medium">
                                            {{ $contrato->persona->tipo_documento ?? 'DOC' }}: {{ $contrato->persona->numero_documento ?? '---' }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-center text-xs text-gray-600 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                                {{ $contrato->condicion->nombre_condicion ?? '-' }}
                            </td>
                            <td class="px-3 py-2 text-center text-xs text-gray-600 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                                {{ $contrato->planilla->nombre_planilla ?? '-' }}
                            </td>
                            @foreach(\App\Models\Adicional::TIPOS as $tipo)
                                <td class="px-1 py-1 text-center border-r border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-0.5">
                                        <input type="number" step="0.01" min="0"
                                            class="adicional-input w-full text-xs px-1.5 py-1.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary/50 text-right"
                                            data-contrato="{{ $contrato->id_contrato }}"
                                            data-tipo="{{ $tipo }}"
                                            data-negativo="{{ in_array($tipo, \App\Models\Adicional::TIPOS_NEGATIVOS) ? '1' : '0' }}"
                                            data-periodo="{{ $periodoSeleccionado }}"
                                            value="{{ $adic[$tipo]['monto'] !== '' ? $adic[$tipo]['monto'] : '' }}"
                                            placeholder="0.00"
                                        >
                                        <button type="button"
                                            class="motivo-btn flex-shrink-0 text-gray-400 hover:text-primary transition-colors p-0.5 cursor-pointer"
                                            data-contrato="{{ $contrato->id_contrato }}"
                                            data-tipo="{{ $tipo }}"
                                            data-periodo="{{ $periodoSeleccionado }}"
                                            data-motivo="{{ $adic[$tipo]['motivo'] ?? '' }}"
                                            title="Motivo: {{ $adic[$tipo]['motivo'] ?? 'Sin motivo' }}"
                                        >
                                            <i class="fa-solid fa-comment-dots text-[10px] {{ ($adic[$tipo]['motivo'] ?? '') ? 'text-primary' : '' }}"></i>
                                        </button>
                                    </div>
                                </td>
                            @endforeach
                            <td class="px-3 py-2 text-center text-sm font-bold whitespace-nowrap total-cell {{ $total >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                                data-contrato="{{ $contrato->id_contrato }}">
                                S/ {{ number_format($total, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + count(\App\Models\Adicional::TIPOS) + 1 }}" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                No hay contratos activos para este periodo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="bg-white dark:bg-[#273142] rounded-xl p-12 text-center shadow-sm border border-light-border dark:border-dark-border">
            <i class="fa-solid fa-hand-holding-dollar text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">Seleccione un periodo para gestionar adicionales.</p>
        </div>
    @endif

    <!-- Modal Motivo -->
    <div id="motivo-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-[#273142] rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">
                <i class="fa-solid fa-comment-dots mr-2 text-primary"></i>Motivo
            </h3>
            <textarea id="motivo-text" rows="3" placeholder="Ingrese el motivo..."
                class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 resize-none"></textarea>
            <input type="hidden" id="motivo-contrato">
            <input type="hidden" id="motivo-tipo">
            <input type="hidden" id="motivo-periodo">
            <div class="flex justify-end gap-3 mt-4">
                <button type="button" id="motivo-cancel" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white transition-colors cursor-pointer">
                    Cancelar
                </button>
                <button type="button" id="motivo-save" class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors cursor-pointer">
                    Guardar
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filtroForm = document.getElementById('adicionales-filtros');
            const periodo  = document.getElementById('periodo');
            const empresa  = document.getElementById('nombre_empresa');
            const planilla = document.getElementById('id_planilla');
            const familia  = document.getElementById('id_familia');
            const doc      = document.getElementById('numero_documento');

            const submitForm = () => { if (filtroForm) filtroForm.submit(); };

            // Combos {p, f, e} para el periodo actual
            const combos = @json($combos);

            // Snapshots completos de opciones antes de cualquier filtro
            const allPlanillaOpts = planilla ? Array.from(planilla.options).map(o => ({ v: o.value, t: o.text })) : [];
            const allFamiliaOpts  = familia  ? Array.from(familia.options).map(o => ({ v: o.value, t: o.text })) : [];

            // Reconstruye un select manteniendo solo las opciones válidas
            function rebuildSelect(sel, allOpts, valid) {
                const cur = sel.value;
                while (sel.options.length) sel.remove(0);
                allOpts.forEach(o => {
                    if (o.v === '' || !valid || valid.has(String(o.v))) sel.add(new Option(o.t, o.v));
                });
                if (Array.from(sel.options).some(o => o.value === cur)) sel.value = cur;
            }

            // Obtiene el conjunto válido de un campo dado los filtros activos (excluye el propio campo)
            function validFor(field, filters) {
                let rows = combos;
                if (filters.e) rows = rows.filter(c => c.e === filters.e);
                if (filters.p) rows = rows.filter(c => String(c.p) === filters.p);
                if (filters.f) rows = rows.filter(c => String(c.f) === filters.f);
                return rows.length ? new Set(rows.map(c => String(c[field]))) : null;
            }

            function applyInitialFilters() {
                if (!combos.length) return;
                const eVal = empresa  ? empresa.value  : '';
                const pVal = planilla ? planilla.value : '';
                const fVal = familia  ? familia.value  : '';
                // Empresa no se reconstruye (es filtro raíz)
                if (planilla) { rebuildSelect(planilla, allPlanillaOpts, validFor('p', { e: eVal, f: fVal })); planilla.value = pVal; }
                if (familia)  { rebuildSelect(familia,  allFamiliaOpts,  validFor('f', { e: eVal, p: pVal })); familia.value  = fVal; }
            }

            applyInitialFilters();

            if (periodo) periodo.addEventListener('change', submitForm);

            // Empresa: reconstruye planilla y familia
            if (empresa) empresa.addEventListener('change', function () {
                const eVal = this.value;
                if (planilla) rebuildSelect(planilla, allPlanillaOpts, validFor('p', { e: eVal }));
                if (familia)  rebuildSelect(familia,  allFamiliaOpts,  validFor('f', { e: eVal, p: planilla ? planilla.value : '' }));
                submitForm();
            });

            // Planilla: reconstruye familia respetando empresa
            if (planilla) planilla.addEventListener('change', function () {
                const eVal = empresa ? empresa.value : '';
                if (familia) rebuildSelect(familia, allFamiliaOpts, validFor('f', { e: eVal, p: this.value }));
                submitForm();
            });

            // Familia: reconstruye planilla respetando empresa
            if (familia) familia.addEventListener('change', function () {
                const eVal = empresa ? empresa.value : '';
                if (planilla) rebuildSelect(planilla, allPlanillaOpts, validFor('p', { e: eVal, f: this.value }));
                submitForm();
            });

            if (doc) {
                let t = null;
                doc.addEventListener('input', () => { clearTimeout(t); t = setTimeout(submitForm, 450); });
                doc.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); submitForm(); } });
            }

            // Guardado AJAX
            const inputs = document.querySelectorAll('.adicional-input');
            inputs.forEach(input => {
                let debounce = null;
                input.addEventListener('change', function() { clearTimeout(debounce); guardarAdicional(this); });
                input.addEventListener('input', function() {
                    clearTimeout(debounce);
                    debounce = setTimeout(() => recalcularTotal(this.dataset.contrato), 100);
                });
            });

            function guardarAdicional(inputEl, motivo) {
                const idContrato = inputEl.dataset.contrato;
                const tipoAdicional = inputEl.dataset.tipo;
                const periodoVal = inputEl.dataset.periodo;
                const monto = inputEl.value;

                const motivoBtn = document.querySelector(`.motivo-btn[data-contrato="${idContrato}"][data-tipo="${tipoAdicional}"]`);
                const motivoActual = motivo !== undefined ? motivo : (motivoBtn ? motivoBtn.dataset.motivo : '');

                inputEl.classList.add('opacity-50');
                inputEl.disabled = true;

                fetch('{{ route("adicionales.guardar") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        id_contrato: idContrato,
                        periodo: periodoVal,
                        tipo_adicional: tipoAdicional,
                        monto: monto || null,
                        motivo: motivoActual
                    })
                })
                .then(response => response.json())
                .then(data => {
                    inputEl.classList.remove('opacity-50');
                    inputEl.disabled = false;
                    if (data.success) {
                        inputEl.classList.add('ring-2', 'ring-green-500');
                        setTimeout(() => inputEl.classList.remove('ring-2', 'ring-green-500'), 500);
                        recalcularTotal(idContrato);
                    } else {
                        inputEl.classList.add('ring-2', 'ring-red-500');
                        setTimeout(() => inputEl.classList.remove('ring-2', 'ring-red-500'), 1000);
                    }
                })
                .catch(() => {
                    inputEl.classList.remove('opacity-50');
                    inputEl.disabled = false;
                    inputEl.classList.add('ring-2', 'ring-red-500');
                    setTimeout(() => inputEl.classList.remove('ring-2', 'ring-red-500'), 1000);
                });
            }

            function recalcularTotal(idContrato) {
                let total = 0;
                document.querySelectorAll(`.adicional-input[data-contrato="${idContrato}"]`).forEach(inp => {
                    const val = parseFloat(inp.value) || 0;
                    const esNegativo = inp.dataset.negativo === '1';
                    total += esNegativo ? -val : val;
                });

                const totalCell = document.querySelector(`.total-cell[data-contrato="${idContrato}"]`);
                if (totalCell) {
                    totalCell.textContent = 'S/ ' + total.toFixed(2);
                    totalCell.classList.remove('text-green-600', 'dark:text-green-400', 'text-red-600', 'dark:text-red-400');
                    totalCell.classList.add(total >= 0 ? 'text-green-600' : 'text-red-600');
                    totalCell.classList.add(total >= 0 ? 'dark:text-green-400' : 'dark:text-red-400');
                }
            }

            // Modal Motivo
            const modal = document.getElementById('motivo-modal');
            const motivoText = document.getElementById('motivo-text');
            const motivoContrato = document.getElementById('motivo-contrato');
            const motivoTipo = document.getElementById('motivo-tipo');
            const motivoPeriodo = document.getElementById('motivo-periodo');

            document.querySelectorAll('.motivo-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    motivoContrato.value = this.dataset.contrato;
                    motivoTipo.value = this.dataset.tipo;
                    motivoPeriodo.value = this.dataset.periodo;
                    motivoText.value = this.dataset.motivo || '';
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    motivoText.focus();
                });
            });

            document.getElementById('motivo-cancel').addEventListener('click', () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });

            document.getElementById('motivo-save').addEventListener('click', () => {
                const contrato = motivoContrato.value;
                const tipo = motivoTipo.value;
                const nuevoMotivo = motivoText.value;

                const btn = document.querySelector(`.motivo-btn[data-contrato="${contrato}"][data-tipo="${tipo}"]`);
                if (btn) {
                    btn.dataset.motivo = nuevoMotivo;
                    btn.title = 'Motivo: ' + (nuevoMotivo || 'Sin motivo');
                    const icon = btn.querySelector('i');
                    nuevoMotivo ? icon.classList.add('text-primary') : icon.classList.remove('text-primary');
                }

                const inputEl = document.querySelector(`.adicional-input[data-contrato="${contrato}"][data-tipo="${tipo}"]`);
                if (inputEl) guardarAdicional(inputEl, nuevoMotivo);

                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });

            modal.addEventListener('click', (e) => {
                if (e.target === modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
            });
        });
    </script>
    @endpush

    <!-- Modal Importar Movilidad -->
    @can('adicionales.edit')
    <div id="modal-importar" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center">
        <div class="bg-white dark:bg-[#273142] rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                    <i class="fa-solid fa-file-excel text-green-600 mr-2"></i>Cargar Movilidad
                </h3>
                <button type="button" onclick="document.getElementById('modal-importar').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            @if(session('success'))
                <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 text-sm">
                    <i class="fa-solid fa-circle-check mr-1"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
                    <i class="fa-solid fa-circle-xmark mr-1"></i> {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('adicionales.importar-movilidad') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Periodo</label>
                    <select name="periodo" required
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                        <option value="">Seleccione</option>
                        @foreach($periodos as $p)
                            <option value="{{ $p }}" {{ $periodoSeleccionado == $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Archivo Excel</label>
                    <input type="file" name="archivo" accept=".xlsx,.xls" required
                        class="w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-medium hover:file:bg-primary/20 cursor-pointer">
                    <p class="mt-1 text-xs text-gray-400">Columnas requeridas: <strong>id_contrato</strong>, <strong>monto</strong></p>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit"
                        class="flex-1 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium cursor-pointer">
                        <i class="fa-solid fa-upload mr-1"></i> Cargar
                    </button>
                    <button type="button" onclick="document.getElementById('modal-importar').classList.add('hidden')"
                        class="flex-1 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors text-sm font-medium cursor-pointer">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan
</x-app-layout>
