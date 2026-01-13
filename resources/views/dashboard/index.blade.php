<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Grid de KPIs -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <x-ui.kpi-card title="Total Empleados" :value="$metrics['empleados_total'] ?? 0" />
                <x-ui.kpi-card title="Nuevos (Mes)" :value="$metrics['nuevos_mes'] ?? 0" color="text-blue-600" />
                <x-ui.kpi-card title="Contratos Activos" :value="$metrics['contratos_activos'] ?? 0" color="text-green-600" />
            </div>

            <!-- Área de Contenido Principal (Gráficos o Tablas Recientes) -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-4">Actividad Reciente</h3>
                    <p class="text-gray-500">Aquí se mostrarán los últimos movimientos o gráficos de nómina.</p>
                    <!-- Aquí tu equipo puede insertar Chart.js o una tabla resumen -->
                </div>
            </div>

        </div>
    </div>
</x-app-layout>