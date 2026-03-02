<x-app-layout>
    @section('title', 'Equipos — Préstamos')

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <header class="mb-6 flex items-center justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Equipos — Préstamos</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gestión de préstamos temporales entre supervisores</p>
        </div>
        <div class="flex items-center gap-3">
            @if($totalPendientes > 0)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 text-sm font-medium rounded-lg">
                <i class="fa-solid fa-clock-rotate-left"></i>
                {{ $totalPendientes }} pendiente{{ $totalPendientes > 1 ? 's' : '' }} de aprobación
            </span>
            @endif
            <button type="button" onclick="abrirModalSolicitar()"
                class="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-300 hover:border-primary hover:text-primary transition-colors cursor-pointer bg-white dark:bg-[#273142]">
                <i class="fa-solid fa-arrow-down-to-bracket"></i>
                Solicitar colaborador
            </button>
            <button type="button" onclick="abrirModalPrestar()"
                class="btn btn-primary flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-arrow-up-from-bracket"></i>
                Prestar colaborador
            </button>
        </div>
    </header>

    {{-- ── Tabs ─────────────────────────────────────────────────────────────── --}}
    <div class="mb-4 border-b border-light-border dark:border-dark-border">
        <nav class="flex gap-1" id="tabs-nav">
            @php
                $tabs = [
                    ['key' => 'activos',    'label' => 'Activos',    'count' => $activos->count(),    'color' => 'green'],
                    ['key' => 'pendientes', 'label' => 'Pendientes', 'count' => $pendientes->count(), 'color' => 'amber'],
                    ['key' => 'proximos',   'label' => 'Próximos',   'count' => $proximos->count(),   'color' => 'blue'],
                    ['key' => 'historial',  'label' => 'Historial',  'count' => $historial->count(),  'color' => 'gray'],
                ];
                $firstTab = 'pendientes';
                if ($pendientes->isEmpty()) $firstTab = 'activos';
                if ($activos->isEmpty() && $pendientes->isEmpty()) $firstTab = 'proximos';
            @endphp
            @foreach($tabs as $tab)
            <button type="button"
                id="tab-btn-{{ $tab['key'] }}"
                onclick="switchTab('{{ $tab['key'] }}')"
                class="tab-btn flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer
                       {{ $tab['key'] === $firstTab
                          ? 'border-primary text-primary bg-primary/5'
                          : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-primary' }}">
                {{ $tab['label'] }}
                @if($tab['count'] > 0)
                <span class="text-xs px-1.5 py-0.5 rounded-full font-semibold
                    {{ $tab['key'] === 'pendientes' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300'
                      : ($tab['key'] === 'activos' ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300'
                      : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400') }}">
                    {{ $tab['count'] }}
                </span>
                @endif
            </button>
            @endforeach
        </nav>
    </div>

    {{-- ── Panel ACTIVOS ───────────────────────────────────────────────────── --}}
    <div id="panel-activos" class="{{ $firstTab !== 'activos' ? 'hidden' : '' }}">
        @if($activos->isEmpty())
            @include('equipos.partials.empty', ['mensaje' => 'No hay préstamos activos hoy.', 'icon' => 'fa-people-arrows'])
        @else
            @include('equipos.partials.tabla-prestamos', ['coleccion' => $activos, 'modo' => 'activos', 'userId' => $userId, 'puedeAprobar' => $puedeAprobar])
        @endif
    </div>

    {{-- ── Panel PENDIENTES ─────────────────────────────────────────────────── --}}
    <div id="panel-pendientes" class="{{ $firstTab !== 'pendientes' ? 'hidden' : '' }}">
        @if($pendientes->isEmpty())
            @include('equipos.partials.empty', ['mensaje' => 'No hay préstamos pendientes de aprobación.', 'icon' => 'fa-inbox'])
        @else
            @include('equipos.partials.tabla-prestamos', ['coleccion' => $pendientes, 'modo' => 'pendientes', 'userId' => $userId, 'puedeAprobar' => $puedeAprobar])
        @endif
    </div>

    {{-- ── Panel PRÓXIMOS ───────────────────────────────────────────────────── --}}
    <div id="panel-proximos" class="{{ $firstTab !== 'proximos' ? 'hidden' : '' }}">
        @if($proximos->isEmpty())
            @include('equipos.partials.empty', ['mensaje' => 'No hay préstamos próximos programados.', 'icon' => 'fa-calendar-days'])
        @else
            @include('equipos.partials.tabla-prestamos', ['coleccion' => $proximos, 'modo' => 'proximos', 'userId' => $userId, 'puedeAprobar' => $puedeAprobar])
        @endif
    </div>

    {{-- ── Panel HISTORIAL ─────────────────────────────────────────────────── --}}
    <div id="panel-historial" class="{{ $firstTab !== 'historial' ? 'hidden' : '' }}">
        @if($historial->isEmpty())
            @include('equipos.partials.empty', ['mensaje' => 'Sin historial de préstamos.', 'icon' => 'fa-clock-rotate-left'])
        @else
            @include('equipos.partials.tabla-prestamos', ['coleccion' => $historial, 'modo' => 'historial', 'userId' => $userId, 'puedeAprobar' => $puedeAprobar])
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         MODAL: PRESTAR colaborador
    ══════════════════════════════════════════════════════════════════════════ --}}
    <x-ui.modal-shell id="modal-prestar" max-width="640px">
        <x-ui.modal-header modal-id="modal-prestar" title="Prestar colaborador"
            icon="fa-arrow-up-from-bracket" icon-class="text-primary" />

        <x-ui.modal-section label="Colaborador y destino" icon="fa-users">
            <div class="grid grid-cols-1 gap-4">
                <x-forms.field label="Colaborador a prestar">
                    <x-forms.select id="prestar-empleado">
                        <option value="">— Selecciona —</option>
                        @foreach($misColaboradores as $persona)
                        <option value="{{ $persona->id }}">
                            {{ $persona->nombre_corto ?? '—' }}
                        </option>
                        @endforeach
                    </x-forms.select>
                    @if($misColaboradores->isEmpty())
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                        No tienes colaboradores asignados en tu jerarquía.
                    </p>
                    @endif
                </x-forms.field>

                <x-forms.field label="Supervisor / área destino">
                    <x-forms.select id="prestar-destino">
                        <option value="">— Selecciona —</option>
                        @foreach($supervisores as $sup)
                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
            </div>
        </x-ui.modal-section>

        <x-ui.modal-section label="Período del préstamo" icon="fa-calendar-range">
            <div class="grid grid-cols-2 gap-4">
                <x-forms.field label="Fecha inicio">
                    <x-forms.text-input type="date" id="prestar-fecha-inicio" />
                </x-forms.field>
                <x-forms.field label="Fecha fin">
                    <x-forms.text-input type="date" id="prestar-fecha-fin" />
                </x-forms.field>
            </div>
        </x-ui.modal-section>

        <x-ui.modal-section label="Motivo (opcional)" icon="fa-comment-dots">
            <textarea id="prestar-motivo" rows="2"
                placeholder="Describe brevemente el motivo del préstamo..."
                class="form-input w-full resize-none"></textarea>
        </x-ui.modal-section>

        <div id="prestar-error" class="hidden mx-5 mb-3 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2"></div>

        <x-ui.modal-footer modal-id="modal-prestar" cancel-label="Cancelar">
            <x-slot name="acciones">
                <x-forms.primary-button id="btn-prestar-submit" onclick="submitPrestar()">
                    <i class="fa-solid fa-arrow-up-from-bracket mr-1"></i> Prestar
                </x-forms.primary-button>
            </x-slot>
        </x-ui.modal-footer>
    </x-ui.modal-shell>

    {{-- ═══════════════════════════════════════════════════════════════════════
         MODAL: SOLICITAR colaborador
    ══════════════════════════════════════════════════════════════════════════ --}}
    <x-ui.modal-shell id="modal-solicitar" max-width="640px">
        <x-ui.modal-header modal-id="modal-solicitar" title="Solicitar colaborador"
            icon="fa-arrow-down-to-bracket" icon-class="text-primary" />

        <x-ui.modal-section label="Colaborador a solicitar" icon="fa-user-check">
            <div class="grid grid-cols-1 gap-4">
                <x-forms.field label="Buscar colaborador">
                    <div class="relative">
                        <input type="text" id="solicitar-search"
                            placeholder="Escribe el nombre..."
                            autocomplete="off"
                            oninput="filtrarPersonas(this.value)"
                            class="form-input w-full pr-8" />
                        <i class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    </div>
                    <div id="solicitar-lista"
                        class="mt-1 border border-gray-200 dark:border-dark-border rounded-lg max-h-48 overflow-y-auto hidden">
                        @foreach($personasActivas as $p)
                        <div class="persona-item px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-[#1b2431] cursor-pointer transition-colors"
                             data-id="{{ $p['persona_id'] }}"
                             data-nombre="{{ $p['nombre'] }}"
                             onclick="seleccionarPersona({{ $p['persona_id'] }}, '{{ addslashes($p['nombre']) }}')">
                            {{ $p['nombre'] }}
                        </div>
                        @endforeach
                        <div id="solicitar-sin-resultados" class="hidden px-3 py-2 text-sm text-gray-400">Sin resultados.</div>
                    </div>
                </x-forms.field>

                <div id="solicitar-seleccionado" class="hidden rounded-lg bg-primary/5 border border-primary/20 px-3 py-2 flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-800 dark:text-white" id="solicitar-nombre-display"></span>
                    <button type="button" onclick="limpiarSeleccionPersona()" class="text-gray-400 hover:text-red-500 cursor-pointer">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <input type="hidden" id="solicitar-empleado-id" />
            </div>
        </x-ui.modal-section>

        <x-ui.modal-section label="Período del préstamo" icon="fa-calendar-range">
            <div class="grid grid-cols-2 gap-4">
                <x-forms.field label="Fecha inicio">
                    <x-forms.text-input type="date" id="solicitar-fecha-inicio" />
                </x-forms.field>
                <x-forms.field label="Fecha fin">
                    <x-forms.text-input type="date" id="solicitar-fecha-fin" />
                </x-forms.field>
            </div>
        </x-ui.modal-section>

        <x-ui.modal-section label="Motivo (opcional)" icon="fa-comment-dots">
            <textarea id="solicitar-motivo" rows="2"
                placeholder="Describe brevemente el motivo de la solicitud..."
                class="form-input w-full resize-none"></textarea>
        </x-ui.modal-section>

        <div id="solicitar-error" class="hidden mx-5 mb-3 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2"></div>

        <x-ui.modal-footer modal-id="modal-solicitar" cancel-label="Cancelar">
            <x-slot name="acciones">
                <x-forms.primary-button id="btn-solicitar-submit" onclick="submitSolicitar()">
                    <i class="fa-solid fa-paper-plane mr-1"></i> Enviar solicitud
                </x-forms.primary-button>
            </x-slot>
        </x-ui.modal-footer>
    </x-ui.modal-shell>

    {{-- ═══════════════════════════════════════════════════════════════════════
         MODAL: RECHAZAR
    ══════════════════════════════════════════════════════════════════════════ --}}
    <x-ui.modal-shell id="modal-rechazar" max-width="480px">
        <x-ui.modal-header modal-id="modal-rechazar" title="Rechazar préstamo"
            icon="fa-ban" icon-class="text-red-500" />

        <x-ui.modal-section label="Motivo del rechazo" icon="fa-comment-dots">
            <textarea id="rechazar-motivo" rows="3"
                placeholder="Opcional: explica el motivo del rechazo..."
                class="form-input w-full resize-none"></textarea>
        </x-ui.modal-section>

        <input type="hidden" id="rechazar-prestamo-id" />

        <x-ui.modal-footer modal-id="modal-rechazar" cancel-label="Cancelar">
            <x-slot name="acciones">
                <x-forms.danger-button id="btn-rechazar-submit" onclick="submitRechazar()">
                    <i class="fa-solid fa-ban mr-1"></i> Rechazar
                </x-forms.danger-button>
            </x-slot>
        </x-ui.modal-footer>
    </x-ui.modal-shell>

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── Tabs ───────────────────────────────────────────────────────────────────
const TAB_KEYS = ['activos', 'pendientes', 'proximos', 'historial'];

function switchTab(key) {
    TAB_KEYS.forEach(k => {
        const btn   = document.getElementById('tab-btn-' + k);
        const panel = document.getElementById('panel-' + k);
        const activo = k === key;
        btn.classList.toggle('border-primary', activo);
        btn.classList.toggle('text-primary', activo);
        btn.classList.toggle('bg-primary/5', activo);
        btn.classList.toggle('border-transparent', !activo);
        btn.classList.toggle('text-gray-500', !activo);
        btn.classList.toggle('dark:text-gray-400', !activo);
        panel.classList.toggle('hidden', !activo);
    });
}

// ── Modal helpers ──────────────────────────────────────────────────────────
function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
function setError(id, msg) {
    const el = document.getElementById(id);
    if (msg) { el.textContent = msg; el.classList.remove('hidden'); }
    else      { el.classList.add('hidden'); }
}
function setLoading(btnId, loading, label) {
    const btn = document.getElementById(btnId);
    btn.disabled = loading;
    btn.innerHTML = loading
        ? '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Procesando...'
        : label;
}

// ── Modal PRESTAR ──────────────────────────────────────────────────────────
function abrirModalPrestar() {
    document.getElementById('prestar-empleado').value     = '';
    document.getElementById('prestar-destino').value      = '';
    document.getElementById('prestar-fecha-inicio').value = '{{ $hoy }}';
    document.getElementById('prestar-fecha-fin').value    = '';
    document.getElementById('prestar-motivo').value       = '';
    setError('prestar-error', '');
    setLoading('btn-prestar-submit', false, '<i class="fa-solid fa-arrow-up-from-bracket mr-1"></i> Prestar');
    openModal('modal-prestar');
}

async function submitPrestar() {
    const empleadoId = document.getElementById('prestar-empleado').value;
    const destinoId  = document.getElementById('prestar-destino').value;
    const fechaIni   = document.getElementById('prestar-fecha-inicio').value;
    const fechaFin   = document.getElementById('prestar-fecha-fin').value;
    const motivo     = document.getElementById('prestar-motivo').value;

    if (!empleadoId) return setError('prestar-error', 'Selecciona un colaborador.');
    if (!destinoId)  return setError('prestar-error', 'Selecciona el supervisor destino.');
    if (!fechaIni)   return setError('prestar-error', 'Indica la fecha de inicio.');
    if (!fechaFin)   return setError('prestar-error', 'Indica la fecha de fin.');
    if (fechaFin < fechaIni) return setError('prestar-error', 'La fecha fin debe ser igual o posterior al inicio.');

    setError('prestar-error', '');
    setLoading('btn-prestar-submit', true, '');

    try {
        const res  = await fetch('{{ route("equipos.prestamos.crear") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                accion: 'prestar',
                empleado_id: parseInt(empleadoId),
                supervisor_destino_id: parseInt(destinoId),
                fecha_inicio: fechaIni,
                fecha_fin: fechaFin,
                motivo,
            }),
        });
        const data = await res.json();
        if (data.success) { closeModal('modal-prestar'); location.reload(); }
        else setError('prestar-error', data.error ?? 'Error al crear el préstamo.');
    } catch {
        setError('prestar-error', 'Error de conexión.');
    }
    setLoading('btn-prestar-submit', false, '<i class="fa-solid fa-arrow-up-from-bracket mr-1"></i> Prestar');
}

