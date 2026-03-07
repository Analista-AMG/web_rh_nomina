@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── Tabs ───────────────────────────────────────────────────────────────────
const TAB_KEYS = ['activos', 'pendientes', 'proximos', 'historial'];

function switchTab(key) {
    TAB_KEYS.forEach(k => {
        const btn   = document.getElementById('tab-btn-' + k);
        const panel = document.getElementById('panel-' + k);
        const activo = k === key;
        btn.classList.toggle('border-primary', activo);
        btn.classList.toggle('text-primary', activo);
        btn.classList.toggle('bg-primary/5', activo);
        btn.classList.toggle('border-transparent', !activo);
        btn.classList.toggle('text-gray-500', !activo);
        btn.classList.toggle('dark:text-gray-400', !activo);
        panel.classList.toggle('hidden', !activo);
    });
}

// ── Modal helpers ──────────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

function setLoading(btnId, loading, label) {
    const btn = document.getElementById(btnId);
    btn.disabled = loading;
    btn.innerHTML = loading ? '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Procesando...' : label;
}

function setModalError(prefix, msg) {
    const el = document.getElementById(prefix + '-error');
    document.getElementById(prefix + '-error-texto').textContent = msg;
    el.classList.toggle('hidden', !msg);
}

// ── Duración (compartida Prestar + Solicitar) ──────────────────────────────
function actualizarDuracion(mod) {
    const p    = mod === 'prestar';
    const ini  = document.getElementById((p ? 'prestar' : 'solicitar') + '-fecha-inicio').value;
    const fin  = document.getElementById((p ? 'prestar' : 'solicitar') + '-fecha-fin').value;
    const wrap = document.getElementById((p ? 'prestar' : 'solic') + '-duracion-wrap');
    const txt  = document.getElementById((p ? 'prestar' : 'solic') + '-duracion-texto');
    if (!ini || !fin) { wrap.classList.add('hidden'); return; }
    const dias = Math.round((new Date(fin) - new Date(ini)) / 86400000) + 1;
    if (dias <= 0)    { wrap.classList.add('hidden'); return; }
    txt.textContent = `${dias} día${dias !== 1 ? 's' : ''}`;
    wrap.classList.remove('hidden');
}

// ── Modal PRESTAR (2 pasos) ─────────────────────────────────────────────────
const MAX_PRESTAMOS = 3;
let prestarSeleccionados = [];
let prestarDestinoId     = null;

function prestarFechaCorta(str) {
    if (!str) return '';
    const [y, m, d] = str.split('-');
    return `${d}/${m}/${y}`;
}

function resetPrestarColaborador() {
    prestarSeleccionados = [];
    prestarDestinoId     = null;
    const filtro = document.getElementById('prestar-filtro');
    if (filtro) filtro.value = '';
    document.getElementById('prestar-destino-seleccionado')?.classList.add('hidden');
    document.getElementById('prestar-destino-lista')?.classList.remove('hidden');
    filtrarColaboradores();
    actualizarContadorPrestar();
}

function resetPrestarPaso1() {
    document.getElementById('prestar-fecha-inicio').value = '';
    document.getElementById('prestar-fecha-fin').value    = '';
    document.getElementById('prestar-duracion-wrap').classList.add('hidden');
    resetPrestarColaborador();
}

function abrirModalPrestar() {
    resetPrestarPaso1();
    document.getElementById('prestar-fecha-inicio').value = '{{ $hoy }}';
    document.getElementById('prestar-motivo').value       = '';
    setModalError('prestar', '');
    prestarIrPaso1();
    openModal('modal-prestar');
}

function prestarIrPaso1() {
    document.getElementById('prestar-step-1').classList.remove('hidden');
    document.getElementById('prestar-step-2').classList.add('hidden');
    document.getElementById('prestar-footer-1').classList.remove('hidden');
    document.getElementById('prestar-footer-2').classList.add('hidden');
    const dot1 = document.getElementById('prestar-dot-1');
    dot1.className = 'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-colors bg-primary text-white';
    dot1.innerHTML = '1';
    document.getElementById('prestar-label-1').className = 'text-xs font-medium text-primary';
    document.getElementById('prestar-dot-2').className   = 'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-colors bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500';
    document.getElementById('prestar-label-2').className = 'text-xs font-medium text-gray-400 dark:text-gray-500';
    document.getElementById('prestar-subtitulo').textContent = 'Selecciona el período del préstamo';
    setModalError('prestar', '');
}

