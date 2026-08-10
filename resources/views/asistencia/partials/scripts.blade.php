@php
    // Mismo umbral que el prefiltro server-side en AsistenciaController::index().
    // Si es vacío, el servidor no restringió $filas por nombre en esta carga.
    $fNombreActivo = trim(request('f_nombre', ''));
    $fNombreActivo = mb_strlen($fNombreActivo) >= 4 ? $fNombreActivo : '';
@endphp
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Nombre por el que el servidor ya restringió $filas en esta carga de página
    // (prefiltro server-side al cambiar de período). Si el usuario sigue escribiendo
    // un texto distinto, filtrar solo en el cliente dejaría fuera a colaboradores que
    // el servidor nunca envió al navegador — hay que recargar para traer el universo
    // completo del período con el nuevo término.
    const SERVER_F_NOMBRE = @json($fNombreActivo);

    // Seleccionar período y auto-submit — preserva filtros client-side en la URL
    window.seleccionarPago = function (id) {
        document.getElementById('pago_id').value = id;
        const set = (elId, val) => { const el = document.getElementById(elId); if (el) el.value = val ?? ''; };
        set('hidden-f-nombre',  document.getElementById('filtro-nombre')?.value ?? '');
        set('hidden-f-campana', document.getElementById('filtro-campana')?.value ?? '');
        set('hidden-f-centro',  document.getElementById('filtro-centro')?.value ?? '');
        set('hidden-f-familia', document.getElementById('filtro-familia')?.value ?? '');
        set('hidden-f-directos', soloDirectos ? '1' : '0');
        document.getElementById('form-filtro').submit();
    };

    // ── Filtros client-side ───────────────────────────────────────────────
    const filtroNombre      = document.getElementById('filtro-nombre');
    const filtroCampana     = document.getElementById('filtro-campana');
    const filtroCentro      = document.getElementById('filtro-centro');
    const filtroFamilia     = document.getElementById('filtro-familia');
    const btnDirectosTodos = document.getElementById('btn-directos-todos');
    const btnDirectosSolo  = document.getElementById('btn-directos-solo');
    let soloDirectos = false;
    const btnLimpiar        = document.getElementById('btn-limpiar-nombre');
    const contador          = document.getElementById('contador-colaboradores');

    const filasDatos = [...document.querySelectorAll('tbody tr[data-nombre]')].map(tr => ({
        el:        tr,
        nombre:    tr.dataset.nombre    ?? '',
        campana:   tr.dataset.campana   ?? '',
        centro:    tr.dataset.centro    ?? '',
        familia:   tr.dataset.familia   ?? '',
        esDirecto: tr.dataset.esDirecto === '1',
    }));

    const POR_PAGINA = 20;
    let paginaActual = 1;
    let filasFiltradas = [...filasDatos];

    function buildLabelMap(select) {
        const map = { _placeholder: select?.options[0]?.text ?? 'Todos' };
        [...(select?.options ?? [])].forEach(opt => { if (opt.value) map[opt.value] = opt.text; });
        return map;
    }
    const labels = {
        campana: buildLabelMap(filtroCampana),
        centro:  buildLabelMap(filtroCentro),
        familia: buildLabelMap(filtroFamilia),
    };

    function coincide(fila, nombre, campana, centro, familia) {
        return (!nombre       || fila.nombre.includes(nombre))
            && (!campana      || fila.campana === campana)
            && (!centro       || fila.centro  === centro)
            && (!familia      || fila.familia === familia)
            && (!soloDirectos || fila.esDirecto);
    }

    function reconstruirSelect(select, campo, nombre, campana, centro, familia) {
        if (!select) return;
        const actual      = select.value;
        const disponibles = new Set();
        filasDatos.forEach(fila => {
            if (coincide(fila, nombre, campana, centro, familia) && fila[campo])
                disponibles.add(fila[campo]);
        });
        const lbl = labels[campo];
        select.innerHTML = `<option value="">${lbl._placeholder}</option>`;
        [...disponibles].sort().forEach(val => {
            const opt = document.createElement('option');
            opt.value       = val;
            opt.textContent = lbl[val] ?? val;
            if (val === actual) opt.selected = true;
            select.appendChild(opt);
        });
        if (actual && !disponibles.has(actual)) select.value = '';
    }

    function aplicarFiltros() {
        const nombre  = (filtroNombre?.value ?? '').toLowerCase().trim();
        const campana = filtroCampana?.value ?? '';
        const centro  = filtroCentro?.value  ?? '';
        const familia = filtroFamilia?.value  ?? '';

        filasFiltradas = filasDatos.filter(fila => coincide(fila, nombre, campana, centro, familia));
        paginaActual = 1;
        renderPagina();

        if (btnLimpiar) btnLimpiar.classList.toggle('hidden', !nombre);

        reconstruirSelect(filtroCampana, 'campana', nombre, '',      centro,  familia);
        reconstruirSelect(filtroCentro,  'centro',  nombre, campana, '',      familia);
        reconstruirSelect(filtroFamilia, 'familia', nombre, campana, centro,  '');
    }

    function renderPagina() {
        const total       = filasFiltradas.length;
        const totalPaginas = Math.max(1, Math.ceil(total / POR_PAGINA));
        if (paginaActual > totalPaginas) paginaActual = totalPaginas;

        const inicio = (paginaActual - 1) * POR_PAGINA;
        const fin    = inicio + POR_PAGINA;

        filasDatos.forEach(f => f.el.style.display = 'none');
        filasFiltradas.slice(inicio, fin).forEach(f => f.el.style.display = '');

        if (contador) contador.textContent = total;

        renderPaginacion(total, totalPaginas);
    }

    function renderPaginacion(total, totalPaginas) {
        const container = document.getElementById('paginacion-asistencia');
        if (!container) return;

        if (totalPaginas <= 1) { container.innerHTML = ''; return; }

        const desde = Math.max(1, paginaActual - 2);
        const hasta  = Math.min(totalPaginas, paginaActual + 2);

        const btnBase  = 'px-3 py-2 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors';
        const btnActivo = 'px-3 py-2 text-white bg-primary border border-primary rounded-md shadow-sm';
        const btnDis   = 'px-3 py-2 text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md cursor-not-allowed';
        const dots     = `<li><span class="px-3 py-2 text-gray-500 dark:text-gray-400">…</span></li>`;

        let items = '';

        // Anterior
        items += paginaActual === 1
            ? `<li><span class="${btnDis}"><i class="fa-solid fa-chevron-left"></i></span></li>`
            : `<li><button onclick="irPagina(${paginaActual - 1})" class="${btnBase}"><i class="fa-solid fa-chevron-left"></i></button></li>`;

        if (desde > 1) {
            items += `<li><button onclick="irPagina(1)" class="${btnBase}">1</button></li>`;
            if (desde > 2) items += dots;
        }

        for (let p = desde; p <= hasta; p++) {
            items += p === paginaActual
                ? `<li><span class="${btnActivo}">${p}</span></li>`
                : `<li><button onclick="irPagina(${p})" class="${btnBase}">${p}</button></li>`;
        }

        if (hasta < totalPaginas) {
            if (hasta < totalPaginas - 1) items += dots;
            items += `<li><button onclick="irPagina(${totalPaginas})" class="${btnBase}">${totalPaginas}</button></li>`;
        }

        // Siguiente
        items += paginaActual === totalPaginas
            ? `<li><span class="${btnDis}"><i class="fa-solid fa-chevron-right"></i></span></li>`
            : `<li><button onclick="irPagina(${paginaActual + 1})" class="${btnBase}"><i class="fa-solid fa-chevron-right"></i></button></li>`;

        container.innerHTML = `
            <nav class="flex items-center justify-center mt-4 px-1">
                <ul class="flex items-center gap-1">${items}</ul>
            </nav>`;
    }

    window.irPagina = function(p) {
        paginaActual = p;
        renderPagina();
        document.querySelector('.overflow-x-auto')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    function dispararNombre() {
        if (!filtroNombre) return;
        const val = filtroNombre.value.trim();

        // El servidor ya restringió $filas por SERVER_F_NOMBRE en esta carga. Si el texto
        // actual ya no coincide, hace falta un reload real (misma ruta que cambiar de
        // período) para volver a traer el universo completo del período con el nuevo término.
        if (SERVER_F_NOMBRE && val.toLowerCase() !== SERVER_F_NOMBRE.toLowerCase()) {
            seleccionarPago(document.getElementById('pago_id').value);
            return;
        }

        if (val.length === 0 || val.length >= 4) aplicarFiltros();
    }

    filtroNombre?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); dispararNombre(); } });
    filtroNombre?.addEventListener('blur', dispararNombre);
    filtroCampana?.addEventListener('change', aplicarFiltros);
    filtroCentro?.addEventListener('change', aplicarFiltros);
    filtroFamilia?.addEventListener('change', aplicarFiltros);

    window.filtrarDirectos = function(solo) {
        soloDirectos = solo;
        const activo   = 'bg-primary text-white shadow-sm';
        const inactivo = 'text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600';
        btnDirectosTodos?.classList.toggle('bg-primary',    !solo);
        btnDirectosTodos?.classList.toggle('text-white',    !solo);
        btnDirectosTodos?.classList.toggle('shadow-sm',     !solo);
        btnDirectosTodos?.classList.toggle('text-gray-500', solo);
        btnDirectosTodos?.classList.toggle('dark:text-gray-400', solo);
        btnDirectosSolo?.classList.toggle('bg-primary',    solo);
        btnDirectosSolo?.classList.toggle('text-white',    solo);
        btnDirectosSolo?.classList.toggle('shadow-sm',     solo);
        btnDirectosSolo?.classList.toggle('text-gray-500', !solo);
        btnDirectosSolo?.classList.toggle('dark:text-gray-400', !solo);
        aplicarFiltros();
    };


    btnLimpiar?.addEventListener('click', () => {
        if (filtroNombre) filtroNombre.value = '';
        dispararNombre();
    });

    // Restaurar filtros client-side desde URL (persistidos al cambiar período)
    const _p = new URLSearchParams(window.location.search);
    if (filtroNombre  && _p.get('f_nombre'))  filtroNombre.value  = _p.get('f_nombre');
    if (filtroCampana && _p.get('f_campana')) filtroCampana.value = _p.get('f_campana');
    if (filtroCentro  && _p.get('f_centro'))  filtroCentro.value  = _p.get('f_centro');
    if (filtroFamilia && _p.get('f_familia')) filtroFamilia.value = _p.get('f_familia');
    if (_p.get('f_directos') === '1' && btnDirectosTodos && btnDirectosSolo) {
        soloDirectos = true;
        btnDirectosTodos.classList.remove('bg-primary', 'text-white', 'shadow-sm');
        btnDirectosTodos.classList.add('text-gray-500', 'dark:text-gray-400');
        btnDirectosSolo.classList.add('bg-primary', 'text-white', 'shadow-sm');
        btnDirectosSolo.classList.remove('text-gray-500', 'dark:text-gray-400');
    }

    // Paginación inicial (aplica filtros restaurados)
    aplicarFiltros();

    // Items map: id => codigo for column-action feedback
    const itemsMap = @json($itemsAsistencia->mapWithKeys(fn($i) => [$i->id => $i->codigo_asistencia]));

    // Códigos de asistencia donde aplica distinguir Remoto / Presencial
    const CODIGOS_REMOTO = @json($codigosRemoto ?? []);
    window.aplicaRemoto = function (codigo) {
        return CODIGOS_REMOTO.includes(codigo);
    };

    // Disparado por el badge P/R (botón, no checkbox). Se pasa el valor ya actualizado
    // de "remoto" explícitamente en vez de leerlo del DOM, porque Alpine aplica sus
    // cambios de atributos (:data-remoto) en el siguiente microtask, no de forma
    // síncrona — leerlo justo aquí devolvería el valor anterior.
    window.guardarRemoto = function (btn, esRemoto) {
        const sel = btn.closest('.asist-grupo')?.querySelector('.asistencia-select');
        if (sel) guardarAsistencia(sel, { es_remoto: esRemoto });
    };

    function guardarAsistencia(sel, overrides = {}) {
        const grupo            = sel.closest('.asist-grupo');
        const contratoId       = sel.dataset.contrato;
        const fecha            = sel.dataset.fecha;
        const itemAsistenciaId = sel.value || null;
        const tardanzaCheck    = grupo?.querySelector('.tardanza-check');
        const minTardanzaInput = grupo?.querySelector('.min-tardanza-input');
        const tardanza         = tardanzaCheck?.checked ?? false;
        const minTardanza      = (tardanza && minTardanzaInput?.value) ? parseInt(minTardanzaInput.value) : null;
        const remotoBadge      = grupo?.querySelector('.remoto-badge');
        const esRemoto         = 'es_remoto' in overrides ? overrides.es_remoto : (remotoBadge?.dataset.remoto === '1');

        sel.classList.add('opacity-50');
        sel.disabled = true;

        fetch('{{ route("asistencia.guardar") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                contrato_id:        parseInt(contratoId),
                fecha:              fecha,
                item_asistencia_id: itemAsistenciaId ? parseInt(itemAsistenciaId) : null,
                tardanza:           tardanza,
                min_tardanza:       minTardanza,
                es_remoto:          esRemoto,
            }),
        })
        .then(r => r.json())
        .then(data => {
            sel.classList.remove('opacity-50');
            sel.disabled = false;
            if (data.success) {
                sel.classList.add('ring-2', 'ring-green-500');
                setTimeout(() => sel.classList.remove('ring-2', 'ring-green-500'), 600);
            } else {
                sel.classList.add('ring-2', 'ring-red-500');
                setTimeout(() => sel.classList.remove('ring-2', 'ring-red-500'), 1000);
                alert(data.error || 'Error al guardar');
                sel.value = sel.dataset.prevValue ?? '';
            }
        })
        .catch(() => {
            sel.classList.remove('opacity-50');
            sel.disabled = false;
            sel.classList.add('ring-2', 'ring-red-500');
            setTimeout(() => sel.classList.remove('ring-2', 'ring-red-500'), 1000);
        });
    }

    document.querySelectorAll('.asistencia-select').forEach(sel => {
        sel.dataset.prevValue = sel.value;
        sel.addEventListener('change', function () {
            guardarAsistencia(this);
            this.dataset.prevValue = this.value;
        });
    });

    document.querySelectorAll('.tardanza-check').forEach(chk => {
        chk.addEventListener('change', function () {
            const sel = this.closest('.asist-grupo')?.querySelector('.asistencia-select');
            if (sel) guardarAsistencia(sel);
        });
    });

    document.querySelectorAll('.min-tardanza-input').forEach(inp => {
        inp.addEventListener('blur', function () {
            const grupo = this.closest('.asist-grupo');
            const sel   = grupo?.querySelector('.asistencia-select');
            if (sel && sel.value) guardarAsistencia(sel);
        });
    });

    document.querySelectorAll('.col-action').forEach(headerSel => {
        headerSel.addEventListener('change', function () {
            const fecha  = this.dataset.fecha;
            const itemId = this.value;
            if (!itemId) return;

            document.querySelectorAll(`.asistencia-select[data-fecha="${fecha}"]`).forEach(cellSel => {
                // Solo filas visibles en la página actual
                if (cellSel.closest('tr')?.style.display === 'none') return;
                if (cellSel.value !== itemId) {
                    const opt = cellSel.querySelector(`option[value="${itemId}"]`);
                    if (opt) {
                        cellSel.value = itemId;
                        cellSel.dispatchEvent(new Event('change'));
                    }
                }
            });
            this.value = '';
        });
    });
});