// ── Modal SOLICITAR ────────────────────────────────────────────────────────
function abrirModalSolicitar() {
    document.getElementById('solicitar-search').value       = '';
    document.getElementById('solicitar-empleado-id').value  = '';
    document.getElementById('solicitar-nombre-display').textContent = '';
    document.getElementById('solicitar-fecha-inicio').value = '{{ $hoy }}';
    document.getElementById('solicitar-fecha-fin').value    = '';
    document.getElementById('solicitar-motivo').value       = '';
    document.getElementById('solicitar-seleccionado').classList.add('hidden');
    document.getElementById('solicitar-lista').classList.add('hidden');
    setError('solicitar-error', '');
    setLoading('btn-solicitar-submit', false, '<i class="fa-solid fa-paper-plane mr-1"></i> Enviar solicitud');
    openModal('modal-solicitar');
}

function filtrarPersonas(q) {
    const norm  = q.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const items = document.querySelectorAll('#solicitar-lista .persona-item');
    const lista = document.getElementById('solicitar-lista');
    let   visibles = 0;

    items.forEach(el => {
        const nombre = el.dataset.nombre.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        const match  = nombre.includes(norm);
        el.style.display = match ? '' : 'none';
        if (match) visibles++;
    });

    document.getElementById('solicitar-sin-resultados').classList.toggle('hidden', visibles > 0);
    lista.classList.toggle('hidden', norm.length < 2);
}

