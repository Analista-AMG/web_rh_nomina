@props(['data' => null])

@php
    if (!$data) return;
    $hayAlgo = (!$data->esRrhh && ($data->pendientes > 0 || $data->movCambio > 0))
            || ($data->esRrhh  && $data->requierenRrhh > 0);
    if (!$hayAlgo) return;

    $labelPeriodo = ucfirst(\Carbon\Carbon::parse($data->periodo . '-01')->translatedFormat('F Y'));
@endphp

{{-- Banner full-width dentro del área de contenido: negative margin compensa el padding del <main> --}}
<div class="sticky top-0 z-20 -mx-4 sm:-mx-8 -mt-4 sm:-mt-8 mb-4 sm:mb-6">

    @if(!$data->esRrhh)

        @if($data->pendientes > 0)
        <div class="flex items-center gap-3 px-4 sm:px-8 py-2.5 bg-amber-500 dark:bg-amber-600">
            <i class="fa-solid fa-bell text-white flex-shrink-0 text-sm"></i>
            <p class="text-sm text-white font-medium flex-1 leading-tight">
                Tienes
                <strong>{{ $data->pendientes }} contrato{{ $data->pendientes !== 1 ? 's' : '' }}</strong>
                pendiente{{ $data->pendientes !== 1 ? 's' : '' }} de confirmar en {{ $labelPeriodo }}.
            </p>
            <a href="{{ route('contratos.confirmaciones', ['periodo' => $data->periodo]) }}"
               class="flex-shrink-0 text-xs font-bold text-white/90 hover:text-white underline underline-offset-2 whitespace-nowrap">
                Confirmar ahora →
            </a>
        </div>
        @endif

        @if($data->movCambio > 0)
        <div class="flex items-center gap-3 px-4 sm:px-8 py-2.5 bg-blue-600 dark:bg-blue-700">
            <i class="fa-solid fa-rotate text-white flex-shrink-0 text-sm"></i>
            <p class="text-sm text-white font-medium flex-1 leading-tight">
                RRHH actualizó
                <strong>{{ $data->movCambio }} contrato{{ $data->movCambio !== 1 ? 's' : '' }}</strong>
                ya confirmado{{ $data->movCambio !== 1 ? 's' : '' }} en {{ $labelPeriodo }}. Revisa y reconfirma.
            </p>
            <a href="{{ route('contratos.confirmaciones', ['periodo' => $data->periodo]) }}"
               class="flex-shrink-0 text-xs font-bold text-white/90 hover:text-white underline underline-offset-2 whitespace-nowrap">
                Ver cambios →
            </a>
        </div>
        @endif

    @else

        @if($data->requierenRrhh > 0)
        <div class="flex items-center gap-3 px-4 sm:px-8 py-2.5 bg-red-600 dark:bg-red-700">
            <i class="fa-solid fa-triangle-exclamation text-white flex-shrink-0 text-sm"></i>
            <p class="text-sm text-white font-medium flex-1 leading-tight">
                <strong>{{ $data->requierenRrhh }} contrato{{ $data->requierenRrhh !== 1 ? 's' : '' }}</strong>
                {{ $data->requierenRrhh !== 1 ? 'están reportados' : 'está reportado' }}
                como incorrecto{{ $data->requierenRrhh !== 1 ? 's' : '' }} o con cambio rechazado en {{ $labelPeriodo }}.
            </p>
            <a href="{{ route('contratos.confirmaciones.tablero', ['periodo' => $data->periodo]) }}"
               class="flex-shrink-0 text-xs font-bold text-white/90 hover:text-white underline underline-offset-2 whitespace-nowrap">
                Ver tablero →
            </a>
        </div>
        @endif

    @endif

</div>
