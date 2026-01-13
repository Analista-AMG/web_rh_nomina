<!-- MODAL VER CONTRATO -->
<div id="view-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60 transition-opacity" onclick="closeModal('view-modal')" style="backdrop-filter: blur(5px);"></div>
        
        <div class="relative z-10 w-full transform overflow-hidden rounded-xl bg-white dark:bg-dark-card text-left shadow-2xl border border-light-border dark:border-dark-border" style="max-width: 800px;">
            <div class="bg-white dark:bg-dark-card px-8 py-6 border-b border-light-border dark:border-dark-border flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Detalle del Contrato</h3>
                <button onclick="closeModal('view-modal')" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="fa-solid fa-times text-xl"></i></button>
            </div>

            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Empleado -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Colaborador</label>
                        <input type="text" id="view-empleado" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Cargo -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Cargo</label>
                        <input type="text" id="view-cargo" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Fechas -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Fecha Inicio</label>
                        <input type="text" id="view-inicio" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Fecha Fin</label>
                        <input type="text" id="view-fin" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Salario -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Haber Básico</label>
                        <input type="text" id="view-salario" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>

                    <!-- Estado -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-500 mb-1">Estado</label>
                        <input type="text" id="view-estado" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <x-forms.secondary-button onclick="closeModal('view-modal')">Cerrar</x-forms.secondary-button>
                </div>
            </div>
        </div>
    </div>
</div>