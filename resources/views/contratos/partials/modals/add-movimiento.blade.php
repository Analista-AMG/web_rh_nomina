<!-- MODAL AÑADIR MOVIMIENTO -->
<div id="add-movimiento-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60 transition-opacity" onclick="closeModal('add-movimiento-modal')" style="backdrop-filter: blur(5px);"></div>

        <div class="relative z-10 w-full transform overflow-hidden rounded-xl bg-white dark:bg-dark-card text-left shadow-2xl border border-light-border dark:border-dark-border" style="max-width: 900px;">
            <div class="bg-white dark:bg-dark-card px-8 py-6 border-b border-light-border dark:border-dark-border flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Añadir Movimiento</h3>
                <button onclick="closeModal('add-movimiento-modal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>

            <div class="p-8">
                <form id="form-add-movimiento">
                    <input type="hidden" id="add-mov-contrato-id">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tipo de Movimiento -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Tipo de Movimiento</label>
                            <input type="text" id="add-mov-tipo" value="Movimiento Regular" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4 cursor-not-allowed" readonly>
                        </div>

                        <!-- Cargo -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Cargo</label>
                            <select id="add-mov-cargo-id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4">
                                <option value="">Cargando...</option>
                            </select>
                        </div>

                        <!-- Planilla -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Planilla</label>
                            <select id="add-mov-planilla-id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4">
                                <option value="">Cargando...</option>
                            </select>
                        </div>

                        <!-- Fecha Inicio -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Fecha Inicio</label>
                            <input type="date" id="add-mov-inicio" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4" required>
                        </div>

                        <!-- Fecha Fin -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Fecha Fin</label>
                            <input type="date" id="add-mov-fin" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4">
                        </div>

                        <!-- Haber Básico -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Haber Básico (S/)</label>
                            <input type="number" step="0.01" id="add-mov-haber" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4 font-mono" required>
                        </div>

                        <!-- Movilidad -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Movilidad (S/)</label>
                            <input type="number" step="0.01" id="add-mov-movilidad" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4 font-mono" value="0">
                        </div>

                        <!-- Asignación Familiar -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Asignación Familiar</label>
                            <select id="add-mov-asignacion" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4">
                                <option value="0">No</option>
                                <option value="1">Sí</option>
                            </select>
                        </div>

                        <!-- Fondo de Pensiones -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Fondo de Pensiones</label>
                            <select id="add-mov-fp-id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4">
                                <option value="">Cargando...</option>
                            </select>
                        </div>

                        <!-- Condición -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Condición</label>
                            <select id="add-mov-condicion-id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4">
                                <option value="">Cargando...</option>
                            </select>
                        </div>

                        <!-- Banco -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Banco</label>
                            <select id="add-mov-banco-id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4">
                                <option value="">Cargando...</option>
                            </select>
                        </div>

                        <!-- Centro de Costo -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Centro de Costo</label>
                            <select id="add-mov-centro-costo-id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4">
                                <option value="">Cargando...</option>
                            </select>
                        </div>

                        <!-- Moneda -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Moneda</label>
                            <select id="add-mov-moneda-id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4">
                                <option value="">Cargando...</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-light-border dark:border-dark-border">
                        <x-forms.secondary-button type="button" onclick="closeModal('add-movimiento-modal')">Cancelar</x-forms.secondary-button>
                        <x-forms.primary-button type="button" id="btn-add-movimiento">Guardar Movimiento</x-forms.primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
