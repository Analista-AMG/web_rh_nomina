<x-app-layout>
    @section('title', 'Control de Contratos - AMG')

    <header class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Control de Contratos</h1>
        <span class="text-xs text-gray-400 dark:text-gray-500">Ventana de 15 días</span>
    </header>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border p-4 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-circle-exclamation text-red-500 dark:text-red-400"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Vencidos sin acción</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $countVencidos }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border p-4 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-user-minus text-gray-500 dark:text-gray-400"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Bajas recientes</p>
                <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ $countBajas }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border p-4 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-clock text-amber-500 dark:text-amber-400"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Por vencer</p>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $countPorVencer }}</p>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <form id="form-alertas-filtros" method="GET" action="{{ route('contratos.alertas') }}" class="mb-4">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border p-4 shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                {{-- Empresa --}}
                <div class="relative">
                    <i class="fa-solid fa-briefcase absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    <select name="empresa" onchange="document.getElementById('form-alertas-filtros').submit()"
                            class="w-full pl-8 pr-3 py-2 text-sm rounded-lg border border-light-border dark:border-dark-border bg-white dark:bg-[#273142] text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all appearance-none">
                        <option value="">Todas las empresas</option>
                        @foreach($empresas as $e)
                            <option value="{{ $e }}" @selected($empresa === $e)>{{ $e }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Planilla --}}
                <div class="relative">
                    <i class="fa-solid fa-file-invoice absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    <select name="planilla_id" onchange="document.getElementById('form-alertas-filtros').submit()"
                            class="w-full pl-8 pr-3 py-2 text-sm rounded-lg border border-light-border dark:border-dark-border bg-white dark:bg-[#273142] text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all appearance-none">
                        <option value="">Todas las planillas</option>
                        @foreach($planillas as $p)
                            <option value="{{ $p->id }}" @selected($planillaId == $p->id)>{{ $p->nombre_planilla }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Centro de Costo --}}
                <div class="relative">
                    <i class="fa-solid fa-building absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    <select name="centro_costo_id" onchange="document.getElementById('form-alertas-filtros').submit()"
                            class="w-full pl-8 pr-3 py-2 text-sm rounded-lg border border-light-border dark:border-dark-border bg-white dark:bg-[#273142] text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all appearance-none">
                        <option value="">Todos los centros</option>
                        @foreach($centrosCosto as $cc)
                            <option value="{{ $cc->id }}" @selected($centroCostoId == $cc->id)>{{ $cc->nombre_centro_costo }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Familia --}}
                <div class="relative">
                    <i class="fa-solid fa-layer-group absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    <select name="familia_id" onchange="document.getElementById('form-alertas-filtros').submit()"
                            class="w-full pl-8 pr-3 py-2 text-sm rounded-lg border border-light-border dark:border-dark-border bg-white dark:bg-[#273142] text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all appearance-none">
                        <option value="">Todas las familias</option>
                        @foreach($familias as $f)
                            <option value="{{ $f->id }}" @selected($familiaId == $f->id)>{{ $f->nombre_familia }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Nombre --}}
                <div class="relative flex gap-2">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                        <input type="text" name="nombre" id="filtro-alertas-nombre" value="{{ $nombre }}"
                               placeholder="Buscar por nombre..."
                               class="w-full pl-8 pr-3 py-2 text-sm rounded-lg border border-light-border dark:border-dark-border bg-transparent text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all">
                    </div>
                    @if($nombre || $empresa || $planillaId || $centroCostoId || $familiaId)
                        <a href="{{ route('contratos.alertas', ['tab' => $tab]) }}"
                           class="px-3 py-2 rounded-lg border border-light-border dark:border-dark-border text-gray-500 dark:text-gray-400 text-sm hover:bg-gray-50 dark:hover:bg-[#1e2836] transition-colors flex-shrink-0">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <script>
        (function () {
            let timer;
            const input = document.getElementById('filtro-alertas-nombre');
            if (input) {
                input.addEventListener('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(() => document.getElementById('form-alertas-filtros').submit(), 500);
                });
            }
        })();
    </script>

    {{-- Tabs + botón exportar --}}
    <div class="mb-4 flex items-end justify-between border-b border-light-border dark:border-dark-border">
    <div class="flex gap-1">
        @php
            $tabs = [
                'vencidos'   => ['label' => 'Vencidos sin acción', 'count' => $countVencidos,  'color' => 'red'],
                'bajas'      => ['label' => 'Bajas recientes',     'count' => $countBajas,      'color' => 'gray'],
                'por_vencer' => ['label' => 'Por vencer',          'count' => $countPorVencer,  'color' => 'amber'],
            ];
        @endphp
        @foreach($tabs as $key => $t)
            <a href="{{ route('contratos.alertas', ['tab' => $key]) }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px
                   {{ $tab === $key
                       ? 'border-primary text-primary'
                       : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300' }}">
                {{ $t['label'] }}
                @if($t['count'] > 0)
                    <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold
                        {{ $tab === $key ? 'bg-primary/10 text-primary' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">
                        {{ $t['count'] }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>

    </div>

    {{-- Tabla --}}
    @php
        $esBajas     = $tab === 'bajas';
        $esVencidos  = $tab === 'vencidos';
        $esPorVencer = $tab === 'por_vencer';
    @endphp

    @if($filas->isEmpty())
        <div class="bg-white dark:bg-[#273142] rounded-xl border border-light-border dark:border-dark-border shadow-sm py-16 text-center">
            <i class="fa-solid fa-circle-check text-4xl text-green-400 dark:text-green-500 mb-3"></i>
            <p class="text-gray-500 dark:text-gray-400 font-medium">Sin registros en esta sección.</p>
        </div>
    @else
        <div class="overflow-x-auto px-4 pb-2">
            <table class="w-full text-sm" style="border-collapse: separate; border-spacing: 0 4px;">
                <thead>
                    <tr>
                        <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-left">Colaborador</th>
                        <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">
                            {{ $esBajas ? 'Fecha Baja' : 'Fin Contrato' }}
                        </th>
                        <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Días</th>
                        <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-left">Planilla</th>
                        <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-left">Centro de Costo</th>
                        <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-left">Familia</th>
                        <th class="px-6 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($filas as $contrato)
                        @php
                            $fechaRef = $esBajas
                                ? $contrato->fecha_renuncia
                                : $contrato->fin_contrato;

                            $dias = $hoy->diffInDays($fechaRef, false);

                            $persona         = $contrato->persona;
                            $nombreCompleto  = trim(($persona->apellido_paterno ?? '') . ' ' . ($persona->apellido_materno ?? '') . ', ' . ($persona->nombres ?? ''));
                            $iniciales       = mb_substr($persona->apellido_paterno ?? '?', 0, 1, 'UTF-8')
                                             . mb_substr(explode(' ', trim($persona->nombres ?? '?'))[0], 0, 1, 'UTF-8');
                        @endphp
                        <tr class="group transition-all duration-300 transform hover:scale-[1.01] hover:shadow-xl hover:z-10 cursor-pointer">
                            {{-- Colaborador --}}
                            <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-left rounded-l-xl border-y border-l border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold flex-shrink-0">
                                        {{ $iniciales }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-800 dark:text-white leading-tight">{{ $nombreCompleto }}</span>
                                        <span class="text-[13px] text-gray-500 font-bold mt-0.5 font-mono">{{ $persona->numero_documento ?? '—' }}</span>
                                    </div>
                                </div>
                            </td>

                            {{-- Fecha --}}
                            <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-center border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm font-medium
                                {{ $esVencidos ? 'text-red-600 dark:text-red-400' : ($esPorVencer ? 'text-amber-600 dark:text-amber-400' : 'text-gray-600 dark:text-gray-300') }}">
                                {{ $fechaRef?->format('d/m/Y') ?? '—' }}
                            </td>

                            {{-- Días --}}
                            <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-center border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                                @if($esPorVencer)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                        en {{ abs($dias) }}d
                                    </span>
                                @elseif($esVencidos)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                        hace {{ abs($dias) }}d
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400">
                                        hace {{ abs($dias) }}d
                                    </span>
                                @endif
                            </td>

                            {{-- Planilla --}}
                            <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-600 dark:text-gray-300 border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                                {{ $contrato->planilla?->nombre_planilla ?? '—' }}
                            </td>

                            {{-- Centro de Costo --}}
                            <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-600 dark:text-gray-300 border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                                {{ $contrato->centroCosto?->nombre_centro_costo ?? '—' }}
                            </td>

                            {{-- Familia --}}
                            <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-600 dark:text-gray-300 border-y border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                                {{ $contrato->familia?->nombre_familia ?? '—' }}
                            </td>

                            {{-- Acción --}}
                            <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-center rounded-r-xl border-y border-r border-light-border dark:border-dark-border group-hover:bg-gray-50 dark:group-hover:bg-[#323d4d] transition-all duration-300 shadow-sm">
                                <a href="{{ route('contratos.index', ['search_doc' => $persona->numero_documento]) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-primary/10 text-primary hover:bg-primary/20 transition-colors">
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                    Ver contrato
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginación + exportar --}}
        @php
            $exportParams = array_filter([
                'tab'             => $tab,
                'empresa'         => $empresa,
                'planilla_id'     => $planillaId,
                'centro_costo_id' => $centroCostoId,
                'familia_id'      => $familiaId,
                'nombre'          => $nombre,
            ]);
        @endphp
        <div class="mt-4 flex items-center justify-between">
            <div class="flex-1 flex justify-center">
                @if($filas->hasPages())
                    {{ $filas->links('vendor.pagination.tailwind') }}
                @endif
            </div>
            <a href="{{ route('contratos.alertas.exportar', $exportParams) }}"
               class="ml-4 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-xs font-medium hover:bg-green-100 dark:hover:bg-green-900/40 transition shrink-0">
                <i class="fa-solid fa-file-excel text-sm"></i>
                Exportar Excel
            </a>
        </div>
    @endif

</x-app-layout>
