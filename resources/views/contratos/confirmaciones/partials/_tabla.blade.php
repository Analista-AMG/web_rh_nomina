{{-- Tabla de colaboradores para confirmación --}}
{{-- Variables esperadas: $filas, $periodo, $esRrhh (bool) --}}

@php $tablaUid = 'tabla-'.uniqid(); @endphp

@if($filas->isEmpty())
    <div class="text-center py-12 text-gray-400 dark:text-gray-500">
        @php $filtro = $filtro ?? null; @endphp
        @if($filtro === 'pendiente')
            <i class="fa-regular fa-clock text-3xl mb-3 block"></i>
            <p class="text-sm">No hay contratos pendientes en este periodo.</p>
        @elseif($filtro === 'datos_actualizados')
            <i class="fa-solid fa-rotate text-3xl mb-3 block"></i>
            <p class="text-sm">No hay contratos con datos actualizados en este periodo.</p>
        @elseif($filtro === 'confirmado')
            <i class="fa-solid fa-check text-3xl mb-3 block"></i>
            <p class="text-sm">No hay contratos confirmados en este periodo.</p>
        @else
            <i class="fa-solid fa-users-slash text-3xl mb-3 block"></i>
            <p class="text-sm">No hay colaboradores con contrato en este periodo.</p>
        @endif
    </div>
