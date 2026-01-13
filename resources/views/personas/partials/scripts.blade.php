<script>
    // --- 1. Funciones Globales de UI ---
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
        console.log('Módulo Personas: Iniciado');

        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

        // A. Lógica de Edición (AJAX)
        const btnSave = document.querySelector('#edit-modal button.bg-primary');
        if(btnSave) {
            btnSave.addEventListener('click', async () => {
                const id = document.getElementById('edit-id') ? document.getElementById('edit-id').value : '';
                if(!id) { alert('Error: ID no encontrado.'); return; }

                const data = {
                    tipo_documento: document.getElementById('edit-tdoc').value,
                    numero_documento: document.getElementById('edit-doc').value,
                    nacionalidad: document.getElementById('edit-nacionalidad').value,
                    nombres: document.getElementById('edit-nombres').value,
                    apellido_paterno: document.getElementById('edit-paterno').value,
                    apellido_materno: document.getElementById('edit-materno').value,
                    fecha_nacimiento: document.getElementById('edit-nac').value,
                    genero: document.getElementById('edit-genero').value,
                    correo_electronico_personal: document.getElementById('edit-correo-pers').value,
                    correo_electronico_corporativo: document.getElementById('edit-correo-corp').value,
                    direccion: document.getElementById('edit-direccion').value,
                };

                try {
                    btnSave.disabled = true;
                    btnSave.innerText = 'Guardando...';

                    const response = await fetch(`/personas/${id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });

                    if (response.ok) {
                        alert('Guardado exitosamente');
                        window.location.reload();
                    } else {
                        const result = await response.json();
                        alert('Error: ' + (result.message || 'Verifique los datos'));
                    }
                } catch (error) {
                    console.error(error);
                    alert('Error de conexión');
                } finally {
                    btnSave.disabled = false;
                    btnSave.innerText = 'Guardar Cambios';
                }
            });
        }

        // B. Event Delegation (Botones Tabla)
        document.addEventListener('click', function(e) {
            // Botón Editar
            const btnEdit = e.target.closest('.btn-edit');
            if (btnEdit) {
                const data = btnEdit.closest('tr').dataset;
                // Llenar formulario
                document.getElementById('edit-id').value = data.id || '';
                setVal('edit-doc', data.doc);
                setVal('edit-tdoc', data.tdoc || 'DNI');
                setVal('edit-nacionalidad', data.nacionalidad);
                setVal('edit-nombres', data.nombres);
                setVal('edit-paterno', data.paterno);
                setVal('edit-materno', data.materno);
                setVal('edit-nac', data.nac);
                setVal('edit-genero', data.genero || '1');
                setVal('edit-correo-pers', data.correoPers);
                setVal('edit-correo-corp', data.correoCorp);
                setVal('edit-direccion', data.direccion);
                
                openModal('edit-modal');
            }

            // Botón Ver
            const btnView = e.target.closest('.btn-view');
            if (btnView) {
                const data = btnView.closest('tr').dataset;
                setVal('view-doc', data.doc);
                setVal('view-tdoc', data.tdoc);
                setVal('view-nacionalidad', data.nacionalidad);
                setVal('view-nombres', data.nombres);
                setVal('view-paterno', data.paterno);
                setVal('view-materno', data.materno);
                setVal('view-nac', data.nac);
                setVal('view-genero', (data.genero == '1' ? 'Masculino' : (data.genero == '2' ? 'Femenino' : 'Otro')));
                setVal('view-correo-pers', data.correoPers);
                setVal('view-correo-corp', data.correoCorp);
                setVal('view-direccion', data.direccion);
                
                openModal('view-modal');
            }

            // Botón Eliminar
            if (e.target.closest('.btn-delete')) {
                if(confirm('¿Estás seguro de eliminar este registro?')) {
                    alert('Funcionalidad pendiente.');
                }
            }
        });

        // Helper interno
        function setVal(id, val) {
            const el = document.getElementById(id);
            if(el) el.value = val || '';
        }

        // C. Buscador (Debounce)
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