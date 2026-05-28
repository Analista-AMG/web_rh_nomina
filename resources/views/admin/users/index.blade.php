<x-app-layout>
    @section('title', 'Gestión de Usuarios')

    <header class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Gestión de Usuarios</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Administra usuarios y sus roles</p>
    </header>

    {{-- Filtros --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <a href="{{ request()->fullUrlWithQuery(['con_rol' => '1', 'page' => 1]) }}"
           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border transition
                  {{ $conRol
                     ? 'bg-primary text-white border-primary'
                     : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-primary hover:text-primary' }}">
            <i class="fa-solid fa-user-tag text-xs"></i> Con rol
        </a>
        <a href="{{ request()->fullUrlWithQuery(['con_rol' => '0', 'page' => 1]) }}"
           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border transition
                  {{ !$conRol
                     ? 'bg-primary text-white border-primary'
                     : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-primary hover:text-primary' }}">
            <i class="fa-solid fa-users text-xs"></i> Todos
        </a>

        <form method="GET" action="{{ request()->url() }}" class="flex items-center gap-1.5">
            <input type="hidden" name="con_rol" value="{{ $conRol ? '1' : '0' }}">
            <input type="text" name="doc" value="{{ request('doc') }}"
                   placeholder="Buscar por N° doc..."
                   class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 w-48">
            @if(request('doc'))
            <a href="{{ request()->fullUrlWithQuery(['doc' => '', 'page' => 1]) }}"
               class="p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition" title="Limpiar">
                <i class="fa-solid fa-times text-xs"></i>
            </a>
            @endif
        </form>

        <span class="text-xs text-gray-400 dark:text-gray-500 ml-1">
            {{ $users->total() }} usuario(s)
        </span>
    </div>

    <!-- Tabla de Usuarios -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="border-b dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Usuario</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Email</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Roles Actuales</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700" data-user-id="{{ $user->id }}">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold">
                                        {{ substr($user->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                                        @if($user->id === auth()->id())
                                        <span class="text-xs text-blue-600 dark:text-blue-400">(Tú)</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $user->email }}
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-1 user-roles">
                                    @forelse($user->roles as $role)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                        {{ $role->name }}
                                        @if($user->id !== auth()->id() && !($user->hasRole('Administrador') && !auth()->user()->hasRole('Administrador')))
                                        <button type="button" onclick="removeRole({{ $user->id }}, '{{ $role->name }}')" class="hover:text-red-600">
                                            <i class="fa-solid fa-times text-xs"></i>
                                        </button>
                                        @endif
                                    </span>
                                    @empty
                                    <span class="text-xs text-gray-400">Sin roles asignados</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if($user->id === auth()->id())
                                    <span class="text-xs text-gray-400">No puedes modificarte</span>
                                @elseif($user->hasRole('Administrador') && !auth()->user()->hasRole('Administrador'))
                                    <span class="text-xs text-gray-400 italic">Protegido</span>
                                @else
                                <button onclick="openAssignRoleModal({{ $user->id }}, '{{ $user->name }}')" class="px-3 py-1 bg-primary text-white rounded text-sm hover:bg-primary/80 transition">
                                    <i class="fa-solid fa-user-tag mr-1"></i> Asignar Rol
                                </button>
                                <button onclick="viewPermissions({{ $user->id }})" class="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 transition ml-2">
                                    <i class="fa-solid fa-key mr-1"></i> Ver Permisos
                                </button>
                                @role('Administrador')
                                <button onclick="resetPassword({{ $user->id }}, '{{ addslashes($user->name) }}')" class="px-3 py-1 bg-amber-500 text-white rounded text-sm hover:bg-amber-600 transition ml-2" title="Resetear contraseña a password123">
                                    <i class="fa-solid fa-rotate-left mr-1"></i> Reset Pass
                                </button>
                                @php $tieneTablero = $user->can('contratos.confirmar.tablero'); @endphp
                                <button
                                    onclick="toggleTablero({{ $user->id }}, {{ $tieneTablero ? 'true' : 'false' }})"
                                    data-tablero-btn="{{ $user->id }}"
                                    class="px-3 py-1 rounded text-sm transition ml-2 {{ $tieneTablero ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-200' }}"
                                    title="Acceso al tablero de confirmaciones">
                                    <i class="fa-solid fa-table-columns mr-1"></i>
                                    {{ $tieneTablero ? 'Tablero ✓' : 'Tablero' }}
                                </button>
                                @endrole
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($users->hasPages())
            <div class="mt-4">
                {{ $users->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Modal: Asignar Rol -->
    <div id="assign-role-modal" class="fixed inset-0 z-50 hidden" role="dialog">
        <div class="fixed inset-0 bg-gray-900/60" onclick="closeAssignRoleModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Asignar Rol</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Usuario: <strong id="modal-user-name"></strong></p>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Selecciona un rol:</label>
                    <select id="role-select" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white px-3 py-2">
                        <option value="">-- Selecciona --</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-3">
                    <button onclick="closeAssignRoleModal()" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white rounded hover:bg-gray-400 transition">
                        Cancelar
                    </button>
                    <button onclick="assignRole()" class="px-4 py-2 bg-primary text-white rounded hover:bg-primary/80 transition">
                        Asignar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Ver Permisos -->
    <div id="permissions-modal" class="fixed inset-0 z-50 hidden" role="dialog">
        <div class="fixed inset-0 bg-gray-900/60" onclick="closePermissionsModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Permisos del Usuario</h3>

                <div id="permissions-list" class="mb-4 max-h-96 overflow-y-auto">
                    <!-- Se llenará con JavaScript -->
                </div>

                <div class="flex justify-end">
                    <button onclick="closePermissionsModal()" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white rounded hover:bg-gray-400 transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let currentUserId = null;

        function openAssignRoleModal(userId, userName) {
            currentUserId = userId;
            document.getElementById('modal-user-name').textContent = userName;
            document.getElementById('assign-role-modal').classList.remove('hidden');
        }

        function closeAssignRoleModal() {
            document.getElementById('assign-role-modal').classList.add('hidden');
            document.getElementById('role-select').value = '';
        }

        async function assignRole() {
            const role = document.getElementById('role-select').value;
            if (!role) {
                alert('Selecciona un rol');
                return;
            }

            try {
                const response = await fetch(`/admin/users/${currentUserId}/assign-role`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ role })
                });

                const result = await response.json();

                if (response.ok) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.error || 'Error al asignar rol');
                }
            } catch (error) {
                console.error(error);
                alert('Error de conexión');
            }
        }

        async function removeRole(userId, roleName) {
            if (!confirm(`¿Remover el rol "${roleName}"?`)) return;

            try {
                const response = await fetch(`/admin/users/${userId}/remove-role`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ role: roleName })
                });

                const result = await response.json();

                if (response.ok) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.error || 'Error al remover rol');
                }
            } catch (error) {
                console.error(error);
                alert('Error de conexión');
            }
        }

        async function viewPermissions(userId) {
            try {
                const response = await fetch(`/admin/users/${userId}/permissions`);
                const result = await response.json();

                const permissionsList = document.getElementById('permissions-list');

                if (result.permissions.length === 0) {
                    permissionsList.innerHTML = '<p class="text-gray-500">Sin permisos asignados</p>';
                } else {
                    permissionsList.innerHTML = '<div class="grid grid-cols-2 gap-2">' +
                        result.permissions.map(p => `<span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded text-sm">${p}</span>`).join('') +
                        '</div>';
                }

                document.getElementById('permissions-modal').classList.remove('hidden');
            } catch (error) {
                console.error(error);
                alert('Error al cargar permisos');
            }
        }

        function closePermissionsModal() {
            document.getElementById('permissions-modal').classList.add('hidden');
        }

        async function toggleTablero(userId, tieneAcceso) {
            const accion = tieneAcceso ? 'revocar' : 'habilitar';
            if (!confirm(`¿${accion.charAt(0).toUpperCase() + accion.slice(1)} el acceso al tablero de confirmaciones?`)) return;

            try {
                const response = await fetch(`/admin/users/${userId}/toggle-permission`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (response.ok) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.error || 'Error al modificar permiso');
                }
            } catch (error) {
                console.error(error);
                alert('Error de conexión');
            }
        }

        async function resetPassword(userId, userName) {
            if (!confirm(`¿Resetear la contraseña de "${userName}" a "password123"?`)) return;

            try {
                const response = await fetch(`/admin/users/${userId}/reset-password`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();
                alert(result.message || result.error || 'Error');
            } catch (error) {
                alert('Error de conexión');
            }
        }
    </script>
</x-app-layout>
