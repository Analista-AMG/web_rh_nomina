// =============================================================
// Módulo Contratos
// =============================================================

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

// --- Helpers ---
function setTxt(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val || '—';
}

function setVal(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val ?? '';
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const datePart = dateString.includes('T') ? dateString.split('T')[0] : dateString;
    const [year, month, day] = datePart.split('-');
    if (!year || !month || !day) return dateString;
    return `${day}/${month}/${year}`;
}

async function apiFetch(url, options = {}) {
    return fetch(url, {
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            ...options.headers,
        },
        ...options,
    });
}

// --- Modales ---
window.openModal = function (id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    } else {
        console.error('Modal no encontrado:', id);
    }
};

window.closeModal = function (id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    if (id === 'evaluar-contrato-modal') {
        const docInput   = document.getElementById('evaluar-numero-documento');
        const fechaInput = document.getElementById('evaluar-fecha-inicio');
        const nombreDiv  = document.getElementById('evaluar-persona-nombre');
        if (docInput)   docInput.value = '';
        if (fechaInput) { fechaInput.value = ''; fechaInput.removeAttribute('min'); fechaInput.disabled = true; }
        if (nombreDiv)  { nombreDiv.textContent = ''; nombreDiv.classList.remove('text-red-500'); }
    }
};

// =============================================================
// A. BÚSQUEDA (debounce server-side)
// =============================================================
(function initBusqueda() {
    const nameInput = document.getElementById('server-search-name');
    const docInput  = document.getElementById('server-search-doc');
    let timer = null;

    function search() {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const url = new URL(window.location.href);
            nameInput?.value ? url.searchParams.set('search_name', nameInput.value) : url.searchParams.delete('search_name');
            docInput?.value  ? url.searchParams.set('search_doc',  docInput.value)  : url.searchParams.delete('search_doc');
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }, 1100);
    }

    nameInput?.addEventListener('input', search);
    docInput?.addEventListener('input', search);
})();

// =============================================================
// B. TABLA — Filas expandibles + botones de fila principal
// =============================================================
document.addEventListener('DOMContentLoaded', function () {

    // B.1 Expandir sub-filas al hacer click en la fila
    document.querySelectorAll('.expandable-row').forEach(row => {
        row.addEventListener('click', function (e) {
            if (e.target.closest('button, a, form')) return;

            const subRow = this.nextElementSibling;
            const icon   = this.querySelector('.fa-chevron-down');

            if (subRow?.classList.contains('sub-row')) {
                const isHidden = subRow.style.display === 'none' || subRow.style.display === '';
                subRow.style.display = isHidden ? 'table-row' : 'none';
                icon?.classList.toggle('rotate-180', isHidden);
            }
        });

        // B.2 Botones de la fila principal (Ver, Añadir Movimiento, Dar de Baja)
        row.querySelectorAll('.btn-view-contrato, .btn-add-movimiento-main, .btn-baja-contrato').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();

                if (this.classList.contains('btn-view-contrato'))       abrirVerContrato(this.dataset);
                if (this.classList.contains('btn-add-movimiento-main')) abrirAddMovimiento(this.dataset);
                if (this.classList.contains('btn-baja-contrato'))       abrirBajaContrato(this.dataset);
            });
        });

        // B.3 Botones de la sub-fila (cabecera: Editar contrato)
        row.nextElementSibling?.querySelector('.flex.justify-end')
            ?.querySelectorAll('.btn-view, .btn-edit, .btn-delete')
            ?.forEach(btn => btn.addEventListener('click', e => e.stopPropagation()));

        // B.4 Botones de movimientos dentro de la sub-fila
        row.nextElementSibling?.querySelectorAll('.btn-view-movimiento, .btn-edit-movimiento, .btn-delete-movimiento')
            ?.forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const data = this.closest('tr').dataset;

                    if (this.classList.contains('btn-view-movimiento'))   abrirVerMovimiento(data);
                    if (this.classList.contains('btn-edit-movimiento'))   abrirEditMovimiento(data);
                    if (this.classList.contains('btn-delete-movimiento')) eliminarMovimiento(data);
                });
            });
    });

    // B.5 Editar contrato (sub-fila cabecera)
    document.addEventListener('click', function (e) {
        const btnEdit = e.target.closest('.btn-edit');
        if (btnEdit) {
            const data = btnEdit.closest('tr').dataset;
            setVal('edit-id',       data.id);
            setVal('edit-empleado', data.empleado);
            setVal('edit-cargo',    data.cargo);
            setVal('edit-salario',  data.salario);
            setVal('edit-inicio',   data.inicio);
            setVal('edit-fin',      data.fin);
            setVal('edit-estado',   data.estado);
            openModal('edit-modal');
        }
    });
});