@else
<form method="POST"
      action="{{ $esRrhh ? route('contratos.confirmaciones.intervenir') : route('contratos.confirmaciones.confirmar') }}"
      data-tabla="{{ $tablaUid }}">
    @csrf
    <input type="hidden" name="periodo" value="{{ $periodo }}">

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-light-border dark:border-dark-border">
                    @if($puedeActuar)
                    <th class="pb-3 pr-3 w-8">
                        <input type="checkbox" data-select-all="{{ $tablaUid }}"
                               class="rounded border-gray-300 dark:border-gray-600 text-primary focus:ring-primary/40 cursor-pointer">
                    </th>
                    @endif
                    <th class="pb-3 pr-4 text-left font-semibold">Colaborador</th>
                    <th class="pb-3 pr-4 text-left font-semibold">Empresa</th>
                    <th class="pb-3 pr-4 text-left font-semibold">Régimen</th>
                    <th class="pb-3 pr-4 text-left font-semibold">Centro Costo</th>
                    <th class="pb-3 pr-4 text-left font-semibold">Familia</th>
                    <th class="pb-3 pr-4 text-left font-semibold">Cargo</th>
                    <th class="pb-3 pr-4 text-left font-semibold">Condición</th>
                    <th class="pb-3 text-left font-semibold">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @foreach($filas as $fila)
                @php
                    $conf      = $fila->confirmacion;
                    $estado    = $conf?->estado ?? 'pendiente';
                    $mov       = $fila->movimiento;
                    $persona   = $fila->persona;
                    $contrato  = $fila->contrato;
                    $movCambio = $fila->movCambio ?? false;
                    $pendiente = !$conf || $estado === 'pendiente' || $movCambio;
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-[#1e2a38]/40 transition-colors">
                    {{-- Checkbox --}}
                    @if($puedeActuar)
                    <td class="py-3 pr-3">
                        <input type="checkbox" name="contrato_ids[]" value="{{ $contrato->id }}"
                               class="fila-check rounded border-gray-300 dark:border-gray-600 text-primary focus:ring-primary/40 cursor-pointer">
                    </td>
                    @endif

                    {{-- Colaborador --}}
                    <td class="py-3 pr-4">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0 text-xs font-bold text-primary">
                                {{ strtoupper(substr($persona->nombres ?? '?', 0, 1)) }}
                            </div>
                            @php
                                $asig         = $fila->asignacion ?? null;
                                $asigSolapo   = $fila->asigSolapo ?? false;
                                $asigAnterior = $fila->asigAnterior ?? null;
                                $asigFin      = $asig?->fecha_fin;
                                $finEnMes     = $asigFin && str_starts_with((string) $asigFin, $periodo);
                            @endphp
                            <div class="relative" data-tip-wrap data-tip-align="left" data-tip-w="224">
                                <p class="font-medium text-gray-800 dark:text-gray-100 leading-tight {{ $asig ? 'cursor-help' : '' }}">
                                    {{ $persona->apellido_paterno }} {{ $persona->apellido_materno }}, {{ $persona->nombres }}
                                </p>
                                <p class="text-xs text-gray-400">{{ $persona->numero_documento }}</p>
                                @if($asig)
                                <div class="absolute bottom-full left-0 mb-2 w-56 rounded-lg bg-gray-900 text-white shadow-xl
                                            invisible opacity-0 pointer-events-none" data-tip-box>
                                    <div class="p-3 space-y-0.5">
                                        @if($asigSolapo)
                                            {{-- Variante A/B: supervisor actual solapa con el contrato --}}
                                            <p class="text-[11px] text-gray-400 mb-1 font-medium">Período en equipo</p>
                                            @if($asigAnterior)
                                            {{-- Variante B: hubo supervisor anterior en este mismo contrato --}}
                                            <p class="text-[11px] text-gray-500 font-medium mb-0.5">Anterior:</p>
                                            <p class="text-[11px] text-gray-300">{{ $asigAnterior->superior?->name ?: 'Desconocido' }}</p>
                                            <p class="text-[11px] text-gray-500">
                                                {{ \Carbon\Carbon::parse($asigAnterior->fecha_inicio)->format('d/m/Y') }}
                                                –
                                                {{ $asigAnterior->fecha_fin ? \Carbon\Carbon::parse($asigAnterior->fecha_fin)->format('d/m/Y') : '—' }}
                                            </p>
                                            <div class="border-t border-gray-700 my-1.5"></div>
                                            <p class="text-[11px] text-gray-400 font-medium mb-0.5">Tu asignación:</p>
                                            @endif
                                            <p class="text-[11px] text-gray-200">
                                                Desde: {{ \Carbon\Carbon::parse($asig->fecha_inicio)->format('d/m/Y') }}
                                            </p>
                                            @if($asigFin)
                                            <p class="text-[11px] {{ $finEnMes ? 'text-amber-400' : 'text-gray-200' }}">
                                                Hasta: {{ \Carbon\Carbon::parse($asigFin)->format('d/m/Y') }}
                                            </p>
                                            @else
                                            <p class="text-[11px] text-green-400">Activo todo el periodo</p>
                                            @endif
                                        @else
                                            @if($asigAnterior)
                                            {{-- Variante C: supervisor anterior identificado --}}
                                            <p class="text-[11px] text-gray-400 mb-1 font-medium">Período en equipo</p>
                                            <p class="text-[11px] text-gray-100 font-medium">{{ $asigAnterior->superior?->name ?: 'Desconocido' }}</p>
                                            <p class="text-[11px] text-gray-400">
                                                {{ \Carbon\Carbon::parse($asigAnterior->fecha_inicio)->format('d/m/Y') }}
                                                →
                                                {{ $asigAnterior->fecha_fin ? \Carbon\Carbon::parse($asigAnterior->fecha_fin)->format('d/m/Y') : '—' }}
                                            </p>
                                            <div class="border-t border-gray-700 my-1.5"></div>
                                            <p class="text-[11px] text-gray-400">Tu equipo desde:</p>
                                            <p class="text-[11px] text-gray-200">
                                                {{ \Carbon\Carbon::parse($asig->fecha_inicio)->format('d/m/Y') }}
                                            </p>
                                            @else
                                            {{-- Variante D: sin registro de supervisor anterior --}}
                                            <p class="text-[11px] text-gray-400 mb-1 font-medium">Tu equipo desde:</p>
                                            <p class="text-[11px] text-gray-200">
                                                {{ \Carbon\Carbon::parse($asig->fecha_inicio)->format('d/m/Y') }}
                                            </p>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="absolute top-full left-3 border-4 border-transparent border-t-gray-900"></div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Campos críticos del movimiento --}}
                    <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">
                        {{ $mov?->planilla?->nombre_empresa ?? '—' }}
                    </td>
                    <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">
                        {{ $mov?->planilla?->regimen ?? '—' }}
                    </td>
                    <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">
                        {{ $mov?->centroCosto?->nombre_centro_costo ?? '—' }}
                    </td>
                    <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">
                        {{ $mov?->familia?->nombre_familia ?? '—' }}
                    </td>
                    <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">
                        {{ $mov?->cargo?->nombre_cargo ?? '—' }}
                    </td>
                    <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">
                        {{ $mov?->condicion?->nombre_condicion ?? '—' }}
                    </td>

                    {{-- Estado --}}
                    <td class="py-3">
                        @if($movCambio)
                            <div class="relative inline-flex" data-tip-wrap data-tip-align="right" data-tip-w="256">
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 cursor-help">
                                    <i class="fa-solid fa-rotate"></i> Datos actualizados
                                </span>
                                {{-- Tooltip enriquecido --}}
                                <div class="absolute bottom-full right-0 mb-2 w-64 rounded-lg bg-gray-900 text-white shadow-xl
                                            invisible opacity-0 pointer-events-none" data-tip-box>
                                    <div class="p-3 space-y-1.5">
                                        <p class="text-gray-400 text-[11px]">
                                            Confirmado: {{ $conf->confirmado_en?->format('d/m/Y H:i') }}
                                        </p>
                                        @if(!empty($fila->cambiados))
                                        <div class="border-t border-gray-700 pt-1.5 space-y-1">
                                            @foreach($fila->cambiados as $campo => [$antes, $ahora])
                                            <div class="flex items-start gap-1 text-[11px]">
                                                <span class="text-gray-400 w-24 flex-shrink-0">{{ $campo }}:</span>
                                                <span class="flex items-center gap-1 flex-wrap min-w-0">
                                                    @if($antes !== null)
                                                        <span class="line-through text-red-400 truncate">{{ $antes }}</span>
                                                        <span class="text-gray-500">→</span>
                                                    @endif
                                                    <span class="text-green-400 truncate">{{ $ahora ?? '—' }}</span>
                                                </span>
                                            </div>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                    <div class="absolute top-full right-3 border-4 border-transparent border-t-gray-900"></div>
                                </div>
                            </div>
                        @elseif($estado === 'confirmado')
                            <div class="relative inline-flex" data-tip-wrap data-tip-align="right" data-tip-w="224">
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 cursor-help">
                                    <i class="fa-solid fa-check"></i> Confirmado
                                </span>
                                <div class="absolute bottom-full right-0 mb-2 w-56 rounded-lg bg-gray-900 text-white shadow-xl
                                            invisible opacity-0 pointer-events-none" data-tip-box>
                                    <div class="p-3 space-y-1">
                                        <p class="text-[11px] text-gray-300 font-medium">
                                            {{ $conf->confirmadoPor?->name ?? 'Desconocido' }}
                                        </p>
                                        <p class="text-[11px] text-gray-400">
                                            {{ $conf->confirmado_en?->format('d/m/Y H:i') }}
                                        </p>
                                        @if($conf->origen_accion === 'rrhh')
                                        <p class="text-[11px] text-blue-400 font-medium">Intervención RRHH</p>
                                        @endif
                                    </div>
                                    <div class="absolute top-full right-3 border-4 border-transparent border-t-gray-900"></div>
                                </div>
                            </div>
                        @elseif($estado === 'requiere_actualizacion')
                            @if($esRrhh && $movCambio)
                            {{-- Supervisor rechazó un cambio hecho por RRHH --}}
                            <div class="relative inline-flex" data-tip-wrap data-tip-align="right" data-tip-w="224">
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 cursor-help">
                                    <i class="fa-solid fa-xmark"></i> Rechazó cambio RRHH
                                </span>
                                @if($conf->comentario)
                                <div class="absolute bottom-full right-0 mb-2 w-56 rounded-lg bg-gray-900 text-white shadow-xl
                                            invisible opacity-0 pointer-events-none" data-tip-box>
                                    <div class="p-3">
                                        <p class="text-[11px] text-gray-400 font-medium mb-1">Comentario del supervisor:</p>
                                        <p class="text-[11px] text-gray-200">{{ $conf->comentario }}</p>
                                    </div>
                                    <div class="absolute top-full right-3 border-4 border-transparent border-t-gray-900"></div>
                                </div>
                                @endif
                            </div>
                            @else
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400"
                                  title="{{ $conf->comentario ? 'Comentario: '.$conf->comentario : '' }}">
                                <i class="fa-solid fa-triangle-exclamation"></i> Requiere actualización
                            </span>
                            @endif
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                                <i class="fa-regular fa-clock"></i> Pendiente
                            </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($puedeActuar)
    {{-- Footer de acción --}}
    <div class="mt-4 pt-4 border-t border-light-border dark:border-dark-border flex flex-col sm:flex-row items-start sm:items-end gap-3">
        <div class="flex-1 w-full">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                Comentario (opcional — aplica a todos los seleccionados)
            </label>
            <textarea name="comentario" rows="2" maxlength="500"
                      class="w-full text-sm rounded-lg border border-light-border dark:border-dark-border bg-white dark:bg-[#1e2a38] text-gray-800 dark:text-gray-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all resize-none"
                      placeholder="Observaciones..."></textarea>
        </div>
        <div class="flex flex-col gap-2 flex-shrink-0">
            <button type="submit" name="estado" value="confirmado"
                    data-btn-lote="{{ $tablaUid }}"
                    disabled
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-green-600 hover:bg-green-700 disabled:opacity-40 disabled:cursor-not-allowed text-white transition-colors">
                <i class="fa-solid fa-check"></i> Confirmar ok
            </button>
            <button type="submit" name="estado" value="requiere_actualizacion"
                    data-btn-lote="{{ $tablaUid }}"
                    disabled
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-amber-500 hover:bg-amber-600 disabled:opacity-40 disabled:cursor-not-allowed text-white transition-colors">
                <i class="fa-solid fa-triangle-exclamation"></i> Requiere actualización
            </button>
        </div>
    </div>
    @endif
