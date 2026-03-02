<div class="bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase border-b border-light-border dark:border-dark-border bg-gray-50 dark:bg-[#1b2431]">
                    <th class="px-5 py-3 text-left font-medium">Colaborador</th>
                    <th class="px-5 py-3 text-left font-medium">Origen → Destino</th>
                    <th class="px-5 py-3 text-center font-medium">Período</th>
                    <th class="px-5 py-3 text-center font-medium">Creado por</th>
                    <th class="px-5 py-3 text-center font-medium">Estado</th>
                    <th class="px-5 py-3 text-center font-medium">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-light-border dark:divide-dark-border">
                @foreach($coleccion as $p)
                <tr class="hover:bg-gray-50 dark:hover:bg-[#1b2431] transition-colors">

                    {{-- Colaborador --}}
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-800 dark:text-white">
                            {{ $p->empleado?->nombre_corto ?? '—' }}
                        </p>
                    </td>

                    {{-- Origen → Destino --}}
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2 text-xs">
                            <div class="text-right">
                                <p class="font-medium text-gray-700 dark:text-gray-200">{{ $p->supervisorOrigen?->name ?? '—' }}</p>
                                <p class="text-gray-400">{{ $p->campanaOrigen?->nombre ?? '—' }}</p>
                            </div>
                            <i class="fa-solid fa-arrow-right text-gray-300 dark:text-gray-600 flex-shrink-0"></i>
                            <div>
                                <p class="font-medium text-gray-700 dark:text-gray-200">{{ $p->supervisorDestino?->name ?? '—' }}</p>
                                <p class="text-gray-400">{{ $p->campanaDestino?->nombre ?? '—' }}</p>
                            </div>
                        </div>
                    </td>

                    {{-- Período --}}
                    <td class="px-5 py-3 text-center text-gray-600 dark:text-gray-300 text-xs whitespace-nowrap">
                        <div>{{ $p->fecha_inicio->format('d/m/Y') }}</div>
                        <div class="text-gray-400">→ {{ $p->fecha_fin->format('d/m/Y') }}</div>
                        @php
                            $dias = $p->fecha_inicio->diffInDays($p->fecha_fin) + 1;
                        @endphp
                        <div class="text-[11px] text-gray-400 mt-0.5">{{ $dias }} día{{ $dias !== 1 ? 's' : '' }}</div>
                    </td>

                    {{-- Creado por --}}
                    <td class="px-5 py-3 text-center text-xs text-gray-500 dark:text-gray-400">
                        {{ $p->creadoPor?->name ?? '—' }}
                        <div class="text-[11px] text-gray-400 mt-0.5">{{ $p->created_at->format('d/m H:i') }}</div>
                    </td>

                    {{-- Estado --}}
                    <td class="px-5 py-3 text-center">
                        @if($p->estado === 'aprobado')
                            <span class="inline-flex items-center gap-1 text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-2 py-1 rounded-full">
                                <i class="fa-solid fa-check"></i> Aprobado
                            </span>
                        @elseif($p->estado === 'rechazado')
                            <span class="inline-flex items-center gap-1 text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 px-2 py-1 rounded-full">
                                <i class="fa-solid fa-xmark"></i> Rechazado
                            </span>
                            @if($p->motivo_rechazo)
                            <p class="text-[11px] text-red-400 mt-1 max-w-[140px] mx-auto truncate" title="{{ $p->motivo_rechazo }}">
                                {{ $p->motivo_rechazo }}
                            </p>
                            @endif
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-semibold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 px-2 py-1 rounded-full">
                                <i class="fa-regular fa-clock"></i> Pendiente
                            </span>
                        @endif

                        {{-- Aprobado por --}}
                        @if($p->aprobadoPor && $p->estado !== 'pendiente')
                        <p class="text-[11px] text-gray-400 mt-1">
                            por {{ $p->aprobadoPor->name }}
                        </p>
                        @endif
                    </td>

                    {{-- Acciones --}}
                    <td class="px-5 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">

                            {{-- Aprobar / Rechazar (aprobadores, solo en pendientes) --}}
                            @if($modo === 'pendientes' && $puedeAprobar)
                            <button type="button"
                                onclick="aprobar({{ $p->id }})"
                                class="p-1.5 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors cursor-pointer"
                                title="Aprobar">
                                <i class="fa-solid fa-check text-sm"></i>
                            </button>
                            <button type="button"
                                onclick="abrirModalRechazar({{ $p->id }})"
                                class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors cursor-pointer"
                                title="Rechazar">
                                <i class="fa-solid fa-ban text-sm"></i>
                            </button>
                            @endif

                            {{-- Cancelar (creador, solo en pendientes) --}}
                            @if($modo === 'pendientes' && (int)$p->creado_por === (int)$userId)
                            <button type="button"
                                onclick="cancelar({{ $p->id }})"
                                class="p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors cursor-pointer"
                                title="Cancelar solicitud">
                                <i class="fa-solid fa-xmark text-sm"></i>
                            </button>
                            @endif

                            {{-- Motivo si tiene --}}
                            @if($p->motivo && in_array($modo, ['activos', 'proximos', 'historial']))
                            <span class="text-gray-300 dark:text-gray-600 text-xs" title="{{ $p->motivo }}">
                                <i class="fa-solid fa-comment-dots"></i>
                            </span>
                            @endif

                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