// =============================================================
// C. VER CONTRATO
// =============================================================
const statusMap = {
    'Activo':     { heroClass: 'view-hero-active',   avatarClass: 'view-avatar-active',   badgeClass: 'view-badge-active',   color: 'var(--color-success)' },
    'Pendiente':  { heroClass: 'view-hero-pending',  avatarClass: 'view-avatar-pending',  badgeClass: 'view-badge-pending',  color: 'var(--color-warning)' },
    'Finalizado': { heroClass: 'view-hero-inactive', avatarClass: 'view-avatar-inactive', badgeClass: 'view-badge-inactive', color: 'var(--color-danger)'  },
};

function abrirVerContrato(d) {
    const status   = statusMap[d.estado] ?? statusMap['Finalizado'];
    const heroEl   = document.getElementById('view-hero');
    const avatarEl = document.getElementById('view-avatar');
    const badgeEl  = document.getElementById('view-estado-badge');

    ['view-hero-active',   'view-hero-pending',   'view-hero-inactive'].forEach(c => heroEl?.classList.remove(c));
    ['view-avatar-active', 'view-avatar-pending', 'view-avatar-inactive'].forEach(c => avatarEl?.classList.remove(c));
    ['view-badge-active',  'view-badge-pending',  'view-badge-inactive'].forEach(c => badgeEl?.classList.remove(c));

    heroEl?.classList.add(status.heroClass);
    avatarEl?.classList.add(status.avatarClass);
    badgeEl?.classList.add(status.badgeClass);
    document.getElementById('view-modal')?.style.setProperty('--view-status-color', status.color);

    const words = (d.colaborador || '').replace(',', '').split(' ').filter(Boolean);
    setTxt('view-avatar-initials', ((words[0]?.[0] ?? '') + (words[words.length - 1]?.[0] ?? '')).toUpperCase() || '?');

    setTxt('view-estado-label',   d.estado);
    setTxt('view-nombre',         d.colaborador);
    setTxt('view-documento-chip', d.documento);
    setTxt('view-inicio-chip',    d.inicio);
    setTxt('view-fin-chip',       d.fin);

    setTxt('view-cargo',          d.cargo);
    setTxt('view-planilla',       d.planilla);
    setTxt('view-condicion',      d.condicion);
    setTxt('view-centro-costo',   d.centroCosto);
    setTxt('view-familia',        d.familia);
    setTxt('view-periodo-prueba', d.periodoPrueba);
    setTxt('view-inicio',         d.inicio);
    setTxt('view-fin',            d.fin);
    setTxt('view-fecha-renuncia', d.fechaRenuncia);

    setTxt('view-haber',      d.haber     ? 'S/ ' + d.haber     : '—');
    setTxt('view-movilidad',  d.movilidad ? 'S/ ' + d.movilidad : '—');
    setTxt('view-asignacion', d.asignacion);
    setTxt('view-moneda',     d.moneda);

    setTxt('view-fp',                       d.fp);
    setTxt('view-banco',                    d.banco);
    setTxt('view-numero-cuenta',            d.numeroCuenta);
    setTxt('view-codigo-interbancario',     d.codigoInterbancario);
    setTxt('view-numero-cuenta-cts',        d.numeroCuentaCts);
    setTxt('view-codigo-interbancario-cts', d.codigoInterbancarioCts);

    openModal('view-modal');
}

// =============================================================
// D. GUARDAR EDICIÓN DE CONTRATO
// =============================================================
document.getElementById('btn-save-contrato')?.addEventListener('click', async function () {
    const id = document.getElementById('edit-id')?.value;
    if (!id) { alert('Error: ID no encontrado.'); return; }

    const btn = this;
    const data = {
        inicio_contrato: document.getElementById('edit-inicio')?.value,
        fin_contrato:    document.getElementById('edit-fin')?.value,
        haber_basico:    document.getElementById('edit-salario')?.value,
    };

    try {
        btn.disabled = true; btn.innerText = 'Guardando...';
        const res = await apiFetch(`/contratos/${id}`, { method: 'PUT', body: JSON.stringify(data) });
        if (res.ok) { alert('Guardado exitosamente'); location.reload(); }
        else { const r = await res.json(); alert('Error: ' + (r.message || 'Verifique los datos')); }
    } catch { alert('Error de conexión'); }
    finally { btn.disabled = false; btn.innerText = 'Guardar Cambios'; }
});

// =============================================================
// E. SELECTS DIMENSIONALES — cargados una sola vez, con cache
// =============================================================
function populateSelect(selectId, data, valueField, textField) {
    const select = document.getElementById(selectId);
    if (!select) return;
    const frag = document.createDocumentFragment();
    const def = document.createElement('option');
    def.value = ''; def.textContent = 'Seleccione...';
    frag.appendChild(def);
    data.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item[valueField];
        opt.textContent = item[textField];
        frag.appendChild(opt);
    });
    select.innerHTML = '';
    select.appendChild(frag);
}

let _dimCache = null;
let _dimLoading = null;

