<x-app-layout>
    @section('title', 'Auditoría')

    <header class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Auditoría del Sistema</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Registro de cambios realizados en el sistema</p>
    </header>

    <!-- Filtros -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('admin.audit.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Módulo</label>
                    <select name="log_name" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white text-sm">
                        <option value="">Todos</option>
                        @foreach($logNames as $name)
                            <option value="{{ $name }}" {{ request('log_name') === $name ? 'selected' : '' }}>
                                {{ ucfirst($name) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Usuario</label>
                    <select name="causer_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white text-sm">
                        <option value="">Todos</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('causer_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Desde</label>
                        <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hasta</label>
                        <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary/80 transition">
                        <i class="fa-solid fa-filter mr-1"></i> Filtrar
                    </button>
                    <a href="{{ route('admin.audit.index') }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white rounded-lg text-sm hover:bg-gray-400 transition">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    @php
        function formatAuditValue($value) {
            if (is_array($value)) return json_encode($value);
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}/', $value)) {
                return \Carbon\Carbon::parse($value)->format('d/m/Y');
            }
            return $value;
        }
    @endphp

    <!-- Tabla de Auditoría -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="border-b dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Fecha</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Usuario</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Módulo</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Acción</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Entidad</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                {{ $activity->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                {{ $activity->causer?->name ?? 'Sistema' }}
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex px-2 py-1 rounded text-xs font-medium
                                    @switch($activity->log_name)
                                        @case('usuarios') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 @break
                                        @case('personas') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 @break
                                        @case('contratos') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400 @break
                                        @case('movimientos') bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 @break
                                        @case('bajas') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 @break
                                        @case('asistencia') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 @break
                                        @default bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                    @endswitch
                                ">
                                    {{ ucfirst($activity->log_name) }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                @php
                                    $eventLabels = [
                                        'created' => ['Creado', 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'],
                                        'updated' => ['Actualizado', 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'],
                                        'deleted' => ['Eliminado', 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'],
                                    ];
                                    $label = $eventLabels[$activity->event] ?? [ucfirst($activity->event ?? 'N/A'), 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'];
                                @endphp
                                <span class="inline-flex px-2 py-1 rounded text-xs font-medium {{ $label[1] }}">
                                    {{ $label[0] }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                {{ class_basename($activity->subject_type ?? '') }} #{{ $activity->subject_id }}
                            </td>
                            <td class="px-4 py-4 text-sm">
                                <button onclick="toggleDetail({{ $activity->id }})" class="text-primary hover:text-primary/80 text-sm transition">
                                    <i class="fa-solid fa-eye mr-1"></i> Ver cambios
                                </button>
                                <div id="detail-{{ $activity->id }}" class="hidden mt-2 max-w-md">
                                    @if($activity->event === 'updated' && $activity->properties->has('old'))
                                        <div class="space-y-1">
                                            @foreach($activity->properties['attributes'] ?? [] as $key => $newValue)
                                                @php $oldValue = $activity->properties['old'][$key] ?? '-'; @endphp
                                                <div class="text-xs">
                                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $key }}:</span>
                                                    <span class="text-red-500">{{ formatAuditValue($oldValue) }}</span>
                                                    <i class="fa-solid fa-arrow-right text-gray-400 mx-1 text-[10px]"></i>
                                                    <span class="text-green-600 dark:text-green-400">{{ formatAuditValue($newValue) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif($activity->event === 'created' && $activity->properties->has('attributes'))
                                        <div class="space-y-1">
                                            @foreach($activity->properties['attributes'] ?? [] as $key => $value)
                                                <div class="text-xs">
                                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $key }}:</span>
                                                    <span class="text-green-600 dark:text-green-400">{{ formatAuditValue($value) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif($activity->event === 'deleted' && $activity->properties->has('old'))
                                        <div class="space-y-1">
                                            @foreach($activity->properties['old'] ?? [] as $key => $value)
                                                <div class="text-xs">
                                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $key }}:</span>
                                                    <span class="text-red-500">{{ formatAuditValue($value) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">{{ $activity->description }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                No se encontraron registros de auditoría.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($activities->hasPages())
            <div class="mt-4">
                {{ $activities->links() }}
            </div>
            @endif
        </div>
    </div>

    <script>
        function toggleDetail(id) {
            const el = document.getElementById('detail-' + id);
            el.classList.toggle('hidden');
        }
    </script>
</x-app-layout>