function prestarIrPaso2() {
    const ini = document.getElementById('prestar-fecha-inicio').value;
    const fin = document.getElementById('prestar-fecha-fin').value;
    if (!ini)     return setModalError('prestar', 'Indica la fecha de inicio.');
    if (!fin)     return setModalError('prestar', 'Indica la fecha de fin.');
    if (fin < ini) return setModalError('prestar', 'La fecha fin debe ser igual o posterior al inicio.');
    setModalError('prestar', '');

    const dias = Math.round((new Date(fin) - new Date(ini)) / 86400000) + 1;
    document.getElementById('prestar-rango-texto').textContent = `${prestarFechaCorta(ini)} → ${prestarFechaCorta(fin)}`;
    document.getElementById('prestar-rango-dias').textContent  = `${dias} día${dias !== 1 ? 's' : ''}`;

    resetPrestarColaborador();

    document.getElementById('prestar-step-1').classList.add('hidden');
    document.getElementById('prestar-step-2').classList.remove('hidden');
    document.getElementById('prestar-footer-1').classList.add('hidden');
    document.getElementById('prestar-footer-2').classList.remove('hidden');
    const dot1 = document.getElementById('prestar-dot-1');
    dot1.className = 'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-colors bg-green-500 text-white';
    dot1.innerHTML = '<i class="fa-solid fa-check text-[10px]"></i>';
    document.getElementById('prestar-label-1').className = 'text-xs font-medium text-green-600 dark:text-green-400';
    document.getElementById('prestar-dot-2').className   = 'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-colors bg-primary text-white';
    document.getElementById('prestar-label-2').className = 'text-xs font-medium text-primary';
    document.getElementById('prestar-subtitulo').textContent = 'Selecciona colaboradores y supervisor destino';
}

function filtrarColaboradores() {
    const q           = (document.getElementById('prestar-filtro')?.value ?? '').toLowerCase().trim();
    const ini         = document.getElementById('prestar-fecha-inicio')?.value ?? '';
    const fin         = document.getElementById('prestar-fecha-fin')?.value   ?? '';
    const selectedIds = prestarSeleccionados.map(x => x.id);
    const maxReached  = prestarSeleccionados.length >= MAX_PRESTAMOS;
    let visibles = 0;
    document.querySelectorAll('.prestar-item').forEach(el => {
        const id          = parseInt(el.dataset.id);
        const cIni        = el.dataset.contratoInicio ?? '';
        const cFin        = el.dataset.contratoFin   ?? '';
        const matchNombre = !q || el.dataset.nombre.toLowerCase().includes(q);
        const matchFecha  = !ini || !fin || (cIni !== '' && cIni <= ini && (!cFin || cFin >= fin));
        const visible     = matchNombre && matchFecha;
        el.style.display  = visible ? '' : 'none';
        if (visible) {
            visibles++;
            const sel = selectedIds.includes(id);
            el.classList.toggle('bg-primary/5',        sel);
            el.classList.toggle('dark:bg-primary/10',  sel);
            el.classList.toggle('opacity-40',          !sel && maxReached);
            el.classList.toggle('pointer-events-none', !sel && maxReached);
            el.querySelector('.prestar-check')?.classList.toggle('hidden', !sel);
            el.querySelector('.prestar-plus')?.classList.toggle('hidden',   sel);
        }
    });
    document.getElementById('prestar-sin-resultados')?.classList.toggle('hidden', visibles > 0);
}

function prestarSeleccionar(el) {
    const id  = parseInt(el.dataset.id);
    const idx = prestarSeleccionados.findIndex(x => x.id === id);
    if (idx !== -1) prestarSeleccionados.splice(idx, 1);
    else if (prestarSeleccionados.length < MAX_PRESTAMOS) prestarSeleccionados.push({ id, nombre: el.dataset.nombre });
    filtrarColaboradores();
    actualizarContadorPrestar();
    setModalError('prestar', '');
}

