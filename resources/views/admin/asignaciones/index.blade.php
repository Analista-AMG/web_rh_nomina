<x-app-layout>
    @section('title', 'Asignaciones')

    @php
        $nivelRol   = ['Colaborador' => 1, 'Supervisor' => 2, 'Coordinador' => 3, 'Jefe Operaciones' => 4];
        $pendientes = $statsPendientes;
        $aprobadas  = $statsAprobadas;
        $rechazadas = $statsRechazadas;
    @endphp

    <header class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Asignaciones</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Gestión de asignaciones por campaña</p>
        </div>
        <div class="flex items-center gap-3">
            @if($puedeVerSinAsignacion)
            <a href="{{ $mostrarSinAsignacion ? route('admin.asignaciones.index') : route('admin.asignaciones.index', ['sin_asignacion' => 1]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border transition
                      {{ $mostrarSinAsignacion
                         ? 'bg-orange-500 text-white border-orange-500'
                         : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-orange-400 hover:text-orange-500' }}">
                <i class="fa-solid fa-user-slash text-xs"></i>
                Sin asignación
                @if($countSinAsignacion > 0)
                <span class="inline-flex items-center justify-center w-4 h-4 rounded-full text-[10px] font-bold
                    {{ $mostrarSinAsignacion ? 'bg-white text-orange-500' : 'bg-orange-100 text-orange-600 dark:bg-orange-900/40 dark:text-orange-400' }}">
                    {{ $countSinAsignacion }}
                </span>
                @endif
            </a>
            @endif
            <a href="{{ $mostrarCerradas ? route('admin.asignaciones.index') : route('admin.asignaciones.index', ['cerradas' => 1]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border transition
                      {{ $mostrarCerradas
                         ? 'bg-gray-700 text-white border-gray-600'
                         : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-primary hover:text-primary' }}">
                <i class="fa-solid fa-lock text-xs"></i>
                {{ $mostrarCerradas ? 'Ver activas' : 'Ver cerradas' }}
            </a>
            @if(!$mostrarCerradas && !$mostrarSinAsignacion)
            <button onclick="openCreateModal()"
                class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/80 transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-plus"></i> Nueva Asignación
            </button>
            @endif
        </div>
    </header>

    {{-- Stats --}}
    @if(!$mostrarCerradas && !$mostrarSinAsignacion)
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
    @if(!$mostrarCerradas && !$mostrarSinAsignacion)
    <form id="form-filtros-asignaciones" method="GET" action="{{ route('admin.asignaciones.index') }}" class="flex gap-3 mb-4 flex-wrap">
        <input type="hidden" name="cerradas" value="0">
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
    @endif

    {{-- Tabla Sin Asignación --}}
    @if($mostrarSinAsignacion)

    {{-- Filtros sin asignación --}}
    <form id="form-sin-asignacion" method="GET" action="{{ route('admin.asignaciones.index') }}" class="mb-4">
        <input type="hidden" name="sin_asignacion" value="1">
        <div class="flex gap-3 items-center flex-wrap">

            {{-- Dropdown multi-select: Planilla --}}
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

            {{-- Dropdown multi-select: Centro de Costo --}}
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

                {{-- Inputs ocultos --}}
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

            {{-- Dropdown multi-select: Familia --}}
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

                {{-- Inputs ocultos --}}
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

            {{-- Limpiar filtros --}}
            @if(!empty($filtroPlanilla) || !empty($filtroCentro) || !empty($filtroFamilia))
                <a href="{{ route('admin.asignaciones.index', ['sin_asignacion' => '1']) }}"
                   class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            @endif
        </div>
    </form>

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
                            {{ $row->contrato?->planilla?->nombre_planilla ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                            {{ $row->contrato?->centroCosto?->nombre_centro_costo ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                            {{ $row->contrato?->familia?->nombre_familia ?? '—' }}
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
    @endif

    {{-- Tabla --}}
    @if(!$mostrarSinAsignacion)
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
                            @if($a->puede_editar_propia_asistencia)
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

    {{-- Paginación --}}
    @if(!$mostrarSinAsignacion && $asignaciones->hasPages())
    <div class="mt-4">
        {{ $asignaciones->links() }}
    </div>
    @endif
    @endif

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
                            @if($miNivelMax > 4)<option value="Jefe Operaciones">Jefe Operaciones</option>@endif
                            @if($miNivelMax > 3)<option value="Coordinador">Coordinador</option>@endif
                            @if($miNivelMax > 2)<option value="Supervisor">Supervisor</option>@endif
                            @if($miNivelMax > 1)<option value="Colaborador">Colaborador</option>@endif
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Fecha Inicio <span class="text-red-500">*</span></label>
                        <input type="date" id="create-fecha" class="form-input w-full text-sm" value="{{ now()->toDateString() }}" min="{{ now()->subDays(20)->toDateString() }}">
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
                        <div class="relative">
                            <input type="text" id="create-usuario-search"
                                class="form-input w-full text-sm"
                                placeholder="Selecciona una campaña primero..."
                                autocomplete="off" disabled>
                            <input type="hidden" id="create-usuario">
                            <ul id="usuario-dropdown"
                                class="absolute z-50 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded shadow-lg max-h-48 overflow-y-auto hidden text-sm mt-1">
                            </ul>
                        </div>
                        <div id="usuario-chips" class="flex flex-wrap gap-1 mt-1 hidden"></div>
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

    {{-- ── Modal Editar ────────────────────────────────────────────────────── --}}
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
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                        Activo desde
                    </label>
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


    @push('scripts')
    <script>
        const csrf     = document.querySelector('meta[name="csrf-token"]').content;
        const miNivel  = {{ $miNivelMax }};
        const nivelRol = { 'Colaborador': 1, 'Supervisor': 2, 'Coordinador': 3, 'Jefe Operaciones': 4 };

        function openCreateModal() {
            document.getElementById('create-rol').value    = '';
            document.getElementById('create-fecha').value  = '{{ now()->toDateString() }}';
            resetCampanaSelect();
            resetUsuarioSelect();
            resetSuperiorSelect();
            hideNotes();
            document.getElementById('modal-create').classList.replace('hidden', 'flex');
        }

        function openCreateModalPreFilled(userId, userName, userDoc) {
            openCreateModal();
            // Pre-seleccionar el usuario (se muestra como chip, usuario solo elige rol/campaña)
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

        let usuariosDisponibles = [];
        let usuariosSeleccionados = [];  // [{ id, name, numero_documento }]
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

            // Para compatibilidad con submitCreate (single user): usar el primero
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

            if (lista.length === 0) { dd.innerHTML = '<li class="px-3 py-2 text-gray-400 dark:text-gray-500">Sin resultados</li>'; dd.classList.remove('hidden'); return; }

            dd.innerHTML = '';
            lista.forEach(u => {
                const li       = document.createElement('li');
                li.className   = 'px-3 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200';
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
            const hint    = document.getElementById('campana-hint');

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
                usuariosDisponibles = await res.json();
                usuariosSeleccionados = [];
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
                        body:    JSON.stringify({
                            campana_id:   campanaId,
                            rol:          rol,
                            user_id:      u.id,
                            superior_id:  superiorId || null,
                            fecha_inicio: fecha,
                        }),
                    });
                    const data = await res.json();
                    if (!res.ok) errores.push(`${u.name}: ${data.message || 'Error'}`);
                } catch(e) { errores.push(`${u.name}: Error de conexión`); }
            }

            closeCreateModal();
            if (errores.length) alert('Errores:\n' + errores.join('\n'));
            location.reload();
        }

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

        // ── Modal EDITAR ──────────────────────────────────────────────────────
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
            const id           = document.getElementById('edit-id').value;
            const wrap         = document.getElementById('edit-superior-wrap');
            const sinSuperior  = wrap.classList.contains('hidden');
            const superiorId  = sinSuperior ? null : document.getElementById('edit-superior').value;
            const puedeEditar = document.getElementById('edit-puede-editar-propia').checked;
            const fechaInicio = document.getElementById('edit-fecha-inicio').value;

            try {
                const res  = await fetch(`/admin/asignaciones/${id}/editar`, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ superior_id: superiorId || null, puede_editar_propia_asistencia: puedeEditar, fecha_inicio: fechaInicio }),
                });
                const data = await res.json();
                if (res.ok) { closeEditarModal(); location.reload(); }
                else { alert(data.message || 'Error.'); }
            } catch(e) { alert('Error de conexión.'); }
        }

        document.getElementById('modal-editar').addEventListener('click', function(e) {
            if (e.target === this) closeEditarModal();
        });

        // Buscador por nombre con debounce
        (function () {
            const input = document.getElementById('search-asignacion-nombre');
            if (!input) return;
            let timer = null;
            input.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    document.getElementById('form-filtros-asignaciones').submit();
                }, 800);
            });
        })();
    </script>
    @endpush
</x-app-layout>
