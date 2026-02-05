<!-- MODAL VER MOVIMIENTO -->
<div id="view-movimiento-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60 transition-opacity" onclick="closeModal('view-movimiento-modal')" style="backdrop-filter: blur(5px);"></div>

        <div class="relative z-10 w-full transform overflow-hidden rounded-xl bg-white dark:bg-dark-card text-left shadow-2xl border border-light-border dark:border-dark-border" style="max-width: 900px;">
            <div class="bg-white dark:bg-dark-card px-8 py-6 border-b border-light-border dark:border-dark-border flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Detalle del Movimiento</h3>
                <button onclick="closeModal('view-movimiento-modal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>

            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Tipo de Movimiento -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Tipo de Movimiento</label>
                        <input type="text" id="view-mov-tipo" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200 font-semibold" readonly>
                    </div>

                    <!-- Cargo -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Cargo</label>
                        <input type="text" id="view-mov-cargo" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Planilla -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Planilla</label>
                        <input type="text" id="view-mov-planilla" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Fecha Inicio -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Fecha Inicio</label>
                        <input type="text" id="view-mov-inicio" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Fecha Fin -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Fecha Fin</label>
                        <input type="text" id="view-mov-fin" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Haber Básico -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Haber Básico</label>
                        <input type="text" id="view-mov-haber" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200 font-mono" readonly>
                    </div>

                    <!-- Asignación Familiar -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Asignación Familiar</label>
                        <input type="text" id="view-mov-asignacion" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Movilidad -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Movilidad</label>
                        <input type="text" id="view-mov-movilidad" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200 font-mono" readonly>
                    </div>

                    <!-- Fondo de Pensiones -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Fondo de Pensiones</label>
                        <input type="text" id="view-mov-fp" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Condición -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Condición</label>
                        <input type="text" id="view-mov-condicion" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Banco -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Banco</label>
                        <input type="text" id="view-mov-banco" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Centro de Costo -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Centro de Costo</label>
                        <input type="text" id="view-mov-centro-costo" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Familia -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Familia</label>
                        <input type="text" id="view-mov-familia" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Moneda -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Moneda</label>
                        <input type="text" id="view-mov-moneda" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Estado -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Estado</label>
                        <input type="text" id="view-mov-estado" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Fecha de Registro -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Fecha de Registro</label>
                        <input type="text" id="view-mov-fecha-registro" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <x-forms.secondary-button onclick="closeModal('view-movimiento-modal')">Cerrar</x-forms.secondary-button>
                </div>
            </div>
        </div>
    </div>
</div>
