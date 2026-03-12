@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Seleccionar período y auto-submit
    window.seleccionarPago = function (id) {
        document.getElementById('pago_id').value = id;
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

        let visible = 0;
        filasDatos.forEach(fila => {
            const match = coincide(fila, nombre, campana, centro, familia);
            fila.el.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        if (contador) contador.textContent = visible;
        if (btnLimpiar) btnLimpiar.classList.toggle('hidden', !nombre);

        reconstruirSelect(filtroCampana, 'campana', nombre, '',      centro,  familia);
        reconstruirSelect(filtroCentro,  'centro',  nombre, campana, '',      familia);
        reconstruirSelect(filtroFamilia, 'familia', nombre, campana, centro,  '');
    }

    function dispararNombre() {
        if (!filtroNombre) return;
        const val = filtroNombre.value.trim();
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

    // ── Filtro por rango de fechas: muestra/oculta columnas ──────────────
    const filtroDesde = document.getElementById('filtro-fecha-desde');
    const filtroHasta = document.getElementById('filtro-fecha-hasta');

    const rangoBase = '{{ \Carbon\Carbon::parse($pagoSeleccionado?->inicio)->format("d/m/Y") }} – {{ \Carbon\Carbon::parse($pagoSeleccionado?->fin)->format("d/m/Y") }}';

    function fmt(ymd) {
        if (!ymd) return '';
        const [y, m, d] = ymd.split('-');
        return `${d}/${m}/${y}`;
    }

    function aplicarFiltroFechas() {
        const desde = filtroDesde?.value ?? '';
        const hasta = filtroHasta?.value ?? '';

        if (desde && filtroHasta) filtroHasta.min = desde;
        if (hasta && filtroDesde) filtroDesde.max = hasta;

        document.querySelectorAll('th[data-fecha], td[data-fecha]').forEach(el => {
            const f = el.dataset.fecha;
            const visible = (!desde && !hasta)
                || (!desde && f <= hasta)
                || (!hasta && f >= desde)
                || (f >= desde && f <= hasta);
            el.style.display = visible ? '' : 'none';
        });

        document.querySelectorAll('th[data-mes]').forEach(th => {
            const mes = th.dataset.mes;
            const visibles = [...document.querySelectorAll(`th[data-fecha]`)]
                .filter(el => el.dataset.fecha.startsWith(mes) && el.style.display !== 'none').length;
            th.style.display = visibles === 0 ? 'none' : '';
            if (visibles > 0) th.colSpan = visibles;
        });

        const statsRango = document.getElementById('stats-rango');
        if (statsRango) {
            if (!desde && !hasta) {
                statsRango.textContent = rangoBase;
            } else {
                const d = desde ? fmt(desde) : rangoBase.split(' – ')[0];
                const h = hasta ? fmt(hasta) : rangoBase.split(' – ')[1];
                statsRango.textContent = `${d} – ${h}`;
            }
        }
    }

    filtroDesde?.addEventListener('change', aplicarFiltroFechas);
    filtroHasta?.addEventListener('change', aplicarFiltroFechas);

    btnLimpiar?.addEventListener('click', () => {
        if (filtroNombre) filtroNombre.value = '';
        aplicarFiltros();
    });

    // Items map: id => codigo for column-action feedback
    const itemsMap = @json($itemsAsistencia->mapWithKeys(fn($i) => [$i->id => $i->codigo_asistencia]));

    function guardarAsistencia(sel) {
        const grupo            = sel.closest('.asist-grupo');
        const contratoId       = sel.dataset.contrato;
        const fecha            = sel.dataset.fecha;
        const itemAsistenciaId = sel.value || null;
        const tardanzaCheck    = grupo?.querySelector('.tardanza-check');
        const minTardanzaInput = grupo?.querySelector('.min-tardanza-input');
        const tardanza         = tardanzaCheck?.checked ?? false;
        const minTardanza      = (tardanza && minTardanzaInput?.value) ? parseInt(minTardanzaInput.value) : null;

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
</script>
@endpush