function seleccionarPersona(id, nombre) {
    document.getElementById('solicitar-empleado-id').value      = id;
    document.getElementById('solicitar-nombre-display').textContent = nombre;
    document.getElementById('solicitar-seleccionado').classList.remove('hidden');
    document.getElementById('solicitar-lista').classList.add('hidden');
    document.getElementById('solicitar-search').value = '';
}

function limpiarSeleccionPersona() {
    document.getElementById('solicitar-empleado-id').value = '';
    document.getElementById('solicitar-seleccionado').classList.add('hidden');
}

async function submitSolicitar() {
    const empleadoId = document.getElementById('solicitar-empleado-id').value;
    const fechaIni   = document.getElementById('solicitar-fecha-inicio').value;
    const fechaFin   = document.getElementById('solicitar-fecha-fin').value;
    const motivo     = document.getElementById('solicitar-motivo').value;

    if (!empleadoId) return setError('solicitar-error', 'Selecciona un colaborador.');
    if (!fechaIni)   return setError('solicitar-error', 'Indica la fecha de inicio.');
    if (!fechaFin)   return setError('solicitar-error', 'Indica la fecha de fin.');
    if (fechaFin < fechaIni) return setError('solicitar-error', 'La fecha fin debe ser igual o posterior al inicio.');

    setError('solicitar-error', '');
    setLoading('btn-solicitar-submit', true, '');

    try {
        const res  = await fetch('{{ route("equipos.prestamos.crear") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                accion: 'solicitar',
                empleado_id: parseInt(empleadoId),
                fecha_inicio: fechaIni,
                fecha_fin: fechaFin,
                motivo,
            }),
        });
        const data = await res.json();
        if (data.success) { closeModal('modal-solicitar'); location.reload(); }
        else setError('solicitar-error', data.error ?? 'Error al enviar la solicitud.');
    } catch {
        setError('solicitar-error', 'Error de conexión.');
    }
    setLoading('btn-solicitar-submit', false, '<i class="fa-solid fa-paper-plane mr-1"></i> Enviar solicitud');
}

