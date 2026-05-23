<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="border-b dark:border-gray-700">
                <tr>
                    <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Usuario</th>
                    <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Campaña</th>
                    <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Rol</th>
                    <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Superior</th>
                    <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase text-center">Estado</th>
                    <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Desde</th>
                    @if($esCerradas)
                    <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Cerrada el</th>
                    @else
                    <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase text-center">Acciones</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($asignaciones as $a)
                @if(!$esCerradas)
                @php
                    $nivelRol     = ['Colaborador' => 1, 'Supervisor' => 2, 'Coordinador' => 3, 'Jefe Operaciones' => 4];
                    $puedeManejar = $esAdmin || $miNivelMax > ($nivelRol[$a->rol] ?? 0);
                @endphp
                @endif
                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">

                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $a->usuario->name ?? '—' }}</div>
                        @php $persona = $personasPorDoc[$a->usuario?->numero_documento] ?? null; @endphp
                        @if($a->usuario?->numero_documento)
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="text-xs text-gray-400">{{ $a->usuario->numero_documento }}</span>
                            @if($persona)
                            @php
                                $contratoRef = $persona->contrato_activo ?? $persona->contratos->first();
                                $finEfectivo = $contratoRef
                                    ? ($contratoRef->fecha_renuncia ?? $contratoRef->fin_contrato)
                                    : null;
                            @endphp
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $persona->estadoBadgeClass }}">
                                {{ $persona->estadoLabel }}
                                @if($finEfectivo)
                                    · {{ $finEfectivo->format('d/m/Y') }}
                                @endif
                            </span>
                            @endif
                        </div>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                        {{ $a->campana->nombre ?? '—' }}
                    </td>

                    <td class="px-4 py-3">
                        @php
                            $rolClases = [
                                'Jefe Operaciones' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                'Coordinador'      => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                                'Supervisor'       => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                'Colaborador'      => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $rolClases[$a->rol] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $a->rol }}
                        </span>
                        @if(!$esCerradas && $a->puede_editar_propia_asistencia)
                        <span class="inline-flex items-center ml-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400"
                              title="Puede editar su propia asistencia">
                            <i class="fa-solid fa-calendar-pen"></i>
                        </span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                        {{ $a->superior->name ?? '—' }}
                    </td>

                    <td class="px-4 py-3 text-center">
                        @if($a->estado === 'pendiente')
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                            <i class="fa-solid fa-clock mr-1 text-xs"></i> Pendiente
                        </span>
                        @elseif($a->estado === 'aprobado')
                            @if($a->activo)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                <i class="fa-solid fa-circle-check mr-1 text-xs"></i> Aprobada
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                <i class="fa-solid fa-pause mr-1 text-xs"></i> Pausada
                            </span>
                            @endif
                        @else
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"
                            title="{{ $a->motivo_rechazo }}">
                            <i class="fa-solid fa-xmark mr-1 text-xs"></i> Rechazada
                        </span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                        {{ $a->fecha_inicio?->format('d/m/Y') ?? '—' }}
                    </td>

                    @if($esCerradas)
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                        {{ $a->fecha_fin?->format('d/m/Y') ?? '—' }}
                    </td>
                    @else
                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        @if($a->estado === 'pendiente' && $puedeManejar)
                        <button onclick="aprobar({{ $a->id }})"
                            class="inline-flex items-center px-2 py-1 text-xs rounded bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50 transition mr-1 cursor-pointer">
                            <i class="fa-solid fa-check mr-1"></i> Aprobar
                        </button>
                        <button onclick="openRechazarModal({{ $a->id }})"
                            class="inline-flex items-center px-2 py-1 text-xs rounded bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 transition cursor-pointer">
                            <i class="fa-solid fa-xmark mr-1"></i> Rechazar
                        </button>
                        @elseif($a->estado === 'aprobado' && $puedeManejar)
                        @if($a->activo)
                        <button onclick="openEditarModal({{ $a->id }}, '{{ addslashes($a->usuario->name ?? '') }}', {{ $a->campana_id }}, '{{ $a->rol }}', {{ $a->superior_id ?? 'null' }}, {{ $a->puede_editar_propia_asistencia ? 'true' : 'false' }}, '{{ $a->fecha_inicio->format('Y-m-d') }}')"
                            class="inline-flex items-center px-2 py-1 text-xs rounded bg-indigo-100 text-indigo-700 hover:bg-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400 transition mr-1 cursor-pointer"
                            title="Editar superior">
                            <i class="fa-solid fa-pencil mr-1"></i> Editar
                        </button>
                        @endif
                        <button onclick="pausar({{ $a->id }})"
                            class="inline-flex items-center px-2 py-1 text-xs rounded {{ $a->activo ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400' }} transition mr-1 cursor-pointer">
                            <i class="fa-solid {{ $a->activo ? 'fa-pause' : 'fa-play' }} mr-1"></i>
                            {{ $a->activo ? 'Pausar' : 'Activar' }}
                        </button>
                        <button onclick="cerrar({{ $a->id }})"
                            class="inline-flex items-center px-2 py-1 text-xs rounded bg-gray-100 text-gray-600 hover:bg-red-100 hover:text-red-700 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-red-900/30 dark:hover:text-red-400 transition cursor-pointer"
                            title="Cerrar definitivamente">
                            <i class="fa-solid fa-lock"></i>
                        </button>
                        @if($esAdmin)
                        <button onclick="eliminar({{ $a->id }}, '{{ addslashes($a->usuario->name ?? '') }}')"
                            class="inline-flex items-center px-2 py-1 text-xs rounded bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 transition ml-1 cursor-pointer"
                            title="Eliminar asignación">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                        @endif
                        @elseif($a->estado === 'rechazado' && $a->motivo_rechazo)
                        <span class="text-xs text-gray-400 italic" title="{{ $a->motivo_rechazo }}">
                            <i class="fa-solid fa-circle-info mr-1"></i>{{ Str::limit($a->motivo_rechazo, 35) }}
                        </span>
                        @else
                        <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-14 text-center text-gray-400 dark:text-gray-500">
                        <i class="fa-solid fa-users-slash text-4xl mb-3 block"></i>
                        {{ $esCerradas ? 'No hay asignaciones cerradas.' : 'No hay asignaciones registradas.' }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
