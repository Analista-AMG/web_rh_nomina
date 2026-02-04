<x-app-layout>
    @section('title', 'Asistencia - AMG International')

    <header class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Registro de Asistencia</h1>
    </header>

    <!-- Filtros -->
    <div class="mb-6 bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
        <form method="GET" action="{{ route('asistencia.index') }}" class="flex flex-wrap items-end gap-4" id="asistencia-filtros">
            <div class="min-w-[170px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha inicio</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio" value="{{ request('fecha_inicio') }}"
                    min="{{ $minFechaPago }}" max="{{ $maxFechaPago }}"
                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
            </div>
            <div class="min-w-[170px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha fin</label>
                <input type="date" name="fecha_fin" id="fecha_fin" value="{{ request('fecha_fin') }}"
                    min="{{ $minFechaPago }}" max="{{ $maxFechaPago }}"
                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
            </div>
            <div class="min-w-[160px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quincena</label>
                <select name="id_pago" id="id_pago" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50" {{ request('fecha_inicio') && request('fecha_fin') ? '' : 'disabled' }}>
                    <option value="">Seleccione</option>
                    @foreach($quincenas as $q)
                        <option value="{{ $q['id_pago'] }}" {{ request('id_pago') == $q['id_pago'] ? 'selected' : '' }}>
                            {{ $q['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[180px]">
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
            <div class="min-w-[180px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">N° Documento</label>
                <div class="relative">
                    <i class="fa-solid fa-id-card absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="numero_documento" id="numero_documento" value="{{ request('numero_documento') }}" placeholder="Buscar documento"
                        class="w-full pl-10 pr-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                </div>
            </div>
        </form>
    </div>

    @if($pagoSeleccionado)
        <!-- Info del periodo -->
        <div class="mb-4 flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
            <span><i class="fa-solid fa-calendar mr-1"></i> {{ \Carbon\Carbon::parse($pagoSeleccionado->inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($pagoSeleccionado->fin)->format('d/m/Y') }}</span>
            <span><i class="fa-solid fa-users mr-1"></i> {{ $contratos->count() }} contratos</span>
        </div>

        <!-- Leyenda de Items de Asistencia -->
        <div class="mb-4 bg-white dark:bg-[#273142] rounded-xl p-3 shadow-sm border border-light-border dark:border-dark-border">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 mr-3">Leyenda:</span>
            @foreach($itemsAsistencia as $item)
                <span class="inline-flex items-center px-2 py-1 mr-2 mb-1 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                    <strong class="mr-1">{{ $item->codigo_asistencia }}</strong> = {{ $item->descripcion }}
                </span>
            @endforeach
        </div>

        <!-- Tabla de Asistencia -->
        <div class="overflow-x-auto bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-[#1e2836]">
                    @php
                        $mesSpans = [];
                        $mesActual = null;
                        foreach ($fechas as $f) {
                            $key = $f->format('Y-m');
                            if ($mesActual === null || $mesActual !== $key) {
                                $mesSpans[] = [
                                    'key' => $key,
                                    'label' => $f->locale('es')->isoFormat('MMMM YYYY'),
                                    'count' => 1,
                                ];
                                $mesActual = $key;
                            } else {
                                $mesSpans[count($mesSpans) - 1]['count']++;
                            }
                        }
                    @endphp
                    <tr>
                        <th class="sticky left-0 z-10 bg-gray-50 dark:bg-[#1e2836] px-4 py-2 text-left text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 min-w-[250px]">
                            Mes
                        </th>
                        <th class="px-3 py-2 text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 min-w-[80px]"></th>
                        <th class="px-3 py-2 text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 min-w-[100px]"></th>
                        <th class="px-3 py-2 text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700"></th>
                        <th class="px-3 py-2 text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700"></th>
                        @foreach($mesSpans as $m)
                            <th colspan="{{ $m['count'] }}" class="px-1 py-2 text-center text-sm font-bold uppercase tracking-widest text-gray-700 dark:text-gray-100 border-r border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-[#222c3a]">
                                {{ $m['label'] }}
                            </th>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="sticky left-0 z-10 bg-gray-50 dark:bg-[#1e2836] px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 min-w-[250px]">
                            Colaborador
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 min-w-[80px]">
                            Condición
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 min-w-[100px]">
                            Planilla
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">
                            Inicio
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">
                            Fin
                        </th>
                        @foreach($fechas as $fecha)
                            @php
                                $diaSemana = $fecha->locale('es')->isoFormat('ddd');
                                $esFinSemana = $fecha->isWeekend();
                                $esFeriado = isset($feriados[$fecha->format('Y-m-d')]);
                            @endphp
                            <th class="px-1 py-2 text-center text-xs font-semibold uppercase tracking-wider min-w-[55px] {{ $esFeriado ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400' : ($esFinSemana ? 'bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400') }}">
                                <div class="flex flex-col items-center gap-1">
                                    <div class="flex flex-col">
                                        <span class="text-[10px]">{{ $diaSemana }}</span>
                                        <span>{{ $fecha->format('d') }}</span>
                                    </div>
                                    <select class="column-action-select w-full text-[10px] p-0.5 rounded border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 focus:outline-none text-center" data-fecha="{{ $fecha->format('Y-m-d') }}">
                                        <option value="">-</option>
                                        @if($esFeriado)
                                            <option value="TF">TF</option>
                                            <option value="DF">DF</option>
                                        @else
                                            <option value="A">A</option>
                                            <option value="D">D</option>
                                        @endif
                                    </select>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($contratos as $contrato)
                        @php
                            $inicioContrato = \Carbon\Carbon::parse($contrato->inicio_contrato);
                            $finContrato = $contrato->fin_contrato ? \Carbon\Carbon::parse($contrato->fin_contrato) : null;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#323d4d] transition-colors">
                            <td class="sticky left-0 z-10 bg-white dark:bg-[#273142] px-4 py-2 border-r border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center text-primary text-sm font-bold flex-shrink-0">
                                        {{ substr($contrato->persona->nombres ?? '?', 0, 1) }}{{ substr($contrato->persona->apellido_paterno ?? '?', 0, 1) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-800 dark:text-white leading-tight">
                                            {{ $contrato->persona->apellido_paterno ?? '' }} {{ $contrato->persona->apellido_materno ?? '' }} {{ $contrato->persona->nombres ?? 'Sin Asignar' }}
                                        </span>
                                        <span class="text-[11px] text-gray-500 font-medium">
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
                            <td class="px-3 py-2 text-center text-xs text-gray-600 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                                {{ $inicioContrato->format('d/m/y') }}
                            </td>
                            <td class="px-3 py-2 text-center text-xs text-gray-600 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                                {{ $finContrato ? $finContrato->format('d/m/y') : '-' }}
                            </td>
                            @foreach($fechas as $fecha)
                                @php
                                    $fechaStr = $fecha->format('Y-m-d');
                                    $dentroRango = $fecha->gte($inicioContrato) && (!$finContrato || $fecha->lte($finContrato));
                                    $asistencia = $contrato->asistencias_periodo[$fechaStr] ?? null;
                                    $valorActual = $asistencia ? $asistencia->id_cod_asistencia : '';
                                    $esFinSemana = $fecha->isWeekend();
                                    $esFeriado = isset($feriados[$fechaStr]);
                                @endphp
                                <td class="px-1 py-1 text-center {{ $esFeriado ? 'bg-red-50 dark:bg-red-900/20' : ($esFinSemana ? 'bg-orange-50 dark:bg-orange-900/20' : '') }} {{ !$dentroRango ? 'bg-gray-100 dark:bg-gray-800' : '' }}">
                                    @if($dentroRango)
                                        <select
                                            class="asistencia-select w-full text-xs px-1 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary/50 text-center"
                                            data-contrato="{{ $contrato->id_contrato }}"
                                            data-fecha="{{ $fechaStr }}"
                                        >
                                            <option value="">-</option>
                                            @foreach($itemsAsistencia as $item)
                                                @if($esFeriado)
                                                    @if(!Str::startsWith($item->codigo_asistencia, 'A') && $item->codigo_asistencia !== 'D')
                                                        <option value="{{ $item->id_cod_asistencia }}" {{ $valorActual == $item->id_cod_asistencia ? 'selected' : '' }}>
                                                            {{ $item->codigo_asistencia }}
                                                        </option>
                                                    @endif
                                                @else
                                                    <option value="{{ $item->id_cod_asistencia }}" {{ $valorActual == $item->id_cod_asistencia ? 'selected' : '' }}>
                                                        {{ $item->codigo_asistencia }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-600 text-xs">
                                            <i class="fa-solid fa-lock text-[10px]"></i>
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 5 + count($fechas) }}" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                @if($pagoSeleccionado)
                                    No hay contratos con días válidos en este periodo.
                                @else
                                    Seleccione un periodo para ver la asistencia.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="bg-white dark:bg-[#273142] rounded-xl p-12 text-center shadow-sm border border-light-border dark:border-dark-border">
            <i class="fa-solid fa-calendar-days text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">Seleccione un periodo para ver y registrar asistencia.</p>
        </div>
    @endif

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filtroForm = document.getElementById('asistencia-filtros');
            const fechaInicio = document.getElementById('fecha_inicio');
            const fechaFin = document.getElementById('fecha_fin');
            const idPago = document.getElementById('id_pago');
            const planilla = document.getElementById('id_planilla');
            const doc = document.getElementById('numero_documento');
            const rangosPago = @json($rangosPago);

            const submitForm = () => {
                if (filtroForm) filtroForm.submit();
            };

            const dateCoveredByPago = (isoDate) => {
                if (!isoDate || !Array.isArray(rangosPago)) return false;
                return rangosPago.some(r => isoDate >= r.inicio && isoDate <= r.fin);
            };

            const onDateRangeChange = () => {
                if (idPago) idPago.value = '';
                if (fechaInicio && fechaFin) {
                    if (fechaInicio.value && !dateCoveredByPago(fechaInicio.value)) {
                        alert('La fecha inicio no pertenece a ninguna quincena disponible.');
                        fechaInicio.value = '';
                        return;
                    }
                    if (fechaFin.value && !dateCoveredByPago(fechaFin.value)) {
                        alert('La fecha fin no pertenece a ninguna quincena disponible.');
                        fechaFin.value = '';
                        return;
                    }
                    if (!fechaInicio.value || !fechaFin.value) return;
                    if (fechaInicio.value > fechaFin.value) return;
                }
                submitForm();
            };

            if (fechaInicio) {
                fechaInicio.addEventListener('change', onDateRangeChange);
            }
            if (fechaFin) {
                fechaFin.addEventListener('change', onDateRangeChange);
            }
            if (idPago) {
                idPago.addEventListener('change', () => {
                    if (fechaInicio && fechaFin && fechaInicio.value && fechaFin.value && fechaInicio.value > fechaFin.value) return;
                    submitForm();
                });
            }
            if (planilla) planilla.addEventListener('change', submitForm);

            if (doc) {
                let t = null;
                doc.addEventListener('input', () => {
                    clearTimeout(t);
                    t = setTimeout(submitForm, 450);
                });
                doc.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        submitForm();
                    }
                });
            }

            // Mapeo de códigos de asistencia a sus IDs
            const itemsAsistenciaMap = @json($itemsAsistencia->mapWithKeys(function ($item) {
                return [$item->codigo_asistencia => $item->id_cod_asistencia];
            }));

            // Lógica de guardado para selectores individuales
            const selects = document.querySelectorAll('.asistencia-select');
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    guardarAsistencia(this);
                });
            });
            
            // Lógica para los selectores de acción de columna
            const columnActionSelects = document.querySelectorAll('.column-action-select');
            columnActionSelects.forEach(headerSelect => {
                headerSelect.addEventListener('change', function() {
                    const fecha = this.dataset.fecha;
                    const codigoSeleccionado = this.value; // ej: "A", "D", "TF", "DF"

                    if (!codigoSeleccionado) return;

                    const idParaAsignar = itemsAsistenciaMap[codigoSeleccionado] || '';
                    const selectsEnColumna = document.querySelectorAll(`.asistencia-select[data-fecha="${fecha}"]`);

                    selectsEnColumna.forEach(cellSelect => {
                        // Solo cambia si el valor es diferente y la opción existe en el select
                        if (cellSelect.value !== idParaAsignar && Array.from(cellSelect.options).some(opt => opt.value == idParaAsignar)) {
                            cellSelect.value = idParaAsignar;
                            // Disparamos el evento change para que se guarde automáticamente
                            cellSelect.dispatchEvent(new Event('change'));
                        }
                    });

                    // Reseteamos el select del header
                    this.value = '';
                });
            });

            // Función de guardado refactorizada
            function guardarAsistencia(selectElement) {
                const idContrato = selectElement.dataset.contrato;
                const fecha = selectElement.dataset.fecha;
                const idCodAsistencia = selectElement.value;

                selectElement.classList.add('opacity-50');
                selectElement.disabled = true;

                fetch('{{ route("asistencia.guardar") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        id_contrato: idContrato,
                        fecha: fecha,
                        id_cod_asistencia: idCodAsistencia || null
                    })
                })
                .then(response => response.json())
                .then(data => {
                    selectElement.classList.remove('opacity-50');
                    selectElement.disabled = false;

                    if (data.success) {
                        selectElement.classList.add('ring-2', 'ring-green-500');
                        setTimeout(() => {
                            selectElement.classList.remove('ring-2', 'ring-green-500');
                        }, 500);
                    } else {
                        selectElement.classList.add('ring-2', 'ring-red-500');
                        setTimeout(() => {
                            selectElement.classList.remove('ring-2', 'ring-red-500');
                        }, 1000);
                        alert(data.error || 'Error al guardar');
                    }
                })
                .catch(error => {
                    selectElement.classList.remove('opacity-50');
                    selectElement.disabled = false;
                    selectElement.classList.add('ring-2', 'ring-red-500');
                    setTimeout(() => {
                        selectElement.classList.remove('ring-2', 'ring-red-500');
                    }, 1000);
                    console.error('Error:', error);
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
