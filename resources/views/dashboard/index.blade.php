<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Grid de KPIs -->
            <!-- <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <x-ui.kpi-card title="Total Empleados" :value="$metrics['empleados_total'] ?? 0" />
                <x-ui.kpi-card title="Nuevos (Mes)" :value="$metrics['nuevos_mes'] ?? 0" color="text-blue-600" />
                <x-ui.kpi-card title="Contratos Activos" :value="$metrics['contratos_activos'] ?? 0" color="text-green-600" />
            </div> -->

            <!-- Power BI Dashboard -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-2 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-4">Business Intelligence - Nómina</h3>

                    <!-- Contenedor con altura fija ajustable -->
                    <div class="relative w-full" style="height: 800px;">
                        <iframe
                            title="BD_NOMINA_ALL"
                            src="https://app.powerbi.com/view?r=eyJrIjoiZmI4ZjZhZWYtZDE1MC00MWE2LWI4ZGMtYzMwZTk3MTRjYWNhIiwidCI6IjVhZTRkNjc0LWU2ZGEtNDBjMS1iNTNjLWY3NDNhNTc0OWY1ZCIsImMiOjR9"
                            frameborder="0"
                            allowFullScreen="true"
                            class="w-full h-full rounded-lg border border-gray-200 dark:border-gray-700">
                        </iframe>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>