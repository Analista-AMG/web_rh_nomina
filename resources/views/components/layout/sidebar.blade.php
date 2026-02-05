<aside id="sidebar" class="flex-shrink-0 bg-sidebar-theme border-r border-light-border dark:border-[#2d3748] flex flex-col relative transition-all duration-200 shadow-lg z-20" style="width: 260px;">
    
    <!-- Header -->
    <div class="h-16 flex items-center justify-between px-4 logo-section overflow-hidden transition-all duration-300">
        <!-- Logo y Texto -->
        <div class="text-base flex items-center gap-3 text-primary font-bold text-xl overflow-hidden sidebar-text">
            <i class="fa-solid fa-chart-simple text-2xl flex-shrink-0"></i>
            <span>AMG</span>
        </div>
        <!-- El botón de hamburguesa -->
        <button id="sidebar-toggle" class="text-gray-400 hover:text-primary transition-colors p-2 flex-shrink-0  dark:hover:bg-[#1b2431] flex items-center justify-center mx-auto lg:mx-0 cursor-pointer">
            <i class="fa-solid fa-bars text-2xl"></i>
        </button>
    </div>
    
    <!-- User Profile -->
    <div class="flex flex-col items-center py-6 overflow-hidden">
        <div id="avatar-container" class="w-20 h-20 rounded-full overflow-hidden mb-4 border-2 border-primary p-1 transition-all duration-300 flex-shrink-0">
            <img class="w-full h-full rounded-full object-cover" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=random" alt="{{ auth()->user()->name }}">
        </div>
        <div class="sidebar-text text-center overflow-hidden">
            <h3 class="font-semibold text-base whitespace-nowrap dark:text-white">{{ auth()->user()->name }}</h3>
            <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ auth()->user()->roles->first()?->name ?? 'Sin rol' }}</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4 overflow-x-hidden custom-scrollbar">
        <ul class="space-y-1 px-2">
            <li>
                <a href="{{ route('home') }}" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('home') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors group" title="Home">
                    <i class="fa-solid fa-house text-lg flex-shrink-0"></i>
                    <span class="sidebar-text font-medium">Home</span>
                </a>
            </li>
            @can('personas.view')
            <li>
                <a href="{{ route('personas.index') }}" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('personas.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors group" title="Personas">
                    <i class="fa-solid fa-users text-lg flex-shrink-0"></i>
                    <span class="sidebar-text font-medium">Personas</span>
                </a>
            </li>
            @endcan
            @can('contratos.view')
            <li>
                <a href="{{ route('contratos.index') }}" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('contratos.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors group" title="Contratos">
                    <i class="fa-solid fa-file-contract text-lg flex-shrink-0"></i>
                    <span class="sidebar-text font-medium">Contratos</span>
                </a>
            </li>
            @endcan
            @can('asistencia.view')
            <li>
                <a href="{{ route('asistencia.index') }}" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('asistencia.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors group" title="Asistencia">
                    <i class="fa-solid fa-clipboard-user text-lg flex-shrink-0"></i>
                    <span class="sidebar-text font-medium">Asistencia</span>
                </a>
            </li>
            @endcan
            @can('dashboard.view')
            <li>
                <a href="{{ route('dashboard') }}" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors group" title="Dashboard">
                    <i class="fa-solid fa-chart-pie text-lg flex-shrink-0"></i>
                    <span class="sidebar-text font-medium">Dashboard</span>
                </a>
            </li>
            @endcan

            @canany(['users.view', 'roles.view', 'audit.view'])
            <li class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase mb-2 sidebar-text">Administración</p>
            </li>
            @endcanany
            @can('users.view')
            <li>
                <a href="{{ route('admin.users.index') }}" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors group" title="Gestión de Usuarios">
                    <i class="fa-solid fa-users-cog text-lg flex-shrink-0"></i>
                    <span class="sidebar-text font-medium">Usuarios</span>
                </a>
            </li>
            @endcan
            @can('roles.view')
            <li>
                <a href="{{ route('admin.roles.index') }}" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.roles.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors group" title="Gestión de Roles">
                    <i class="fa-solid fa-shield-halved text-lg flex-shrink-0"></i>
                    <span class="sidebar-text font-medium">Roles y Permisos</span>
                </a>
            </li>
            @endcan
            @can('audit.view')
            <li>
                <a href="{{ route('admin.audit.index') }}" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.audit.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors group" title="Auditoría">
                    <i class="fa-solid fa-clock-rotate-left text-lg flex-shrink-0"></i>
                    <span class="sidebar-text font-medium">Auditoría</span>
                </a>
            </li>
            @endcan
        </ul>
    </nav>
    
    <!-- Dark Mode & Logout Container -->
    <div class="p-4 border-t border-light-border dark:border-[#2d3748] mt-auto overflow-hidden theme-toggle-container flex flex-col items-center gap-2">
        <!-- Logout Button -->
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" class="nav-link w-full flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-transparent text-gray-600 dark:text-gray-300 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400 transition-colors cursor-pointer group" title="Cerrar Sesión">
                <i class="fa-solid fa-right-from-bracket flex-shrink-0 text-lg"></i>
                <span class="sidebar-text font-medium">Cerrar Sesión</span>
            </button>
        </form>

        <!-- Dark Mode Toggle -->
        <button id="theme-toggle" class="nav-link w-full flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-light-border dark:border-[#2d3748] hover:bg-gray-100 dark:hover:bg-[#e67e22] transition-colors cursor-pointer" title="Cambiar Tema">
            <i id="theme-icon" class="fa-solid fa-moon flex-shrink-0"></i>
            <span id="theme-text" class="sidebar-text">Modo Oscuro</span>
        </button>
        
        <div class="text-center text-xs text-gray-400 sidebar-text mt-2 whitespace-nowrap">
            Copyright © {{ date('Y') }}
        </div>
    </div>

    <!-- El Resizer -->
    <div id="resizer" class="resizer"></div>
</aside>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const resizer = document.getElementById('resizer');
        const toggleBtn = document.getElementById('sidebar-toggle');
        const avatarContainer = document.getElementById('avatar-container');
        const body = document.body;

        // --- 1. Resizer Logic ---
        let isResizing = false;

        if(resizer) {
            resizer.addEventListener('mousedown', (e) => {
                isResizing = true;
                body.style.cursor = 'col-resize'; 
                body.style.userSelect = 'none';   
                sidebar.classList.add('sidebar-resizing');
                sidebar.classList.add('no-transition');
            });

            document.addEventListener('mousemove', (e) => {
                if (!isResizing) return;
                
                let newWidth = e.clientX;
                if (newWidth < 80) newWidth = 80;   
                if (newWidth > 400) newWidth = 400; 

                sidebar.style.width = `${newWidth}px`;

                if (newWidth < 180) {
                    if (!sidebar.classList.contains('collapsed')) collapseSidebar();
                } else {
                    if (sidebar.classList.contains('collapsed')) expandSidebar();
                }
            });

            document.addEventListener('mouseup', () => {
                if (isResizing) {
                    isResizing = false;
                    body.style.cursor = '';
                    body.style.userSelect = '';
                    sidebar.classList.remove('sidebar-resizing');
                    sidebar.classList.remove('no-transition');
                    localStorage.setItem('sidebar-width', sidebar.style.width);
                }
            });
        }

        // --- 2. Toggle Logic ---
        function collapseSidebar() {
            sidebar.classList.add('collapsed');
            if(avatarContainer) {
                avatarContainer.classList.replace('w-20', 'w-16');
                avatarContainer.classList.replace('h-20', 'h-16');
            }
        }

        function expandSidebar() {
            sidebar.classList.remove('collapsed');
            if(avatarContainer) {
                avatarContainer.classList.replace('w-16', 'w-20');
                avatarContainer.classList.replace('h-16', 'h-20');
            }
        }

        if(toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                if (sidebar.classList.contains('collapsed')) {
                    expandSidebar();
                    sidebar.style.width = '260px';
                } else {
                    collapseSidebar();
                    sidebar.style.width = '80px';
                }
            });
        }

        // --- 3. Dark Mode Logic (Trigger) ---
        const themeBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const themeText = document.getElementById('theme-text');
        const html = document.documentElement;

        function updateThemeUI() {
            if (html.classList.contains('dark')) {
                themeIcon.className = 'fa-solid fa-sun';
                if(themeText) themeText.innerText = '';
            } else {
                themeIcon.className = 'fa-solid fa-moon';
                if(themeText) themeText.innerText = '';
            }
        }
        
        // Inicializar icono correcto
        updateThemeUI();

        if(themeBtn) {
            themeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (html.classList.contains('dark')) {
                    html.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                } else {
                    html.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                }
                updateThemeUI();
            });
        }
    });
</script>
@endpush