</form>

<script>
(function () {
    document.querySelectorAll('[data-tip-wrap]').forEach(function (wrap) {
        var box     = wrap.querySelector('[data-tip-box]');
        if (!box) return;
        var tipW    = parseInt(wrap.dataset.tipW || '224');
        var parent  = box.parentNode;
        var sibling = box.nextSibling;

        wrap.addEventListener('mouseenter', function () {
            var r = wrap.getBoundingClientRect();

            // Portal a <html>: body tiene overflow:hidden → Chromium recorta fixed hijos de body.
            document.documentElement.appendChild(box);
            box.style.cssText = 'position:fixed;top:-9999px;left:-9999px;bottom:auto;right:auto;'
                + 'width:' + tipW + 'px;margin:0;'
                + 'visibility:hidden;opacity:1;z-index:9999;pointer-events:none;'
                + 'transform:translateZ(0);transition:none;';

            var bh   = box.offsetHeight || 80;
            var top  = r.top - bh - 8;
            var left = wrap.dataset.tipAlign === 'right' ? r.right - tipW : r.left;

            left = Math.max(4, Math.min(left, window.innerWidth - tipW - 4));

            box.style.top        = top  + 'px';
            box.style.left       = left + 'px';
            box.style.visibility = 'visible';
        });

        wrap.addEventListener('mouseleave', function () {
            if (sibling) parent.insertBefore(box, sibling);
            else         parent.appendChild(box);
            box.style.cssText = '';
        });
    });
})();

(function () {
    const uid       = @json($tablaUid);
    const form      = document.querySelector('[data-tabla="' + uid + '"]');
    const selectAll = document.querySelector('[data-select-all="' + uid + '"]');
    const checks    = () => form ? form.querySelectorAll('.fila-check') : [];
    const btns      = () => document.querySelectorAll('[data-btn-lote="' + uid + '"]');

    function syncBtns() {
        const any = [...checks()].some(c => c.checked);
        btns().forEach(b => b.disabled = !any);
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checks().forEach(c => c.checked = this.checked);
            syncBtns();
        });
    }

    if (form) {
        form.addEventListener('change', function (e) {
            if (e.target.classList.contains('fila-check')) {
                const all = [...checks()];
                if (selectAll) selectAll.checked = all.length > 0 && all.every(c => c.checked);
                syncBtns();
            }
        });
    }
})();
</script>
@endif
