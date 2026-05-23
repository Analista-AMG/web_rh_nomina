<x-app-layout>
    @section('title', 'Asignaciones')

    @include('admin.asignaciones.partials._header', ['modoActivo' => 'normal'])

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 flex items-center gap-3">
            <i class="fa-solid fa-clock text-yellow-500 text-2xl"></i>
            <div>
                <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-400">{{ $statsPendientes }}</p>
                <p class="text-xs text-yellow-600 dark:text-yellow-500">Pendientes de aprobación</p>
            </div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-green-500 text-2xl"></i>
            <div>
                <p class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $statsAprobadas }}</p>
                <p class="text-xs text-green-600 dark:text-green-500">Aprobadas y activas</p>
            </div>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 flex items-center gap-3">
            <i class="fa-solid fa-circle-xmark text-red-500 text-2xl"></i>
            <div>
                <p class="text-2xl font-bold text-red-700 dark:text-red-400">{{ $statsRechazadas }}</p>
                <p class="text-xs text-red-600 dark:text-red-500">Rechazadas</p>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <form id="form-filtros-asignaciones" method="GET" action="{{ route('admin.asignaciones.index') }}" class="flex gap-3 mb-4 flex-wrap">
        <div class="relative">
            <i class="fa-solid fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            <input type="text" id="search-asignacion-nombre" name="search_name"
                   value="{{ $filtroNombre }}"
                   placeholder="Buscar por nombre..."
                   autocomplete="off"
                   class="form-input text-sm pl-8" style="width:220px;">
        </div>
        <select name="campana_id" onchange="this.form.submit()" class="form-input text-sm" style="width:220px;">
            <option value="">Todas las campañas</option>
            @foreach($campanas as $c)
            <option value="{{ $c->id }}" @selected($filtroCampana == $c->id)>{{ $c->nombre }}</option>
            @endforeach
        </select>
        <select name="estado_persona" onchange="this.form.submit()" class="form-input text-sm" style="width:200px;">
            <option value="">Todos los contratos</option>
            <option value="activo"    @selected($filtroEstadoPersona === 'activo')>Activo</option>
            <option value="inactivo"  @selected($filtroEstadoPersona === 'inactivo')>Inactivo</option>
            <option value="pendiente" @selected($filtroEstadoPersona === 'pendiente')>Pendiente</option>
        </select>
        @if($filtroCampana || $filtroEstadoPersona || $filtroNombre)
        <a href="{{ route('admin.asignaciones.index') }}"
           class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition"
           title="Limpiar filtros">
            <i class="fa-solid fa-xmark"></i>
        </a>
        @endif
    </form>

    @include('admin.asignaciones.partials._tabla', ['esCerradas' => false])

    @if($asignaciones->hasPages())
    <div class="mt-4">
        {{ $asignaciones->links() }}
    </div>
    @endif

    @include('admin.asignaciones.partials._modal-crear')

    {{-- Modal Editar --}}
    <div id="modal-editar" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-pencil text-indigo-500"></i> Editar Asignación
                </h2>
                <button onclick="closeEditarModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition cursor-pointer">
                    <i class="fa-solid fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg px-4 py-3 text-sm">
                    <p class="font-medium text-gray-800 dark:text-white" id="edit-info-usuario">—</p>
                </div>
                <input type="hidden" id="edit-id">
                <input type="hidden" id="edit-campana-id">
                <input type="hidden" id="edit-rol">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Activo desde</label>
                    <input type="date" id="edit-fecha-inicio" class="form-input w-full text-sm">
                </div>
                <div id="edit-superior-wrap">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                        Superior <span class="text-gray-400 font-normal">(opcional)</span>
                    </label>
                    <select id="edit-superior" class="form-input w-full text-sm">
                        <option value="">Cargando...</option>
                    </select>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="edit-puede-editar-propia" class="rounded border-gray-300 text-primary focus:ring-primary">
                    <label for="edit-puede-editar-propia" class="text-sm text-gray-600 dark:text-gray-300 cursor-pointer">
                        Puede editar su propia asistencia
                    </label>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t dark:border-gray-700">
                <button onclick="closeEditarModal()" class="btn btn-secondary text-sm px-4 py-2 cursor-pointer">Cancelar</button>
                <button onclick="submitEditar()" class="btn btn-primary text-sm px-4 py-2 cursor-pointer">
                    <i class="fa-solid fa-save mr-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Cerrar --}}
    <div id="modal-cerrar" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-lock text-gray-500"></i> Cerrar Asignación
                </h2>
                <button onclick="closeCerrarModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition cursor-pointer">
                    <i class="fa-solid fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Esta acción no se puede deshacer. Indica la fecha de cierre de la asignación.
                </p>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Fecha de cierre <span class="text-red-500">*</span></label>
                    <input type="date" id="cerrar-fecha" class="form-input w-full text-sm">
                </div>
                <input type="hidden" id="cerrar-id">
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t dark:border-gray-700">
                <button onclick="closeCerrarModal()" class="btn btn-secondary text-sm px-4 py-2 cursor-pointer">Cancelar</button>
                <button onclick="submitCerrar()" class="btn btn-danger text-sm px-4 py-2 cursor-pointer">
                    <i class="fa-solid fa-lock mr-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Rechazar --}}
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

    @push('scripts')
    <script>
        const csrf     = document.querySelector('meta[name="csrf-token"]').content;
        const miNivel  = {{ $miNivelMax }};
        const nivelRol = { 'Colaborador': 1, 'Supervisor': 2, 'Coordinador': 3, 'Jefe Operaciones': 4 };

        // ── Modal Crear ───────────────────────────────────────────────────────

        function openCreateModal() {
            document.getElementById('create-rol').value   = '';
            document.getElementById('create-fecha').value = '{{ now()->toDateString() }}';
            resetCampanaSelect();
            resetUsuarioSelect();
            resetSuperiorSelect();
            hideNotes();
            document.getElementById('modal-create').classList.replace('hidden', 'flex');
        }

        function openCreateModalPreFilled(userId, userName, userDoc) {
            openCreateModal();
            usuariosSeleccionados = [{ id: userId, name: userName, numero_documento: userDoc }];
            renderChips();
            actualizarSearchPlaceholder();
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

        let usuariosDisponibles   = [];
        let usuariosSeleccionados = [];
        let maxUsuarios = 1;

        function resetUsuarioSelect() {
            usuariosDisponibles   = [];
            usuariosSeleccionados = [];
            const search = document.getElementById('create-usuario-search');
            search.value       = '';
            search.placeholder = 'Selecciona una campaña primero...';
            search.disabled    = true;
            document.getElementById('create-usuario').value = '';
            document.getElementById('usuario-dropdown').classList.add('hidden');
            document.getElementById('usuario-dropdown').innerHTML = '';
            renderChips();
        }

        function renderChips() {
            const container = document.getElementById('usuario-chips');
            const hidden    = document.getElementById('create-usuario');
            container.innerHTML = '';

            if (usuariosSeleccionados.length === 0) {
                container.classList.add('hidden');
                hidden.value = '';
                return;
            }

            container.classList.remove('hidden');
            usuariosSeleccionados.forEach(u => {
                const chip = document.createElement('span');
                chip.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-primary/10 text-primary dark:bg-primary/20 border border-primary/30';
                chip.innerHTML = `${u.name} <button type="button" class="hover:text-red-500" data-id="${u.id}">&times;</button>`;
                chip.querySelector('button').addEventListener('click', () => {
                    usuariosSeleccionados = usuariosSeleccionados.filter(x => x.id !== u.id);
                    renderChips();
                    actualizarSearchPlaceholder();
                });
                container.appendChild(chip);
            });
            hidden.value = usuariosSeleccionados[0]?.id ?? '';
        }

        function actualizarSearchPlaceholder() {
            const search = document.getElementById('create-usuario-search');
            if (usuariosSeleccionados.length >= maxUsuarios) {
                search.placeholder = `Máximo ${maxUsuarios} usuario${maxUsuarios > 1 ? 's' : ''} seleccionado${maxUsuarios > 1 ? 's' : ''}`;
                search.disabled = true;
                search.value    = '';
            } else {
                search.placeholder = maxUsuarios > 1
                    ? `Buscar usuario (${usuariosSeleccionados.length}/${maxUsuarios})...`
                    : 'Buscar por nombre o documento...';
                search.disabled = false;
            }
        }

        function seleccionarUsuario(u) {
            if (usuariosSeleccionados.find(x => x.id === u.id)) return;
            if (usuariosSeleccionados.length >= maxUsuarios) return;
            usuariosSeleccionados.push(u);
            renderChips();
            actualizarSearchPlaceholder();
            document.getElementById('create-usuario-search').value = '';
            document.getElementById('usuario-dropdown').classList.add('hidden');
        }

        function filtrarUsuarios() {
            const search = document.getElementById('create-usuario-search');
            const dd     = document.getElementById('usuario-dropdown');
            const q      = search.value.trim().toLowerCase();

            if (!q) { dd.classList.add('hidden'); return; }

            const yaIds = usuariosSeleccionados.map(x => x.id);
            const lista = usuariosDisponibles.filter(u =>
                !yaIds.includes(u.id) &&
                (u.name.toLowerCase().includes(q) || u.numero_documento.includes(q))
            );

            if (lista.length === 0) {
                dd.innerHTML = '<li class="px-3 py-2 text-gray-400 dark:text-gray-500">Sin resultados</li>';
                dd.classList.remove('hidden');
                return;
            }

            dd.innerHTML = '';
            lista.forEach(u => {
                const li     = document.createElement('li');
                li.className = 'px-3 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200';
                li.textContent = `${u.name} (${u.numero_documento})`;
                li.addEventListener('mousedown', e => { e.preventDefault(); seleccionarUsuario(u); });
                dd.appendChild(li);
            });
            dd.classList.remove('hidden');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const search = document.getElementById('create-usuario-search');
            search.addEventListener('input', filtrarUsuarios);
            search.addEventListener('blur',  () => setTimeout(() =>
                document.getElementById('usuario-dropdown').classList.add('hidden'), 150));
        });

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

            maxUsuarios = (rol === 'Colaborador') ? 3 : 1;
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
            document.getElementById('campana-hint').classList.toggle('hidden', esJO);
            updateNotes(rol);
        }

        async function onCampanaChange() {
            const campanaId = document.getElementById('create-campana').value;
            const rol       = document.getElementById('create-rol').value;
            resetUsuarioSelect();
            resetSuperiorSelect();
            if (!campanaId) return;

            try {
                const res = await fetch(`/admin/asignaciones/usuarios-disponibles?campana_id=${campanaId}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                });
                usuariosDisponibles = await res.json();
                renderChips();
                actualizarSearchPlaceholder();
                document.getElementById('create-usuario-search').disabled = false;
                document.getElementById('create-usuario-search').focus();
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
                        opt.textContent = `${s.name} — ${s.rol} — ${s.campana}`;
                        sel.appendChild(opt);
                    });
                    sel.disabled = false;
                }
            } catch(e) { console.error(e); }
        }

        async function submitCreate() {
            const campanaId  = document.getElementById('create-campana').value;
            const rol        = document.getElementById('create-rol').value;
            const superiorId = document.getElementById('create-superior').value;
            const fecha      = document.getElementById('create-fecha').value;

            if (!campanaId || !rol || usuariosSeleccionados.length === 0 || !fecha) {
                alert('Por favor completa todos los campos obligatorios.');
                return;
            }

            const errores = [];
            for (const u of usuariosSeleccionados) {
                try {
                    const res = await fetch('/admin/asignaciones', {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body:    JSON.stringify({ campana_id: campanaId, rol, user_id: u.id, superior_id: superiorId || null, fecha_inicio: fecha }),
                    });
                    const data = await res.json();
                    if (!res.ok) errores.push(`${u.name}: ${data.message || 'Error'}`);
                } catch(e) { errores.push(`${u.name}: Error de conexión`); }
            }

            closeCreateModal();
            if (errores.length) alert('Errores:\n' + errores.join('\n'));
            location.reload();
        }

        // ── Acciones tabla ────────────────────────────────────────────────────

        async function eliminar(id, nombre) {
            if (!confirm(`¿Eliminar definitivamente la asignación de "${nombre}"? Esta acción no se puede deshacer.`)) return;
            try {
                const res  = await fetch(`/admin/asignaciones/${id}`, {
                    method:  'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (res.ok) { alert(data.message); location.reload(); }
                else { alert(data.message || 'Error al eliminar.'); }
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

        // ── Modal Rechazar ────────────────────────────────────────────────────

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

        // ── Modal Cerrar ──────────────────────────────────────────────────────

        function cerrar(id) {
            document.getElementById('cerrar-id').value    = id;
            document.getElementById('cerrar-fecha').value = '{{ now()->toDateString() }}';
            document.getElementById('modal-cerrar').classList.replace('hidden', 'flex');
        }

        function closeCerrarModal() {
            document.getElementById('modal-cerrar').classList.replace('flex', 'hidden');
        }

        async function submitCerrar() {
            const id    = document.getElementById('cerrar-id').value;
            const fecha = document.getElementById('cerrar-fecha').value;
            if (!fecha) { alert('La fecha de cierre es obligatoria.'); return; }

            try {
                const res  = await fetch(`/admin/asignaciones/${id}/cerrar`, {
                    method:  'PATCH',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body:    JSON.stringify({ fecha_fin: fecha }),
                });
                const data = await res.json();
                if (res.ok) { closeCerrarModal(); alert(data.message); location.reload(); }
                else { alert(data.message || 'Error.'); }
            } catch(e) { alert('Error de conexión.'); }
        }

        document.getElementById('modal-cerrar').addEventListener('click', function(e) {
            if (e.target === this) closeCerrarModal();
        });

        // ── Modal Editar ──────────────────────────────────────────────────────

        async function openEditarModal(id, usuario, campanaId, rol, superiorActualId, puedeEditarPropia = false, fechaInicio = '') {
            document.getElementById('edit-id').value         = id;
            document.getElementById('edit-campana-id').value = campanaId;
            document.getElementById('edit-rol').value        = rol;
            document.getElementById('edit-info-usuario').textContent = usuario;
            document.getElementById('edit-puede-editar-propia').checked = puedeEditarPropia;
            document.getElementById('edit-fecha-inicio').value = fechaInicio;

            const superiorWrap = document.getElementById('edit-superior-wrap');
            const sinSuperior  = nivelRol[rol] >= nivelRol['Jefe Operaciones'];
            superiorWrap.classList.toggle('hidden', sinSuperior);

            if (!sinSuperior) {
                const sel = document.getElementById('edit-superior');
                sel.innerHTML = '<option value="">Cargando...</option>';
                try {
                    const res  = await fetch(`/admin/asignaciones/superiores-disponibles?campana_id=${campanaId}&rol=${encodeURIComponent(rol)}`, {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                    });
                    const data = await res.json();
                    sel.innerHTML = '<option value="">Sin superior</option>';
                    data.forEach(s => {
                        const opt       = document.createElement('option');
                        opt.value       = s.id;
                        opt.textContent = `${s.name} — ${s.rol} [${s.campana}]`;
                        sel.appendChild(opt);
                    });
                    if (superiorActualId) sel.value = superiorActualId;
                } catch(e) {
                    sel.innerHTML = '<option value="">Error al cargar</option>';
                }
            }

            document.getElementById('modal-editar').classList.replace('hidden', 'flex');
        }

        function closeEditarModal() {
            document.getElementById('modal-editar').classList.replace('flex', 'hidden');
        }

        async function submitEditar() {
            const id          = document.getElementById('edit-id').value;
            const wrap        = document.getElementById('edit-superior-wrap');
            const sinSuperior = wrap.classList.contains('hidden');
            const superiorId  = sinSuperior ? null : document.getElementById('edit-superior').value;
            const puedeEditar = document.getElementById('edit-puede-editar-propia').checked;
            const fechaInicio = document.getElementById('edit-fecha-inicio').value;

            try {
                const res  = await fetch(`/admin/asignaciones/${id}/editar`, {
                    method:  'PATCH',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body:    JSON.stringify({ superior_id: superiorId || null, puede_editar_propia_asistencia: puedeEditar, fecha_inicio: fechaInicio }),
                });
                const data = await res.json();
                if (res.ok) { closeEditarModal(); location.reload(); }
                else { alert(data.message || 'Error.'); }
            } catch(e) { alert('Error de conexión.'); }
        }

        document.getElementById('modal-editar').addEventListener('click', function(e) {
            if (e.target === this) closeEditarModal();
        });

        // ── Buscador con debounce ─────────────────────────────────────────────

        (function () {
            const input = document.getElementById('search-asignacion-nombre');
            if (!input) return;
            let timer = null;
            input.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(() => document.getElementById('form-filtros-asignaciones').submit(), 800);
            });
        })();
    </script>
    @endpush
</x-app-layout>