function actualizarContadorPrestar() {
    const n   = prestarSeleccionados.length;
    const el  = document.getElementById('prestar-contador');
    const btn = document.getElementById('btn-prestar-submit');
    if (el)  { el.classList.toggle('hidden', n === 0); el.textContent = `${n} de ${MAX_PRESTAMOS} seleccionado${n !== 1 ? 's' : ''}`; }
    if (btn) btn.innerHTML = `<i class="fa-solid fa-arrow-up-from-bracket text-xs"></i> Confirmar ${n > 1 ? n + ' préstamos' : 'préstamo'}`;
}

function prestarSeleccionarDestino(el) {
    prestarDestinoId = parseInt(el.dataset.id);
    document.getElementById('prestar-destino-avatar').textContent = el.dataset.nombre.charAt(0).toUpperCase();
    document.getElementById('prestar-destino-nombre').textContent = el.dataset.nombre;
    document.getElementById('prestar-destino-info').textContent   = `[${el.dataset.rol}] [${el.dataset.campana}]`;
    document.getElementById('prestar-destino-seleccionado').classList.remove('hidden');
    document.getElementById('prestar-destino-lista').classList.add('hidden');
}

function limpiarDestinoPrestar() {
    prestarDestinoId = null;
    document.getElementById('prestar-destino-seleccionado').classList.add('hidden');
    document.getElementById('prestar-destino-lista').classList.remove('hidden');
}

async function submitPrestar() {
    if (!prestarSeleccionados.length) return setModalError('prestar', 'Selecciona al menos un colaborador.');
    if (!prestarDestinoId)            return setModalError('prestar', 'Selecciona el supervisor destino.');

    const fechaIni = document.getElementById('prestar-fecha-inicio').value;
    const fechaFin = document.getElementById('prestar-fecha-fin').value;
    const motivo   = document.getElementById('prestar-motivo').value;

    setModalError('prestar', '');
    setLoading('btn-prestar-submit', true, '');

    try {
        let creados = 0, omitidos = 0;
        for (const item of prestarSeleccionados) {
            const res  = await fetch('{{ route("equipos.prestamos.crear") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ accion: 'prestar', empleado_id: item.id, supervisor_destino_id: prestarDestinoId, fecha_inicio: fechaIni, fecha_fin: fechaFin, motivo }),
            });
            const data = await res.json();
            if (!data.success) {
                setModalError('prestar', `${item.nombre}: ${data.error ?? 'Error al crear el préstamo.'}`);
                setLoading('btn-prestar-submit', false, '<i class="fa-solid fa-arrow-up-from-bracket text-xs"></i> Confirmar préstamo');
                return;
            }
            data.skipped ? omitidos++ : creados++;
        }
        closeModal('modal-prestar');
        if (creados === 0) setModalError('prestar', `Sin cambios: ${omitidos === 1 ? 'el colaborador ya pertenece' : 'los colaboradores ya pertenecen'} al supervisor destino seleccionado.`);
        else location.reload();
    } catch {
        setModalError('prestar', 'Error de conexión.');
        setLoading('btn-prestar-submit', false, '<i class="fa-solid fa-arrow-up-from-bracket text-xs"></i> Confirmar préstamo');
    }
}

// ── Modal SOLICITAR (2 pasos) ───────────────────────────────────────────────
let _solicBusquedaActual = '';

function iniciales(nombre) {
    const p = nombre.trim().split(/\s+/);
    return ((p[0]?.[0] ?? '') + (p[1]?.[0] ?? '')).toUpperCase();
}

function abrirModalSolicitar() {
    document.getElementById('solicitar-fecha-inicio').value = '{{ $hoy }}';
    document.getElementById('solicitar-fecha-fin').value    = '';
    document.getElementById('solic-duracion-wrap').classList.add('hidden');
    solicResetPersona();
    setModalError('solicitar', '');
    solicIrPaso1();
    openModal('modal-solicitar');
}

function solicResetPersona() {
    document.getElementById('solicitar-search').value        = '';
    document.getElementById('solicitar-empleado-id').value   = '';
    document.getElementById('solicitar-supervisor-id').value = '';
    document.getElementById('solicitar-seleccionado').classList.add('hidden');
    document.getElementById('solicitar-lista').classList.add('hidden');
    document.getElementById('solicitar-items').innerHTML     = '';
    document.getElementById('solicitar-motivo').value        = '';
    _solicBusquedaActual = '';
}

