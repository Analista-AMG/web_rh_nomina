<x-app-layout>
    @section('title', 'Asignaciones')

    @php
        $nivelRol   = ['Colaborador' => 1, 'Supervisor' => 2, 'Coordinador' => 3, 'Jefe Operaciones' => 4];
        $pendientes = $asignaciones->where('estado', 'pendiente')->count();
        $aprobadas  = $asignaciones->where('estado', 'aprobado')->count();
        $rechazadas = $asignaciones->where('estado', 'rechazado')->count();
    @endphp

    <header class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Asignaciones</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Gestión de asignaciones por campaña</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ request()->fullUrlWithQuery(['cerradas' => $mostrarCerradas ? '0' : '1']) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border transition
                      {{ $mostrarCerradas
                         ? 'bg-gray-700 text-white border-gray-600'
                         : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-primary hover:text-primary' }}">
                <i class="fa-solid fa-lock text-xs"></i>
                {{ $mostrarCerradas ? 'Ver activas' : 'Ver cerradas' }}
            </a>
            @if(!$mostrarCerradas)
            <button onclick="openCreateModal()"
                class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/80 transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-plus"></i> Nueva Asignación
            </button>
            @endif
        </div>
    </header>

    {{-- Stats --}}
    @if(!$mostrarCerradas)
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 flex items-center gap-3">
            <i class="fa-solid fa-clock text-yellow-500 text-2xl"></i>
            <div>
                <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-400">{{ $pendientes }}</p>
                <p class="text-xs text-yellow-600 dark:text-yellow-500">Pendientes de aprobación</p>
            </div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-green-500 text-2xl"></i>
            <div>
                <p class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $aprobadas }}</p>
                <p class="text-xs text-green-600 dark:text-green-500">Aprobadas y activas</p>
            </div>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 flex items-center gap-3">
            <i class="fa-solid fa-circle-xmark text-red-500 text-2xl"></i>
            <div>
                <p class="text-2xl font-bold text-red-700 dark:text-red-400">{{ $rechazadas }}</p>
                <p class="text-xs text-red-600 dark:text-red-500">Rechazadas</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Filtros --}}
    @if(!$mostrarCerradas)
    <div class="flex gap-3 mb-4">
        <select id="filtro-campana" onchange="filterTable()" class="form-input text-sm" style="width:220px;">
            <option value="">Todas las campañas</option>
            @foreach($campanas as $c)
            <option value="{{ $c->id }}">{{ $c->nombre }}</option>
            @endforeach
        </select>
        <select id="filtro-estado" onchange="filterTable()" class="form-input text-sm" style="width:180px;">
            <option value="">Todos los estados</option>
            <option value="pendiente">Pendientes</option>
            <option value="aprobado">Aprobadas</option>
            <option value="rechazado">Rechazadas</option>
        </select>
    </div>
    @endif

    {{-- Tabla --}}
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
                        @if($mostrarCerradas)
                        <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Cerrada el</th>
                        @else
                        <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase text-center">Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="tabla-body">
                    @forelse($asignaciones as $a)
                    @php
                        $puedeManejar = $esAdmin || $miNivelMax > ($nivelRol[$a->rol] ?? 0);
                    @endphp
                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors"
                        data-campana="{{ $a->campana_id }}"
                        data-estado="{{ $a->estado }}">

                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $a->usuario->name ?? '—' }}</div>
                            @if($a->usuario?->numero_documento)
                            <div class="text-xs text-gray-400 mt-0.5">{{ $a->usuario->numero_documento }}</div>
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

                        @if($mostrarCerradas)
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
                            <button onclick="openTransferirModal({{ $a->id }}, '{{ addslashes($a->usuario->name ?? '') }}', '{{ addslashes($a->campana->nombre ?? '') }}', {{ $a->campana_id }}, '{{ $a->rol }}')"
                                class="inline-flex items-center px-2 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 transition mr-1 cursor-pointer"
                                title="Transferir a nuevo superior o cambiar rol">
                                <i class="fa-solid fa-right-left mr-1"></i> Transferir
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
                        <td colspan="{{ $mostrarCerradas ? 7 : 7 }}" class="px-4 py-14 text-center text-gray-400 dark:text-gray-500">
                            <i class="fa-solid fa-users-slash text-4xl mb-3 block"></i>
                            {{ $mostrarCerradas ? 'No hay asignaciones cerradas.' : 'No hay asignaciones registradas.' }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Modal Crear ────────────────────────────────────────────────────── --}}
    <div id="modal-create" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-primary"></i> Nueva Asignación
                </h2>
                <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition cursor-pointer">
                    <i class="fa-solid fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Rol <span class="text-red-500">*</span></label>
                        <select id="create-rol" onchange="onRolChange()" class="form-input w-full text-sm">
                            <option value="">Seleccionar rol...</option>
                            <option value="Jefe Operaciones">Jefe Operaciones</option>
                            <option value="Coordinador">Coordinador</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Colaborador">Colaborador</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Fecha Inicio <span class="text-red-500">*</span></label>
                        <input type="date" id="create-fecha" class="form-input w-full text-sm" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Campaña <span class="text-red-500">*</span></label>
                        <select id="create-campana" onchange="onCampanaChange()" class="form-input w-full text-sm" disabled>
                            <option value="">Selecciona un rol primero...</option>
                            @foreach($campanas as $c)
                            <option value="{{ $c->id }}" data-tiene-subs="{{ $c->subcampanas_count > 0 ? '1' : '0' }}">
                                {{ $c->nombre }}{{ $c->subcampanas_count > 0 ? ' (grupo)' : '' }}
                            </option>
                            @endforeach
                        </select>
                        <p id="campana-hint" class="hidden mt-1 text-xs text-amber-600 dark:text-amber-400">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                            Las campañas marcadas como <em>(grupo)</em> solo aplican para Jefe de Operaciones.
                        </p>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Usuario <span class="text-red-500">*</span></label>
                        <select id="create-usuario" class="form-input w-full text-sm" disabled>
                            <option value="">Selecciona una campaña primero...</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                            Superior <span class="text-gray-400 font-normal">(opcional)</span>
                        </label>
                        <select id="create-superior" class="form-input w-full text-sm" disabled>
                            <option value="">Selecciona campaña y rol primero...</option>
                        </select>
                    </div>
                </div>
                <div id="auto-approve-note" class="hidden text-xs text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg p-3 flex items-center gap-2">
                    <i class="fa-solid fa-circle-check flex-shrink-0"></i>
                    Tienes autoridad suficiente — será aprobada automáticamente.
                </div>
                <div id="pending-note" class="hidden text-xs text-yellow-700 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3 flex items-center gap-2">
                    <i class="fa-solid fa-clock flex-shrink-0"></i>
                    Quedará pendiente de aprobación por un superior.
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t dark:border-gray-700">
                <button onclick="closeCreateModal()" class="btn btn-secondary text-sm px-4 py-2 cursor-pointer">Cancelar</button>
                <button onclick="submitCreate()" class="btn btn-primary text-sm px-4 py-2 cursor-pointer">
                    <i class="fa-solid fa-save mr-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>

    {{-- ── Modal Rechazar ──────────────────────────────────────────────────── --}}
    <div id="modal-rechazar" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-circle-xmark text-red-500"></i> Rechazar Asignación
                </h2>
                <button onclick="closeRechazarModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition cursor-pointer">
                    <i class="fa-solid fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6">
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-2">Motivo del rechazo <span class="text-red-500">*</span></label>
                <textarea id="motivo-rechazo" rows="3"
                    class="form-input w-full text-sm resize-none"
                    placeholder="Indica el motivo del rechazo..."></textarea>
                <input type="hidden" id="rechazar-id" value="">
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t dark:border-gray-700">
                <button onclick="closeRechazarModal()" class="btn btn-secondary text-sm px-4 py-2 cursor-pointer">Cancelar</button>
                <button onclick="submitRechazar()" class="btn btn-danger text-sm px-4 py-2 cursor-pointer">
                    <i class="fa-solid fa-xmark mr-1"></i> Rechazar
                </button>
            </div>
        </div>
    </div>

    {{-- ── Modal Transferir ────────────────────────────────────────────────── --}}
    <div id="modal-transferir" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-right-left text-blue-500"></i> Transferir Asignación
                </h2>
                <button onclick="closeTransferirModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition cursor-pointer">
                    <i class="fa-solid fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                {{-- Info actual --}}
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg px-4 py-3 text-sm space-y-1">
                    <p class="text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wide mb-1">Asignación actual</p>
                    <p class="font-medium text-gray-800 dark:text-white" id="tr-info-usuario">—</p>
                    <p class="text-gray-500 dark:text-gray-400 text-xs" id="tr-info-campana">—</p>
                    <p class="text-gray-500 dark:text-gray-400 text-xs" id="tr-info-rol">—</p>
                </div>

                <input type="hidden" id="tr-id" value="">
                <input type="hidden" id="tr-campana-id" value="">
                <input type="hidden" id="tr-rol-actual" value="">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Nuevo Rol</label>
                        <select id="tr-rol" onchange="onTransferirRolChange()" class="form-input w-full text-sm">
                            <option value="Jefe Operaciones">Jefe Operaciones</option>
                            <option value="Coordinador">Coordinador</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Colaborador">Colaborador</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Déjalo igual para no cambiar rol.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Fecha Inicio <span class="text-red-500">*</span></label>
                        <input type="date" id="tr-fecha" class="form-input w-full text-sm" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Nuevo Superior <span class="text-gray-400 font-normal">(opcional)</span></label>
                        <select id="tr-superior" class="form-input w-full text-sm">
                            <option value="">Cargando...</option>
                        </select>
                    </div>
                </div>

                <div class="text-xs text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 flex items-start gap-2">
                    <i class="fa-solid fa-circle-info flex-shrink-0 mt-0.5"></i>
                    <span>Se cerrará la asignación actual y se creará una nueva aprobada automáticamente.</span>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t dark:border-gray-700">
                <button onclick="closeTransferirModal()" class="btn btn-secondary text-sm px-4 py-2 cursor-pointer">Cancelar</button>
                <button onclick="submitTransferir()" class="btn btn-primary text-sm px-4 py-2 cursor-pointer">
                    <i class="fa-solid fa-right-left mr-1"></i> Transferir
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const csrf     = document.querySelector('meta[name="csrf-token"]').content;
        const miNivel  = {{ $miNivelMax }};
        const nivelRol = { 'Colaborador': 1, 'Supervisor': 2, 'Coordinador': 3, 'Jefe Operaciones': 4 };

        function filterTable() {
            const campana = document.getElementById('filtro-campana').value;
            const estado  = document.getElementById('filtro-estado').value;
            document.querySelectorAll('#tabla-body tr[data-campana]').forEach(row => {
                const ok = (!campana || row.dataset.campana === campana)
                        && (!estado  || row.dataset.estado  === estado);
                row.style.display = ok ? '' : 'none';
            });
        }

        function openCreateModal() {
            document.getElementById('create-rol').value    = '';
            document.getElementById('create-fecha').value  = '{{ now()->toDateString() }}';
            resetCampanaSelect();
            resetUsuarioSelect();
            resetSuperiorSelect();
            hideNotes();
            document.getElementById('modal-create').classList.replace('hidden', 'flex');
        }

        function closeCreateModal() {
            document.getElementById('modal-create').classList.replace('flex', 'hidden');
        }

        function resetCampanaSelect() {
            const sel = document.getElementById('create-campana');
            sel.value    = '';
            sel.disabled = true;
            Array.from(sel.options).forEach(o => o.hidden = false);
            document.getElementById('campana-hint').classList.add('hidden');
        }

        function resetUsuarioSelect() {
            const sel = document.getElementById('create-usuario');
            sel.innerHTML = '<option value="">Selecciona una campaña primero...</option>';
            sel.disabled  = true;
        }

        function resetSuperiorSelect() {
            const sel = document.getElementById('create-superior');
            sel.innerHTML = '<option value="">Selecciona campaña y rol primero...</option>';
            sel.disabled  = true;
        }

        function hideNotes() {
            document.getElementById('auto-approve-note').classList.add('hidden');
            document.getElementById('pending-note').classList.add('hidden');
        }

        function updateNotes(rol) {
            if (!rol) { hideNotes(); return; }
            if (miNivel > (nivelRol[rol] ?? 0)) {
                document.getElementById('auto-approve-note').classList.remove('hidden');
                document.getElementById('pending-note').classList.add('hidden');
            } else {
                document.getElementById('auto-approve-note').classList.add('hidden');
                document.getElementById('pending-note').classList.remove('hidden');
            }
        }

        function onRolChange() {
            const rol     = document.getElementById('create-rol').value;
            const selCamp = document.getElementById('create-campana');
            const hint    = document.getElementById('campana-hint');

            resetCampanaSelect();
            resetUsuarioSelect();
            resetSuperiorSelect();
            hideNotes();

            if (!rol) return;

            const esJO = rol === 'Jefe Operaciones';
            let hayOpciones = false;
            Array.from(selCamp.options).forEach(opt => {
                if (!opt.value) return;
                opt.hidden = !esJO && opt.dataset.tieneSubs === '1';
                if (!opt.hidden) hayOpciones = true;
            });

            selCamp.disabled = !hayOpciones;
            hint.classList.toggle('hidden', esJO);
            updateNotes(rol);
        }

        async function onCampanaChange() {
            const campanaId = document.getElementById('create-campana').value;
            const rol       = document.getElementById('create-rol').value;
            resetUsuarioSelect();
            resetSuperiorSelect();
            if (!campanaId) return;

            try {
                const res   = await fetch(`/admin/asignaciones/usuarios-disponibles?campana_id=${campanaId}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                });
                const users = await res.json();
                const sel   = document.getElementById('create-usuario');
                sel.innerHTML = '<option value="">Seleccionar usuario...</option>';
                users.forEach(u => {
                    const opt       = document.createElement('option');
                    opt.value       = u.id;
                    opt.textContent = `${u.name} (${u.numero_documento})`;
                    sel.appendChild(opt);
                });
                sel.disabled = false;
            } catch(e) { console.error(e); }

            if (!rol) return;
            try {
                const res        = await fetch(`/admin/asignaciones/superiores-disponibles?campana_id=${campanaId}&rol=${encodeURIComponent(rol)}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                });
                const superiores = await res.json();
                const sel        = document.getElementById('create-superior');

                if (superiores.length === 0) {
                    sel.innerHTML = '<option value="">Sin superiores disponibles en esta campaña</option>';
                    sel.disabled  = true;
                } else {
                    sel.innerHTML = '<option value="">Sin superior (cima de jerarquía)</option>';
                    superiores.forEach(s => {
                        const opt       = document.createElement('option');
                        opt.value       = s.id;
                        opt.textContent = `${s.name} — ${s.rol}`;
                        sel.appendChild(opt);
                    });
                    sel.disabled = false;
                }
            } catch(e) { console.error(e); }
        }

        async function submitCreate() {
            const campanaId  = document.getElementById('create-campana').value;
            const rol        = document.getElementById('create-rol').value;
            const usuarioId  = document.getElementById('create-usuario').value;
            const superiorId = document.getElementById('create-superior').value;
            const fecha      = document.getElementById('create-fecha').value;

            if (!campanaId || !rol || !usuarioId || !fecha) {
                alert('Por favor completa todos los campos obligatorios.');
                return;
            }

            try {
                const res = await fetch('/admin/asignaciones', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body:    JSON.stringify({
                        campana_id:   campanaId,
                        rol:          rol,
                        user_id:      usuarioId,
                        superior_id:  superiorId || null,
                        fecha_inicio: fecha,
                    }),
                });
                const data = await res.json();
                if (res.ok) { closeCreateModal(); alert(data.message); location.reload(); }
                else { alert(data.message || 'Error al crear la asignación.'); }
            } catch(e) { alert('Error de conexión.'); }
        }

        async function aprobar(id) {
            if (!confirm('¿Aprobar esta asignación?')) return;
            try {
                const res  = await fetch(`/admin/asignaciones/${id}/aprobar`, {
                    method:  'PATCH',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (res.ok) { alert(data.message); location.reload(); }
                else { alert(data.message || 'Error.'); }
            } catch(e) { alert('Error de conexión.'); }
        }

        function openRechazarModal(id) {
            document.getElementById('rechazar-id').value    = id;
            document.getElementById('motivo-rechazo').value = '';
            document.getElementById('modal-rechazar').classList.replace('hidden', 'flex');
        }

        function closeRechazarModal() {
            document.getElementById('modal-rechazar').classList.replace('flex', 'hidden');
        }

        async function submitRechazar() {
            const id     = document.getElementById('rechazar-id').value;
            const motivo = document.getElementById('motivo-rechazo').value.trim();
            if (!motivo) { alert('El motivo es obligatorio.'); return; }

            try {
                const res  = await fetch(`/admin/asignaciones/${id}/rechazar`, {
                    method:  'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body:    JSON.stringify({ motivo }),
                });
                const data = await res.json();
                if (res.ok) { closeRechazarModal(); alert(data.message); location.reload(); }
                else { alert(data.message || 'Error.'); }
            } catch(e) { alert('Error de conexión.'); }
        }

        async function pausar(id) {
            if (!confirm('¿Cambiar el estado de esta asignación?')) return;
            try {
                const res  = await fetch(`/admin/asignaciones/${id}/pausar`, {
                    method:  'PATCH',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (res.ok) { alert(data.message); location.reload(); }
                else { alert(data.message || 'Error.'); }
            } catch(e) { alert('Error de conexión.'); }
        }

        async function cerrar(id) {
            if (!confirm('¿Cerrar definitivamente esta asignación?\nEsta acción no se puede deshacer.')) return;
            try {
                const res  = await fetch(`/admin/asignaciones/${id}/cerrar`, {
                    method:  'PATCH',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (res.ok) { alert(data.message); location.reload(); }
                else { alert(data.message || 'Error.'); }
            } catch(e) { alert('Error de conexión.'); }
        }

        // ── Transferir ────────────────────────────────────────────────────────────

        async function openTransferirModal(id, usuario, campana, campanaId, rol) {
            document.getElementById('tr-id').value         = id;
            document.getElementById('tr-campana-id').value = campanaId;
            document.getElementById('tr-rol-actual').value = rol;
            document.getElementById('tr-info-usuario').textContent = usuario;
            document.getElementById('tr-info-campana').textContent = 'Campaña: ' + campana;
            document.getElementById('tr-info-rol').textContent     = 'Rol actual: ' + rol;
            document.getElementById('tr-fecha').value  = '{{ now()->toDateString() }}';
            document.getElementById('tr-rol').value    = rol;

            document.getElementById('modal-transferir').classList.replace('hidden', 'flex');
            await cargarSuperioresTransferir(campanaId, rol);
        }

        function closeTransferirModal() {
            document.getElementById('modal-transferir').classList.replace('flex', 'hidden');
        }

        async function onTransferirRolChange() {
            const campanaId = document.getElementById('tr-campana-id').value;
            const rol       = document.getElementById('tr-rol').value;
            await cargarSuperioresTransferir(campanaId, rol);
        }

        async function cargarSuperioresTransferir(campanaId, rol) {
            const sel = document.getElementById('tr-superior');
            sel.innerHTML = '<option value="">Cargando...</option>';
            sel.disabled  = true;

            try {
                const res        = await fetch(`/admin/asignaciones/superiores-disponibles?campana_id=${campanaId}&rol=${encodeURIComponent(rol)}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                });
                const superiores = await res.json();

                sel.innerHTML = '<option value="">Sin superior (cima de jerarquía)</option>';
                superiores.forEach(s => {
                    const opt       = document.createElement('option');
                    opt.value       = s.id;
                    opt.textContent = `${s.name} — ${s.rol}`;
                    sel.appendChild(opt);
                });
                sel.disabled = false;
            } catch(e) {
                sel.innerHTML = '<option value="">Error al cargar superiores</option>';
            }
        }

        async function submitTransferir() {
            const id         = document.getElementById('tr-id').value;
            const superiorId = document.getElementById('tr-superior').value;
            const rol        = document.getElementById('tr-rol').value;
            const fecha      = document.getElementById('tr-fecha').value;

            if (!fecha) { alert('La fecha de inicio es obligatoria.'); return; }

            try {
                const res = await fetch(`/admin/asignaciones/${id}/transferir`, {
                    method:  'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body:    JSON.stringify({ superior_id: superiorId || null, rol, fecha_inicio: fecha }),
                });
                const data = await res.json();
                if (res.ok) { closeTransferirModal(); alert(data.message); location.reload(); }
                else { alert(data.message || 'Error al transferir.'); }
            } catch(e) { alert('Error de conexión.'); }
        }
    </script>
    @endpush
</x-app-layout>