async function loadDimensionales() {
    if (_dimCache) return _dimCache;
    if (!_dimLoading) {
        _dimLoading = Promise.all([
            fetch('/api/cargos').then(r => r.json()),
            fetch('/api/planillas').then(r => r.json()),
            fetch('/api/fondos-pensiones').then(r => r.json()),
            fetch('/api/condiciones').then(r => r.json()),
            fetch('/api/bancos').then(r => r.json()),
            fetch('/api/centros-costo').then(r => r.json()),
            fetch('/api/familias').then(r => r.json()),
            fetch('/api/monedas').then(r => r.json()),
        ]).then(([cargos, planillas, fps, condiciones, bancos, centros, familias, monedas]) => {
            _dimCache = { cargos, planillas, fps, condiciones, bancos, centros, familias, monedas };
            return _dimCache;
        });
    }
    return _dimLoading;
}

async function loadSelects(prefijo) {
    const selectEl = document.getElementById(`${prefijo}-cargo-id`);
    if (!selectEl) return;
    // Si ya está poblado y el cache existe, no repoblar
    if (_dimCache && selectEl.options.length > 1) return;
    try {
        const { cargos, planillas, fps, condiciones, bancos, centros, familias, monedas } = await loadDimensionales();
        const p = prefijo;
        populateSelect(`${p}-cargo-id`,       cargos,      'id_cargo',        'nombre_cargo');
        populateSelect(`${p}-planilla-id`,     planillas,   'id_planilla',     'nombre_planilla');
        populateSelect(`${p}-fp-id`,           fps,         'id_fondo',        'fondo_pension');
        populateSelect(`${p}-condicion-id`,    condiciones, 'id_condicion',    'nombre_condicion');
        populateSelect(`${p}-banco-id`,        bancos,      'id_banco',        'nombre_banco');
        populateSelect(`${p}-centro-costo-id`, centros,     'id_centro_costo', 'nombre');
        populateSelect(`${p}-familia-id`,      familias,    'id_familia',      'nombre');
        populateSelect(`${p}-moneda-id`,       monedas,     'id_moneda',       'nombre_moneda');
    } catch (err) {
        console.error('Error cargando selects:', prefijo, err);
    }
}

// Pre-cargar dimensionales al iniciar (warm-up, usan el mismo cache)
loadDimensionales();

// =============================================================
// F. VER MOVIMIENTO
// =============================================================
function abrirVerMovimiento(data) {
    // Para movimientos de sistema, el estado relevante es el del contrato
    const esRegular    = data.movTipo === 'Movimiento Regular';
    const estadoHero   = esRegular ? data.movEstado : (data.contratoEstado || data.movEstado);
    const status       = statusMap[estadoHero] ?? statusMap['Finalizado'];

    const heroEl   = document.getElementById('view-mov-hero');
    const avatarEl = document.getElementById('view-mov-avatar');
    const badgeEl  = document.getElementById('view-mov-estado-badge');

    ['view-hero-active', 'view-hero-pending', 'view-hero-inactive'].forEach(c => heroEl?.classList.remove(c));
    ['view-avatar-active', 'view-avatar-pending', 'view-avatar-inactive'].forEach(c => avatarEl?.classList.remove(c));
    ['view-badge-active',  'view-badge-pending',  'view-badge-inactive'].forEach(c => badgeEl?.classList.remove(c));

    heroEl?.classList.add(status.heroClass);
    avatarEl?.classList.add(status.avatarClass);
    badgeEl?.classList.add(status.badgeClass);
    document.getElementById('view-movimiento-modal')?.style.setProperty('--view-status-color', status.color);

    // Hero
    const movWords = (data.movColaborador || '').split(' ').filter(Boolean);
    setTxt('view-mov-avatar-initials', ((movWords[0]?.[0] ?? '') + (movWords[movWords.length - 1]?.[0] ?? '')).toUpperCase() || '?');
    setTxt('view-mov-estado-label',   estadoHero || '—');
    setTxt('view-mov-nombre',         data.movColaborador || '—');
    setTxt('view-mov-tipo-chip',      data.movTipo        || '—');
    setTxt('view-mov-inicio-chip',    data.movInicio      || '—');
    setTxt('view-mov-fin-chip',       data.movFin         || 'Indefinido');

    // Columna izquierda
    setTxt('view-mov-cargo',          data.movCargo       || 'N/A');
    setTxt('view-mov-planilla',       data.movPlanilla    || 'N/A');
    setTxt('view-mov-condicion',      data.movCondicion   || 'N/A');
    setTxt('view-mov-centro-costo',   data.movCentroCosto || 'N/A');
    setTxt('view-mov-familia',        data.movFamilia     || 'N/A');
    setTxt('view-mov-asignacion',     data.movAsignacion  || 'No');
    setTxt('view-mov-inicio',         data.movInicio       || '—');
    setTxt('view-mov-fin',            data.movFin          || 'Indefinido');
    setTxt('view-mov-fecha-registro', data.movFechaRegistro || '—');

    // Columna derecha
    setTxt('view-mov-haber',          data.movHaber     ? 'S/ ' + data.movHaber     : '—');
    setTxt('view-mov-movilidad',      data.movMovilidad ? 'S/ ' + data.movMovilidad : '—');
    setTxt('view-mov-moneda',         data.movMoneda    || 'N/A');
    setTxt('view-mov-fp',                     data.movFp                  || 'N/A');
    setTxt('view-mov-banco',                  data.movBanco               || 'N/A');
    setTxt('view-mov-numero-cuenta',          data.movNumeroCuenta        || '—');
    setTxt('view-mov-codigo-interbancario',   data.movCodigoInterbancario || '—');

    openModal('view-movimiento-modal');
}

