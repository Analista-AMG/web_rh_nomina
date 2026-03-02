<x-app-layout>
    @section('title', 'Campañas')

    @php
        $empresasJson = $empresas->map(fn($e) => ['id' => $e->id, 'nombre' => $e->nombre])->values();
    @endphp

    <header class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Campañas</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Gestiona campañas y sub-campañas</p>
        </div>
        @if($puedeCrearRaiz)
        <button onclick="openRaizModal()"
            class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/80 transition flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Nueva Campaña
        </button>
        @endif
    </header>

    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="border-b dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Campaña</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Empresa</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Fecha Inicio</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase text-center">Estado</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campanas as $campana)
                            {{-- Fila raíz --}}
                            <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 bg-gray-50/50 dark:bg-gray-700/30">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        @if($campana->subcampanas->count())
                                        <button onclick="toggleSubs({{ $campana->id }})"
                                            class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-primary transition-colors"
                                            title="Expandir / Colapsar">
                                            <i id="chevron-{{ $campana->id }}" class="fa-solid fa-chevron-right text-xs transition-transform duration-200"></i>
                                        </button>
                                        @else
                                        <span class="w-5"></span>
                                        @endif
                                        <i class="fa-solid fa-bullhorn text-primary text-xs w-4 text-center"></i>
                                        <span class="font-semibold text-gray-900 dark:text-white text-sm">{{ $campana->nombre }}</span>
                                        @if($campana->subcampanas->count())
                                        <span class="text-xs text-gray-400 dark:text-gray-500">({{ $campana->subcampanas->count() }} subs)</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $campana->empresa->nombre ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $campana->fecha_inicio?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($campana->fecha_fin)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                        <i class="fa-solid fa-lock mr-1 text-xs"></i> Cerrada
                                    </span>
                                    @elseif($campana->activo)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        Activa
                                    </span>
                                    @else
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                        Pausada
                                    </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if(!$campana->fecha_fin)
                                    {{-- Sub-campaña --}}
                                    <button onclick="openSubModal({{ $campana->id }}, '{{ addslashes($campana->nombre) }}')"
                                        class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 transition"
                                        title="Nueva sub-campaña">
                                        <i class="fa-solid fa-code-branch"></i>
                                    </button>
                                    {{-- Editar --}}
                                    <button onclick="openEditModal({{ $campana->id }}, '{{ addslashes($campana->nombre) }}', '{{ $campana->fecha_inicio?->format('Y-m-d') }}')"
                                        class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 transition ml-1">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    {{-- Pausa / Reactivar --}}
                                    <button onclick="toggleActivo({{ $campana->id }}, {{ $campana->activo ? 'true' : 'false' }})"
                                        class="px-3 py-1 rounded text-sm transition ml-1
                                        {{ $campana->activo ? 'bg-yellow-500 text-white hover:bg-yellow-600' : 'bg-green-600 text-white hover:bg-green-700' }}"
                                        title="{{ $campana->activo ? 'Pausar' : 'Reactivar' }}">
                                        <i class="fa-solid {{ $campana->activo ? 'fa-pause' : 'fa-play' }}"></i>
                                    </button>
                                    {{-- Cerrar definitivamente (JO+) --}}
                                    @if($puedeCrearRaiz)
                                    <button onclick="cerrarCampana({{ $campana->id }}, '{{ addslashes($campana->nombre) }}')"
                                        class="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700 transition ml-1"
                                        title="Cerrar definitivamente">
                                        <i class="fa-solid fa-lock"></i>
                                    </button>
                                    @endif
                                    @else
                                    {{-- Cerrada: solo editar (por si se equivocaron en nombre) --}}
                                    <button onclick="openEditModal({{ $campana->id }}, '{{ addslashes($campana->nombre) }}', '{{ $campana->fecha_inicio?->format('Y-m-d') }}')"
                                        class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 transition">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    @endif
                                </td>
                            </tr>

                            {{-- Filas sub-campañas (ocultas por defecto) --}}
                            @foreach($campana->subcampanas as $sub)
                            <tr data-sub-of="{{ $campana->id }}"
                                class="hidden border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-2 pl-10">
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 dark:text-gray-500 text-xs select-none">└─</span>
                                        <i class="fa-solid fa-diagram-project text-gray-400 dark:text-gray-500 text-xs w-4 text-center"></i>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $sub->nombre }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $campana->empresa->nombre ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $sub->fecha_inicio?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-center">
                                    @if($sub->fecha_fin)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                        <i class="fa-solid fa-lock mr-1 text-xs"></i> Cerrada
                                    </span>
                                    @elseif($sub->activo)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        Activa
                                    </span>
                                    @else
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                        Pausada
                                    </span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-center whitespace-nowrap">
                                    @if(!$sub->fecha_fin)
                                    <button onclick="openEditModal({{ $sub->id }}, '{{ addslashes($sub->nombre) }}', '{{ $sub->fecha_inicio?->format('Y-m-d') }}')"
                                        class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 transition">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button onclick="toggleActivo({{ $sub->id }}, {{ $sub->activo ? 'true' : 'false' }})"
                                        class="px-3 py-1 rounded text-sm transition ml-1
                                        {{ $sub->activo ? 'bg-yellow-500 text-white hover:bg-yellow-600' : 'bg-green-600 text-white hover:bg-green-700' }}"
                                        title="{{ $sub->activo ? 'Pausar' : 'Reactivar' }}">
                                        <i class="fa-solid {{ $sub->activo ? 'fa-pause' : 'fa-play' }}"></i>
                                    </button>
                                    @if($puedeCrearRaiz)
                                    <button onclick="cerrarCampana({{ $sub->id }}, '{{ addslashes($sub->nombre) }}')"
                                        class="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700 transition ml-1"
                                        title="Cerrar definitivamente">
                                        <i class="fa-solid fa-lock"></i>
                                    </button>
                                    @endif
                                    @else
                                    <button onclick="openEditModal({{ $sub->id }}, '{{ addslashes($sub->nombre) }}', '{{ $sub->fecha_inicio?->format('Y-m-d') }}')"
                                        class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 transition">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach

                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">
                                Sin campañas registradas
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal: Nueva Campaña Raíz (Admin y JO) --}}
    @if($puedeCrearRaiz)
    <div id="modal-raiz" class="fixed inset-0 z-50 hidden" role="dialog">
        <div class="fixed inset-0 bg-gray-900/60" onclick="closeRaizModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-5">Nueva Campaña</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Empresa *</label>
                        <select id="raiz-empresa"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <option value="">— Selecciona empresa —</option>
                            @foreach($empresas as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre *</label>
                        <input type="text" id="raiz-nombre" maxlength="200" placeholder="Nombre de la campaña"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha de inicio</label>
                        <input type="date" id="raiz-fecha"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button onclick="closeRaizModal()"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-white rounded-lg text-sm hover:bg-gray-300 transition">
                        Cancelar
                    </button>
                    <button onclick="submitRaiz()"
                        class="px-4 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary/80 transition">
                        Crear
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal: Nueva Sub-Campaña --}}
    <div id="modal-sub" class="fixed inset-0 z-50 hidden" role="dialog">
        <div class="fixed inset-0 bg-gray-900/60" onclick="closeSubModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Nueva Sub-Campaña</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    Campaña padre: <span id="sub-padre-nombre" class="font-medium text-gray-700 dark:text-gray-200"></span>
                </p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre *</label>
                        <input type="text" id="sub-nombre" maxlength="200" placeholder="Nombre de la sub-campaña"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha de inicio</label>
                        <input type="date" id="sub-fecha"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button onclick="closeSubModal()"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-white rounded-lg text-sm hover:bg-gray-300 transition">
                        Cancelar
                    </button>
                    <button onclick="submitSub()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                        Crear Sub-Campaña
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Editar --}}
    <div id="modal-edit" class="fixed inset-0 z-50 hidden" role="dialog">
        <div class="fixed inset-0 bg-gray-900/60" onclick="closeEditModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-5">Editar Campaña</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre *</label>
                        <input type="text" id="edit-nombre" maxlength="200"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha de inicio</label>
                        <input type="date" id="edit-fecha"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button onclick="closeEditModal()"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-white rounded-lg text-sm hover:bg-gray-300 transition">
                        Cancelar
                    </button>
                    <button onclick="submitEdit()"
                        class="px-4 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary/80 transition">
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrf    = document.querySelector('meta[name="csrf-token"]').content;
        const esAdmin = @json($esAdmin);
        let editingId = null;
        let subParentId = null;

        // ── Campaña raíz (admin) ──────────────────────────────────────────────
        function openRaizModal() {
            document.getElementById('raiz-empresa').value = '';
            document.getElementById('raiz-nombre').value  = '';
            document.getElementById('raiz-fecha').value   = '';
            document.getElementById('modal-raiz').classList.remove('hidden');
            setTimeout(() => document.getElementById('raiz-nombre').focus(), 50);
        }
        function closeRaizModal() {
            document.getElementById('modal-raiz').classList.add('hidden');
        }
        async function submitRaiz() {
            const empresa_id = document.getElementById('raiz-empresa').value;
            const nombre     = document.getElementById('raiz-nombre').value.trim();
            if (!empresa_id) { alert('Selecciona una empresa'); return; }
            if (!nombre)     { alert('El nombre es obligatorio'); return; }

            const res = await fetch('{{ route('admin.campanas.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({
                    empresa_id: parseInt(empresa_id),
                    nombre,
                    fecha_inicio: document.getElementById('raiz-fecha').value || null,
                }),
            });
            const data = await res.json();
            if (res.ok) { alert(data.message); location.reload(); }
            else        { alert(data.message || 'Error al crear'); }
        }

        // ── Sub-campaña ───────────────────────────────────────────────────────
        function openSubModal(parentId, parentNombre) {
            subParentId = parentId;
            document.getElementById('sub-padre-nombre').textContent = parentNombre;
            document.getElementById('sub-nombre').value = '';
            document.getElementById('sub-fecha').value  = '';
            document.getElementById('modal-sub').classList.remove('hidden');
            setTimeout(() => document.getElementById('sub-nombre').focus(), 50);
        }
        function closeSubModal() {
            document.getElementById('modal-sub').classList.add('hidden');
        }
        async function submitSub() {
            const nombre = document.getElementById('sub-nombre').value.trim();
            if (!nombre) { alert('El nombre es obligatorio'); return; }

            const res = await fetch('{{ route('admin.campanas.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({
                    parent_id:    subParentId,
                    nombre,
                    fecha_inicio: document.getElementById('sub-fecha').value || null,
                }),
            });
            const data = await res.json();
            if (res.ok) { alert(data.message); location.reload(); }
            else        { alert(data.message || 'Error al crear'); }
        }

        // ── Editar ────────────────────────────────────────────────────────────
        function openEditModal(id, nombre, fecha) {
            editingId = id;
            document.getElementById('edit-nombre').value = nombre;
            document.getElementById('edit-fecha').value  = fecha || '';
            document.getElementById('modal-edit').classList.remove('hidden');
            setTimeout(() => document.getElementById('edit-nombre').focus(), 50);
        }
        function closeEditModal() {
            document.getElementById('modal-edit').classList.add('hidden');
        }
        async function submitEdit() {
            const nombre = document.getElementById('edit-nombre').value.trim();
            if (!nombre) { alert('El nombre es obligatorio'); return; }

            const res = await fetch(`/admin/campanas/${editingId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ nombre, fecha_inicio: document.getElementById('edit-fecha').value || null }),
            });
            const data = await res.json();
            if (res.ok) { alert(data.message); location.reload(); }
            else        { alert(data.message || 'Error al actualizar'); }
        }

        // ── Expandir / colapsar ───────────────────────────────────────────────
        function toggleSubs(id) {
            const rows    = document.querySelectorAll(`tr[data-sub-of="${id}"]`);
            const chevron = document.getElementById(`chevron-${id}`);
            const open    = chevron.classList.contains('rotate-90');
            rows.forEach(r => r.classList.toggle('hidden', open));
            chevron.classList.toggle('rotate-90', !open);
        }

        // ── Cerrar definitivamente (JO+) ─────────────────────────────────────
        async function cerrarCampana(id, nombre) {
            if (!confirm(`¿Cerrar definitivamente la campaña "${nombre}"?\n\nEsta acción establece la fecha de fin a hoy y no se puede deshacer desde la interfaz.`)) return;

            const res = await fetch(`/admin/campanas/${id}/cerrar`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (res.ok) { alert(data.message); location.reload(); }
            else        { alert(data.message || 'Error al cerrar'); }
        }

        // ── Toggle activo ─────────────────────────────────────────────────────
        async function toggleActivo(id, activo) {
            const accion = activo ? 'desactivar' : 'activar';
            if (!confirm(`¿${accion.charAt(0).toUpperCase() + accion.slice(1)} esta campaña?`)) return;

            const res = await fetch(`/admin/campanas/${id}/toggle`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (res.ok) { alert(data.message); location.reload(); }
            else        { alert('Error al cambiar estado'); }
        }
    </script>
</x-app-layout>
