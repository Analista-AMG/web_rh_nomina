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

        // Limpiar campos del modal de evaluación al cerrarlo
        if (id === 'evaluar-contrato-modal') {
            const numeroDocumentoInput = document.getElementById('evaluar-numero-documento');
            const fechaInicioInput = document.getElementById('evaluar-fecha-inicio');
            const personaNombreDiv = document.getElementById('evaluar-persona-nombre');

            if(numeroDocumentoInput) {
                numeroDocumentoInput.value = '';
            }
            if(fechaInicioInput) {
                fechaInicioInput.value = '';
                fechaInicioInput.removeAttribute('min');
                fechaInicioInput.disabled = true;
            }
            if(personaNombreDiv) {
                personaNombreDiv.textContent = '';
                personaNombreDiv.classList.remove('text-red-500');
            }
        }
    };

    // --- 2. Lógica del Módulo ---
    (function() {
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

        // A. Lógica de Edición (AJAX)
        const btnSave = document.getElementById('btn-save-contrato');
        if(btnSave) {
            btnSave.addEventListener('click', async () => {
                const id = document.getElementById('edit-id') ? document.getElementById('edit-id').value : '';
                if(!id) { alert('Error: ID no encontrado.'); return; }

                const data = {
                    fecha_inicio: document.getElementById('edit-inicio').value,
                    fecha_fin: document.getElementById('edit-fin').value,
                    haber_basico: document.getElementById('edit-salario').value,
                    estado: document.getElementById('edit-estado').value,
                };

                try {
                    btnSave.disabled = true;
                    btnSave.innerText = 'Guardando...';

                    const response = await fetch(`/contratos/${id}`, {
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

        // D. Cargar datos para selects del modal de edición de movimientos
        window.selectsLoaded = false;

        async function loadSelectData() {
            try {
                // Cargar todos los datos en paralelo
                const responses = await Promise.all([
                    fetch('/api/cargos'),
                    fetch('/api/planillas'),
                    fetch('/api/fondos-pensiones'),
                    fetch('/api/condiciones'),
                    fetch('/api/bancos'),
                    fetch('/api/centros-costo'),
                    fetch('/api/familias'),
                    fetch('/api/monedas')
                ]);

                const [cargos, planillas, fondosPensiones, condiciones, bancos, centrosCosto, familias, monedas] = await Promise.all(
                    responses.map(r => r.json())
                );
                // TABLA MOVIMIENTOS
                populateSelect('edit-mov-cargo-id', cargos, 'id_cargo', 'nombre_cargo'); // Llenar select de Cargos
                populateSelect('edit-mov-planilla-id', planillas, 'id_planilla', 'nombre_planilla');// Llenar select de Planillas
                populateSelect('edit-mov-fp-id', fondosPensiones, 'id_fondo', 'fondo_pension');// Llenar select de Fondos de Pensiones
                populateSelect('edit-mov-condicion-id', condiciones, 'id_condicion', 'nombre_condicion');// Llenar select de Condiciones
                populateSelect('edit-mov-banco-id', bancos, 'id_banco', 'nombre_banco');// Llenar select de Bancos
                populateSelect('edit-mov-centro-costo-id', centrosCosto, 'id_centro_costo', 'nombre'); // Llenar select de Centros de Costo
                populateSelect('edit-mov-familia-id', familias, 'id_familia', 'nombre'); // Llenar select de Centros de Costo
                populateSelect('edit-mov-moneda-id', monedas, 'id_moneda', 'nombre_moneda');// Llenar select de Monedas

                // MODAL AÑADIR MOVIMIENTO
                populateSelect('add-mov-cargo-id', cargos, 'id_cargo', 'nombre_cargo');
                populateSelect('add-mov-planilla-id', planillas, 'id_planilla', 'nombre_planilla');
                populateSelect('add-mov-fp-id', fondosPensiones, 'id_fondo', 'fondo_pension');
                populateSelect('add-mov-condicion-id', condiciones, 'id_condicion', 'nombre_condicion');
                populateSelect('add-mov-banco-id', bancos, 'id_banco', 'nombre_banco');
                populateSelect('add-mov-centro-costo-id', centrosCosto, 'id_centro_costo', 'nombre');
                populateSelect('add-mov-familia-id', familias, 'id_familia', 'nombre');
                populateSelect('add-mov-moneda-id', monedas, 'id_moneda', 'nombre_moneda');

                window.selectsLoaded = true;

            } catch (error) {
                console.error('❌ ERROR cargando datos para selects:', error);
            }
        }

        function populateSelect(selectId, data, valueField, textField) {
            const select = document.getElementById(selectId);
            if (!select) {
                console.error('Select no encontrado:', selectId);
                return;
            }

            select.innerHTML = '<option value="">Seleccione...</option>';
            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item[valueField];
                option.textContent = item[textField];
                select.appendChild(option);
            });
        }

        // Cargar datos al iniciar
        loadSelectData();

        // ============================================
        // E. FLUJO DE CREACION DE CONTRATOS (3 PASOS)
        // ============================================

        // Variables globales del flujo
        window.datosEvaluacion = null;

        // --- INICIO: Bloquear fechas y mostrar nombre en evaluacion de contrato ---
        const numeroDocumentoInput = document.getElementById('evaluar-numero-documento');
        const fechaInicioInput = document.getElementById('evaluar-fecha-inicio');
        const personaNombreDiv = document.getElementById('evaluar-persona-nombre');

        if (numeroDocumentoInput && fechaInicioInput && personaNombreDiv) {
            numeroDocumentoInput.addEventListener('blur', async () => {
                const numeroDocumento = numeroDocumentoInput.value;
                
                // --- Resetear y deshabilitar por defecto ---
                personaNombreDiv.textContent = '';
                fechaInicioInput.removeAttribute('min');
                fechaInicioInput.value = ''; // Limpiar valor
                fechaInicioInput.disabled = true; // Deshabilitar

                if (!numeroDocumento || numeroDocumento.length < 8) {
                    return;
                }

                // Mostrar un estado de carga
                personaNombreDiv.textContent = 'Buscando...';
                personaNombreDiv.classList.remove('text-red-500');

                try {
                    const response = await fetch(`/api/personas/${numeroDocumento}/ultimo-inicio`);
                    const data = await response.json();
                    
                    // Si la respuesta no es OK o no se encuentra la persona, mostrar error y mantener deshabilitado
                    if (!response.ok || !data.persona_nombre) {
                        personaNombreDiv.textContent = 'No se encontró el documento.';
                        personaNombreDiv.classList.add('text-red-500');
                        return; // Salir y dejar el campo de fecha deshabilitado
                    }

                    // --- ÉXITO: Habilitar y configurar ---
                    personaNombreDiv.textContent = data.persona_nombre;
                    personaNombreDiv.classList.remove('text-red-500');
                    
                    // Bloquear fecha y pre-llenar
                    if (data.ultimo_fin_contrato) {
                        const finContrato = new Date(data.ultimo_fin_contrato + 'T00:00:00');
                        finContrato.setDate(finContrato.getDate() + 1);
                        const proximaFechaDisponible = finContrato.toISOString().split('T')[0];
                        fechaInicioInput.min = proximaFechaDisponible;
                        fechaInicioInput.value = proximaFechaDisponible; // Pre-llenar la fecha
                    } else {
                        fechaInicioInput.removeAttribute('min');
                        fechaInicioInput.value = ''; // Asegurar que esté vacío si no hay contrato anterior
                    }

                    // Habilitar el campo de fecha solo si todo fue exitoso
                    fechaInicioInput.disabled = false;

                } catch (error) {
                    console.error('Error al obtener datos de persona:', error);
                    personaNombreDiv.textContent = 'Error de conexión.';
                    personaNombreDiv.classList.add('text-red-500');
                    // El campo de fecha permanece deshabilitado
                }
            });
        }
        // --- FIN: Bloquear fechas y mostrar nombre en evaluacion de contrato ---

        // --- PASO 1: EVALUAR CONTRATO ---
        const formEvaluar = document.getElementById('form-evaluar-contrato');
        if (formEvaluar) {
            formEvaluar.addEventListener('submit', async (e) => {
                e.preventDefault();

                const numeroDocumento = document.getElementById('evaluar-numero-documento').value;
                const fechaInicio = document.getElementById('evaluar-fecha-inicio').value;
                const btnEvaluar = document.getElementById('btn-evaluar-contrato');

                if (!numeroDocumento || !fechaInicio) {
                    alert('Por favor complete todos los campos');
                    return;
                }

                try {
                    btnEvaluar.disabled = true;
                    btnEvaluar.innerText = 'Evaluando...';

                    const response = await fetch('/api/contratos/evaluar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            numero_documento: numeroDocumento,
                            fecha_inicio: fechaInicio
                        })
                    });

                    const resultado = await response.json();

                    if (!response.ok || resultado.error || !resultado.ok) {
                        alert(resultado.error || resultado.message || 'No se puede crear el contrato. Verifique el documento y la fecha ingresada.');
                        return;
                    }

                    // Guardar datos de evaluacion para el siguiente paso
                    window.datosEvaluacion = {
                        ...resultado,
                        numero_documento: numeroDocumento,
                        fecha_inicio: fechaInicio
                    };

                    // Cerrar modal de evaluacion
                    closeModal('evaluar-contrato-modal');

                    // Limpiar formulario
                    formEvaluar.reset();

                    // Decidir siguiente paso
                    if (resultado.tiene_historial) {
                        // Paso 2: Mostrar historial
                        await mostrarHistorial(resultado.id_persona);
                    } else {
                        // Paso 3: Ir directo a crear
                        abrirModalCrear();
                    }

                } catch (error) {
                    console.error('Error evaluando contrato:', error);
                    alert('Error al evaluar. Intente nuevamente.');
                } finally {
                    btnEvaluar.disabled = false;
                    btnEvaluar.innerText = 'Evaluar';
                }
            });
        }

        // --- PASO 2: MOSTRAR HISTORIAL ---
        async function mostrarHistorial(idPersona) {
            try {
                const response = await fetch('/api/contratos/historial', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ id_persona: idPersona })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    console.error('Error en respuesta:', errorData);
                    throw new Error(errorData.error || errorData.message || 'Error al cargar historial');
                }

                const contratos = await response.json();

                // Llenar info de persona
                const persona = window.datosEvaluacion.persona;
                const infoPersona = `${persona.apellido_paterno} ${persona.apellido_materno || ''}, ${persona.nombres} - ${persona.tipo_documento}: ${persona.numero_documento}`;
                document.getElementById('historial-persona-info').innerText = infoPersona;

                // Llenar tabla
                const tbody = document.getElementById('tabla-historial-body');
                tbody.innerHTML = '';

                contratos.forEach((contrato) => {
                    const inicio = formatDateDisplay(contrato.inicio_contrato);
                    const fin = contrato.fin_contrato ? formatDateDisplay(contrato.fin_contrato) : 'Indefinido';
                    
                    const estadoCalculado = contrato.estado; // Esto ya debería ser la cadena del accessor
                    let estadoTexto;
                    let badgeClass;

                    if (estadoCalculado == 'Activo') {
                        badgeClass = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
                        estadoTexto = 'Activo';
                    } else if (estadoCalculado == 'Pendiente') {
                        badgeClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
                        estadoTexto = 'Pendiente';
                    } else { // 'Finalizado'
                        badgeClass = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
                        estadoTexto = 'Finalizado';
                    }

                    const inicialNombre = (contrato.persona?.nombres || '?').charAt(0);
                    const inicialApellido = (contrato.persona?.apellido_paterno || '?').charAt(0);

                    // Fila principal del contrato
                    const row = document.createElement('tr');
                    row.className = 'group transition-all duration-300 transform hover:scale-[1.01] hover:shadow-lg cursor-pointer';
                    row.innerHTML = `
                        <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-700 dark:text-white border-y">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold">
                                    ${inicialNombre}${inicialApellido}
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-800 dark:text-white">
                                        ${contrato.persona?.apellido_paterno || ''} ${contrato.persona?.nombres || 'Sin nombre'}
                                    </span>
                                    <span class="text-[11px] text-gray-500 font-medium">
                                        ${contrato.persona?.tipo_documento || 'DOC'}: ${contrato.persona?.numero_documento || '---'}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-700 dark:text-white border-y">${contrato.cargo?.nombre_cargo || 'N/A'}</td>
                        <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-700 dark:text-white border-y font-mono">S/ ${parseFloat(contrato.haber_basico || 0).toFixed(2)}</td>
                        <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-500 dark:text-white border-y">${inicio}</td>
                        <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-500 dark:text-white border-y">${fin}</td>
                        <td class="bg-white dark:bg-[#273142] px-6 py-2.5 border-y border-r rounded-r-xl">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeClass}">${estadoTexto}</span>
                        </td>
                    `;

                    // Click para expandir movimientos
                    row.addEventListener('click', () => {
                        const nextRow = row.nextElementSibling;
                        if (nextRow && nextRow.classList.contains('sub-row-hist')) {
                            nextRow.style.display = nextRow.style.display === 'none' ? 'table-row' : 'none';
                        }
                    });

                    tbody.appendChild(row);

                    // Sub-fila con movimientos
                    const subRow = document.createElement('tr');
                    subRow.className = 'sub-row-hist';
                    subRow.style.display = 'none';

                    let movimientosHtml = '<p class="text-center text-gray-500 py-4">Sin movimientos registrados</p>';

                    if (contrato.movimientos && contrato.movimientos.length > 0) {
                        movimientosHtml = `
                            <table class="min-w-full text-sm">
                                <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                                    <tr>
                                        <th class="py-2 px-3">Tipo</th>
                                        <th class="py-2 px-3">Fecha Efectiva</th>
                                        <th class="py-2 px-3">Salario</th>
                                        <th class="py-2 px-3">Cargo</th>
                                        <th class="py-2 px-3">Planilla</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${contrato.movimientos.map(mov => `
                                        <tr class="hover:bg-gray-100 dark:hover:bg-gray-700/50">
                                            <td class="py-2 px-3 text-gray-700 dark:text-gray-300">${mov.tipo_movimiento || '-'}</td>
                                            <td class="py-2 px-3 text-gray-700 dark:text-gray-300">${formatDateDisplay(mov.inicio)}</td>
                                            <td class="py-2 px-3 text-gray-700 dark:text-gray-300 font-mono">S/ ${parseFloat(mov.haber_basico || 0).toFixed(2)}</td>
                                            <td class="py-2 px-3 text-gray-700 dark:text-gray-300">${mov.cargo?.nombre_cargo || 'N/A'}</td>
                                            <td class="py-2 px-3 text-gray-700 dark:text-gray-300">${mov.planilla?.nombre_planilla || 'N/A'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        `;
                    }

                    subRow.innerHTML = `
                        <td colspan="6" class="p-0">
                            <div class="bg-gray-50 dark:bg-[#1e2836] p-4">${movimientosHtml}</div>
                        </td>
                    `;

                    tbody.appendChild(subRow);
                });

                // Abrir modal de historial
                openModal('historial-previa-modal');

            } catch (error) {
                console.error('Error cargando historial:', error);
                // Mostrar error pero permitir continuar
                if (confirm('Error al cargar historial: ' + error.message + '\n\n¿Desea continuar con la creacion del contrato?')) {
                    abrirModalCrear();
                }
            }
        }

        // Boton continuar desde historial
        const btnContinuarCrear = document.getElementById('btn-continuar-crear');
        if (btnContinuarCrear) {
            btnContinuarCrear.addEventListener('click', () => {
                closeModal('historial-previa-modal');
                abrirModalCrear();
            });
        }

        // --- PASO 3: ABRIR MODAL DE CREACION ---
        function abrirModalCrear() {
            if (!window.datosEvaluacion) {
                alert('Error: No hay datos de evaluacion. Inicie el proceso nuevamente.');
                return;
            }

            const datos = window.datosEvaluacion;

            // Llenar campos ocultos
            document.getElementById('crear-token').value = datos.token;
            document.getElementById('crear-id-persona').value = datos.id_persona;

            // Llenar cabecera con datos del colaborador
            const persona = datos.persona;
            const iniciales = `${(persona.nombres || '?').charAt(0)}${(persona.apellido_paterno || '?').charAt(0)}`;
            const nombreCompleto = `${persona.apellido_paterno} ${persona.apellido_materno || ''}, ${persona.nombres}`.trim();

            document.getElementById('crear-avatar').innerText = iniciales;
            document.getElementById('crear-persona-nombre-header').innerText = nombreCompleto;
            document.getElementById('crear-persona-documento').innerText = `${persona.tipo_documento}: ${persona.numero_documento}`;
            document.getElementById('crear-tipo-movimiento').innerText = datos.tipo_movimiento;

            // Llenar fecha de inicio
            document.getElementById('crear-inicio-contrato').value = datos.fecha_inicio;

            // Llamar a la validacion de fecha para bloquear el calendario de fin
            actualizarMinFechaFin();

            // Cargar selects y luego pre-cargar datos del ultimo contrato si existen
            cargarSelectsCrear().then(() => {
                if (datos.datos_ultimo_contrato) {
                    precargarDatosUltimoContrato(datos.datos_ultimo_contrato);
                }
            });

            // Abrir modal
            openModal('crear-contrato-modal');
        }

        // Pre-cargar datos del ultimo contrato en el formulario
        function precargarDatosUltimoContrato(datosUltimo) {
            // Esperar un momento para que los selects se llenen
            setTimeout(() => {
                // Selects
                if (datosUltimo.id_cargo) {
                    document.getElementById('crear-cargo').value = datosUltimo.id_cargo;
                }
                if (datosUltimo.id_planilla) {
                    document.getElementById('crear-planilla').value = datosUltimo.id_planilla;
                }
                if (datosUltimo.id_fp) {
                    document.getElementById('crear-fp').value = datosUltimo.id_fp;
                }
                if (datosUltimo.id_condicion) {
                    document.getElementById('crear-condicion').value = datosUltimo.id_condicion;
                }
                if (datosUltimo.id_banco) {
                    document.getElementById('crear-banco').value = datosUltimo.id_banco;
                }
                if (datosUltimo.id_moneda) {
                    document.getElementById('crear-moneda').value = datosUltimo.id_moneda;
                }
                if (datosUltimo.id_centro_costo) {
                    document.getElementById('crear-centro-costo').value = datosUltimo.id_centro_costo;
                }
                if (datosUltimo.id_familia) {
                    document.getElementById('crear-familia').value = datosUltimo.id_familia;
                }

                // Campos de texto/numero
                if (datosUltimo.haber_basico) {
                    document.getElementById('crear-haber-basico').value = datosUltimo.haber_basico;
                }
                if (datosUltimo.asignacion_familiar !== undefined) {
                    document.getElementById('crear-asignacion-familiar').value = datosUltimo.asignacion_familiar ? '1' : '0';
                }
                if (datosUltimo.numero_cuenta) {
                    document.getElementById('crear-numero-cuenta').value = datosUltimo.numero_cuenta;
                }
                if (datosUltimo.codigo_interbancario) {
                    document.getElementById('crear-codigo-interbancario').value = datosUltimo.codigo_interbancario;
                }
                if (datosUltimo.numero_cuenta_cts) {
                    document.getElementById('crear-numero-cuenta-cts').value = datosUltimo.numero_cuenta_cts;
                }
                if (datosUltimo.codigo_interbancario_cts) {
                    document.getElementById('crear-codigo-interbancario-cts').value = datosUltimo.codigo_interbancario_cts;
                }
                if (datosUltimo.periodo_prueba !== undefined) {
                    document.getElementById('crear-periodo-prueba').value = datosUltimo.periodo_prueba ? '1' : '0';
                }

                console.log('Datos pre-cargados exitosamente');
            }, 300); // Delay para asegurar que los selects esten cargados
        }

        // Cargar datos para selects del modal de creacion
        async function cargarSelectsCrear() {
            try {
                const responses = await Promise.all([
                    fetch('/api/cargos'),
                    fetch('/api/planillas'),
                    fetch('/api/fondos-pensiones'),
                    fetch('/api/condiciones'),
                    fetch('/api/bancos'),
                    fetch('/api/centros-costo'),
                    fetch('/api/familias'),
                    fetch('/api/monedas')
                ]);

                const [cargos, planillas, fondosPensiones, condiciones, bancos, centrosCosto, familias,  monedas] = await Promise.all(
                    responses.map(r => r.json())
                );

                populateSelect('crear-cargo', cargos, 'id_cargo', 'nombre_cargo');
                populateSelect('crear-planilla', planillas, 'id_planilla', 'nombre_planilla');
                populateSelect('crear-fp', fondosPensiones, 'id_fondo', 'fondo_pension');
                populateSelect('crear-condicion', condiciones, 'id_condicion', 'nombre_condicion');
                populateSelect('crear-banco', bancos, 'id_banco', 'nombre_banco');
                populateSelect('crear-centro-costo', centrosCosto, 'id_centro_costo', 'nombre');
                populateSelect('crear-familia', familias, 'id_familia', 'nombre');
                populateSelect('crear-moneda', monedas, 'id_moneda', 'nombre_moneda');

            } catch (error) {
                console.error('Error cargando selects de creacion:', error);
            }
        }

        // --- GUARDAR CONTRATO ---
        const formCrear = document.getElementById('form-crear-contrato');
        if (formCrear) {
            formCrear.addEventListener('submit', async (e) => {
                e.preventDefault();

                const formData = new FormData(formCrear);
                const data = Object.fromEntries(formData.entries());

                const btnGuardar = document.getElementById('btn-guardar-contrato');

                try {
                    btnGuardar.disabled = true;
                    btnGuardar.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Guardando...';

                    const response = await fetch('/contratos', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });

                    const resultado = await response.json();

                    if (response.ok && (resultado.ok || resultado.success)) {
                        alert(resultado.mensaje || resultado.message || 'Contrato creado exitosamente');
                        window.location.reload();
                    } else {
                        alert(resultado.error || resultado.mensaje || resultado.message || 'Error al crear el contrato');
                    }

                } catch (error) {
                    console.error('Error guardando contrato:', error);
                    alert('Error de conexion. Intente nuevamente.');
                } finally {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="fa-solid fa-save mr-2"></i>Guardar Contrato';
                }
            });
        }

        // Helper para formatear fechas (evita problemas de zona horaria)
        function formatDateDisplay(dateString) {
            if (!dateString) return '-';

            // Si viene en formato ISO con tiempo, extraer solo la fecha
            let datePart = dateString;
            if (dateString.includes('T')) {
                datePart = dateString.split('T')[0];
            }

            // Parsear manualmente para evitar problemas de zona horaria
            const parts = datePart.split('-');
            if (parts.length !== 3) return dateString;

            const year = parts[0];
            const month = parts[1];
            const day = parts[2];

            return `${day}/${month}/${year}`;
        }

        const crearInicioContratoInput = document.getElementById('crear-inicio-contrato');
        const crearFinContratoInput = document.getElementById('crear-fin-contrato');

        function actualizarMinFechaFin() {
            if (!crearInicioContratoInput || !crearFinContratoInput) return;

            const inicioDateString = crearInicioContratoInput.value;
            if (inicioDateString) {
                // Convertir a objeto Date, añadir un día, y convertir de nuevo a YYYY-MM-DD
                const startDate = new Date(inicioDateString + 'T00:00:00'); // Usar T00:00:00 para evitar problemas de zona horaria
                startDate.setDate(startDate.getDate() + 1);
                const nextDay = startDate.toISOString().split('T')[0];
                crearFinContratoInput.min = nextDay; // Ahora es estrictamente mayor
                
                // También actualizar el valor si ya no es válido (si es menor o igual al inicioDateString original)
                if (crearFinContratoInput.value && crearFinContratoInput.value <= inicioDateString) {
                    crearFinContratoInput.value = '';
                }

            } else {
                crearFinContratoInput.removeAttribute('min');
            }
        }
        
        if (crearInicioContratoInput && crearFinContratoInput) {
            crearInicioContratoInput.addEventListener('change', actualizarMinFechaFin);

            crearFinContratoInput.addEventListener('change', () => {
                const inicioDate = crearInicioContratoInput.value;
                const finDate = crearFinContratoInput.value;

                // Ahora debe ser estrictamente mayor, por lo tanto, si es menor o igual, es inválido
                if (inicioDate && finDate && finDate <= inicioDate) {
                    alert('La Fecha de Fin debe ser posterior a la Fecha de Inicio.');
                    crearFinContratoInput.value = ''; // Limpiar o ajustar
                }
            });
        }
        // --- FIN VALIDACION DE FECHAS EN MODAL DE CREACION ---

        // ============================================
        // F. Logica de guardado de movimiento (AJAX)
        const btnSaveMovimiento = document.getElementById('btn-save-movimiento');
        if (btnSaveMovimiento) {
            btnSaveMovimiento.addEventListener('click', async () => {
                const id = document.getElementById('edit-mov-id').value;
                if (!id) {
                    alert('Error: ID no encontrado.');
                    return;
                }

                const data = {
                    id_cargo: document.getElementById('edit-mov-cargo-id').value,
                    id_planilla: document.getElementById('edit-mov-planilla-id').value,
                    inicio: document.getElementById('edit-mov-inicio').value,
                    fin: document.getElementById('edit-mov-fin').value || null,
                    haber_basico: document.getElementById('edit-mov-haber').value,
                    movilidad: document.getElementById('edit-mov-movilidad').value,
                    asignacion_familiar: document.getElementById('edit-mov-asignacion').value,
                    id_fp: document.getElementById('edit-mov-fp-id').value,
                    id_condicion: document.getElementById('edit-mov-condicion-id').value,
                    id_banco: document.getElementById('edit-mov-banco-id').value,
                    id_centro_costo: document.getElementById('edit-mov-centro-costo-id').value,
                    id_familia: document.getElementById('edit-mov-familia-id').value,
                    id_moneda: document.getElementById('edit-mov-moneda-id').value
                };

                try {
                    btnSaveMovimiento.disabled = true;
                    btnSaveMovimiento.innerText = 'Guardando...';

                    const response = await fetch(`/contratos/movimientos/${id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });

                    if (response.ok) {
                        alert('Movimiento actualizado exitosamente');
                        window.location.reload();
                    } else {
                        const result = await response.json();
                        alert('Error: ' + (result.message || 'Verifique los datos'));
                    }
                } catch (error) {
                    console.error(error);
                    alert('Error de conexión');
                } finally {
                    btnSaveMovimiento.disabled = false;
                    btnSaveMovimiento.innerText = 'Guardar Cambios';
                }
            });
        }

        // ============================================
        // G. Logica de AÑADIR movimiento
        // ============================================

        // Guardar nuevo movimiento
        const btnAddMovimiento = document.getElementById('btn-add-movimiento');
        if (btnAddMovimiento) {
            btnAddMovimiento.addEventListener('click', async () => {
                const contratoId = document.getElementById('add-mov-contrato-id').value;
                if (!contratoId) {
                    alert('Error: ID de contrato no encontrado.');
                    return;
                }

                const data = {
                    id_contrato: contratoId,
                    tipo_movimiento: 'Movimiento Regular',
                    id_cargo: document.getElementById('add-mov-cargo-id').value,
                    id_planilla: document.getElementById('add-mov-planilla-id').value,
                    inicio: document.getElementById('add-mov-inicio').value,
                    fin: document.getElementById('add-mov-fin').value || null,
                    haber_basico: document.getElementById('add-mov-haber').value,
                    movilidad: document.getElementById('add-mov-movilidad').value,
                    asignacion_familiar: document.getElementById('add-mov-asignacion').value,
                    id_fp: document.getElementById('add-mov-fp-id').value,
                    id_condicion: document.getElementById('add-mov-condicion-id').value,
                    id_banco: document.getElementById('add-mov-banco-id').value,
                    id_centro_costo: document.getElementById('add-mov-centro-costo-id').value,
                    id_familia: document.getElementById('add-mov-familia-id').value,
                    id_moneda: document.getElementById('add-mov-moneda-id').value
                };

                try {
                    btnAddMovimiento.disabled = true;
                    btnAddMovimiento.innerText = 'Guardando...';

                    const response = await fetch('/contratos/movimientos', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });

                    if (response.ok) {
                        alert('Movimiento registrado exitosamente');
                        window.location.reload();
                    } else {
                        const result = await response.json();
                        alert('Error: ' + (result.message || 'Verifique los datos'));
                    }
                } catch (error) {
                    console.error(error);
                    alert('Error de conexión');
                } finally {
                    btnAddMovimiento.disabled = false;
                    btnAddMovimiento.innerText = 'Guardar Movimiento';
                }
            });
        }

        // ============================================
        // H. Logica de DAR DE BAJA
        // ============================================

        // H.1 Eliminar Baja
        const btnEliminarBaja = document.getElementById('btn-eliminar-baja');
        if (btnEliminarBaja) {
            btnEliminarBaja.addEventListener('click', async () => {
                const id = document.getElementById('baja-contrato-id').value;

                if (!id) {
                    alert('Error: ID de contrato no encontrado.');
                    return;
                }

                if (!confirm('¿Está seguro de eliminar esta baja? El contrato será reactivado.')) {
                    return;
                }

                try {
                    btnEliminarBaja.disabled = true;
                    btnEliminarBaja.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Eliminando...';

                    const response = await fetch(`/contratos/${id}/baja`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        alert(result.message || 'Baja eliminada correctamente');
                        window.location.reload();
                    } else {
                        alert('Error: ' + (result.message || 'No se pudo eliminar la baja'));
                    }
                } catch (error) {
                    console.error(error);
                    alert('Error de conexión');
                } finally {
                    btnEliminarBaja.disabled = false;
                    btnEliminarBaja.innerHTML = '<i class="fa-solid fa-trash-can mr-2"></i> Eliminar Baja';
                }
            });
        }

        // H.2 Confirmar Baja
        const btnConfirmarBaja = document.getElementById('btn-confirmar-baja');
        if (btnConfirmarBaja) {
            btnConfirmarBaja.addEventListener('click', async () => {
                const id = document.getElementById('baja-contrato-id').value;
                const fechaBaja = document.getElementById('baja-fecha').value;
                const motivoBaja = document.getElementById('baja-motivo').value;
                const avisoCon15Dias = document.getElementById('baja-aviso-15-dias').value;
                const recomiendaReingreso = document.getElementById('baja-recomienda-reingreso').value;
                const observacion = document.getElementById('baja-observacion').value;

                if (!id) {
                    alert('Error: ID de contrato no encontrado.');
                    return;
                }
                if (!fechaBaja) {
                    alert('Seleccione una fecha de baja.');
                    return;
                }
                if (!motivoBaja) {
                    alert('Seleccione un motivo de baja.');
                    return;
                }

                if (!confirm('¿Está seguro de registrar esta baja?')) {
                    return;
                }

                const textoOriginal = btnConfirmarBaja.innerText;

                try {
                    btnConfirmarBaja.disabled = true;
                    btnConfirmarBaja.innerText = 'Procesando...';

                    const response = await fetch(`/contratos/${id}/baja`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            fecha_baja: fechaBaja,
                            motivo_baja: motivoBaja,
                            aviso_con_15_dias: avisoCon15Dias === '1',
                            recomienda_reingreso: recomiendaReingreso === '1',
                            observacion: observacion
                        })
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        alert(result.message || 'Baja registrada correctamente');
                        window.location.reload();
                    } else {
                        alert('Error: ' + (result.message || 'No se pudo registrar la baja'));
                    }
                } catch (error) {
                    console.error(error);
                    alert('Error de conexión');
                } finally {
                    btnConfirmarBaja.disabled = false;
                    btnConfirmarBaja.innerText = textoOriginal;
                }
            });
        }

    })();
</script>