// =============================================================
// G. EDITAR MOVIMIENTO
// =============================================================
function collectMovData(p) {
    return {
        cargo_id:            document.getElementById(`${p}-cargo-id`)?.value,
        planilla_id:         document.getElementById(`${p}-planilla-id`)?.value,
        inicio:              document.getElementById(`${p}-inicio`)?.value,
        fin:                 document.getElementById(`${p}-fin`)?.value || null,
        haber_basico:        document.getElementById(`${p}-haber`)?.value,
        movilidad:           document.getElementById(`${p}-movilidad`)?.value,
        asignacion_familiar: document.getElementById(`${p}-asignacion`)?.value,
        fondo_pensiones_id:  document.getElementById(`${p}-fp-id`)?.value,
        condicion_id:        document.getElementById(`${p}-condicion-id`)?.value,
        banco_id:            document.getElementById(`${p}-banco-id`)?.value,
        centro_costo_id:     document.getElementById(`${p}-centro-costo-id`)?.value,
        familia_id:          document.getElementById(`${p}-familia-id`)?.value,
        moneda_id:           document.getElementById(`${p}-moneda-id`)?.value,
    };
}

async function abrirEditMovimiento(data) {
    // Header
    const eWords  = (data.movColaborador || '').split(' ').filter(Boolean);
    const eAvatar = document.getElementById('edit-mov-avatar');
    if (eAvatar) eAvatar.innerText = ((eWords[0]?.[0] ?? '') + (eWords[eWords.length - 1]?.[0] ?? '')).toUpperCase() || '?';
    setTxt('edit-mov-nombre-header',    data.movColaborador || 'Colaborador');
    setTxt('edit-mov-documento-header', data.movDocumento   || '');
    setTxt('edit-mov-tipo-badge',       data.movTipo        || '');

    // Fechas
    const editInicio = document.getElementById('edit-mov-inicio');
    const editFin    = document.getElementById('edit-mov-fin');
    editInicio.min   = data.contratoInicio || '';
    editInicio.max   = data.contratoFin    || '';
    editFin.min      = data.contratoInicio || '';
    editFin.max      = data.contratoFin    || '';
    editInicio.value = data.movInicioRaw   || '';
    editFin.value    = data.movFinRaw      || '';

    // Campos simples
    setVal('edit-mov-id',         data.movId            || '');
    setVal('edit-mov-haber',      parseFloat(data.movHaberRaw || 0).toFixed(2));
    setVal('edit-mov-asignacion', data.movAsignacionRaw || '0');

    openModal('edit-movimiento-modal');

    // Esperar a que los selects estén poblados antes de setear valores
    await loadSelects('edit-mov');
    setVal('edit-mov-cargo-id',        data.movCargoId       || '');
    setVal('edit-mov-planilla-id',     data.movPlanillaId    || '');
    setVal('edit-mov-fp-id',           data.movFpId          || '');
    setVal('edit-mov-condicion-id',    data.movCondicionId   || '');
    setVal('edit-mov-banco-id',        data.movBancoId       || '');
    setVal('edit-mov-centro-costo-id', data.movCentroCostoId || '');
    setVal('edit-mov-familia-id',      data.movFamiliaId     || '');
    setVal('edit-mov-moneda-id',       data.movMonedaId      || '');
}

document.getElementById('btn-save-movimiento')?.addEventListener('click', async function () {
    const id = document.getElementById('edit-mov-id')?.value;
    if (!id) { alert('Error: ID no encontrado.'); return; }

    const btn = this;
    try {
        btn.disabled = true; btn.innerText = 'Guardando...';
        const res = await apiFetch(`/contratos/movimientos/${id}`, { method: 'PUT', body: JSON.stringify(collectMovData('edit-mov')) });
        if (res.ok) { alert('Movimiento actualizado exitosamente'); location.reload(); }
        else { const r = await res.json(); alert('Error: ' + (r.message || 'Verifique los datos')); }
    } catch { alert('Error de conexión'); }
    finally { btn.disabled = false; btn.innerText = 'Guardar Cambios'; }
});

