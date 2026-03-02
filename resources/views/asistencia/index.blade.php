<x-app-layout>
    @section('title', 'Asistencia - AMG International')

    <header class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Registro de Asistencia</h1>
        @if(!$esAdmin && $diaActual <= 2 && $pagoSeleccionado)
            @php $mesAnterior = $hoy->copy()->subMonth()->isoFormat('MMMM YYYY'); @endphp
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                <i class="fa-solid fa-clock"></i> Gracia: puedes editar {{ $mesAnterior }} hasta mañana
            </span>
        @endif
    </header>

    {{-- Filtro de periodo --}}
    <div class="mb-6 bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
        <form method="GET" action="{{ route('asistencia.index') }}" id="form-filtro" class="flex flex-wrap items-end gap-4">
            <div class="min-w-[260px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Periodo / Quincena</label>
                <select name="pago_id" id="pago_id"
                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                    @foreach($pagos as $pago)
                        <option value="{{ $pago->id }}" {{ $pagoSeleccionado && $pagoSeleccionado->id == $pago->id ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($pago->inicio)->format('d/m/Y') }}
                            – {{ \Carbon\Carbon::parse($pago->fin)->format('d/m/Y') }}
                            (Q{{ $pago->quincena }} · {{ $pago->periodo }})
                        </option>
                    @endforeach
                </select>
            </div>
            <x-forms.primary-button type="submit">
                <i class="fa-solid fa-filter mr-1"></i> Filtrar
            </x-forms.primary-button>
        </form>
    </div>

    @if($pagoSeleccionado)
        {{-- Stats bar --}}
        <div class="mb-4 flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
            <span>
                <i class="fa-solid fa-calendar mr-1"></i>
                {{ \Carbon\Carbon::parse($pagoSeleccionado->inicio)->format('d/m/Y') }}
                – {{ \Carbon\Carbon::parse($pagoSeleccionado->fin)->format('d/m/Y') }}
            </span>
            <span><i class="fa-solid fa-users mr-1"></i> {{ $filas->count() }} colaborador(es)</span>
            @if(!$esAdmin && $diaActual >= 3)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                    <i class="fa-solid fa-lock text-[10px]"></i> Meses anteriores bloqueados
                </span>
            @endif
        </div>

        {{-- Legend --}}
        <div class="mb-4 bg-white dark:bg-[#273142] rounded-xl p-3 shadow-sm border border-light-border dark:border-dark-border flex flex-wrap items-center gap-x-4 gap-y-1">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Leyenda:</span>
            @foreach($itemsAsistencia as $item)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                    <strong class="mr-1">{{ $item->codigo_asistencia }}</strong> {{ $item->descripcion }}
                </span>
            @endforeach
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-400"><i class="fa-solid fa-lock text-[9px]"></i> Sin contrato</span>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-amber-100 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400"><i class="fa-solid fa-lock text-[9px]"></i> Periodo cerrado</span>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-blue-50 dark:bg-blue-900/20 text-blue-500"><i class="fa-solid fa-lock text-[9px]"></i> Otro supervisor</span>
        </div>

        @if($filas->isEmpty())
            <div class="bg-white dark:bg-[#273142] rounded-xl p-12 text-center shadow-sm border border-light-border dark:border-dark-border">
                <i class="fa-solid fa-user-slash text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">No tienes colaboradores asignados para este periodo.</p>
                @if(!$esAdmin)
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Los colaboradores se determinan por la jerarquía de asignaciones.</p>
                @endif
            </div>
        @else
            {{-- Attendance grid --}}
            <div class="overflow-x-auto bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-[#1e2836]">
                        @php
                            $mesSpans  = [];
                            $mesActualHeader = null;
                            foreach ($fechas as $f) {
                                $key = $f->format('Y-m');
                                if ($mesActualHeader !== $key) {
                                    $mesSpans[]       = ['label' => $f->locale('es')->isoFormat('MMMM YYYY'), 'count' => 1];
                                    $mesActualHeader  = $key;
                                } else {
                                    $mesSpans[count($mesSpans) - 1]['count']++;
                                }
                            }
                        @endphp
                        {{-- Row 1: month headers --}}
                        <tr>
                            <th class="sticky left-0 z-10 bg-gray-50 dark:bg-[#1e2836] px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 min-w-[220px]">Mes</th>
                            <th class="px-3 py-2 border-r border-gray-200 dark:border-gray-700 min-w-[120px]"></th>
                            <th class="px-3 py-2 border-r border-gray-200 dark:border-gray-700 min-w-[90px]"></th>
                            <th class="px-3 py-2 border-r border-gray-200 dark:border-gray-700 min-w-[80px]"></th>
                            @foreach($mesSpans as $m)
                                <th colspan="{{ $m['count'] }}" class="px-1 py-2 text-center text-sm font-bold uppercase tracking-widest text-gray-700 dark:text-gray-100 border-r border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-[#222c3a]">
                                    {{ $m['label'] }}
                                </th>
                            @endforeach
                        </tr>
                        {{-- Row 2: column labels + date headers --}}
                        <tr>
                            <th class="sticky left-0 z-10 bg-gray-50 dark:bg-[#1e2836] px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">
                                Colaborador
                            </th>
                            <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">
                                Campaña
                            </th>
                            <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">
                                Planilla
                            </th>
                            <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">
                                Inicio
                            </th>
                            @foreach($fechas as $fecha)
                                @php
                                    $fStr      = $fecha->format('Y-m-d');
                                    $diaSemana = $fecha->locale('es')->isoFormat('ddd');
                                    $esFinSem  = $fecha->isWeekend();
                                    $esFer     = isset($feriados[$fStr]);
                                    $fMes      = $fecha->format('Y-m');
                                    $colLocked = !$esAdmin && $diaActual >= 3 && $fMes < $mesActual;
                                    $thClass   = $esFer
                                        ? 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-[#2f1e1e]'
                                        : ($esFinSem ? 'text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-[#2f2a1e]' : 'text-gray-500 dark:text-gray-400');
                                @endphp
                                <th class="px-1 py-2 text-center text-xs font-semibold uppercase tracking-wider min-w-[52px] border-r border-gray-200 dark:border-gray-700 {{ $thClass }}">
                                    <div class="flex flex-col items-center gap-1">
                                        <div class="flex flex-col leading-tight">
                                            <span class="text-[10px]">{{ $diaSemana }}</span>
                                            <span>{{ $fecha->format('d') }}</span>
                                        </div>
                                        @if(!$colLocked)
                                            <select class="col-action w-full text-[10px] p-0.5 rounded border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 focus:outline-none text-center"
                                                data-fecha="{{ $fStr }}">
                                                <option value="">-</option>
                                                @foreach($itemsAsistencia as $item)
                                                    <option value="{{ $item->id }}" data-codigo="{{ $item->codigo_asistencia }}">{{ $item->codigo_asistencia }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <span class="text-[10px] text-amber-500"><i class="fa-solid fa-lock"></i></span>
                                        @endif
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($filas as $fila)
                            @php
                                $persona = $fila['persona'];
                                $contrato = $fila['contrato'];
                                $iniciales = mb_substr($persona->apellido_paterno ?? '?', 0, 1) .
                                             mb_substr(explode(' ', trim($persona->nombres ?? '?'))[0], 0, 1);
                            @endphp
                            <tr class="group">
                                {{-- Sticky name column --}}
                                <td class="sticky left-0 z-10 bg-white dark:bg-[#273142] px-4 py-2 border-r border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center text-primary text-sm font-bold flex-shrink-0">
                                            {{ mb_strtoupper($iniciales) }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-gray-800 dark:text-white leading-tight">
                                                {{ $persona->apellido_paterno }} {{ $persona->apellido_materno }}
                                                {{ explode(' ', trim($persona->nombres ?? ''))[0] }}
                                            </span>
                                            <span class="text-[11px] text-gray-500">
                                                {{ $persona->tipo_documento ?? 'DOC' }}: {{ $persona->numero_documento ?? '---' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                {{-- Campaña --}}
                                <td class="px-3 py-2 text-center text-xs text-gray-600 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                                    {{ $fila['campana'] }}
                                </td>
                                {{-- Planilla --}}
                                <td class="px-3 py-2 text-center text-xs text-gray-600 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                                    {{ $contrato->planilla->nombre_planilla ?? '-' }}
                                </td>
                                {{-- Inicio contrato --}}
                                <td class="px-3 py-2 text-center text-xs text-gray-600 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                                    {{ $fila['inicio_contrato']->format('d/m/y') }}
                                </td>

                                {{-- Date cells --}}
                                @foreach($fechas as $fecha)
                                    @php
                                        $fStr       = $fecha->format('Y-m-d');
                                        $fMes       = $fecha->format('Y-m');
                                        $esFinSem   = $fecha->isWeekend();
                                        $esFer      = isset($feriados[$fStr]);

                                        $dentroRango = $fecha->gte($fila['inicio_contrato'])
                                            && (!$fila['fin_efectivo'] || $fecha->lte($fila['fin_efectivo']));

                                        $enEquipo   = $esAdmin || isset($fila['en_equipo'][$fStr]);
                                        $bloqueado  = !$esAdmin && $diaActual >= 3 && $fMes < $mesActual;

                                        $asistencia  = $fila['asistencias_periodo'][$fStr] ?? null;
                                        $valorActual = $asistencia?->item_asistencia_id ?? '';
                                        $codigoBadge = $asistencia?->itemAsistencia?->codigo_asistencia ?? '';

                                        // Cell background
                                        if (!$dentroRango) {
                                            $tdBg = 'bg-gray-100 dark:bg-gray-800';
                                        } elseif ($bloqueado) {
                                            $tdBg = 'bg-amber-50 dark:bg-amber-900/10';
                                        } elseif ($esFer) {
                                            $tdBg = 'bg-red-50 dark:bg-[#2f1e1e] group-hover:bg-red-100 dark:group-hover:bg-[#3a2424]';
                                        } elseif ($esFinSem) {
                                            $tdBg = 'bg-orange-50 dark:bg-[#2f2a1e] group-hover:bg-orange-100 dark:group-hover:bg-[#3a3222]';
                                        } else {
                                            $tdBg = 'group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d]';
                                        }
                                    @endphp
                                    <td class="px-1 py-1 text-center border-r border-gray-200 dark:border-gray-700 {{ $tdBg }}">
                                        @if(!$dentroRango)
                                            {{-- Outside contract range --}}
                                            <span class="text-gray-300 dark:text-gray-600 text-xs"><i class="fa-solid fa-lock text-[10px]"></i></span>
                                        @elseif($bloqueado)
                                            {{-- Temporally locked (past month, day >= 3) --}}
                                            <div class="flex items-center justify-center gap-1 text-xs text-amber-500 dark:text-amber-400 px-1 py-1" title="Periodo cerrado">
                                                @if($codigoBadge)
                                                    <span class="font-medium text-amber-700 dark:text-amber-300">{{ $codigoBadge }}</span>
                                                @endif
                                                <i class="fa-solid fa-lock text-[9px]"></i>
                                            </div>
                                        @elseif(!$enEquipo)
                                            {{-- Belongs to another supervisor that day --}}
                                            <div class="flex items-center justify-center gap-1 text-xs text-gray-400 dark:text-gray-500 px-1 py-1 rounded border border-gray-200 dark:border-gray-700 bg-blue-50/50 dark:bg-blue-900/10" title="Otro supervisor ese día">
                                                @if($codigoBadge)
                                                    <span class="font-medium">{{ $codigoBadge }}</span>
                                                @else
                                                    <span>-</span>
                                                @endif
                                                <i class="fa-solid fa-lock text-[9px] text-blue-400"></i>
                                            </div>
                                        @else
                                            {{-- Editable cell --}}
                                            <select
                                                class="asistencia-select w-full text-xs px-1 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary/50 text-center"
                                                data-contrato="{{ $contrato->id }}"
                                                data-fecha="{{ $fStr }}"
                                            >
                                                <option value="">-</option>
                                                @foreach($itemsAsistencia as $item)
                                                    <option value="{{ $item->id }}" {{ $valorActual == $item->id ? 'selected' : '' }}>
                                                        {{ $item->codigo_asistencia }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @else
        <div class="bg-white dark:bg-[#273142] rounded-xl p-12 text-center shadow-sm border border-light-border dark:border-dark-border">
            <i class="fa-solid fa-calendar-days text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">Seleccione un periodo para registrar asistencia.</p>
        </div>
    @endif

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Auto-submit on period change
        const pagoSelect = document.getElementById('pago_id');
        if (pagoSelect) {
            pagoSelect.addEventListener('change', () => document.getElementById('form-filtro').submit());
        }

        // Items map: id => codigo for column-action feedback
        const itemsMap = @json($itemsAsistencia->mapWithKeys(fn($i) => [$i->id => $i->codigo_asistencia]));

        // Save individual cell
        function guardarAsistencia(sel) {
            const contratoId      = sel.dataset.contrato;
            const fecha           = sel.dataset.fecha;
            const itemAsistenciaId = sel.value || null;

            sel.classList.add('opacity-50');
            sel.disabled = true;

            fetch('{{ route("asistencia.guardar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    contrato_id:       parseInt(contratoId),
                    fecha:             fecha,
                    item_asistencia_id: itemAsistenciaId ? parseInt(itemAsistenciaId) : null,
                }),
            })
            .then(r => r.json())
            .then(data => {
                sel.classList.remove('opacity-50');
                sel.disabled = false;
                if (data.success) {
                    sel.classList.add('ring-2', 'ring-green-500');
                    setTimeout(() => sel.classList.remove('ring-2', 'ring-green-500'), 600);
                } else {
                    sel.classList.add('ring-2', 'ring-red-500');
                    setTimeout(() => sel.classList.remove('ring-2', 'ring-red-500'), 1000);
                    alert(data.error || 'Error al guardar');
                    // Revert value
                    sel.value = sel.dataset.prevValue ?? '';
                }
            })
            .catch(() => {
                sel.classList.remove('opacity-50');
                sel.disabled = false;
                sel.classList.add('ring-2', 'ring-red-500');
                setTimeout(() => sel.classList.remove('ring-2', 'ring-red-500'), 1000);
            });
        }

        // Bind cell selects
        document.querySelectorAll('.asistencia-select').forEach(sel => {
            sel.dataset.prevValue = sel.value;
            sel.addEventListener('change', function () {
                guardarAsistencia(this);
                this.dataset.prevValue = this.value;
            });
        });

        // Column mass-apply
        document.querySelectorAll('.col-action').forEach(headerSel => {
            headerSel.addEventListener('change', function () {
                const fecha     = this.dataset.fecha;
                const itemId    = this.value;
                if (!itemId) return;

                document.querySelectorAll(`.asistencia-select[data-fecha="${fecha}"]`).forEach(cellSel => {
                    if (cellSel.value !== itemId) {
                        const opt = cellSel.querySelector(`option[value="${itemId}"]`);
                        if (opt) {
                            cellSel.value = itemId;
                            cellSel.dispatchEvent(new Event('change'));
                        }
                    }
                });
                this.value = '';
            });
        });
    });
    </script>
    @endpush
</x-app-layout>
