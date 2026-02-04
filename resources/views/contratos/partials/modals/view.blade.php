<!-- MODAL VER CONTRATO -->
<div id="view-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60 transition-opacity" onclick="closeModal('view-modal')" style="backdrop-filter: blur(5px);"></div>

        <div class="relative z-10 w-full transform overflow-hidden rounded-xl bg-white dark:bg-dark-card text-left shadow-2xl border border-light-border dark:border-dark-border" style="max-width: 900px;">
            <div class="bg-white dark:bg-dark-card px-8 py-6 border-b border-light-border dark:border-dark-border flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Detalle del Contrato</h3>
                <button onclick="closeModal('view-modal')" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="fa-solid fa-times text-xl"></i></button>
            </div>

            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Colaborador -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Colaborador</label>
                        <input type="text" id="view-colaborador" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200 font-semibold" readonly>
                    </div>

                    <!-- Documento -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Documento</label>
                        <input type="text" id="view-documento" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Cargo -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Cargo</label>
                        <input type="text" id="view-cargo" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Planilla -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Planilla</label>
                        <input type="text" id="view-planilla" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Fecha Inicio -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Fecha Inicio</label>
                        <input type="text" id="view-inicio" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Fecha Fin -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Fecha Fin</label>
                        <input type="text" id="view-fin" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Fecha de Baja -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Fecha de Baja</label>
                        <input type="text" id="view-fecha-renuncia" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Estado -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Estado</label>
                        <input type="text" id="view-estado" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Haber Básico -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Haber Básico</label>
                        <input type="text" id="view-haber" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200 font-mono" readonly>
                    </div>

                    <!-- Asignación Familiar -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Asignación Familiar</label>
                        <input type="text" id="view-asignacion" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Movilidad -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Movilidad</label>
                        <input type="text" id="view-movilidad" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200 font-mono" readonly>
                    </div>

                    <!-- Fondo de Pensiones -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Fondo de Pensiones</label>
                        <input type="text" id="view-fp" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Condición -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Condición</label>
                        <input type="text" id="view-condicion" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Banco -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Banco</label>
                        <input type="text" id="view-banco" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Número de Cuenta -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Número de Cuenta</label>
                        <input type="text" id="view-numero-cuenta" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Código Interbancario -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Código Interbancario</label>
                        <input type="text" id="view-codigo-interbancario" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Número de Cuenta CTS -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Número de Cuenta CTS</label>
                        <input type="text" id="view-numero-cuenta-cts" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Código Interbancario CTS -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Código Interbancario CTS</label>
                        <input type="text" id="view-codigo-interbancario-cts" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Moneda -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Moneda</label>
                        <input type="text" id="view-moneda" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Centro de Costo -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Centro de Costo</label>
                        <input type="text" id="view-centro-costo" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Periodo de Prueba -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Periodo de Prueba</label>
                        <input type="text" id="view-periodo-prueba" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <x-forms.secondary-button onclick="closeModal('view-modal')">Cerrar</x-forms.secondary-button>
                </div>
            </div>
        </div>
    </div>
</div>