// =============================================================
// H. AÑADIR MOVIMIENTO
// =============================================================
async function abrirAddMovimiento(dataset) {
    document.getElementById('form-add-movimiento')?.reset();
    setVal('add-mov-contrato-id',   dataset.contratoId);
    setTxt('add-mov-nombre-header', dataset.colaborador || 'Colaborador');
    setTxt('add-mov-documento',     dataset.documento   || '');

    const words = (dataset.colaborador || '').split(' ').filter(Boolean);
    const avatarEl = document.getElementById('add-mov-avatar');
    if (avatarEl) avatarEl.innerText = ((words[0]?.[0] ?? '') + (words[words.length - 1]?.[0] ?? '')).toUpperCase() || '?';

    const inputInicio = document.getElementById('add-mov-inicio');
    const inputFin    = document.getElementById('add-mov-fin');
    const minFecha    = dataset.contratoInicio || '';
    const maxFecha    = dataset.contratoFin    || '';

    if (inputInicio) { inputInicio.min = minFecha; inputInicio.max = maxFecha; }
    if (inputFin)    { inputFin.min    = minFecha; inputFin.max    = maxFecha; }

    openModal('add-movimiento-modal');

    // Esperar a que los selects estén poblados antes de pre-llenar con último movimiento
    await loadSelects('add-mov');

    try {
        const lastMov = JSON.parse(dataset.lastMov || '{}');
        if (lastMov.haber !== undefined) {
            setVal('add-mov-haber',           parseFloat(lastMov.haber || 0).toFixed(2));
            setVal('add-mov-asignacion',      lastMov.asignacion         || '0');
            setVal('add-mov-cargo-id',        lastMov.cargo_id           || '');
            setVal('add-mov-planilla-id',     lastMov.planilla_id        || '');
            setVal('add-mov-fp-id',           lastMov.fondo_pensiones_id || '');
            setVal('add-mov-condicion-id',    lastMov.condicion_id       || '');
            setVal('add-mov-banco-id',        lastMov.banco_id           || '');
            setVal('add-mov-centro-costo-id', lastMov.centro_costo_id    || '');
            setVal('add-mov-familia-id',      lastMov.familia_id         || '');
            setVal('add-mov-moneda-id',       lastMov.moneda_id          || '');
        }
    } catch (e) {
        console.error('Error parseando datos del último movimiento:', e);
    }
}

document.getElementById('btn-add-movimiento')?.addEventListener('click', async function () {
    const contratoId = document.getElementById('add-mov-contrato-id')?.value;
    if (!contratoId) { alert('Error: ID de contrato no encontrado.'); return; }

    const btn = this;
    const data = { contrato_id: contratoId, tipo_movimiento: 'Movimiento Regular', ...collectMovData('add-mov') };

    try {
        btn.disabled = true; btn.innerText = 'Guardando...';
        const res = await apiFetch('/contratos/movimientos', { method: 'POST', body: JSON.stringify(data) });
        if (res.ok) { alert('Movimiento registrado exitosamente'); location.reload(); }
        else { const r = await res.json(); alert('Error: ' + (r.message || 'Verifique los datos')); }
    } catch { alert('Error de conexión'); }
    finally { btn.disabled = false; btn.innerText = 'Guardar Movimiento'; }
});

// =============================================================
// I. ELIMINAR MOVIMIENTO
// =============================================================
async function eliminarMovimiento(data) {
    const esRegular = data.movTipo === 'Movimiento Regular';
    const mensaje = esRegular
        ? '¿Estás seguro de eliminar este movimiento?'
        : '¿Estás seguro? Esto eliminará el CONTRATO COMPLETO y todos sus movimientos.';

    if (!confirm(mensaje)) return;

    try {
        const res = await apiFetch(`/contratos/movimientos/${data.movId}`, { method: 'DELETE' });
        const result = await res.json();
        if (result.success) { alert(result.message); location.reload(); }
        else { alert(result.error || 'Error al eliminar'); }
    } catch { alert('Error de conexión'); }
}

// =============================================================
// J. DAR DE BAJA
// =============================================================
function abrirBajaContrato(dataset) {
    let bajaData = {};
    try { bajaData = JSON.parse(dataset.baja || '{}'); } catch (e) { console.error(e); }

    const esActualizacion = bajaData.id_baja !== undefined;

    document.getElementById('form-baja-contrato')?.reset();
    setVal('baja-contrato-id', dataset.contratoId);
    setVal('baja-id',          bajaData.id_baja || '');
    setTxt('baja-colaborador-nombre', dataset.colaboradorNombre || '');
    setTxt('baja-colaborador-doc',    dataset.colaboradorDoc    || '');

    const inputFecha = document.getElementById('baja-fecha');
    if (inputFecha) { inputFecha.min = dataset.contratoInicio || ''; inputFecha.max = dataset.contratoFin || ''; }

    const titulo            = document.getElementById('baja-modal-titulo');
    const btnConfirmar      = document.getElementById('btn-confirmar-baja');
    const advertencia       = document.getElementById('baja-advertencia-texto');
    const eliminarContainer = document.getElementById('baja-eliminar-container');
    const spacer            = document.getElementById('baja-spacer');

    if (esActualizacion) {
        if (inputFecha) inputFecha.value = bajaData.fecha_baja || '';
        setVal('baja-motivo',               bajaData.motivo_baja         || '');
        setVal('baja-aviso-15-dias',        bajaData.aviso_con_15_dias   || '0');
        setVal('baja-recomienda-reingreso', bajaData.recomienda_reingreso || '1');
        setVal('baja-observacion',          bajaData.observacion          || '');

        if (titulo)       titulo.textContent      = 'Actualizar Baja';
        if (btnConfirmar) btnConfirmar.textContent = 'Actualizar Baja';
        if (advertencia)  advertencia.innerHTML   = 'Este contrato ya tiene una baja registrada. Puede modificar los datos o eliminarla.';
        eliminarContainer?.classList.remove('hidden');
        spacer?.classList.add('hidden');
    } else {
        if (titulo)       titulo.textContent      = 'Dar de Baja';
        if (btnConfirmar) btnConfirmar.textContent = 'Confirmar Baja';
        if (advertencia)  advertencia.innerHTML   = 'Esta acción registrará la baja del colaborador. El contrato pasará a estado <strong>Finalizado</strong> una vez cumplida la fecha.';
        eliminarContainer?.classList.add('hidden');
        spacer?.classList.remove('hidden');
    }

    openModal('baja-contrato-modal');
}

