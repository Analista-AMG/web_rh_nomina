<!-- MODAL EDITAR CONTRATO -->
<div id="edit-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60 transition-opacity" onclick="closeModal('edit-modal')" style="backdrop-filter: blur(5px);"></div>
        
        <div class="relative z-10 w-full transform overflow-hidden rounded-xl bg-white dark:bg-dark-card text-left shadow-2xl border border-light-border dark:border-dark-border" style="max-width: 800px;">
            <div class="bg-white dark:bg-dark-card px-8 py-6 border-b border-light-border dark:border-dark-border flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Editar Contrato</h3>
                <button onclick="closeModal('edit-modal')" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="fa-solid fa-times text-xl"></i></button>
            </div>

            <div class="p-8">
                <!-- FORMULARIO EDITAR -->
                <form id="edit-form"> <!-- JS manejará el submit -->
                    <input type="hidden" id="edit-id">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Empleado (Solo lectura) -->
                        <div>
                            <x-forms.input-label for="edit-empleado" value="Colaborador" />
                            <x-forms.text-input id="edit-empleado" type="text" class="w-full bg-gray-100" readonly />
                        </div>

                        <!-- Cargo -->
                        <div>
                            <x-forms.input-label for="edit-cargo" value="Cargo" />
                            <!-- Idealmente un select, por ahora texto editable o select si inyectamos opciones -->
                            <x-forms.text-input id="edit-cargo" type="text" class="w-full bg-gray-100" readonly />
                            <p class="text-xs text-gray-500 mt-1">Para cambiar cargo, cree un nuevo contrato o adenda.</p>
                        </div>

                        <!-- Fechas -->
                        <div>
                            <x-forms.input-label for="edit-inicio" value="Fecha Inicio" />
                            <x-forms.text-input id="edit-inicio" type="date" class="w-full" required />
                        </div>
                        <div>
                            <x-forms.input-label for="edit-fin" value="Fecha Fin" />
                            <x-forms.text-input id="edit-fin" type="date" class="w-full" />
                        </div>

                        <!-- Salario -->
                        <div>
                            <x-forms.input-label for="edit-salario" value="Haber Básico (S/)" />
                            <x-forms.text-input id="edit-salario" type="number" step="0.01" class="w-full" required />
                        </div>

                        <!-- Estado -->
                        <div>
                            <x-forms.input-label for="edit-estado" value="Estado" />
                            <select id="edit-estado" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-[#121820] dark:text-white py-2.5 px-4">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-light-border dark:border-dark-border">
                        <x-forms.secondary-button type="button" onclick="closeModal('edit-modal')">Cancelar</x-forms.secondary-button>
                        <x-forms.primary-button>Guardar Cambios</x-forms.primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>