// ── Aprobar ────────────────────────────────────────────────────────────────
async function aprobar(id) {
    if (!confirm('¿Aprobar este préstamo? Se actualizará el equipo_dia para todo el rango.')) return;
    try {
        const res  = await fetch(`/equipos/prestamos/${id}/aprobar`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.error ?? 'Error al aprobar.');
    } catch { alert('Error de conexión.'); }
}

// ── Rechazar ───────────────────────────────────────────────────────────────
function abrirModalRechazar(id) {
    document.getElementById('rechazar-prestamo-id').value = id;
    document.getElementById('rechazar-motivo').value      = '';
    setLoading('btn-rechazar-submit', false, '<i class="fa-solid fa-ban mr-1"></i> Rechazar');
    openModal('modal-rechazar');
}

async function submitRechazar() {
    const id     = document.getElementById('rechazar-prestamo-id').value;
    const motivo = document.getElementById('rechazar-motivo').value;

    setLoading('btn-rechazar-submit', true, '');
    try {
        const res  = await fetch(`/equipos/prestamos/${id}/rechazar`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ motivo_rechazo: motivo }),
        });
        const data = await res.json();
        if (data.success) { closeModal('modal-rechazar'); location.reload(); }
        else alert(data.error ?? 'Error al rechazar.');
    } catch { alert('Error de conexión.'); }
    setLoading('btn-rechazar-submit', false, '<i class="fa-solid fa-ban mr-1"></i> Rechazar');
}

// ── Cancelar ───────────────────────────────────────────────────────────────
async function cancelar(id) {
    if (!confirm('¿Cancelar esta solicitud pendiente?')) return;
    try {
        const res  = await fetch(`/equipos/prestamos/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.error ?? 'Error al cancelar.');
    } catch { alert('Error de conexión.'); }
}

// Cerrar modales con backdrop
['modal-prestar','modal-solicitar','modal-rechazar'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});
</script>
@endpush

</x-app-layout>
