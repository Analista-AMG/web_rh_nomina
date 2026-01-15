<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="text-center mb-10">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Bienvenido al Sistema de Gestión RRHH</h1>
                <p class="text-gray-500 mt-2">Selecciona un módulo para comenzar</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- Tarjeta Personas -->
                <a href="{{ route('personas.index') }}" class="group bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-8 transition-all hover:shadow-lg hover:-translate-y-1 border-b-4 border-blue-500">
                    <div class="flex flex-col items-center">
                        <div class="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-blue-200 transition-colors">
                            <i class="fa-solid fa-users text-2xl text-blue-600"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Personas</h2>
                        <p class="text-gray-500 text-center mt-2 text-sm">Administra el padrón de empleados, datos personales y documentos.</p>
                    </div>
                </a>

                <!-- Tarjeta Contratos -->
                <a href="{{ route('contratos.index') }}" class="group bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-8 transition-all hover:shadow-lg hover:-translate-y-1 border-b-4 border-yellow-500">
                    <div class="flex flex-col items-center">
                        <div class="h-16 w-16 bg-green-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-green-200 transition-colors">
                            <i class="fa-solid fa-file-contract text-2xl text-green-600"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Contratos</h2>
                        <p class="text-gray-500 text-center mt-2 text-sm">Gestiona altas, bajas, renovaciones y condiciones laborales.</p>
                    </div>
                </a>

                <!-- Tarjeta Dashboard -->
                <a href="{{ route('dashboard') }}" class="group bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-8 transition-all hover:shadow-lg hover:-translate-y-1 border-b-4 border-purple-500">
                    <div class="flex flex-col items-center">
                        <div class="h-16 w-16 bg-purple-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-purple-200 transition-colors">
                            <i class="fa-solid fa-chart-pie text-2xl text-purple-600"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Dashboard</h2>
                        <p class="text-gray-500 text-center mt-2 text-sm">Visualiza métricas clave, KPIs y reportes ejecutivos.</p>
                    </div>
                </a>

            </div>

            <!-- Acciones adicionales (Logout) -->
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