function solicIrPaso1() {
    document.getElementById('solic-step-1').classList.remove('hidden');
    document.getElementById('solic-step-2').classList.add('hidden');
    document.getElementById('solic-footer-1').classList.remove('hidden');
    document.getElementById('solic-footer-2').classList.add('hidden');
    const dot1 = document.getElementById('step-dot-1');
    dot1.className = 'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-colors bg-primary text-white';
    dot1.innerHTML = '1';
    document.getElementById('step-label-1').className = 'text-xs font-medium text-primary';
    document.getElementById('step-dot-2').className   = 'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-colors bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500';
    document.getElementById('step-label-2').className = 'text-xs font-medium text-gray-400 dark:text-gray-500';
    document.getElementById('solic-subtitulo').textContent = 'Selecciona el período del préstamo';
    setModalError('solicitar', '');
}

function solicIrPaso2() {
    const ini = document.getElementById('solicitar-fecha-inicio').value;
    const fin = document.getElementById('solicitar-fecha-fin').value;
    if (!ini)      return setModalError('solicitar', 'Indica la fecha de inicio.');
    if (!fin)      return setModalError('solicitar', 'Indica la fecha de fin.');
    if (fin < ini) return setModalError('solicitar', 'La fecha fin debe ser igual o posterior al inicio.');
    setModalError('solicitar', '');

    const dias = Math.round((new Date(fin) - new Date(ini)) / 86400000) + 1;
    document.getElementById('solic-rango-texto').textContent = `${prestarFechaCorta(ini)} → ${prestarFechaCorta(fin)}`;
    document.getElementById('solic-rango-dias').textContent  = `${dias} día${dias !== 1 ? 's' : ''}`;

    solicResetPersona();

    document.getElementById('solic-step-1').classList.add('hidden');
    document.getElementById('solic-step-2').classList.remove('hidden');
    document.getElementById('solic-footer-1').classList.add('hidden');
    document.getElementById('solic-footer-2').classList.remove('hidden');
    const dot1 = document.getElementById('step-dot-1');
    dot1.className = 'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-colors bg-green-500 text-white';
    dot1.innerHTML = '<i class="fa-solid fa-check text-[10px]"></i>';
    document.getElementById('step-label-1').className = 'text-xs font-medium text-green-600 dark:text-green-400';
    document.getElementById('step-dot-2').className   = 'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-colors bg-primary text-white';
    document.getElementById('step-label-2').className = 'text-xs font-medium text-primary';
    document.getElementById('solic-subtitulo').textContent = 'Busca al colaborador que quieres solicitar';
    document.getElementById('solicitar-search').focus();
}

