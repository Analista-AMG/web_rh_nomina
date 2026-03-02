{{-- MODAL HISTORIAL PREVIO — Paso 2 del flujo Nuevo Contrato --}}
<x-ui.modal-shell id="historial-previa-modal" max-width="1200px">

    <x-ui.modal-header modal-id="historial-previa-modal" title="Historial de Contratos" icon="fa-clock-rotate-left" />

    <div class="p-8">

        {{-- Aviso informativo --}}
        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 flex items-start gap-3">
            <i class="fa-solid fa-circle-info text-blue-500 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-sm text-blue-800 dark:text-blue-300">
                    Esta persona ya tiene contratos registrados. Revise el historial antes de continuar.
                </p>
                <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                    Haga clic en una fila para ver los movimientos del contrato.
                </p>
            </div>
        </div>

        {{-- Tabla de historial (llenada dinámicamente por JS — sección K.3) --}}
        <div class="overflow-x-auto">
            <table class="w-full text-center" style="border-collapse: separate; border-spacing: 0 4px;">
                <thead>
                    <tr>
                        <th class="px-6 py-2 text-xs font-semibold text-light-muted dark:text-dark-muted uppercase tracking-wider text-left">Colaborador</th>
                        <th class="px-6 py-2 text-xs font-semibold text-light-muted dark:text-dark-muted uppercase tracking-wider">Cargo</th>
                        <th class="px-6 py-2 text-xs font-semibold text-light-muted dark:text-dark-muted uppercase tracking-wider">Salario</th>
                        <th class="px-6 py-2 text-xs font-semibold text-light-muted dark:text-dark-muted uppercase tracking-wider">Inicio</th>
                        <th class="px-6 py-2 text-xs font-semibold text-light-muted dark:text-dark-muted uppercase tracking-wider">Fin</th>
                        <th class="px-6 py-2 text-xs font-semibold text-light-muted dark:text-dark-muted uppercase tracking-wider">Estado</th>
                    </tr>
                </thead>
                <tbody id="historial-tbody"></tbody>
            </table>
        </div>

    </div>

    <x-ui.modal-footer modal-id="historial-previa-modal">
        <x-forms.primary-button type="button" id="btn-continuar-crear">
            <i class="fa-solid fa-arrow-right mr-2"></i>Continuar con Nuevo Contrato
        </x-forms.primary-button>
    </x-ui.modal-footer>

</x-ui.modal-shell>