document.getElementById('btn-confirmar-baja')?.addEventListener('click', async function () {
    const id     = document.getElementById('baja-contrato-id')?.value;
    const fecha  = document.getElementById('baja-fecha')?.value;
    const motivo = document.getElementById('baja-motivo')?.value;

    if (!id)     { alert('Error: ID de contrato no encontrado.'); return; }
    if (!fecha)  { alert('Seleccione una fecha de baja.'); return; }
    if (!motivo) { alert('Seleccione un motivo de baja.'); return; }
    if (!confirm('¿Está seguro de registrar esta baja?')) return;

    const btn = this;
    const textoOriginal = btn.innerText;

    try {
        btn.disabled = true; btn.innerText = 'Procesando...';
        const res = await apiFetch(`/contratos/${id}/baja`, {
            method: 'PUT',
            body: JSON.stringify({
                fecha_baja:           fecha,
                motivo_baja:          motivo,
                aviso_con_15_dias:    document.getElementById('baja-aviso-15-dias')?.value === '1',
                recomienda_reingreso: document.getElementById('baja-recomienda-reingreso')?.value === '1',
                observacion:          document.getElementById('baja-observacion')?.value,
            }),
        });
        const result = await res.json();
        if (res.ok && result.success) { alert(result.message); location.reload(); }
        else { alert('Error: ' + (result.message || 'No se pudo registrar la baja')); }
    } catch { alert('Error de conexión'); }
    finally { btn.disabled = false; btn.innerText = textoOriginal; }
});

document.getElementById('btn-eliminar-baja')?.addEventListener('click', async function () {
    const id = document.getElementById('baja-contrato-id')?.value;
    if (!id) { alert('Error: ID de contrato no encontrado.'); return; }
    if (!confirm('¿Está seguro de eliminar esta baja? El contrato será reactivado.')) return;

    const btn = this;
    try {
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Eliminando...';
        const res = await apiFetch(`/contratos/${id}/baja`, { method: 'DELETE' });
        const result = await res.json();
        if (res.ok && result.success) { alert(result.message); location.reload(); }
        else { alert('Error: ' + (result.message || 'No se pudo eliminar la baja')); }
    } catch { alert('Error de conexión'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-trash-can mr-2"></i> Eliminar Baja'; }
});

// =============================================================
// K. FLUJO CREAR CONTRATO (3 pasos: Evaluar → Historial → Crear)
// =============================================================
window.datosEvaluacion = null;

// K.1 Buscar persona al salir del campo documento
document.getElementById('evaluar-numero-documento')?.addEventListener('blur', async function () {
    const doc        = this.value;
    const fechaInput = document.getElementById('evaluar-fecha-inicio');
    const nombreDiv  = document.getElementById('evaluar-persona-nombre');

    if (!fechaInput || !nombreDiv) return;

    nombreDiv.textContent = '';
    fechaInput.value = '';
    fechaInput.removeAttribute('min');
    fechaInput.disabled = true;

    if (!doc || doc.length < 8) return;

    nombreDiv.textContent = 'Buscando...';
    nombreDiv.classList.remove('text-red-500');

    try {
        const res  = await fetch(`/api/personas/${doc}/ultimo-inicio`);
        const data = await res.json();

        if (!res.ok || !data.persona_nombre) {
            nombreDiv.textContent = 'No se encontró el documento.';
            nombreDiv.classList.add('text-red-500');
            return;
        }

        nombreDiv.textContent = data.persona_nombre;
        nombreDiv.classList.remove('text-red-500');

        if (data.ultimo_fin_contrato) {
            const finDate = new Date(data.ultimo_fin_contrato + 'T00:00:00');
            finDate.setDate(finDate.getDate() + 1);
            const proximaFecha = finDate.toISOString().split('T')[0];
            fechaInput.min   = proximaFecha;
            fechaInput.value = proximaFecha;
        }

        fechaInput.disabled = false;
    } catch (err) {
        console.error('K.1 error:', err);
        nombreDiv.textContent = 'Error de conexión.';
        nombreDiv.classList.add('text-red-500');
    }
});

// K.2 Paso 1: Evaluar contrato
document.getElementById('form-evaluar-contrato')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const doc         = document.getElementById('evaluar-numero-documento')?.value;
    const fechaInicio = document.getElementById('evaluar-fecha-inicio')?.value;
    const btn         = document.getElementById('btn-evaluar-contrato');

    if (!doc || !fechaInicio) { alert('Por favor complete todos los campos'); return; }

    try {
        btn.disabled = true; btn.innerText = 'Evaluando...';
        const res = await apiFetch('/api/contratos/evaluar', {
            method: 'POST',
            body: JSON.stringify({ numero_documento: doc, fecha_inicio: fechaInicio }),
        });
        const resultado = await res.json();

        if (!res.ok || !resultado.ok) {
            alert(resultado.error || resultado.message || 'No se puede crear el contrato.');
            return;
        }

        window.datosEvaluacion = { ...resultado, numero_documento: doc, fecha_inicio: fechaInicio };

        closeModal('evaluar-contrato-modal');

        if (resultado.tiene_historial) {
            await cargarHistorial(resultado.persona_id);
        } else {
            abrirModalCrear();
        }
    } catch { alert('Error de conexión'); }
    finally { btn.disabled = false; btn.innerText = 'Evaluar'; }
});

