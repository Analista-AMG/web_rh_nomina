<!-- MODAL CREAR CONTRATO -->
<div id="create-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60 transition-opacity" onclick="closeModal('create-modal')" style="backdrop-filter: blur(5px);"></div>
        
        <div class="relative z-10 w-full transform overflow-hidden rounded-xl bg-white dark:bg-dark-card text-left shadow-2xl border border-light-border dark:border-dark-border" style="max-width: 800px;">
            <div class="bg-white dark:bg-dark-card px-8 py-6 border-b border-light-border dark:border-dark-border flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Nuevo Contrato</h3>
                <button onclick="closeModal('create-modal')" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="fa-solid fa-times text-xl"></i></button>
            </div>

            <div class="p-8">
                <form action="{{ route('contratos.store') }}" method="POST">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Empleado -->
                        <div>
                            <x-forms.input-label for="id_persona" value="Empleado" />
                            <select id="id_persona" name="id_persona" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-[#121820] dark:text-white py-2.5 px-4">
                                <option value="">Seleccione...</option>
                                <!-- Backend debe llenar esto -->
                            </select>
                        </div>

                        <!-- Cargo -->
                        <div>
                            <x-forms.input-label for="id_cargo" value="Cargo" />
                            <select id="id_cargo" name="id_cargo" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-[#121820] dark:text-white py-2.5 px-4">
                                <option value="">Seleccione...</option>
                            </select>
                        </div>

                        <!-- Fechas -->
                        <div>
                            <x-forms.input-label for="fecha_inicio" value="Inicio" />
                            <x-forms.text-input id="fecha_inicio" name="fecha_inicio" type="date" class="w-full" required />
                        </div>
                        <div>
                            <x-forms.input-label for="fecha_fin" value="Fin (Opcional)" />
                            <x-forms.text-input id="fecha_fin" name="fecha_fin" type="date" class="w-full" />
                        </div>

                        <!-- Salario -->
                        <div>
                            <x-forms.input-label for="salario" value="Salario Base (S/)" />
                            <x-forms.text-input id="salario" name="haber_basico" type="number" step="0.01" class="w-full" required />
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-light-border dark:border-dark-border">
                        <x-forms.secondary-button type="button" onclick="closeModal('create-modal')">Cancelar</x-forms.secondary-button>
                        <x-forms.primary-button>Guardar</x-forms.primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>