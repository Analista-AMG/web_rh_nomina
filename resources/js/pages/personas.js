// --- Funciones globales de modal ---
window.openModal = function (id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    if (id === 'create-modal') {
        document.getElementById('create-form')?.reset();
        window.resetCreateModalState?.();
    }
};

window.closeModal = function (id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('hidden'); document.body.style.overflow = 'auto'; }
};

// --- Lógica del módulo ---
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    // ── Helpers ──────────────────────────────────────────────────────────

    const jsonHeaders = () => ({
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
    });

    const baseHeaders = () => ({ 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' });

    function setVal(id, val) {
        const el = document.getElementById(id);
        if (!el) return;
        if (el.tagName === 'INPUT' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA') {
            el.value = val || '';
        } else {
            el.textContent = val || '—';
        }
    }

    function mb_substr(str, pos) {
        return (str || '').trim().charAt(pos) || '';
    }

    function normalizeNameInput(id) {
        const el = document.getElementById(id);
        if (!el) return;
        const normalized = String(el.value || '').replace(/\s+/g, ' ').trim().toUpperCase();
        if (el.value !== normalized) el.value = normalized;
    }

    function feedback(id, isValid, message = '') {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('text-green-600', isValid === true);
        el.classList.toggle('text-red-600',   isValid === false);
        el.textContent = message;
    }

    function updateEmailFeedback(inputId, feedbackId) {
        const input = document.getElementById(inputId);
        if (!input) return;
        if (!(input.value || '').trim()) { feedback(feedbackId, null); return; }
        feedback(feedbackId, input.validity.valid,
            input.validity.valid ? 'Correo válido' : 'Correo inválido'
        );
    }

    const normalizeTelefono = (val) => val ? String(val).trim() : '';

    function getMessage(result, fallback) {
        return result?.message
            ?? Object.values(result?.errors ?? {})[0]?.[0]
            ?? fallback;
    }

    // ── Status map compartido ─────────────────────────────────────────────

    const statusMap = {
        '1': { label: 'Activo',    heroClass: 'view-hero-active',   avatarClass: 'view-avatar-active',   badgeClass: 'view-badge-active',   color: 'var(--color-success)' },
        '2': { label: 'Pendiente', heroClass: 'view-hero-pending',  avatarClass: 'view-avatar-pending',  badgeClass: 'view-badge-pending',  color: 'var(--color-warning)' },
        '0': { label: 'Inactivo',  heroClass: 'view-hero-inactive', avatarClass: 'view-avatar-inactive', badgeClass: 'view-badge-inactive', color: 'var(--color-danger)'  },
    };
    const heroClasses   = ['view-hero-active',   'view-hero-pending',   'view-hero-inactive'];
    const avatarClasses = ['view-avatar-active', 'view-avatar-pending', 'view-avatar-inactive'];
    const badgeClasses  = ['view-badge-active',  'view-badge-pending',  'view-badge-inactive'];

    function applyStatus(estado, modalId, headerId, cssVar) {
        const s  = statusMap[String(estado)] || statusMap['0'];
        const hd = document.getElementById(headerId);
        heroClasses.forEach(c => hd?.classList.remove(c));
        hd?.classList.add(s.heroClass);
        document.getElementById(modalId)?.style.setProperty(cssVar, s.color);
        return s;
    }

    // ── A. Crear ──────────────────────────────────────────────────────────

    document.getElementById('create-form')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        ['new-nombres', 'new-apellido_paterno', 'new-apellido_materno'].forEach(normalizeNameInput);
        try {
            const formData = new FormData(this);
            formData.delete('_token');
            const response = await fetch('/personas', {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify(Object.fromEntries(formData)),
            });
            const result = await response.json().catch(() => ({}));
            if (response.ok) { alert(getMessage(result, 'Persona registrada correctamente')); window.location.reload(); }
            else alert(getMessage(result, 'Error: Verifique los datos'));
        } catch { alert('Error de conexión'); }
    });

    // ── A2. Autocompletar RENIEC ──────────────────────────────────────────

    let reniecTimer = null;
    let reniecAbort = null;

    async function fetchReniecData(doc) {
        if (!doc || doc.length < 8) return;
        reniecAbort?.abort();
        reniecAbort = new AbortController();
        try {
            const res = await fetch(`/api/personas/reniec/${encodeURIComponent(doc)}`, {
                headers: { 'Accept': 'application/json' },
                signal: reniecAbort.signal,
            });
            if (!res.ok) return;
            const { found, data } = await res.json().catch(() => ({}));
            if (!found || !data) return;
            setVal('new-nombres',           data.nombres);
            setVal('new-apellido_paterno',  data.apellido_paterno);
            setVal('new-apellido_materno',  data.apellido_materno);
            setVal('new-fecha_nacimiento',  data.fecha_nacimiento);
            if (data.genero) setVal('new-genero', data.genero);
        } catch (e) { if (e.name !== 'AbortError') console.error(e); }
    }

    document.getElementById('new-numero_documento')?.addEventListener('input', function () {
        const doc = this.value.replace(/\D/g, '').trim();
        clearTimeout(reniecTimer);
        reniecTimer = setTimeout(() => fetchReniecData(doc), 500);
    });

    // ── A3. Check documento duplicado ─────────────────────────────────────

    let docTimer = null;
    let docAbort = null;

    async function checkDocumentoDuplicado(doc, excludeId, feedbackId, inputId) {
        if (!doc) { feedback(feedbackId, null); return; }
        docAbort?.abort();
        docAbort = new AbortController();
        try {
            const qs  = excludeId ? `?exclude_id=${encodeURIComponent(excludeId)}` : '';
            const res = await fetch(`/api/personas/check-document/${encodeURIComponent(doc)}${qs}`, {
                headers: { 'Accept': 'application/json' },
                signal: docAbort.signal,
            });
            if (!res.ok) return;
            const { exists } = await res.json().catch(() => ({}));
            if (document.getElementById(inputId)?.value.replace(/\D/g, '').trim() !== doc) return;
            feedback(feedbackId, exists ? false : null,
                exists ? 'El número de documento ya se encuentra registrado' : ''
            );
        } catch (e) { if (e.name !== 'AbortError') console.error(e); }
    }

    function bindDocumentoCheck(inputId, feedbackId, getExcludeId) {
        document.getElementById(inputId)?.addEventListener('input', function () {
            const doc = this.value.replace(/\D/g, '').trim();
            clearTimeout(docTimer);
            docTimer = setTimeout(() => checkDocumentoDuplicado(doc, getExcludeId?.() ?? '', feedbackId, inputId), 500);
        });
    }

    bindDocumentoCheck('new-numero_documento', 'new-doc-feedback');
    bindDocumentoCheck('edit-doc', 'edit-doc-feedback',
        () => document.getElementById('edit-id')?.value ?? ''
    );

    // ── B. Guardar edición ────────────────────────────────────────────────

    document.getElementById('btn-save-persona')?.addEventListener('click', async function () {
        const id = document.getElementById('edit-id')?.value;
        if (!id) { alert('Error: ID no encontrado.'); return; }

        ['edit-nombres', 'edit-paterno', 'edit-materno'].forEach(normalizeNameInput);

        const g = (elId) => document.getElementById(elId)?.value ?? '';
        const data = {
            tipo_documento:                 g('edit-tdoc'),
            numero_documento:               g('edit-doc'),
            nombres:                        g('edit-nombres'),
            apellido_paterno:               g('edit-paterno'),
            apellido_materno:               g('edit-materno'),
            fecha_nacimiento:               g('edit-nac'),
            genero:                         g('edit-genero'),
            nacionalidad:                   g('edit-nacionalidad'),
            departamento:                   g('edit-departamento'),
            provincia:                      g('edit-provincia'),
            distrito:                       g('edit-distrito'),
            numero_telefonico:              g('edit-telefono'),
            correo_electronico_personal:    g('edit-correo-pers'),
            correo_electronico_corporativo: g('edit-correo-corp'),
            direccion:                      g('edit-direccion'),
        };

        this.disabled = true;
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i> Guardando...';
        try {
            const response = await fetch(`/personas/${id}`, {
                method: 'PUT',
                headers: jsonHeaders(),
                body: JSON.stringify(data),
            });
            const result = await response.json().catch(() => ({}));
            if (response.ok) { alert(getMessage(result, 'Guardado exitosamente')); window.location.reload(); }
            else alert(getMessage(result, 'Error: Verifique los datos'));
        } catch { alert('Error de conexión'); }
        finally {
            this.disabled = false;
            this.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Cambios';
        }
    });

    // ── C. Event delegation (botones tabla) ───────────────────────────────

    let modalOpening = false;

    document.addEventListener('click', async function (e) {

        // Editar
        const btnEdit = e.target.closest('.btn-edit');
        if (btnEdit) {
            if (modalOpening) return;
            const d = btnEdit.closest('tr').dataset;
            applyStatus(d.estado, 'edit-modal', 'edit-header', '--edit-status-color');
            document.getElementById('edit-id').value = d.id || '';
            setVal('edit-tdoc',        d.tdoc || 'DNI');
            setVal('edit-doc',         d.doc);
            setVal('edit-nombres',     d.nombres);
            setVal('edit-paterno',     d.paterno);
            setVal('edit-materno',     d.materno);
            setVal('edit-nac',         d.nac);
            setVal('edit-genero',         d.genero || '');
            setVal('edit-nacionalidad',   d.nacionalidad);
            setVal('edit-telefono',       normalizeTelefono(d.telefono));
            setVal('edit-correo-pers',    d.correoPers);
            setVal('edit-correo-corp',    d.correoCorp);
            setVal('edit-direccion',      d.direccion);
            editCascade?.setValues(d.departamento, d.provincia, d.distrito);
            updateEmailFeedback('edit-correo-pers', 'edit-correo-pers-feedback');
            updateEmailFeedback('edit-correo-corp', 'edit-correo-corp-feedback');
            checkDocumentoDuplicado((d.doc || '').replace(/\D/g, ''), d.id || '', 'edit-doc-feedback', 'edit-doc');
            openModal('edit-modal');
        }

        // Ver
        const btnView = e.target.closest('.btn-view');
        if (btnView) {
            if (modalOpening) return;
            modalOpening = true;
            const d = btnView.closest('tr').dataset;

            setVal('view-tdoc',        d.tdoc);
            setVal('view-doc',         d.doc);
            setVal('view-nac',         d.nac);
            setVal('view-genero',      d.genero == '1' ? 'Masculino' : d.genero == '2' ? 'Femenino' : '—');
            setVal('view-telefono',    normalizeTelefono(d.telefono));
            setVal('view-correo-pers', d.correoPers);
            setVal('view-correo-corp', d.correoCorp);
            setVal('view-direccion',   d.direccion);
            setVal('view-nombre-completo', [
                [d.paterno, d.materno].filter(Boolean).join(' '),
                d.nombres,
            ].filter(Boolean).join(', '));
            setVal('view-avatar-initials', (mb_substr(d.paterno, 0) + mb_substr(d.nombres, 0)).toUpperCase() || '?');

            const status = applyStatus(d.estado, 'view-modal', 'view-hero', '--view-status-color');
            const avatarEl = document.getElementById('view-avatar');
            const badgeEl  = document.getElementById('view-estado-badge');
            const labelEl  = document.getElementById('view-estado-label');
            avatarClasses.forEach(c => avatarEl?.classList.remove(c));
            badgeClasses.forEach(c => badgeEl?.classList.remove(c));
            avatarEl?.classList.add(status.avatarClass);
            badgeEl?.classList.add(status.badgeClass);
            if (labelEl) labelEl.textContent = status.label;

            openModal('view-modal');
            modalOpening = false;

            // Nacionalidad — select oculto solo para resolución de texto
            const paisSel  = document.getElementById('view-pais');
            const paisText = document.getElementById('view-pais-text');
            if (paisSel && paisText) {
                paisSel.value      = d.nacionalidad ? String(d.nacionalidad) : '';
                paisText.textContent = paisSel.options[paisSel.selectedIndex]?.text || '—';
            }

            viewCascade?.setValues(d.departamento, d.provincia, d.distrito)
                .then(() => {
                    [['view-departamento','view-departamento-text'],
                     ['view-provincia','view-provincia-text'],
                     ['view-distrito','view-distrito-text']]
                        .forEach(([selId, spanId]) => {
                            const sel  = document.getElementById(selId);
                            const span = document.getElementById(spanId);
                            if (sel && span) span.textContent = sel.value ? (sel.options[sel.selectedIndex]?.text || '—') : '—';
                        });
                });
        }

        // Eliminar — abrir modal
        const btnDelete = e.target.closest('.btn-delete');
        if (btnDelete) {
            const d = btnDelete.closest('tr')?.dataset;
            if (!d?.id) { alert('No se pudo identificar la persona a eliminar.'); return; }
            document.getElementById('delete-nombre').textContent = [d.paterno, d.materno, d.nombres].filter(Boolean).join(' ') || '—';
            document.getElementById('delete-doc').textContent    = d.tdoc && d.doc ? `${d.tdoc}: ${d.doc}` : '—';
            document.getElementById('btn-confirm-delete').dataset.deleteId = d.id;
            openModal('delete-modal');
        }

        // Confirmar eliminación
        const btnConfirm = e.target.closest('#btn-confirm-delete');
        if (btnConfirm) {
            const id = btnConfirm.dataset.deleteId;
            if (!id) return;
            btnConfirm.disabled  = true;
            btnConfirm.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i> Eliminando...';
            try {
                const response = await fetch(`/personas/${id}`, { method: 'DELETE', headers: baseHeaders() });
                const result = await response.json().catch(() => ({}));
                if (response.ok && result.success) {
                    closeModal('delete-modal');
                    alert(getMessage(result, 'Persona eliminada correctamente'));
                    window.location.reload();
                } else {
                    alert(getMessage(result, 'No se pudo eliminar la persona'));
                }
            } catch { alert('Error de conexión'); }
            finally {
                btnConfirm.disabled  = false;
                btnConfirm.innerHTML = '<i class="fa-solid fa-trash text-xs"></i> Eliminar';
            }
        }
    });

    // ── D. Buscador con debounce ──────────────────────────────────────────

    const nameInput      = document.getElementById('server-search-name');
    const searchDocInput = document.getElementById('server-search-doc');
    let searchTimer = null;

    function search() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            const url = new URL(window.location.href);
            nameInput?.value      ? url.searchParams.set('search_name', nameInput.value)     : url.searchParams.delete('search_name');
            searchDocInput?.value ? url.searchParams.set('search_doc', searchDocInput.value) : url.searchParams.delete('search_doc');
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }, 1100);
    }

    nameInput?.addEventListener('input', search);
    searchDocInput?.addEventListener('input', search);

    // ── E. Cascada Departamento → Provincia → Distrito ───────────────────

    async function loadDistritos(select, provinciaId, selectValue) {
        select.innerHTML = '<option value="">Seleccione un distrito</option>';
        if (!provinciaId) return;
        try {
            const data = await fetch(`/api/distritos?provincia_id=${provinciaId}`, {
                headers: { 'Accept': 'application/json' },
            }).then(r => r.json());
            data.forEach(({ id, nombre }) => {
                const opt = document.createElement('option');
                opt.value = id; opt.textContent = nombre;
                select.appendChild(opt);
            });
            if (selectValue) select.value = String(selectValue);
        } catch (e) { console.error('Error cargando distritos', e); }
    }

    function bindCascade(prefix) {
        const departamento = document.getElementById(`${prefix}-departamento`);
        const provincia    = document.getElementById(`${prefix}-provincia`);
        const distrito     = document.getElementById(`${prefix}-distrito`);
        if (!departamento || !provincia || !distrito) return null;

        const provOptions = Array.from(provincia.options);

        const rebuild = (select, options) => {
            select.innerHTML = '';
            const frag = document.createDocumentFragment();
            options.forEach(o => frag.appendChild(o));
            select.appendChild(frag);
        };

        const filterProvincias = () => {
            rebuild(provincia, provOptions.filter(o => !o.value || o.dataset.departamento === departamento.value));
            provincia.value = '';
            loadDistritos(distrito, '', '');
        };

        departamento.addEventListener('change', filterProvincias);
        provincia.addEventListener('change', () => loadDistritos(distrito, provincia.value, ''));

        return {
            setValues: async (departamentoId, provinciaId, distritoId) => {
                departamento.value = departamentoId ? String(departamentoId) : '';
                filterProvincias();
                provincia.value    = provinciaId    ? String(provinciaId)    : '';
                await loadDistritos(distrito, provincia.value, distritoId);
            },
        };
    }

    const createCascade = bindCascade('new');
    const editCascade   = bindCascade('edit');
    const viewCascade   = bindCascade('view');

    // ── F. Validación de correos ──────────────────────────────────────────

    [
        ['new-correo_electronico_personal',    'new-correo-pers-feedback'],
        ['new-correo_electronico_corporativo', 'new-correo-corp-feedback'],
        ['edit-correo-pers', 'edit-correo-pers-feedback'],
        ['edit-correo-corp', 'edit-correo-corp-feedback'],
    ].forEach(([inputId, feedbackId]) => {
        document.getElementById(inputId)?.addEventListener('input', () => updateEmailFeedback(inputId, feedbackId));
    });

    // ── G. Normalizar nombres al perder foco ──────────────────────────────

    ['new-nombres', 'new-apellido_paterno', 'new-apellido_materno', 'edit-nombres', 'edit-paterno', 'edit-materno']
        .forEach(id => document.getElementById(id)?.addEventListener('blur', () => normalizeNameInput(id)));

    // ── Reset estado modal crear ──────────────────────────────────────────

    window.resetCreateModalState = function () {
        feedback('new-doc-feedback',         null);
        feedback('new-correo-pers-feedback', null);
        feedback('new-correo-corp-feedback', null);
        reniecAbort?.abort();
        docAbort?.abort();
        clearTimeout(reniecTimer);
        clearTimeout(docTimer);
    };

})();