async function buscarPersonas() {
    const q   = document.getElementById('solicitar-search').value.trim();
    const ini = document.getElementById('solicitar-fecha-inicio').value;
    const fin = document.getElementById('solicitar-fecha-fin').value;
    const key = `${q}|${ini}|${fin}`;
    if (key === _solicBusquedaActual || q.length < 2) return;
    _solicBusquedaActual = key;

    const lista    = document.getElementById('solicitar-lista');
    const items    = document.getElementById('solicitar-items');
    const cargando = document.getElementById('solicitar-cargando');
    const sinRes   = document.getElementById('solicitar-sin-resultados');

    lista.classList.remove('hidden');
    cargando.classList.remove('hidden');
    items.innerHTML = '';
    sinRes.classList.add('hidden');

    try {
        const params = new URLSearchParams({ q, fecha_inicio: ini, fecha_fin: fin });
        const res    = await fetch(`{{ route('equipos.buscar-solicitar') }}?${params}`, { headers: { 'Accept': 'application/json' } });
        const data   = await res.json();
        cargando.classList.add('hidden');

        if (!data.length) { sinRes.classList.remove('hidden'); return; }

        const esc = v => (v ?? '').toString().replace(/&/g,'&amp;').replace(/"/g,'&quot;');
        items.innerHTML = data.map(p => `
            <div class="flex items-center gap-3 px-4 py-3 hover:bg-primary/5 dark:hover:bg-primary/10 cursor-pointer transition-colors group"
                 data-persona-id="${p.persona_id}" data-supervisor-id="${p.supervisor_id ?? ''}"
                 data-nombre="${esc(p.nombre)}" data-supervisor="${esc(p.supervisor_nombre)}" data-campana="${esc(p.campana)}"
                 onclick="solicSeleccionar(this)">
                <div class="w-9 h-9 rounded-full bg-primary/10 text-primary font-bold text-sm flex items-center justify-center flex-shrink-0 group-hover:bg-primary/20 transition-colors">${iniciales(p.nombre)}</div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 dark:text-white">${p.nombre}</p>
                    <p class="text-xs text-gray-400 mt-0.5 flex items-center gap-1.5">
                        <span><i class="fa-solid fa-user-tie mr-0.5"></i>${p.supervisor_nombre}</span>
                        <span class="text-gray-300">·</span>
                        <span><i class="fa-solid fa-bullhorn mr-0.5"></i>${p.campana}</span>
                    </p>
                </div>
                <i class="fa-solid fa-chevron-right text-gray-300 group-hover:text-primary text-xs flex-shrink-0"></i>
            </div>`).join('');
    } catch {
        cargando.classList.add('hidden');
        sinRes.querySelector('p').textContent = 'Error al conectar. Intenta de nuevo.';
        sinRes.classList.remove('hidden');
    }
}

function solicSeleccionar(el) {
    document.getElementById('solicitar-empleado-id').value          = el.dataset.personaId;
    document.getElementById('solicitar-supervisor-id').value        = el.dataset.supervisorId ?? '';
    document.getElementById('solicitar-nombre-display').textContent = el.dataset.nombre;
    document.getElementById('solicitar-info-display').textContent   =
        el.dataset.supervisor !== '—' ? `Sup: ${el.dataset.supervisor} · ${el.dataset.campana}` : el.dataset.campana;
    document.getElementById('solic-avatar').textContent             = iniciales(el.dataset.nombre);
    document.getElementById('solicitar-seleccionado').classList.remove('hidden');
    document.getElementById('solicitar-lista').classList.add('hidden');
    document.getElementById('solicitar-search').value = '';
    _solicBusquedaActual = '';
    setModalError('solicitar', '');
}

function limpiarSeleccionPersona() {
    document.getElementById('solicitar-empleado-id').value    = '';
    document.getElementById('solicitar-supervisor-id').value  = '';
    document.getElementById('solicitar-seleccionado').classList.add('hidden');
    document.getElementById('solicitar-search').value         = '';
    document.getElementById('solicitar-search').focus();
    _solicBusquedaActual = '';
}

async function submitSolicitar() {
    const empleadoId   = document.getElementById('solicitar-empleado-id').value;
    const supervisorId = document.getElementById('solicitar-supervisor-id').value;
    const fechaIni     = document.getElementById('solicitar-fecha-inicio').value;
    const fechaFin     = document.getElementById('solicitar-fecha-fin').value;
    const motivo       = document.getElementById('solicitar-motivo').value;

    if (!empleadoId) return setModalError('solicitar', 'Selecciona un colaborador.');

    setModalError('solicitar', '');
    setLoading('btn-solicitar-submit', true, '');

    try {
        const res  = await fetch('{{ route("equipos.prestamos.crear") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ accion: 'solicitar', empleado_id: parseInt(empleadoId), supervisor_origen_id: supervisorId ? parseInt(supervisorId) : null, fecha_inicio: fechaIni, fecha_fin: fechaFin, motivo }),
        });
        const data = await res.json();
        if (data.success) { closeModal('modal-solicitar'); location.reload(); }
        else setModalError('solicitar', data.error ?? 'Error al enviar la solicitud.');
    } catch {
        setModalError('solicitar', 'Error de conexión.');
    }
    setLoading('btn-solicitar-submit', false, '<i class="fa-solid fa-paper-plane mr-1 text-xs"></i> Enviar solicitud');
}

// ── Aprobar ────────────────────────────────────────────────────────────────
async function aprobar(id) {
    if (!confirm('¿Aprobar este préstamo? Se actualizará el equipo_dia para todo el rango.')) return;
    try {
        const res  = await fetch(`/equipos/prestamos/${id}/aprobar`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.error ?? 'Error al aprobar.');
    } catch { alert('Error de conexión.'); }
}

