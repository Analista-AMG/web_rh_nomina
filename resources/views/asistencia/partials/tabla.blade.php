{{-- ── Tabla de asistencia ──────────────────────────────────────────────── --}}
<div class="overflow-x-auto bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-[#1e2836]">
            @php
                $mesSpans  = [];
                $mesActualHeader = null;
                foreach ($fechas as $f) {
                    $key = $f->format('Y-m');
                    if ($mesActualHeader !== $key) {
                        $mesSpans[] = ['label' => $f->locale('es')->isoFormat('MMMM YYYY'), 'mes' => $key, 'count' => 1];
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
                    <th data-mes="{{ $m['mes'] }}" colspan="{{ $m['count'] }}" class="px-1 py-2 text-center text-sm font-bold uppercase tracking-widest text-gray-700 dark:text-gray-100 border-r border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-[#222c3a]">
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
                        $colLocked = !$esAdmin && $bloquearAntesDe && $fStr < $bloquearAntesDe;
                        $thClass   = $esFer
                            ? 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-[#2f1e1e]'
                            : ($esFinSem ? 'text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-[#2f2a1e]' : 'text-gray-500 dark:text-gray-400');
                    @endphp
                    <th data-fecha="{{ $fStr }}" class="px-1 py-2 text-center text-xs font-semibold uppercase tracking-wider min-w-[52px] border-r border-gray-200 dark:border-gray-700 {{ $thClass }}">
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
                    $filaNombre  = mb_strtolower(trim(($persona->apellido_paterno ?? '') . ' ' . ($persona->apellido_materno ?? '') . ' ' . ($persona->nombres ?? '')));
                    $filaCampana = mb_strtolower($fila['campana'] ?? '');
                    $filaCentro  = mb_strtolower($contrato->centroCosto?->nombre_centro_costo ?? '');
                    $filaFamilia = mb_strtolower($contrato->familia?->nombre_familia ?? '');
                @endphp
                <tr class="group" data-nombre="{{ $filaNombre }}" data-campana="{{ $filaCampana }}" data-centro="{{ $filaCentro }}" data-familia="{{ $filaFamilia }}">
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
                            $esFinSem   = $fecha->isWeekend();
                            $esFer      = isset($feriados[$fStr]);

                            $dentroRango = $fecha->gte($fila['inicio_contrato'])
                                && (!$fila['fin_efectivo'] || $fecha->lte($fila['fin_efectivo']));

                            $enEquipo   = $esAdmin || isset($fila['en_equipo'][$fStr]);
                            $bloqueado  = !$esAdmin && $bloquearAntesDe && $fStr < $bloquearAntesDe;

                            $asistencia  = $fila['asistencias_periodo'][$fStr] ?? null;
                            $valorActual = $asistencia?->item_asistencia_id ?? '';
                            $codigoBadge = $asistencia?->itemAsistencia?->codigo_asistencia ?? '';

                            $supNombre = $equipoDiaSupervisores[(int)$contrato->persona_id][$fStr] ?? null;

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
                        <td data-fecha="{{ $fStr }}" title="{{ $supNombre ?? 'Sin registro de equipo' }}" class="px-1 py-1 text-center border-r border-gray-200 dark:border-gray-700 {{ $tdBg }}">
                            @if(!$dentroRango)
                                <span class="text-gray-300 dark:text-gray-600 text-xs"><i class="fa-solid fa-lock text-[10px]"></i></span>
                            @elseif($bloqueado)
                                <div class="flex items-center justify-center gap-1 text-xs text-amber-500 dark:text-amber-400 px-1 py-1" title="Periodo cerrado">
                                    @if($codigoBadge)
                                        <span class="font-medium text-amber-700 dark:text-amber-300">{{ $codigoBadge }}</span>
                                    @endif
                                    <i class="fa-solid fa-lock text-[9px]"></i>
                                </div>
                            @elseif(!$enEquipo)
                                <div class="flex items-center justify-center gap-1 text-xs text-gray-400 dark:text-gray-500 px-1 py-1 rounded border border-gray-200 dark:border-gray-700 bg-blue-50/50 dark:bg-blue-900/10">
                                    @if($codigoBadge)
                                        <span class="font-medium">{{ $codigoBadge }}</span>
                                    @else
                                        <span>-</span>
                                    @endif
                                    <i class="fa-solid fa-lock text-[9px] text-blue-400"></i>
                                </div>
                            @else
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
