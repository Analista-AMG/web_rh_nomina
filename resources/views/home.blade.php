<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="text-center mb-10">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Bienvenido al Sistema de Gestión RRHH</h1>
                <p class="text-gray-500 mt-2">Selecciona un módulo para comenzar</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-8 justify-items-center">

                @can('personas.view')
                <a href="{{ route('personas.index') }}" class="group w-full bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-8 transition-all hover:shadow-lg hover:-translate-y-1 border-b-4 border-blue-500">
                    <div class="flex flex-col items-center">
                        <div class="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-blue-200 transition-colors">
                            <i class="fa-solid fa-users text-2xl text-blue-600"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Personas</h2>
                        <p class="text-gray-500 text-center mt-2 text-sm">Administra el padrón de empleados, datos personales y documentos.</p>
                    </div>
                </a>
                @endcan

                @can('contratos.view')
                <a href="{{ route('contratos.index') }}" class="group w-full bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-8 transition-all hover:shadow-lg hover:-translate-y-1 border-b-4 border-yellow-500">
                    <div class="flex flex-col items-center">
                        <div class="h-16 w-16 bg-green-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-green-200 transition-colors">
                            <i class="fa-solid fa-file-contract text-2xl text-green-600"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Contratos</h2>
                        <p class="text-gray-500 text-center mt-2 text-sm">Gestiona altas, bajas, renovaciones y condiciones laborales.</p>
                    </div>
                </a>
                @endcan

                @can('asistencia.view')
                <a href="{{ route('asistencia.index') }}" class="group w-full bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-8 transition-all hover:shadow-lg hover:-translate-y-1 border-b-4 border-teal-500">
                    <div class="flex flex-col items-center">
                        <div class="h-16 w-16 bg-teal-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-teal-200 transition-colors">
                            <i class="fa-solid fa-calendar-check text-2xl text-teal-600"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Asistencia</h2>
                        <p class="text-gray-500 text-center mt-2 text-sm">Registra y controla la asistencia diaria del personal.</p>
                    </div>
                </a>
                @endcan

                @canany(['equipos.view', 'equipos.manage', 'equipos.approve'])
                <a href="{{ route('equipos.index') }}" class="group w-full bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-8 transition-all hover:shadow-lg hover:-translate-y-1 border-b-4 border-cyan-500">
                    <div class="flex flex-col items-center">
                        <div class="h-16 w-16 bg-cyan-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-cyan-200 transition-colors">
                            <i class="fa-solid fa-people-group text-2xl text-cyan-600"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Equipos</h2>
                        <p class="text-gray-500 text-center mt-2 text-sm">Gestiona la asignación diaria de colaboradores por supervisor.</p>
                    </div>
                </a>
                @endcanany

                @can('adicionales.view')
                <a href="{{ route('adicionales.index') }}" class="group w-full bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-8 transition-all hover:shadow-lg hover:-translate-y-1 border-b-4 border-emerald-500">
                    <div class="flex flex-col items-center">
                        <div class="h-16 w-16 bg-emerald-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-emerald-200 transition-colors">
                            <i class="fa-solid fa-hand-holding-dollar text-2xl text-emerald-600"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Adicionales</h2>
                        <p class="text-gray-500 text-center mt-2 text-sm">Gestiona bonos, descuentos, movilidad y reintegros.</p>
                    </div>
                </a>
                @endcan

                @can('calculos.view')
                <a href="{{ route('calculos.index') }}" class="group w-full bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-8 transition-all hover:shadow-lg hover:-translate-y-1 border-b-4 border-orange-500">
                    <div class="flex flex-col items-center">
                        <div class="h-16 w-16 bg-orange-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-orange-200 transition-colors">
                            <i class="fa-solid fa-calculator text-2xl text-orange-600"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Cálculos</h2>
                        <p class="text-gray-500 text-center mt-2 text-sm">Ejecuta cálculos de remuneraciones y proyecciones.</p>
                    </div>
                </a>
                @endcan

                @can('dashboard.view')
                <a href="{{ route('dashboard') }}" class="group w-full bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-8 transition-all hover:shadow-lg hover:-translate-y-1 border-b-4 border-purple-500">
                    <div class="flex flex-col items-center">
                        <div class="h-16 w-16 bg-purple-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-purple-200 transition-colors">
                            <i class="fa-solid fa-chart-pie text-2xl text-purple-600"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Dashboard</h2>
                        <p class="text-gray-500 text-center mt-2 text-sm">Visualiza métricas clave, KPIs y reportes ejecutivos.</p>
                    </div>
                </a>
                @endcan

            </div>

            <div class="mt-12 flex justify-center gap-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors flex items-center gap-2 text-sm font-medium cursor-pointer">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
