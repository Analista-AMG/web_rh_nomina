<x-app-layout>
    @section('title', 'Empresas')

    <header class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Empresas</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Gestiona las empresas del sistema</p>
        </div>
        <button onclick="openCreateModal()"
            class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/80 transition flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Nueva Empresa
        </button>
    </header>

    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="border-b dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Nombre</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase text-center">Campañas</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Fecha Inicio</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase text-center">Estado</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($empresas as $empresa)
                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                            id="empresa-row-{{ $empresa->id }}">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-lg bg-primary/10 flex items-center justify-center">
                                        <i class="fa-solid fa-building text-primary text-sm"></i>
                                    </div>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $empresa->nombre }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                    {{ $empresa->campanas_count }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $empresa->fecha_inicio?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if($empresa->fecha_fin)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                    <i class="fa-solid fa-lock mr-1 text-xs"></i> Cerrada
                                </span>
                                @elseif($empresa->activo)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    Activa
                                </span>
                                @else
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                    Pausada
                                </span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center whitespace-nowrap">
                                @if(!$empresa->fecha_fin)
                                <button onclick="openEditModal({{ $empresa->id }}, '{{ addslashes($empresa->nombre) }}', '{{ $empresa->fecha_inicio?->format('Y-m-d') }}')"
                                    class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 transition">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="toggleActivo({{ $empresa->id }}, {{ $empresa->activo ? 'true' : 'false' }})"
                                    class="px-3 py-1 rounded text-sm transition ml-1
                                    {{ $empresa->activo ? 'bg-yellow-500 text-white hover:bg-yellow-600' : 'bg-green-600 text-white hover:bg-green-700' }}"
                                    title="{{ $empresa->activo ? 'Pausar' : 'Reactivar' }}">
                                    <i class="fa-solid {{ $empresa->activo ? 'fa-pause' : 'fa-play' }}"></i>
                                </button>
                                <button onclick="cerrarEmpresa({{ $empresa->id }}, '{{ addslashes($empresa->nombre) }}')"
                                    class="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700 transition ml-1"
                                    title="Cerrar definitivamente">
                                    <i class="fa-solid fa-lock"></i>
                                </button>
                                @else
                                <button onclick="openEditModal({{ $empresa->id }}, '{{ addslashes($empresa->nombre) }}', '{{ $empresa->fecha_inicio?->format('Y-m-d') }}')"
                                    class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 transition">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">
                                Sin empresas registradas
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Crear --}}
    <div id="create-modal" class="fixed inset-0 z-50 hidden" role="dialog">
        <div class="fixed inset-0 bg-gray-900/60" onclick="closeCreateModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-5">Nueva Empresa</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre *</label>
                        <input type="text" id="create-nombre" maxlength="200" placeholder="Ej. AMG Services S.A.C."
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha de inicio</label>
                        <input type="date" id="create-fecha"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button onclick="closeCreateModal()"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-white rounded-lg text-sm hover:bg-gray-300 transition">
                        Cancelar
                    </button>
                    <button onclick="submitCreate()"
                        class="px-4 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary/80 transition">
                        Crear Empresa
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Editar --}}
    <div id="edit-modal" class="fixed inset-0 z-50 hidden" role="dialog">
        <div class="fixed inset-0 bg-gray-900/60" onclick="closeEditModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-5">Editar Empresa</h3>

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
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        let editingId = null;

        // ── Crear ──────────────────────────────────────────────────────────────
        function openCreateModal() {
            document.getElementById('create-nombre').value = '';
            document.getElementById('create-fecha').value = '';
            document.getElementById('create-modal').classList.remove('hidden');
        }
        function closeCreateModal() {
            document.getElementById('create-modal').classList.add('hidden');
        }
        async function submitCreate() {
            const nombre = document.getElementById('create-nombre').value.trim();
            if (!nombre) { alert('El nombre es obligatorio'); return; }

            const res = await fetch('{{ route('admin.empresas.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({
                    nombre,
                    fecha_inicio: document.getElementById('create-fecha').value || null,
                }),
            });
            const data = await res.json();
            if (res.ok) { alert(data.message); location.reload(); }
            else        { alert(data.message || 'Error al crear'); }
        }

        // ── Editar ─────────────────────────────────────────────────────────────
        function openEditModal(id, nombre, fecha) {
            editingId = id;
            document.getElementById('edit-nombre').value = nombre;
            document.getElementById('edit-fecha').value  = fecha || '';
            document.getElementById('edit-modal').classList.remove('hidden');
        }
        function closeEditModal() {
            document.getElementById('edit-modal').classList.add('hidden');
        }
        async function submitEdit() {
            const nombre = document.getElementById('edit-nombre').value.trim();
            if (!nombre) { alert('El nombre es obligatorio'); return; }

            const res = await fetch(`/admin/empresas/${editingId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({
                    nombre,
                    fecha_inicio: document.getElementById('edit-fecha').value || null,
                }),
            });
            const data = await res.json();
            if (res.ok) { alert(data.message); location.reload(); }
            else        { alert(data.message || 'Error al actualizar'); }
        }

        // ── Cerrar definitivamente ────────────────────────────────────────────
        async function cerrarEmpresa(id, nombre) {
            if (!confirm(`¿Cerrar definitivamente la empresa "${nombre}"?\n\nEsta acción establece la fecha de fin a hoy y no se puede deshacer desde la interfaz.`)) return;

            const res = await fetch(`/admin/empresas/${id}/cerrar`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (res.ok) { alert(data.message); location.reload(); }
            else        { alert(data.message || 'Error al cerrar'); }
        }

        // ── Toggle activo ──────────────────────────────────────────────────────
        async function toggleActivo(id, activo) {
            const accion = activo ? 'desactivar' : 'activar';
            if (!confirm(`¿${accion.charAt(0).toUpperCase() + accion.slice(1)} esta empresa?`)) return;

            const res = await fetch(`/admin/empresas/${id}/toggle`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (res.ok) { alert(data.message); location.reload(); }
            else        { alert('Error al cambiar estado'); }
        }
    </script>
</x-app-layout>