// ── Rechazar ───────────────────────────────────────────────────────────────
function abrirModalRechazar(id) {
    document.getElementById('rechazar-prestamo-id').value = id;
    document.getElementById('rechazar-motivo').value      = '';
    setLoading('btn-rechazar-submit', false, '<i class="fa-solid fa-ban mr-1"></i> Rechazar');
    openModal('modal-rechazar');
}

async function submitRechazar() {
    const id     = document.getElementById('rechazar-prestamo-id').value;
    const motivo = document.getElementById('rechazar-motivo').value;
    setLoading('btn-rechazar-submit', true, '');
    try {
        const res  = await fetch(`/equipos/prestamos/${id}/rechazar`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ motivo_rechazo: motivo }),
        });
        const data = await res.json();
        if (data.success) { closeModal('modal-rechazar'); location.reload(); }
        else alert(data.error ?? 'Error al rechazar.');
    } catch { alert('Error de conexión.'); }
    setLoading('btn-rechazar-submit', false, '<i class="fa-solid fa-ban mr-1"></i> Rechazar');
}

// ── Anular ─────────────────────────────────────────────────────────────────
function abrirModalAnular(id) {
    document.getElementById('anular-prestamo-id').value = id;
    document.getElementById('anular-motivo').value      = '';
    setLoading('btn-anular-submit', false, '<i class="fa-solid fa-rotate-left"></i> Confirmar anulación');
    openModal('modal-anular');
}

async function submitAnular() {
    const id     = document.getElementById('anular-prestamo-id').value;
    const motivo = document.getElementById('anular-motivo').value;
    setLoading('btn-anular-submit', true, '');
    try {
        const res  = await fetch(`/equipos/prestamos/${id}/anular`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ motivo_anulacion: motivo }),
        });
        const data = await res.json();
        if (data.success) { closeModal('modal-anular'); location.reload(); }
        else alert(data.error ?? 'Error al anular.');
    } catch { alert('Error de conexión.'); }
    setLoading('btn-anular-submit', false, '<i class="fa-solid fa-rotate-left"></i> Confirmar anulación');
}

// ── Cancelar ───────────────────────────────────────────────────────────────
async function cancelar(id) {
    if (!confirm('¿Cancelar esta solicitud pendiente?')) return;
    try {
        const res  = await fetch(`/equipos/prestamos/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.success) location.reload();
        else alert(data.error ?? 'Error al cancelar.');
    } catch { alert('Error de conexión.'); }
}

// ── Cerrar modales con backdrop ─────────────────────────────────────────────
['modal-prestar','modal-solicitar','modal-anular','modal-rechazar'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});

// ── Auto-carry manual (provisional) ───────────────────────────────────────
function abrirModalAutoCarry() {
    document.getElementById('modal-autocarry').classList.remove('hidden');
    document.getElementById('autocarry-msg').classList.add('hidden');
}
function cerrarModalAutoCarry() {
    document.getElementById('modal-autocarry').classList.add('hidden');
}
async function ejecutarAutoCarry() {
    const fecha = document.getElementById('autocarry-fecha').value;
    if (!fecha) return;
    const btn = document.getElementById('autocarry-btn');
    const msg = document.getElementById('autocarry-msg');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Ejecutando...';
    msg.classList.add('hidden');
    try {
        const res = await fetch('{{ route("equipos.auto-carry") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ fecha }),
        });
        const text = await res.text();
        let data = {};
        try { data = JSON.parse(text); } catch(e) {}
        msg.className = res.ok
            ? 'mb-3 text-sm rounded-lg px-3 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300'
            : 'mb-3 text-sm rounded-lg px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300';
        msg.textContent = data.message || (res.ok ? 'Ejecutado correctamente.' : `Error ${res.status}: ${text.substring(0, 200)}`);
        msg.classList.remove('hidden');
    } catch (e) {
        msg.className = 'mb-3 text-sm rounded-lg px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300';
        msg.textContent = 'Error de red: ' + e.message;
        msg.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Ejecutar';
    }
}
</script>
@endpush
