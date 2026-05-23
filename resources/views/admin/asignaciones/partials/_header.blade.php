<header class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Asignaciones</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Gestión de asignaciones por campaña</p>
    </div>
    <div class="flex items-center gap-3">
        @if($puedeVerSinAsignacion)
        <a href="{{ $modoActivo === 'sin-asignacion' ? route('admin.asignaciones.index') : route('admin.asignaciones.sin-asignacion') }}"
           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border transition
                  {{ $modoActivo === 'sin-asignacion'
                     ? 'bg-orange-500 text-white border-orange-500'
                     : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-orange-400 hover:text-orange-500' }}">
            <i class="fa-solid fa-user-slash text-xs"></i>
            Sin asignación
            @if($countSinAsignacion > 0)
            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full text-[10px] font-bold
                {{ $modoActivo === 'sin-asignacion' ? 'bg-white text-orange-500' : 'bg-orange-100 text-orange-600 dark:bg-orange-900/40 dark:text-orange-400' }}">
                {{ $countSinAsignacion }}
            </span>
            @endif
        </a>
        @endif
        <a href="{{ $modoActivo === 'cerradas' ? route('admin.asignaciones.index') : route('admin.asignaciones.cerradas') }}"
           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border transition
                  {{ $modoActivo === 'cerradas'
                     ? 'bg-gray-700 text-white border-gray-600'
                     : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-primary hover:text-primary' }}">
            <i class="fa-solid fa-lock text-xs"></i>
            {{ $modoActivo === 'cerradas' ? 'Ver activas' : 'Ver cerradas' }}
        </a>
        @if($modoActivo === 'normal')
        <button onclick="openCreateModal()"
            class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/80 transition flex items-center gap-2 cursor-pointer">
            <i class="fa-solid fa-plus"></i> Nueva Asignación
        </button>
        @endif
    </div>
</header>