// K.3 Paso 2: Historial previo
async function cargarHistorial(personaId) {
    try {
        const res  = await apiFetch('/api/contratos/historial', { method: 'POST', body: JSON.stringify({ persona_id: personaId }) });
        const data = await res.json();

        const tbody = document.getElementById('historial-tbody');
        if (!tbody) return;
        tbody.innerHTML = '';

        const contratos = Array.isArray(data) ? data : (data.data || []);

        contratos.forEach(contrato => {
            const inicio = formatDate(contrato.inicio_contrato);
            const fin    = contrato.fin_contrato ? formatDate(contrato.fin_contrato) : 'Indefinido';

            const badgeMap = {
                'Activo':     'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                'Pendiente':  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                'Finalizado': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            };
            const badgeClass = badgeMap[contrato.estado] ?? badgeMap['Finalizado'];
            const _w       = `${contrato.persona?.apellido_paterno || ''} ${contrato.persona?.apellido_materno || ''} ${contrato.persona?.nombres || ''}`.trim().split(' ').filter(Boolean);
            const inicialA = (_w[0]?.[0] ?? '?').toUpperCase();
            const inicialN = (_w[_w.length - 1]?.[0] ?? '').toUpperCase();

            const row = document.createElement('tr');
            row.className = 'group transition-all duration-300 hover:scale-[1.01] hover:shadow-lg cursor-pointer';
            row.innerHTML = `
                <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm text-gray-700 dark:text-white border-y">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold">${inicialN}${inicialA}</div>
                        <div class="flex flex-col">
                            <span class="text-sm font-bold">${contrato.persona?.apellido_paterno || ''} ${contrato.persona?.nombres || 'Sin nombre'}</span>
                            <span class="text-[11px] text-gray-500">${contrato.persona?.tipo_documento || 'DOC'}: ${contrato.persona?.numero_documento || '---'}</span>
                        </div>
                    </div>
                </td>
                <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm border-y">${contrato.cargo?.nombre_cargo || 'N/A'}</td>
                <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm font-mono border-y">S/ ${parseFloat(contrato.haber_basico || 0).toFixed(2)}</td>
                <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm border-y">${inicio}</td>
                <td class="bg-white dark:bg-[#273142] px-6 py-2.5 text-sm border-y">${fin}</td>
                <td class="bg-white dark:bg-[#273142] px-6 py-2.5 border-y border-r rounded-r-xl">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeClass}">${contrato.estado}</span>
                </td>
            `;

            const subRow = document.createElement('tr');
            subRow.className = 'sub-row-hist';
            subRow.style.display = 'none';

            let movHtml = '<p class="text-center text-gray-500 py-4">Sin movimientos registrados</p>';
            if (contrato.movimientos?.length) {
                movHtml = `<table class="min-w-full text-sm"><thead class="text-xs text-gray-500 uppercase"><tr>
                    <th class="py-2 px-3">Tipo</th><th class="py-2 px-3">Fecha</th>
                    <th class="py-2 px-3">Salario</th><th class="py-2 px-3">Cargo</th><th class="py-2 px-3">Planilla</th>
                </tr></thead><tbody>${contrato.movimientos.map(mov => `
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-700/50">
                        <td class="py-2 px-3">${mov.tipo_movimiento || '-'}</td>
                        <td class="py-2 px-3">${formatDate(mov.inicio)}</td>
                        <td class="py-2 px-3 font-mono">S/ ${parseFloat(mov.haber_basico || 0).toFixed(2)}</td>
                        <td class="py-2 px-3">${mov.cargo?.nombre_cargo || 'N/A'}</td>
                        <td class="py-2 px-3">${mov.planilla?.nombre_planilla || 'N/A'}</td>
                    </tr>`).join('')}</tbody></table>`;
            }

            subRow.innerHTML = `<td colspan="6"><div class="bg-gray-50 dark:bg-[#1e2836] p-4">${movHtml}</div></td>`;

            row.addEventListener('click', () => {
                subRow.style.display = subRow.style.display === 'none' ? 'table-row' : 'none';
            });

            tbody.appendChild(row);
            tbody.appendChild(subRow);
        });

        openModal('historial-previa-modal');
    } catch (err) {
        console.error('Error cargando historial:', err);
        if (confirm('Error al cargar historial. ¿Desea continuar con la creación del contrato?')) {
            abrirModalCrear();
        }
    }
}

document.getElementById('btn-continuar-crear')?.addEventListener('click', () => {
    closeModal('historial-previa-modal');
    abrirModalCrear();
});

// K.4 Paso 3: Modal de creación
async function abrirModalCrear() {
    const datos = window.datosEvaluacion;
    if (!datos) { alert('Error: No hay datos de evaluación. Inicie el proceso nuevamente.'); return; }

    const persona = datos.persona;
    setVal('crear-token',      datos.token);
    setVal('crear-id-persona', datos.persona_id);

    const primerNombre   = (persona.nombres || '').trim().split(' ').filter(Boolean)[0] || '';
    const iniciales      = ((persona.apellido_paterno?.[0] ?? '') + (primerNombre[0] ?? '')).toUpperCase() || '?';
    const nombreCompleto = `${persona.apellido_paterno} ${persona.apellido_materno || ''}, ${persona.nombres}`.trim();

    const avatarEl = document.getElementById('crear-avatar');
    if (avatarEl) avatarEl.innerText = iniciales;
    setTxt('crear-persona-nombre-header', nombreCompleto);
    setTxt('crear-persona-documento',     `${persona.tipo_documento}: ${persona.numero_documento}`);
    setTxt('crear-tipo-movimiento',       datos.tipo_movimiento);

    setVal('crear-inicio-contrato', datos.fecha_inicio);
    actualizarMinFechaFin();

    openModal('crear-contrato-modal');

    // Esperar que los selects estén poblados antes de precargar
    await loadSelects('crear');

    if (datos.datos_ultimo_contrato) {
        const d = datos.datos_ultimo_contrato;
        setVal('crear-cargo-id',                 d.cargo_id                || '');
        setVal('crear-planilla-id',              d.planilla_id             || '');
        setVal('crear-fp-id',                    d.fondo_pensiones_id      || '');
        setVal('crear-condicion-id',             d.condicion_id            || '');
        setVal('crear-banco-id',                 d.banco_id                || '');
        setVal('crear-moneda-id',                d.moneda_id               || '');
        setVal('crear-centro-costo-id',          d.centro_costo_id         || '');
        setVal('crear-familia-id',               d.familia_id              || '');
        setVal('crear-haber-basico',             d.haber_basico            || '');
        setVal('crear-asignacion-familiar',      d.asignacion_familiar ? '1' : '0');
        setVal('crear-numero-cuenta',            d.numero_cuenta            || '');
        setVal('crear-codigo-interbancario',     d.codigo_interbancario     || '');
        setVal('crear-numero-cuenta-cts',        d.numero_cuenta_cts        || '');
        setVal('crear-codigo-interbancario-cts', d.codigo_interbancario_cts || '');
        setVal('crear-periodo-prueba',           d.periodo_prueba ? '1' : '0');
    }
}

// K.5 Validación de fechas en modal crear
const crearInicio = document.getElementById('crear-inicio-contrato');
const crearFin    = document.getElementById('crear-fin-contrato');

function actualizarMinFechaFin() {
    if (!crearInicio || !crearFin) return;
    const val = crearInicio.value;
    if (val) {
        const d = new Date(val + 'T00:00:00');
        d.setDate(d.getDate() + 1);
        const nextDay = d.toISOString().split('T')[0];
        crearFin.min = nextDay;
        if (crearFin.value && crearFin.value <= val) crearFin.value = '';
    } else {
        crearFin.removeAttribute('min');
    }
}

crearInicio?.addEventListener('change', actualizarMinFechaFin);
crearFin?.addEventListener('change', () => {
    if (crearInicio?.value && crearFin.value && crearFin.value <= crearInicio.value) {
        alert('La Fecha de Fin debe ser posterior a la Fecha de Inicio.');
        crearFin.value = '';
    }
});

// K.6 Guardar contrato
document.getElementById('form-crear-contrato')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn  = document.getElementById('btn-guardar-contrato');
    const data = Object.fromEntries(new FormData(this).entries());

    try {
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Guardando...';
        const res    = await apiFetch('/contratos', { method: 'POST', body: JSON.stringify(data) });
        const result = await res.json();
        if (res.ok && (result.ok || result.success)) { alert(result.mensaje || result.message || 'Contrato creado exitosamente'); location.reload(); }
        else { alert(result.error || result.mensaje || result.message || 'Error al crear el contrato'); }
    } catch { alert('Error de conexión. Intente nuevamente.'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-save mr-2"></i>Guardar Contrato'; }
});