// ── Admin: toggle cierre de quincena ─────────────────────────────────────────
window.toggleCierreAdmin = async function (btn) {
    const periodo   = btn.dataset.periodo;
    const quincena  = parseInt(btn.dataset.quincena);
    const bloqueado = btn.dataset.bloqueado === '1';
    const nuevo     = !bloqueado;

    btn.disabled = true;

    try {
        const res = await fetch('{{ route("asistencia.cierre") }}', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ periodo, quincena, bloqueado: nuevo }),
        });

        if (!res.ok) throw new Error();

        btn.dataset.bloqueado = nuevo ? '1' : '0';
        btn.title = nuevo ? 'Cerrado — clic para abrir' : 'Abierto — clic para cerrar';

        const icon = btn.querySelector('i');
        if (nuevo) {
            btn.classList.replace('bg-green-50', 'bg-red-50');
            btn.classList.replace('dark:bg-green-900/20', 'dark:bg-red-900/20');
            btn.classList.replace('border-green-200', 'border-red-200');
            btn.classList.replace('dark:border-green-800', 'dark:border-red-800');
            btn.classList.replace('text-green-600', 'text-red-600');
            btn.classList.replace('dark:text-green-400', 'dark:text-red-400');
            icon.classList.replace('fa-lock-open', 'fa-lock');
        } else {
            btn.classList.replace('bg-red-50', 'bg-green-50');
            btn.classList.replace('dark:bg-red-900/20', 'dark:bg-green-900/20');
            btn.classList.replace('border-red-200', 'border-green-200');
            btn.classList.replace('dark:border-red-800', 'dark:border-green-800');
            btn.classList.replace('text-red-600', 'text-green-600');
            btn.classList.replace('dark:text-red-400', 'dark:text-green-400');
            icon.classList.replace('fa-lock', 'fa-lock-open');
        }
    } catch {
        alert('Error al actualizar el período. Intenta nuevamente.');
    } finally {
        btn.disabled = false;
    }
};
</script>
@endpush
