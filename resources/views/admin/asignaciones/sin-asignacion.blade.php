<x-app-layout>
    @section('title', 'Sin Asignación')

    @include('admin.asignaciones.partials._header', ['modoActivo' => 'sin-asignacion'])

    {{-- Filtros --}}
    <form id="form-sin-asignacion" method="GET" action="{{ route('admin.asignaciones.sin-asignacion') }}" class="mb-4">
        <div class="flex gap-3 items-center flex-wrap">

            {{-- Planilla --}}
            <div x-data="{
                    open: false,
                    selected: {{ json_encode(array_map('intval', $filtroPlanilla)) }},
                    toggle(id) {
                        const idx = this.selected.indexOf(id);
                        if (idx === -1) this.selected.push(id);
                        else this.selected.splice(idx, 1);
                        this.$nextTick(() => document.getElementById('form-sin-asignacion').submit());
                    }
                 }"
                 class="relative" @click.outside="open = false">
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="planilla_id[]" :value="id">
                </template>
                <button type="button" @click="open = !open"
                        class="form-input text-sm flex items-center gap-2 cursor-pointer" style="width:210px;">
                    <i class="fa-solid fa-file-contract text-gray-400 text-xs shrink-0"></i>
                    <span class="truncate flex-1 text-left" x-text="selected.length ? selected.length + ' planilla(s)' : 'Todas las planillas'"></span>
                    <i class="fa-solid fa-chevron-down text-gray-400 text-xs shrink-0 transition-transform" :class="open && 'rotate-180'"></i>
                </button>
                <div x-show="open" x-transition
                     class="absolute z-50 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg"
                     style="min-width:210px; max-height:260px; overflow-y:auto;">
                    @forelse($planillas as $p)
                    <label class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/60 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" value="{{ $p->id }}"
                               :checked="selected.includes({{ $p->id }})"
                               @change="toggle({{ $p->id }})"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="truncate">{{ $p->nombre_planilla }}</span>
                    </label>
                    @empty
                    <p class="px-3 py-2 text-sm text-gray-400">Sin opciones</p>
                    @endforelse
                </div>
            </div>

            {{-- Centro de Costo --}}
            <div x-data="{
                    open: false,
                    selected: {{ json_encode(array_map('intval', $filtroCentro)) }},
                    toggle(id) {
                        const idx = this.selected.indexOf(id);
                        if (idx === -1) this.selected.push(id);
                        else this.selected.splice(idx, 1);
                        this.$nextTick(() => document.getElementById('form-sin-asignacion').submit());
                    }
                 }"
                 class="relative" @click.outside="open = false">
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="centro_costo_id[]" :value="id">
                </template>
                <button type="button" @click="open = !open"
                        class="form-input text-sm flex items-center gap-2 cursor-pointer" style="width:230px;">
                    <i class="fa-solid fa-building text-gray-400 text-xs shrink-0"></i>
                    <span class="truncate flex-1 text-left" x-text="selected.length ? selected.length + ' centro(s)' : 'Todos los centros'"></span>
                    <i class="fa-solid fa-chevron-down text-gray-400 text-xs shrink-0 transition-transform" :class="open && 'rotate-180'"></i>
                </button>
                <div x-show="open" x-transition
                     class="absolute z-50 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg"
                     style="min-width:230px; max-height:260px; overflow-y:auto;">
                    @forelse($centrosCosto as $cc)
                    <label class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/60 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" value="{{ $cc->id }}"
                               :checked="selected.includes({{ $cc->id }})"
                               @change="toggle({{ $cc->id }})"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="truncate">{{ $cc->nombre_centro_costo }}</span>
                    </label>
                    @empty
                    <p class="px-3 py-2 text-sm text-gray-400">Sin opciones</p>
                    @endforelse
                </div>
            </div>

            {{-- Familia --}}
            <div x-data="{
                    open: false,
                    selected: {{ json_encode(array_map('intval', $filtroFamilia)) }},
                    toggle(id) {
                        const idx = this.selected.indexOf(id);
                        if (idx === -1) this.selected.push(id);
                        else this.selected.splice(idx, 1);
                        this.$nextTick(() => document.getElementById('form-sin-asignacion').submit());
                    }
                 }"
                 class="relative" @click.outside="open = false">
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="familia_id[]" :value="id">
                </template>
                <button type="button" @click="open = !open"
                        class="form-input text-sm flex items-center gap-2 cursor-pointer" style="width:210px;">
                    <i class="fa-solid fa-layer-group text-gray-400 text-xs shrink-0"></i>
                    <span class="truncate flex-1 text-left" x-text="selected.length ? selected.length + ' familia(s)' : 'Todas las familias'"></span>
                    <i class="fa-solid fa-chevron-down text-gray-400 text-xs shrink-0 transition-transform" :class="open && 'rotate-180'"></i>
                </button>
                <div x-show="open" x-transition
                     class="absolute z-50 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg"
                     style="min-width:210px; max-height:260px; overflow-y:auto;">
                    @forelse($familias as $f)
                    <label class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/60 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" value="{{ $f->id }}"
                               :checked="selected.includes({{ $f->id }})"
                               @change="toggle({{ $f->id }})"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="truncate">{{ $f->nombre_familia }}</span>
                    </label>
                    @empty
                    <p class="px-3 py-2 text-sm text-gray-400">Sin opciones</p>
                    @endforelse
                </div>
            </div>

            @if(!empty($filtroPlanilla) || !empty($filtroCentro) || !empty($filtroFamilia))
            <a href="{{ route('admin.asignaciones.sin-asignacion') }}"
               class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <i class="fa-solid fa-xmark"></i>
            </a>
            @endif
        </div>
    </form>

    {{-- Tabla --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Colaborador</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Documento</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Contrato activo</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Planilla</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Centro de Costo</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Familia</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sinAsignacion as $row)
                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $row->user->name }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">
                                    Sin asignación
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm font-mono text-gray-600 dark:text-gray-300">
                            {{ $row->user->numero_documento ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                            @if($row->contrato)
                                {{ $row->contrato->inicio_contrato?->format('d/m/Y') }}
                                @if($row->contrato->fin_contrato)
                                    <span class="text-gray-400">→</span>
                                    {{ $row->contrato->fin_contrato?->format('d/m/Y') }}
                                @else
                                    <span class="text-gray-400">→ Indefinido</span>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                            {{ $row->movimiento?->planilla?->nombre_planilla ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                            {{ $row->movimiento?->centroCosto?->nombre_centro_costo ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                            {{ $row->movimiento?->familia?->nombre_familia ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="openCreateModalPreFilled({{ $row->user->id }}, '{{ addslashes($row->user->name) }}', '{{ $row->user->numero_documento }}')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition font-medium cursor-pointer">
                                <i class="fa-solid fa-user-plus text-[10px]"></i>
                                Asignar
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-14 text-center text-gray-400 dark:text-gray-500">
                            <i class="fa-solid fa-circle-check text-4xl mb-3 block text-green-400"></i>
                            Todos los colaboradores con contrato activo tienen asignación vigente.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('admin.asignaciones.partials._modal-crear')

    @push('scripts')
    <script>
        const csrf     = document.querySelector('meta[name="csrf-token"]').content;
        const miNivel  = {{ $miNivelMax }};
        const nivelRol = { 'Colaborador': 1, 'Supervisor': 2, 'Coordinador': 3, 'Jefe Operaciones': 4 };

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
    </script>
    @endpush
</x-app-layout>
