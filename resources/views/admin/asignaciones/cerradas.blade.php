<x-app-layout>
    @section('title', 'Asignaciones Cerradas')

    @include('admin.asignaciones.partials._header', ['modoActivo' => 'cerradas'])

    @include('admin.asignaciones.partials._tabla', ['esCerradas' => true])

    @if($asignaciones->hasPages())
    <div class="mt-4">
        {{ $asignaciones->links() }}
    </div>
    @endif
</x-app-layout>
