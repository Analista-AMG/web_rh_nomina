<x-app-layout>
    @section('title', 'Cola de Aprobación - AMG')

    <header class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Cola de Aprobación</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Solicitudes de traslado y colaboradores sin equipo</p>
        </div>
        <a href="{{ route('equipos.index') }}"
           class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-[#273142] border border-light-border dark:border-dark-border rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-primary transition-colors shadow-sm">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Mi Equipo</span>
        </a>
    </header>

    {{-- Filtro de fecha --}}
    <div class="mb-6 bg-white dark:bg-[#273142] rounded-xl p-4 shadow-sm border border-light-border dark:border-dark-border">
        <form method="GET" action="{{ route('equipos.pendientes') }}" class="flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha</label>
                <input type="date" name="fecha" value="{{ $fecha ?? '' }}"
                    class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
            </div>
            <button type="submit"
                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium cursor-pointer">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Filtrar
            </button>
            @if($fecha)
            <a href="{{ route('equipos.pendientes') }}"
               class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <i class="fa-solid fa-xmark mr-1"></i> Limpiar
            </a>
            @endif
        </form>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        {{-- Solicitudes pendientes --}}
        <div class="bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border">
            <div class="px-5 py-4 border-b border-light-border dark:border-dark-border flex items-center gap-2">
                <i class="fa-solid fa-paper-plane text-primary"></i>
                <h2 class="font-semibold text-gray-800 dark:text-white">
                    Solicitudes pendientes
                    <span class="ml-2 text-xs {{ $solicitudes->isNotEmpty() ? 'bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500' }} px-2 py-0.5 rounded-full">
                        {{ $solicitudes->count() }}
                    </span>
                </h2>
            </div>

            @if($solicitudes->isEmpty())
                <div class="px-5 py-10 text-center text-gray-400 dark:text-gray-500">
                    <i class="fa-solid fa-inbox text-3xl mb-3"></i>
                    <p class="text-sm">No hay solicitudes pendientes{{ $fecha ? ' para esta fecha' : '' }}.</p>
                </div>
            @else
                <div class="divide-y divide-light-border dark:divide-dark-border">
                    @foreach($solicitudes as $sol)
                    <div class="px-5 py-4" id="sol-{{ $sol->id_solicitud }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                    {{ $sol->contrato->persona?->nombre_corto ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Solicita:</span>
                                    {{ $sol->supervisor->name ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Equipo actual:</span>
                                    {{ $sol->prevSupervisor->name ?? '(sin equipo)' }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    <i class="fa-regular fa-calendar mr-1"></i>{{ \Carbon\Carbon::parse($sol->fecha)->format('d/m/Y') }}
                                    <span class="ml-2"><i class="fa-regular fa-clock mr-1"></i>{{ $sol->created_at->format('d/m H:i') }}</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button type="button"
                                    onclick="aprobar({{ $sol->id_solicitud }})"
                                    class="px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors cursor-pointer">
                                    <i class="fa-solid fa-check mr-1"></i> Aprobar
                                </button>
                                <button type="button"
                                    onclick="abrirModalRechazar({{ $sol->id_solicitud }}, '{{ addslashes($sol->contrato->persona?->nombre_corto ?? '') }}')"
                                    class="px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-medium rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors cursor-pointer">
                                    <i class="fa-solid fa-xmark mr-1"></i> Rechazar
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Huérfanos del día --}}
        <div class="bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border">
            <div class="px-5 py-4 border-b border-light-border dark:border-dark-border flex items-center gap-2">
                <i class="fa-solid fa-user-clock text-gray-400"></i>
                <h2 class="font-semibold text-gray-800 dark:text-white">
                    Sin equipo — {{ $fecha ? \Carbon\Carbon::parse($fecha)->format('d/m/Y') : 'Hoy' }}
                    <span class="ml-2 text-xs {{ $huerfanos->total() > 0 ? 'bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500' }} px-2 py-0.5 rounded-full">
                        {{ $huerfanos->total() }}
                    </span>
                </h2>
            </div>

            @if($huerfanos->isEmpty())
                <div class="px-5 py-10 text-center text-gray-400 dark:text-gray-500">
                    <i class="fa-solid fa-check-circle text-3xl mb-3 text-green-400"></i>
                    <p class="text-sm">Todos los colaboradores tienen equipo este día.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase border-b border-light-border dark:border-dark-border">
                                <th class="px-5 py-3 text-left font-medium">Colaborador</th>
                                <th class="px-5 py-3 text-left font-medium">Documento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-light-border dark:divide-dark-border">
                            @foreach($huerfanos as $contrato)
                            <tr class="hover:bg-orange-50 dark:hover:bg-orange-900/10 transition-colors">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-gray-800 dark:text-white">
                                        {{ $contrato->persona?->nombre_corto ?? '—' }}
                                    </p>
                                </td>
                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400 text-xs">
                                    {{ $contrato->persona->numero_documento ?? '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($huerfanos->hasPages())
                <div class="px-5 py-3 border-t border-light-border dark:border-dark-border text-xs text-gray-500 dark:text-gray-400 flex items-center justify-between">
                    <span>{{ $huerfanos->firstItem() }}–{{ $huerfanos->lastItem() }} de {{ $huerfanos->total() }}</span>
                    <div class="flex gap-1">
                        @if($huerfanos->onFirstPage())
                            <span class="px-2 py-1 rounded text-gray-300 dark:text-gray-600 cursor-default"><i class="fa-solid fa-chevron-left"></i></span>
                        @else
                            <a href="{{ $huerfanos->previousPageUrl() }}" class="px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fa-solid fa-chevron-left"></i></a>
                        @endif

                        @foreach($huerfanos->getUrlRange(1, $huerfanos->lastPage()) as $page => $url)
                            @if($page == $huerfanos->currentPage())
                                <span class="px-2 py-1 rounded bg-primary text-white font-medium">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if($huerfanos->hasMorePages())
                            <a href="{{ $huerfanos->nextPageUrl() }}" class="px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fa-solid fa-chevron-right"></i></a>
                        @else
                            <span class="px-2 py-1 rounded text-gray-300 dark:text-gray-600 cursor-default"><i class="fa-solid fa-chevron-right"></i></span>
                        @endif
                    </div>
                </div>
                @endif
            @endif
        </div>

    </div>

    {{-- Historial del día --}}
    @if($historial->total() > 0)
    <div class="mt-6 bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border">
        <div class="px-5 py-4 border-b border-light-border dark:border-dark-border flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-gray-400"></i>
            <h2 class="font-semibold text-gray-800 dark:text-white">
                Historial
                <span class="ml-2 text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 px-2 py-0.5 rounded-full">
                    {{ $historial->total() }} procesadas
                </span>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase border-b border-light-border dark:border-dark-border">
                        <th class="px-5 py-3 text-left font-medium">Colaborador</th>
                        <th class="px-5 py-3 text-left font-medium">Equipo anterior</th>
                        <th class="px-5 py-3 text-left font-medium">Solicitante</th>
                        <th class="px-5 py-3 text-left font-medium">Estado</th>
                        <th class="px-5 py-3 text-left font-medium">Procesado por</th>
                        <th class="px-5 py-3 text-left font-medium">Fechas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-light-border dark:divide-dark-border">
                    @foreach($historial as $h)
                    <tr class="hover:bg-gray-50 dark:hover:bg-[#1b2431] transition-colors">
                        <td class="px-5 py-3">
                            <p class="font-medium text-gray-800 dark:text-white">
                                {{ $h->contrato->persona?->nombre_corto ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($h->fecha)->format('d/m/Y') }}</p>
                        </td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                            {{ $h->prevSupervisor->name ?? '(sin equipo)' }}
                        </td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                            {{ $h->supervisor->name ?? '—' }}
                        </td>
                        <td class="px-5 py-3">
                            @if($h->estado === 'aprobado')
                                <span class="inline-flex items-center gap-1 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-2 py-1 rounded-full">
                                    <i class="fa-solid fa-check"></i> Aprobado
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 px-2 py-1 rounded-full">
                                    <i class="fa-solid fa-xmark"></i> Rechazado
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-300 text-xs">
                            <p class="font-medium text-gray-700 dark:text-gray-300">{{ $h->aprobadoPor->name ?? '—' }}</p>
                            @if($h->motivo_rechazo)
                                <p class="text-red-500 dark:text-red-400 italic mt-0.5">{{ $h->motivo_rechazo }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-xs whitespace-nowrap">
                            <p class="text-gray-400">
                                <i class="fa-regular fa-clock mr-1"></i>{{ $h->created_at->format('d/m/Y H:i') }}
                            </p>
                            <p class="{{ $h->estado === 'aprobado' ? 'text-green-500' : 'text-red-400' }} mt-0.5">
                                <i class="fa-regular fa-circle-check mr-1"></i>{{ $h->updated_at->format('d/m/Y H:i') }}
                            </p>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($historial->hasPages())
        <div class="px-5 py-3 border-t border-light-border dark:border-dark-border text-xs text-gray-500 dark:text-gray-400 flex items-center justify-between">
            <span>{{ $historial->firstItem() }}–{{ $historial->lastItem() }} de {{ $historial->total() }}</span>
            <div class="flex gap-1">
                @if($historial->onFirstPage())
                    <span class="px-2 py-1 rounded text-gray-300 dark:text-gray-600 cursor-default"><i class="fa-solid fa-chevron-left"></i></span>
                @else
                    <a href="{{ $historial->previousPageUrl() }}" class="px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fa-solid fa-chevron-left"></i></a>
                @endif

                @foreach($historial->getUrlRange(1, $historial->lastPage()) as $page => $url)
                    @if($page == $historial->currentPage())
                        <span class="px-2 py-1 rounded bg-primary text-white font-medium">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">{{ $page }}</a>
                    @endif
                @endforeach

                @if($historial->hasMorePages())
                    <a href="{{ $historial->nextPageUrl() }}" class="px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i class="fa-solid fa-chevron-right"></i></a>
                @else
                    <span class="px-2 py-1 rounded text-gray-300 dark:text-gray-600 cursor-default"><i class="fa-solid fa-chevron-right"></i></span>
                @endif
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Modal rechazo --}}
    <div id="modal-rechazar" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#273142] rounded-xl shadow-xl w-full max-w-sm p-6">
            <h3 class="font-semibold text-gray-800 dark:text-white text-lg mb-2">Rechazar solicitud</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Solicitud de <strong id="nombre-rechazar"></strong>
            </p>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo (opcional)</label>
                <textarea id="motivo-rechazo" rows="2" placeholder="Ej: Equipo al límite de capacidad..."
                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 resize-none"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button onclick="cerrarModalRechazar()"
                    class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                    Cancelar
                </button>
                <button id="btn-confirmar-rechazar"
                    class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 cursor-pointer">
                    Confirmar rechazo
                </button>
            </div>
        </div>
    </div>

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── Aprobar ────────────────────────────────────────────────
async function aprobar(idSolicitud) {
    if (!confirm('¿Confirmar aprobación?')) return;
    try {
        const res = await fetch(`/equipos/solicitudes/${idSolicitud}/aprobar`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            const el = document.getElementById(`sol-${idSolicitud}`);
            if (el) {
                el.innerHTML = `<p class="text-sm text-green-600 dark:text-green-400 py-2"><i class="fa-solid fa-check mr-1"></i>Aprobada correctamente</p>`;
            }
            setTimeout(() => location.reload(), 800);
        } else {
            alert(data.error ?? 'Error al aprobar.');
        }
    } catch (e) {
        alert('Error de conexión.');
    }
}

// ── Rechazar ───────────────────────────────────────────────
let idSolicitudPendiente = null;

function abrirModalRechazar(idSolicitud, nombre) {
    idSolicitudPendiente = idSolicitud;
    document.getElementById('nombre-rechazar').textContent = nombre;
    document.getElementById('motivo-rechazo').value = '';
    document.getElementById('modal-rechazar').classList.remove('hidden');
}

function cerrarModalRechazar() {
    document.getElementById('modal-rechazar').classList.add('hidden');
    idSolicitudPendiente = null;
}

document.getElementById('btn-confirmar-rechazar').addEventListener('click', async () => {
    if (!idSolicitudPendiente) return;
    const motivo = document.getElementById('motivo-rechazo').value.trim();
    try {
        const res = await fetch(`/equipos/solicitudes/${idSolicitudPendiente}/rechazar`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ motivo_rechazo: motivo })
        });
        const data = await res.json();
        if (data.success) {
            cerrarModalRechazar();
            location.reload();
        } else {
            alert(data.error ?? 'Error al rechazar.');
        }
    } catch (e) {
        alert('Error de conexión.');
    }
});
</script>
@endpush

</x-app-layout>
