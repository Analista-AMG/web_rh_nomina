<aside id="sidebar" class="flex-shrink-0 bg-sidebar-theme border-r border-light-border dark:border-[#2d3748] flex flex-col relative transition-all duration-200 shadow-lg z-20" style="width: 240px;">
<script>
    (function() {
        var s = document.currentScript.parentElement;
        if (localStorage.getItem('sidebar-collapsed') === '1') {
            s.style.width = '80px';
            s.classList.add('collapsed', 'no-transition');
        }
    })();
</script>
    
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
    <a href="{{ route('profile.edit') }}" class="flex flex-col items-center py-6 overflow-hidden hover:opacity-80 transition-opacity" title="Mi perfil">
        <div id="avatar-container" class="w-20 h-20 rounded-full overflow-hidden mb-4 border-2 border-primary p-1 transition-all duration-300 flex-shrink-0">
            <img class="w-full h-full rounded-full object-cover" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=random" alt="{{ auth()->user()->name }}">
        </div>
        <div class="sidebar-text text-center overflow-hidden">
            <h3 class="font-semibold text-base whitespace-nowrap dark:text-white">{{ auth()->user()->name }}</h3>
            <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ auth()->user()->roles->first()?->name ?? 'Sin rol' }}</span>
        </div>
    </a>

    <!-- Navigation -->
    @php
        $personalActive     = request()->routeIs('personas.*') || request()->routeIs('contratos.*');
        $operacionesActive  = request()->routeIs('asistencia.*') || request()->routeIs('equipos.*');
        $reportesActive     = request()->routeIs('reportes.*');
        $remunerActive      = request()->routeIs('adicionales.*') || request()->routeIs('calculos.*') || request()->routeIs('dashboard');
        $gestionActive      = request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.audit.*');
        $orgActive          = request()->routeIs('admin.empresas.*') || request()->routeIs('admin.campanas.*') || request()->routeIs('admin.asignaciones.*');
    @endphp
    <nav class="flex-1 overflow-y-auto py-4 overflow-x-hidden custom-scrollbar">
        <ul class="space-y-1 px-2">

            {{-- Home --}}
            <li>
                <a href="{{ route('home') }}" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('home') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors" title="Home">
                    <i class="fa-solid fa-house text-lg flex-shrink-0"></i>
                    <span class="sidebar-text font-medium">Home</span>
                </a>
            </li>

            {{-- GRUPO: Personal --}}
            @canany(['personas.view', 'contratos.view'])
            <li class="nav-group" data-group="personal" data-active="{{ $personalActive ? '1' : '0' }}">
                <button class="nav-group-btn w-full flex items-center gap-3 px-4 py-3 rounded-lg {{ $personalActive ? 'text-primary' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors cursor-pointer" title="Personal">
                    <i class="fa-solid fa-id-card text-lg flex-shrink-0"></i>
                    <span class="sidebar-text font-medium flex-1 text-left">Personal</span>
                    <i class="fa-solid fa-chevron-down text-xs sidebar-text nav-group-chevron transition-transform duration-200 flex-shrink-0"></i>
                </button>
                <ul class="nav-group-items overflow-hidden transition-all duration-200 space-y-0.5" style="max-height:0;">
                    @can('personas.view')
                    <li>
                        <a href="{{ route('personas.index') }}" class="nav-link flex items-center gap-3 pl-9 pr-4 py-2.5 rounded-lg {{ request()->routeIs('personas.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors" title="Personas">
                            <i class="fa-solid fa-users text-sm flex-shrink-0"></i>
                            <span class="sidebar-text font-medium">Personas</span>
                        </a>
                    </li>
                    @endcan
                    @can('contratos.view')
                    <li>
                        <a href="{{ route('contratos.index') }}" class="nav-link flex items-center gap-3 pl-9 pr-4 py-2.5 rounded-lg {{ request()->routeIs('contratos.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors" title="Contratos">
                            <i class="fa-solid fa-file-contract text-sm flex-shrink-0"></i>
                            <span class="sidebar-text font-medium">Contratos</span>
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany

            {{-- GRUPO: Operaciones --}}
            @canany(['asistencia.view', 'equipos.manage'])
            <li class="nav-group" data-group="operaciones" data-active="{{ $operacionesActive ? '1' : '0' }}">
                <button class="nav-group-btn w-full flex items-center gap-3 px-4 py-3 rounded-lg {{ $operacionesActive ? 'text-primary' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors cursor-pointer" title="Operaciones">
                    <i class="fa-solid fa-calendar-check text-lg flex-shrink-0"></i>
                    <span class="sidebar-text font-medium flex-1 text-left">Operaciones</span>
                    <i class="fa-solid fa-chevron-down text-xs sidebar-text nav-group-chevron transition-transform duration-200 flex-shrink-0"></i>
                </button>
                <ul class="nav-group-items overflow-hidden transition-all duration-200 space-y-0.5" style="max-height:0;">
                    @can('asistencia.view')
                    <li>
                        <a href="{{ route('asistencia.index') }}" class="nav-link flex items-center gap-3 pl-9 pr-4 py-2.5 rounded-lg {{ request()->routeIs('asistencia.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors" title="Asistencia">
                            <i class="fa-solid fa-clipboard-user text-sm flex-shrink-0"></i>
                            <span class="sidebar-text font-medium">Asistencia</span>
                        </a>
                    </li>
                    @endcan
                    @can('equipos.manage')
                    <li>
                        <a href="{{ route('equipos.index') }}" class="nav-link flex items-center gap-3 pl-9 pr-4 py-2.5 rounded-lg {{ request()->routeIs('equipos.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors" title="Equipos">
                            <i class="fa-solid fa-people-group text-sm flex-shrink-0"></i>
                            <span class="sidebar-text font-medium">Equipos</span>
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany

            {{-- GRUPO: Reportes --}}
            @can('reportes.asistencia.view')
            <li class="nav-group" data-group="reportes" data-active="{{ $reportesActive ? '1' : '0' }}">
                <button class="nav-group-btn w-full flex items-center gap-3 px-4 py-3 rounded-lg {{ $reportesActive ? 'text-primary' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors cursor-pointer" title="Reportes">
                    <i class="fa-solid fa-chart-bar text-lg flex-shrink-0"></i>
                    <span class="sidebar-text font-medium flex-1 text-left">Reportes</span>
                    <i class="fa-solid fa-chevron-down text-xs sidebar-text nav-group-chevron transition-transform duration-200 flex-shrink-0"></i>
                </button>
                <ul class="nav-group-items overflow-hidden transition-all duration-200 space-y-0.5" style="max-height:0;">
                    <li>
                        <a href="{{ route('reportes.asistencia') }}" class="nav-link flex items-center gap-3 pl-9 pr-4 py-2.5 rounded-lg {{ request()->routeIs('reportes.asistencia') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors" title="Asistencia">
                            <i class="fa-solid fa-clipboard-list text-sm flex-shrink-0"></i>
                            <span class="sidebar-text font-medium">Asistencia</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcan

            {{-- GRUPO: Remuneraciones --}}
            @canany(['adicionales.view', 'calculos.view', 'dashboard.view'])
            <li class="nav-group" data-group="remuneraciones" data-active="{{ $remunerActive ? '1' : '0' }}">
                <button class="nav-group-btn w-full flex items-center gap-3 px-4 py-3 rounded-lg {{ $remunerActive ? 'text-primary' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors cursor-pointer" title="Remuneraciones">
                    <i class="fa-solid fa-money-bill-wave text-lg flex-shrink-0"></i>
                    <span class="sidebar-text font-medium flex-1 text-left">Remuneraciones</span>
                    <i class="fa-solid fa-chevron-down text-xs sidebar-text nav-group-chevron transition-transform duration-200 flex-shrink-0"></i>
                </button>
                <ul class="nav-group-items overflow-hidden transition-all duration-200 space-y-0.5" style="max-height:0;">
                    @can('adicionales.view')
                    <li>
                        <a href="{{ route('adicionales.index') }}" class="nav-link flex items-center gap-3 pl-9 pr-4 py-2.5 rounded-lg {{ request()->routeIs('adicionales.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors" title="Adicionales">
                            <i class="fa-solid fa-hand-holding-dollar text-sm flex-shrink-0"></i>
                            <span class="sidebar-text font-medium">Adicionales</span>
                        </a>
                    </li>
                    @endcan
                    @can('calculos.view')
                    <li>
                        <a href="{{ route('calculos.index') }}" class="nav-link flex items-center gap-3 pl-9 pr-4 py-2.5 rounded-lg {{ request()->routeIs('calculos.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors" title="Cálculos">
                            <i class="fa-solid fa-calculator text-sm flex-shrink-0"></i>
                            <span class="sidebar-text font-medium">Cálculos</span>
                        </a>
                    </li>
                    @endcan
                    @can('dashboard.view')
                    <li>
                        <a href="{{ route('dashboard') }}" class="nav-link flex items-center gap-3 pl-9 pr-4 py-2.5 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors" title="Dashboard">
                            <i class="fa-solid fa-chart-pie text-sm flex-shrink-0"></i>
                            <span class="sidebar-text font-medium">Dashboard</span>
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany

            {{-- SECCIÓN: Administración --}}
            @canany(['users.view', 'roles.view', 'audit.view', 'asignaciones.view', 'campanas.view'])
            <li class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase mb-2 sidebar-text">Administración</p>
            </li>
            @endcanany

            {{-- GRUPO: Gestión (Usuarios / Roles / Auditoría) --}}
            @canany(['users.view', 'roles.view', 'audit.view'])
            <li class="nav-group" data-group="gestion" data-active="{{ $gestionActive ? '1' : '0' }}">
                <button class="nav-group-btn w-full flex items-center gap-3 px-4 py-3 rounded-lg {{ $gestionActive ? 'text-primary' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors cursor-pointer" title="Gestión">
                    <i class="fa-solid fa-users-gear text-lg flex-shrink-0"></i>
                    <span class="sidebar-text font-medium flex-1 text-left">Gestión</span>
                    <i class="fa-solid fa-chevron-down text-xs sidebar-text nav-group-chevron transition-transform duration-200 flex-shrink-0"></i>
                </button>
                <ul class="nav-group-items overflow-hidden transition-all duration-200 space-y-0.5" style="max-height:0;">
                    @can('users.view')
                    <li>
                        <a href="{{ route('admin.users.index') }}" class="nav-link flex items-center gap-3 pl-9 pr-4 py-2.5 rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors" title="Gestión de Usuarios">
                            <i class="fa-solid fa-users-cog text-sm flex-shrink-0"></i>
                            <span class="sidebar-text font-medium">Usuarios</span>
                        </a>
                    </li>
                    @endcan
                    @can('roles.view')
                    <li>
                        <a href="{{ route('admin.roles.index') }}" class="nav-link flex items-center gap-3 pl-9 pr-4 py-2.5 rounded-lg {{ request()->routeIs('admin.roles.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors" title="Gestión de Roles">
                            <i class="fa-solid fa-shield-halved text-sm flex-shrink-0"></i>
                            <span class="sidebar-text font-medium">Roles y Permisos</span>
                        </a>
                    </li>
                    @endcan
                    @can('audit.view')
                    <li>
                        <a href="{{ route('admin.audit.index') }}" class="nav-link flex items-center gap-3 pl-9 pr-4 py-2.5 rounded-lg {{ request()->routeIs('admin.audit.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors" title="Auditoría">
                            <i class="fa-solid fa-clock-rotate-left text-sm flex-shrink-0"></i>
                            <span class="sidebar-text font-medium">Auditoría</span>
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany

            {{-- GRUPO: Organización (Empresas / Campañas / Asignaciones) --}}
            @canany(['asignaciones.view', 'campanas.view'])
            <li class="nav-group" data-group="organizacion" data-active="{{ $orgActive ? '1' : '0' }}">
                <button class="nav-group-btn w-full flex items-center gap-3 px-4 py-3 rounded-lg {{ $orgActive ? 'text-primary' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors cursor-pointer" title="Organización">
                    <i class="fa-solid fa-sitemap text-lg flex-shrink-0"></i>
                    <span class="sidebar-text font-medium flex-1 text-left">Organización</span>
                    <i class="fa-solid fa-chevron-down text-xs sidebar-text nav-group-chevron transition-transform duration-200 flex-shrink-0"></i>
                </button>
                <ul class="nav-group-items overflow-hidden transition-all duration-200 space-y-0.5" style="max-height:0;">
                    @role('Administrador')
                    <li>
                        <a href="{{ route('admin.empresas.index') }}" class="nav-link flex items-center gap-3 pl-9 pr-4 py-2.5 rounded-lg {{ request()->routeIs('admin.empresas.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors" title="Empresas">
                            <i class="fa-solid fa-building text-sm flex-shrink-0"></i>
                            <span class="sidebar-text font-medium">Empresas</span>
                        </a>
                    </li>
                    @endrole
                    @can('campanas.view')
                    <li>
                        <a href="{{ route('admin.campanas.index') }}" class="nav-link flex items-center gap-3 pl-9 pr-4 py-2.5 rounded-lg {{ request()->routeIs('admin.campanas.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors" title="Campañas">
                            <i class="fa-solid fa-bullhorn text-sm flex-shrink-0"></i>
                            <span class="sidebar-text font-medium">Campañas</span>
                        </a>
                    </li>
                    @endcan
                    @can('asignaciones.view')
                    <li>
                        <a href="{{ route('admin.asignaciones.index') }}" class="nav-link flex items-center gap-3 pl-9 pr-4 py-2.5 rounded-lg {{ request()->routeIs('admin.asignaciones.*') ? 'bg-primary/10 text-primary font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary' }} transition-colors" title="Asignaciones">
                            <i class="fa-solid fa-user-tag text-sm flex-shrink-0"></i>
                            <span class="sidebar-text font-medium">Asignaciones</span>
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany

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

                if (newWidth < 220) {
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
                    localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
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
            localStorage.setItem('sidebar-collapsed', '1');
            syncGroupsWithSidebarState();
        }

        function expandSidebar() {
            sidebar.classList.remove('collapsed');
            if(avatarContainer) {
                avatarContainer.classList.replace('w-16', 'w-20');
                avatarContainer.classList.replace('h-16', 'h-20');
            }
            localStorage.setItem('sidebar-collapsed', '0');
            hideFlyout();
            syncGroupsWithSidebarState();
        }

        // Restaurar estado colapsado: ancho y clase ya aplicados por el script inline
        // Solo sincronizar avatar y grupos, luego quitar no-transition
        if (localStorage.getItem('sidebar-collapsed') === '1') {
            if(avatarContainer) {
                avatarContainer.classList.replace('w-20', 'w-16');
                avatarContainer.classList.replace('h-20', 'h-16');
            }
            syncGroupsWithSidebarState();
        }
        // Quitar no-transition tras el primer frame para que las interacciones futuras animen
        requestAnimationFrame(() => sidebar.classList.remove('no-transition'));

        if(toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                if (sidebar.classList.contains('collapsed')) {
                    expandSidebar();
                    sidebar.style.width = '240px';
                } else {
                    collapseSidebar();
                    sidebar.style.width = '80px';
                }
            });
        }

        // --- 3. Nav Groups (Accordion + Flyout en colapsado) ---

        // Flyout flotante para modo colapsado
        const flyout = document.createElement('div');
        flyout.id = 'nav-flyout';
        flyout.className = 'fixed z-[200] hidden bg-white dark:bg-[#1e293b] shadow-xl rounded-xl py-2 min-w-[180px] border border-gray-200 dark:border-gray-700';
        document.body.appendChild(flyout);

        function showFlyout(group, btn) {
            const items = group.querySelector('.nav-group-items');
            const links = items.querySelectorAll('a');
            const rect = btn.getBoundingClientRect();

            flyout.innerHTML = '';

            // Título del grupo
            const title = document.createElement('p');
            title.className = 'px-4 py-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider';
            title.textContent = btn.querySelector('span.sidebar-text')?.textContent?.trim() || '';
            flyout.appendChild(title);

            links.forEach(link => {
                const a = document.createElement('a');
                a.href = link.getAttribute('href');
                const isActive = link.classList.contains('text-primary');
                a.className = 'flex items-center gap-3 px-4 py-2.5 text-sm transition-colors ' +
                    (isActive ? 'text-primary font-medium bg-primary/10' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#1b2431] hover:text-primary');
                const icon = link.querySelector('i')?.cloneNode(true);
                if (icon) { icon.className = icon.className.replace('text-sm', 'text-base'); a.appendChild(icon); }
                const span = document.createElement('span');
                span.textContent = link.querySelector('span')?.textContent?.trim() || '';
                a.appendChild(span);
                flyout.appendChild(a);
            });

            // Posición: a la derecha del sidebar, alineado con el botón
            flyout.style.top = Math.min(rect.top, window.innerHeight - flyout.offsetHeight - 16) + 'px';
            flyout.style.left = (rect.right + 8) + 'px';
            flyout.classList.remove('hidden');
        }

        function hideFlyout() {
            flyout.classList.add('hidden');
        }

        document.addEventListener('click', (e) => {
            if (!flyout.contains(e.target) && !e.target.closest('.nav-group-btn')) {
                hideFlyout();
            }
        });
        function openGroup(group) {
            const items = group.querySelector('.nav-group-items');
            const chevron = group.querySelector('.nav-group-chevron');
            items.style.maxHeight = items.scrollHeight + 'px';
            chevron.style.transform = 'rotate(180deg)';
            localStorage.setItem('nav-group-' + group.dataset.group, 'open');
        }

        function closeGroup(group) {
            const items = group.querySelector('.nav-group-items');
            const chevron = group.querySelector('.nav-group-chevron');
            items.style.maxHeight = '0';
            chevron.style.transform = 'rotate(0deg)';
            localStorage.setItem('nav-group-' + group.dataset.group, 'closed');
        }

        function initGroups() {
            const isCollapsed = sidebar.classList.contains('collapsed');
            document.querySelectorAll('.nav-group').forEach(group => {
                const key = 'nav-group-' + group.dataset.group;
                const isActive = group.dataset.active === '1';
                const saved = localStorage.getItem(key);

                // Solo abrir grupos si el sidebar está expandido
                if (!isCollapsed && (isActive || saved === 'open')) {
                    openGroup(group);
                }

                group.querySelector('.nav-group-btn').addEventListener('click', (e) => {
                    if (sidebar.classList.contains('collapsed')) {
                        e.stopPropagation();
                        // Alternar flyout: si ya está visible para este grupo, cerrarlo
                        if (!flyout.classList.contains('hidden') && flyout.dataset.group === group.dataset.group) {
                            hideFlyout();
                        } else {
                            flyout.dataset.group = group.dataset.group;
                            showFlyout(group, group.querySelector('.nav-group-btn'));
                        }
                        return;
                    }
                    const items = group.querySelector('.nav-group-items');
                    if (items.style.maxHeight === '0px' || items.style.maxHeight === '') {
                        openGroup(group);
                    } else {
                        closeGroup(group);
                    }
                });
            });
        }

        function syncGroupsWithSidebarState() {
            const isCollapsed = sidebar.classList.contains('collapsed');
            document.querySelectorAll('.nav-group').forEach(group => {
                const items = group.querySelector('.nav-group-items');
                const btn = group.querySelector('.nav-group-btn');
                if (isCollapsed) {
                    // Sidebar colapsado: solo el icono del grupo centrado, items ocultos
                    btn.classList.add('justify-center');
                    btn.classList.remove('justify-start');
                    items.style.maxHeight = '0';
                } else {
                    // Sidebar expandido: restaurar alineación y estado guardado
                    btn.classList.remove('justify-center');
                    const key = 'nav-group-' + group.dataset.group;
                    const isActive = group.dataset.active === '1';
                    const saved = localStorage.getItem(key);
                    if (isActive || saved === 'open') {
                        openGroup(group);
                    } else {
                        closeGroup(group);
                    }
                }
            });
        }

        initGroups();
        // Sincronizar por si el sidebar ya arranca colapsado
        if (sidebar.classList.contains('collapsed')) syncGroupsWithSidebarState();

        // --- 4. Dark Mode Logic (Trigger) ---
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
