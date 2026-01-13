<script>
    // --- 1. Funciones Globales ---
    window.openModal = function(id) {
        const el = document.getElementById(id);
        if(el) {
            el.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            console.error('Modal no encontrado:', id);
        }
    };

    window.closeModal = function(id) {
        const el = document.getElementById(id);
        if(el) {
            el.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    };

    // --- 2. Lógica del Módulo ---
    (function() {
        console.log('Módulo Contratos: Iniciado');

        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

        // A. Lógica AJAX (Guardar/Editar)
        // (Pendiente: adaptar formulario de edición cuando exista el endpoint update)

        // B. Event Delegation
        document.addEventListener('click', function(e) {
            // Botón Editar
            const btnEdit = e.target.closest('.btn-edit');
            if (btnEdit) {
                const data = btnEdit.closest('tr').dataset;
                
                // Llenar Modal Editar
                setVal('edit-id', data.id);
                setVal('edit-empleado', data.empleado);
                setVal('edit-cargo', data.cargo);
                setVal('edit-salario', data.salario);
                setVal('edit-inicio', data.inicio);
                setVal('edit-fin', data.fin);
                setVal('edit-estado', data.estado); // Select

                openModal('edit-modal'); 
            }

            // Botón Ver
            const btnView = e.target.closest('.btn-view');
            if (btnView) {
                const data = btnView.closest('tr').dataset;

                // Llenar Modal Ver (Textos)
                setVal('view-empleado', data.empleado);
                setVal('view-cargo', data.cargo);
                setVal('view-salario', 'S/ ' + parseFloat(data.salario || 0).toFixed(2));
                setVal('view-inicio', data.inicio);
                setVal('view-fin', data.fin || 'Indefinido');
                setVal('view-estado', data.estado == 1 ? 'Activo' : 'Inactivo');

                openModal('view-modal');
            }

            // Botón Eliminar
            if (e.target.closest('.btn-delete')) {
                if(!confirm('¿Estás seguro de eliminar este contrato?')) {
                    e.preventDefault();
                }
            }
        });

        // Helper interno
        function setVal(id, val) {
            const el = document.getElementById(id);
            if(el) el.value = val || '';
        }

        // C. Buscador
        const nameInput = document.getElementById('server-search-name');
        const docInput = document.getElementById('server-search-doc');
        let t = null;

        function search() {
            clearTimeout(t);
            t = setTimeout(() => {
                const url = new URL(window.location.href);
                
                if(nameInput && nameInput.value) url.searchParams.set('search_name', nameInput.value);
                else url.searchParams.delete('search_name');

                if(docInput && docInput.value) url.searchParams.set('search_doc', docInput.value);
                else url.searchParams.delete('search_doc');
                
                url.searchParams.delete('page');
                window.location.href = url.toString();
            }, 1100);
        }

        if(nameInput) nameInput.addEventListener('input', search);
        if(docInput) docInput.addEventListener('input', search);

    })();
</script>