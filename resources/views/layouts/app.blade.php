<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'AMG')</title>
    <link rel="icon" href="{{ asset('img/icono_empresa.png') }}" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <style>
        /* Estilos críticos para el comportamiento del Sidebar */
        .resizer {
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            height: 100%;
            cursor: col-resize;
            z-index: 100;
            transition: background 0.2s;
        }
        .resizer:hover, .sidebar-resizing .resizer {
            background: var(--color-primary, #da7756);
        }
        
        /* Clase para deshabilitar transiciones durante el arrastre (Anti-Lag) */
        .no-transition {
            transition: none !important;
        }
        
        /* Clase para colapsar suavemente el texto */
        .sidebar-text {
            transition: opacity 0.2s, width 0.2s;
            white-space: nowrap;
            overflow: hidden;
        }
        
        .collapsed .sidebar-text {
            opacity: 0;
            width: 0;
            display: none;
        }
        
        .collapsed .logo-section {
            justify-content: center;
        }
        
        .collapsed .nav-link {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }
        
        /* Asegurar que el icono sea el centro siempre */
        .nav-link i {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 40px; /* Ancho fijo para el área del icono */
        }

        .collapsed .nav-link i {
            margin: 0 !important;
        }

        .collapsed .theme-toggle-container {
            justify-content: center;
        }
        
        /* Centrado dinámico durante el resize */
        .logo-section, .nav-link, .theme-toggle-container button {
            display: flex;
            align-items: center;
            transition: padding 0.2s;
        }
    </style>
</head>
<body class="bg-light-bg text-light-text dark:bg-dark-bg dark:text-dark-text font-sans antialiased transition-colors duration-300 overflow-hidden">
    
    <div class="flex h-screen w-full">
        @if(!request()->routeIs('home'))
            <!-- Sidebar Component -->
            <x-layout.sidebar />
        @endif

        <!-- Main Content (Ocupa el resto) -->
        <main class="flex-1 overflow-y-auto bg-light-bg dark:bg-dark-bg p-4 sm:p-8 relative z-10 {{ request()->routeIs('home') ? 'flex flex-col justify-center' : '' }}">
            {{ $slot }}
        </main>
    </div>
    
    @stack('modals')
    @stack('scripts')
</body>
</html>
