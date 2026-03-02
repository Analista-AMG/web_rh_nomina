<x-app-layout>
    @php
        $estado = $estado ?? request('estado', 'sin_asignacion');
        $motivo = $motivo ?? request('motivo');

        $config = match($estado) {
            'pendiente'   => [
                'icon'       => 'fa-hourglass-half',
                'icon_class' => 'text-yellow-500',
                'bg_class'   => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
                'titulo'     => 'Acceso pendiente de aprobación',
                'subtitulo'  => 'Tu solicitud de acceso está siendo revisada por un administrador.',
                'detalle'    => 'Una vez aprobada, podrás ingresar al sistema sin necesidad de hacer nada más. Te notificaremos cuando esté lista.',
            ],
            'rechazado'   => [
                'icon'       => 'fa-ban',
                'icon_class' => 'text-red-500',
                'bg_class'   => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
                'titulo'     => 'Acceso rechazado',
                'subtitulo'  => 'Tu solicitud de acceso ha sido rechazada.',
                'detalle'    => $motivo ? "Motivo: {$motivo}" : 'Comunícate con el administrador del sistema para más información.',
            ],
            'suspendido'  => [
                'icon'       => 'fa-pause-circle',
                'icon_class' => 'text-orange-500',
                'bg_class'   => 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800',
                'titulo'     => 'Acceso suspendido',
                'subtitulo'  => 'Tu asignación está temporalmente inactiva.',
                'detalle'    => 'Comunícate con tu coordinador o con el administrador del sistema para reactivar tu acceso.',
            ],
            default       => [   // sin_asignacion
                'icon'       => 'fa-user-clock',
                'icon_class' => 'text-blue-500',
                'bg_class'   => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
                'titulo'     => 'Sin asignación activa',
                'subtitulo'  => 'Aún no tienes una asignación de área o campaña en el sistema.',
                'detalle'    => 'Un administrador debe asignarte a una campaña antes de que puedas acceder. Si crees que esto es un error, comunícate con RRHH.',
            ],
        };
    @endphp

    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="w-full max-w-lg">

            {{-- Card principal --}}
            <div class="bg-light-surface dark:bg-dark-surface rounded-2xl shadow-xl overflow-hidden">

                {{-- Header con logo --}}
                <div class="px-8 pt-8 pb-6 text-center border-b border-light-border dark:border-dark-border">
                    <img src="{{ asset('img/icono_empresa.png') }}" alt="AMG" class="h-12 mx-auto mb-4 opacity-90">
                    <h1 class="text-xl font-semibold text-light-text dark:text-dark-text">Sistema de Nómina</h1>
                </div>

                {{-- Cuerpo del estado --}}
                <div class="p-8">
                    {{-- Icono del estado --}}
                    <div class="flex justify-center mb-6">
                        <div class="w-20 h-20 rounded-full {{ $config['bg_class'] }} border-2 flex items-center justify-center">
                            <i class="fa-solid {{ $config['icon'] }} text-3xl {{ $config['icon_class'] }}"></i>
                        </div>
                    </div>

                    {{-- Textos --}}
                    <h2 class="text-xl font-semibold text-center text-light-text dark:text-dark-text mb-2">
                        {{ $config['titulo'] }}
                    </h2>
                    <p class="text-center text-sm text-light-muted dark:text-dark-muted mb-2">
                        {{ $config['subtitulo'] }}
                    </p>
                    <p class="text-center text-sm text-light-muted dark:text-dark-muted">
                        {{ $config['detalle'] }}
                    </p>

                    {{-- Información del usuario --}}
                    <div class="mt-6 p-4 rounded-xl bg-light-bg dark:bg-dark-bg border border-light-border dark:border-dark-border text-sm text-center text-light-muted dark:text-dark-muted">
                        Conectado como <span class="font-medium text-light-text dark:text-dark-text">{{ Auth::user()->name }}</span>
                        &middot;
                        <span class="font-mono">{{ Auth::user()->numero_documento ?? Auth::user()->email }}</span>
                    </div>
                </div>

                {{-- Footer con acción --}}
                <div class="px-8 pb-8 flex flex-col gap-3">
                    {{-- Botón de contacto / refrescar --}}
                    @if($estado === 'pendiente' || $estado === 'sin_asignacion')
                        <button onclick="window.location.reload()"
                            class="btn btn-secondary w-full justify-center">
                            <i class="fa-solid fa-rotate-right mr-2"></i>
                            Verificar estado
                        </button>
                    @endif

                    {{-- Siempre: cerrar sesión --}}
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-danger w-full justify-center">
                            <i class="fa-solid fa-right-from-bracket mr-2"></i>
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>

            {{-- Pie de página --}}
            <p class="mt-6 text-center text-xs text-light-muted dark:text-dark-muted">
                AMG &copy; {{ date('Y') }} &middot; Recursos Humanos
            </p>
        </div>
    </div>
</x-app-layout>
