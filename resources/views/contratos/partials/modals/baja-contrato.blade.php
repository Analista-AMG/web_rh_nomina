<!-- MODAL DAR DE BAJA -->
<div id="baja-contrato-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60 transition-opacity" onclick="closeModal('baja-contrato-modal')" style="backdrop-filter: blur(5px);"></div>

        <div class="relative z-10 w-full transform overflow-hidden rounded-xl bg-white dark:bg-dark-card text-left shadow-2xl border border-light-border dark:border-dark-border" style="max-width: 600px;">
            <div class="bg-white dark:bg-dark-card px-8 py-6 border-b border-light-border dark:border-dark-border flex justify-between items-center">
                <h3 id="baja-modal-titulo" class="text-xl font-bold text-red-600 dark:text-red-400">Dar de Baja</h3>
                <button onclick="closeModal('baja-contrato-modal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>

            <div class="p-8">
                <form id="form-baja-contrato">
                    <input type="hidden" id="baja-contrato-id">
                    <input type="hidden" id="baja-id">

                    <!-- Info del colaborador -->
                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Colaborador</p>
                        <p id="baja-colaborador-nombre" class="text-base font-bold text-gray-800 dark:text-white"></p>
                        <p id="baja-colaborador-doc" class="text-sm text-gray-500 dark:text-gray-400"></p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Fecha de Baja -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Fecha de Baja <span class="text-red-500">*</span></label>
                            <input type="date" id="baja-fecha" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4" required>
                        </div>

                        <!-- Motivo de Baja -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Motivo de Baja <span class="text-red-500">*</span></label>
                            <input type="text" id="baja-motivo" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4" placeholder="Ej: Renuncia voluntaria" required>
                        </div>

                        <!-- Aviso con 15 días -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">¿Aviso con 15 días de anticipación?</label>
                            <select id="baja-aviso-15-dias" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4">
                                <option value="0">No</option>
                                <option value="1">Sí</option>
                            </select>
                        </div>

                        <!-- Recomienda reingreso -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">¿Recomienda reingreso?</label>
                            <select id="baja-recomienda-reingreso" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4">
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>

                    <!-- Observación -->
                    <div class="mt-4">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Observación</label>
                        <textarea id="baja-observacion" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4" placeholder="Observaciones adicionales sobre la baja..."></textarea>
                    </div>

                    <!-- Advertencia -->
                    <div id="baja-advertencia" class="mt-6 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-sm text-red-700 dark:text-red-300">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                            <span id="baja-advertencia-texto">Esta acción registrará la baja del colaborador. El contrato pasará a estado <strong>Finalizado</strong> una vez cumplida la fecha.</span>
                        </p>
                    </div>

                    <div class="flex justify-between pt-6 mt-6 border-t border-light-border dark:border-dark-border">
                        <!-- Botón Eliminar Baja (solo visible en modo actualización) -->
                        <div id="baja-eliminar-container" class="hidden">
                            <button type="button" id="btn-eliminar-baja" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition ease-in-out duration-150">
                                <i class="fa-solid fa-trash-can mr-2"></i> Eliminar Baja
                            </button>
                        </div>
                        <div id="baja-spacer"></div>

                        <div class="flex gap-3">
                            <x-forms.secondary-button type="button" onclick="closeModal('baja-contrato-modal')">Cancelar</x-forms.secondary-button>
                            <button type="button" id="btn-confirmar-baja" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition ease-in-out duration-150">
                                Confirmar Baja
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
