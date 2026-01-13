document.addEventListener('DOMContentLoaded', () => {
    
    // --- 1. Sidebar Logic (Toggle) ---
    const sidebar = document.getElementById('sidebar');
    const sidebarToggleBtn = document.getElementById('sidebar-toggle');
    const resizer = document.getElementById('resizer');
    
    // Toggle Button Logic
    sidebarToggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        if (sidebar.classList.contains('collapsed')) {
            sidebar.style.width = '';
        } else {
            sidebar.style.width = '260px';
        }
    });

    // Resize Logic Restored
    let isResizing = false;

    resizer.addEventListener('mousedown', (e) => {
        isResizing = true;
        sidebar.classList.add('resizing');
        document.body.classList.add('resizing-cursor'); // Add cursor style to body
    });

    document.addEventListener('mousemove', (e) => {
        if (!isResizing) return;

        // Simple direct width update
        let newWidth = e.clientX;
        
        // Constraints
        if (newWidth < 80) newWidth = 80;
        if (newWidth > 500) newWidth = 500;

        sidebar.style.width = `${newWidth}px`;
        
        // Collapse logic
        if (newWidth < 140) {
            sidebar.classList.add('collapsed');
        } else {
            sidebar.classList.remove('collapsed');
        }
    });

    document.addEventListener('mouseup', () => {
        if (isResizing) {
            isResizing = false;
            sidebar.classList.remove('resizing');
            document.body.classList.remove('resizing-cursor');
        }
    });

    // --- 2. Dark Mode Logic ---
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeIcon = themeToggleBtn.querySelector('i');
    const themeText = themeToggleBtn.querySelector('span');
    const html = document.documentElement; 

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        html.setAttribute('data-theme', 'dark');
        updateThemeUI(true);
    }

    themeToggleBtn.addEventListener('click', () => {
        const isDark = html.getAttribute('data-theme') === 'dark';
        if (isDark) {
            html.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
            updateThemeUI(false);
        } else {
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            updateThemeUI(true);
        }
    });

    function updateThemeUI(isDark) {
        if (isDark) {
            themeIcon.className = 'fa-solid fa-sun';
            if(themeText) themeText.textContent = 'Modo Claro';
        } else {
            themeIcon.className = 'fa-solid fa-moon';
            if(themeText) themeText.textContent = 'Modo Oscuro';
        }
    }

    // --- 3. Real-time Search Logic (Dual Inputs) ---
    // For Personas Page
    const searchName = document.getElementById('search-name');
    const searchDoc = document.getElementById('search-doc');
    
    // For Contratos Page
    const searchClient = document.getElementById('search-client');
    const searchType = document.getElementById('search-type');
    
    const tableRows = document.querySelectorAll('.data-table tbody tr');

    function filterTable() {
        const nameTerm = searchName ? searchName.value.toLowerCase() : '';
        const docTerm = searchDoc ? searchDoc.value.toLowerCase() : '';
        const clientTerm = searchClient ? searchClient.value.toLowerCase() : '';
        const typeTerm = searchType ? searchType.value.toLowerCase() : '';

        tableRows.forEach(row => {
            let visible = true;
            
            // Logic for Personas (indices based on table structure)
            if (searchName || searchDoc) {
           
                const cols = row.querySelectorAll('td');
                if (cols.length > 0) {
                    const nameText = cols[1].textContent.toLowerCase(); // Check if this index matches Concatenated Name column
                    const docText = cols[0].textContent.toLowerCase();
                    
                    if (nameTerm && !nameText.includes(nameTerm)) visible = false;
                    if (docTerm && !docText.includes(docTerm)) visible = false;
                }
            }

            // Logic for Contratos
            if (searchClient || searchType) {
                const cols = row.querySelectorAll('td');
                if (cols.length > 0) {
                    const clientText = cols[0].textContent.toLowerCase(); // Column 0: Cliente
                    const typeText = cols[1].textContent.toLowerCase();   // Column 1: Tipo
                    
                    if (clientTerm && !clientText.includes(clientTerm)) visible = false;
                    if (typeTerm && !typeText.includes(typeTerm)) visible = false;
                }
            }

            if (visible) {
                row.style.display = '';
                row.style.animation = 'fadeIn 0.3s ease';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Attach listeners
    if(searchName) searchName.addEventListener('input', filterTable);
    if(searchDoc) searchDoc.addEventListener('input', filterTable);
    if(searchClient) searchClient.addEventListener('input', filterTable);
    if(searchType) searchType.addEventListener('input', filterTable);

    // --- Modal Logic ---
    const editModal = document.getElementById('edit-modal');
    const closeGenericBtn = document.querySelector('.close-modal');
    const closeFooterBtn = document.querySelector('.close-btn-modal');
    const saveEditBtn = document.getElementById('save-edit-btn');

    function openEditModal(row) {
        // 1. Document Data
        document.getElementById('edit-tdoc').value = row.getAttribute('data-tdoc') || 'DNI';
        document.getElementById('edit-doc').value = row.cells[0].textContent; 

        // 2. Personal Data (Separated)
        document.getElementById('edit-nombres').value = row.getAttribute('data-nombres') || '';
        document.getElementById('edit-paterno').value = row.getAttribute('data-paterno') || '';
        document.getElementById('edit-materno').value = row.getAttribute('data-materno') || '';

        // 3. Extra Hidden Data
        document.getElementById('edit-nac').value = row.getAttribute('data-nac') || '';
        document.getElementById('edit-genero').value = row.getAttribute('data-genero') || 'M';
        document.getElementById('edit-direccion').value = row.getAttribute('data-direccion') || '';

        // 4. Contact & Status
        document.getElementById('edit-correo').value = row.cells[8].textContent; 
        
        const statusSpan = row.cells[10].querySelector('.badge');
        const statusText = statusSpan ? statusSpan.textContent.trim() : 'Activo';
        
        // Modal Styling based on Status
        const modalContent = editModal.querySelector('.modal-content');
        modalContent.classList.remove('status-active', 'status-pending', 'status-inactive');
        
        if(statusText === 'Activo') modalContent.classList.add('status-active');
        else if(statusText === 'Pendiente') modalContent.classList.add('status-pending');
        else modalContent.classList.add('status-inactive');
        
        // Open Modal
        editModal.classList.add('show');
    }

    function closeEditModal() {
        editModal.classList.remove('show');
    }

    if (closeGenericBtn) closeGenericBtn.addEventListener('click', closeEditModal);
    if (closeFooterBtn) closeFooterBtn.addEventListener('click', closeEditModal);
    if (saveEditBtn) saveEditBtn.addEventListener('click', (e) => {
        e.preventDefault();
        alert('Datos guardados correctamente (Simulación)');
        closeEditModal();
    });

    const viewModal = document.getElementById('view-modal');
    const closeViewIcon = document.querySelector('.close-view');
    const closeViewBtn = document.querySelector('.close-view-btn');

    function openViewModal(row) {
        // Populate READ-ONLY form fields
        document.getElementById('view-tdoc').value = row.getAttribute('data-tdoc') || 'DNI';
        document.getElementById('view-doc').value = row.cells[0].textContent; 
        document.getElementById('view-nombres').value = row.getAttribute('data-nombres') || '';
        document.getElementById('view-paterno').value = row.getAttribute('data-paterno') || '';
        document.getElementById('view-materno').value = row.getAttribute('data-materno') || '';
        document.getElementById('view-nac').value = row.getAttribute('data-nac') || '';
        
        const generoCode = row.getAttribute('data-genero') || 'M';
        document.getElementById('view-genero').value = generoCode === 'M' ? 'Masculino' : 'Femenino';
        
        document.getElementById('view-direccion').value = row.getAttribute('data-direccion') || '';
        document.getElementById('view-correo').value = row.cells[8].textContent; 
        
        // Status styling for View Modal
        const statusSpan = row.cells[10].querySelector('.badge');
        const statusText = statusSpan ? statusSpan.textContent.trim() : 'Activo';
        
        const modalContent = viewModal.querySelector('.modal-content');
        modalContent.classList.remove('status-active', 'status-pending', 'status-inactive');
        
        if(statusText === 'Activo') modalContent.classList.add('status-active');
        else if(statusText === 'Pendiente') modalContent.classList.add('status-pending');
        else modalContent.classList.add('status-inactive');
        
        viewModal.classList.add('show');
    }

    function closeViewModalFn() {
        viewModal.classList.remove('show');
    }

    if (closeViewIcon) closeViewIcon.addEventListener('click', closeViewModalFn);
    if (closeViewBtn) closeViewBtn.addEventListener('click', closeViewModalFn);
    
    window.addEventListener('click', (e) => {
        if (e.target === viewModal) closeViewModalFn();
        if (e.target === editModal) closeEditModal();
    });

    // --- 4. Table Actions Logic ---
    const deleteButtons = document.querySelectorAll('.icon-btn.delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            if(confirm('¿Estás seguro de que deseas eliminar este registro?')) {
                const row = e.target.closest('tr');
                if(row) {
                    row.style.opacity = '0';
                    row.style.transform = 'scale(0.9)';
                    setTimeout(() => row.remove(), 300);
                }
            }
        });
    });

    const editButtons = document.querySelectorAll('.icon-btn.edit');
    editButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const row = e.target.closest('tr');
            openEditModal(row);
        });
    });

    const viewButtons = document.querySelectorAll('.icon-btn.view');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const row = e.target.closest('tr');
            openViewModal(row);
        });
    });
    
    // Highlight active menu item
    const currentPath = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.navigation a');
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPath || (currentPath === '' && href === 'index.html')) {
            document.querySelectorAll('.navigation li').forEach(li => li.classList.remove('active'));
            link.parentElement.classList.add('active');
        }
    });
});