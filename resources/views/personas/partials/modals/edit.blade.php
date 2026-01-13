<!-- MODAL EDITAR -->
<div id="edit-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60 transition-opacity" onclick="closeModal('edit-modal')" style="backdrop-filter: blur(5px);"></div>
        <div class="relative z-10 w-full transform overflow-hidden rounded-xl bg-white dark:bg-dark-card text-left shadow-2xl border border-light-border dark:border-dark-border" style="max-width: 1000px;">
            <div class="bg-white dark:bg-dark-card px-8 py-6 border-b border-light-border dark:border-dark-border flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Editar Persona</h3>
                <button onclick="closeModal('edit-modal')" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="fa-solid fa-times text-xl"></i></button>
            </div>
            
            <div class="p-8">
                <form id="edit-form">
                    <!-- ID OCULTO PARA UPDATE -->
                    <input type="hidden" id="edit-id">

                    <!-- Fila 1 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <x-forms.input-label for="edit-tdoc" value="Tipo Documento" />
                            <select id="edit-tdoc" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-[#121820] dark:text-white py-2.5 px-4 mt-1 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                                <option value="DNI">DNI</option>
                                <option value="CE">CE</option>
                                <option value="PAS">Pasaporte</option>
                            </select>
                        </div>
                        <div>
                            <x-forms.input-label for="edit-doc" value="N° Documento" />
                            <x-forms.text-input id="edit-doc" type="text" class="w-full mt-1" />
                        </div>
                        <div>
                            <x-forms.input-label for="edit-nacionalidad" value="Nacionalidad" />
                            <x-forms.text-input id="edit-nacionalidad" type="text" class="w-full mt-1" />
                        </div>
                    </div>

                    <!-- Fila 2 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <x-forms.input-label for="edit-paterno" value="Apellido Paterno" />
                            <x-forms.text-input id="edit-paterno" type="text" class="w-full mt-1" />
                        </div>
                        <div>
                            <x-forms.input-label for="edit-materno" value="Apellido Materno" />
                            <x-forms.text-input id="edit-materno" type="text" class="w-full mt-1" />
                        </div>
                        <div>
                            <x-forms.input-label for="edit-nombres" value="Nombres" />
                            <x-forms.text-input id="edit-nombres" type="text" class="w-full mt-1" />
                        </div>
                    </div>

                    <!-- Fila 3 -->
                    <div class="flex flex-col md:flex-row gap-6 mb-6">
                        <div class="flex-1">
                            <x-forms.input-label for="edit-nac" value="Fecha Nacimiento" />
                            <x-forms.text-input id="edit-nac" type="date" class="w-full mt-1" />
                        </div>
                        <div class="flex-1">
                            <x-forms.input-label for="edit-genero" value="Género" />
                            <select id="edit-genero" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-[#121820] dark:text-white py-2.5 px-4 mt-1 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                                <option value="1">Masculino</option>
                                <option value="2">Femenino</option>
                            </select>
                        </div>
                    </div>

                    <!-- Fila 4 -->
                    <div class="flex flex-col md:flex-row gap-6 mb-6">
                        <div class="flex-1">
                            <x-forms.input-label for="edit-correo-pers" value="Correo Personal" />
                            <x-forms.text-input id="edit-correo-pers" type="email" class="w-full mt-1" />
                        </div>
                        <div class="flex-1">
                            <x-forms.input-label for="edit-correo-corp" value="Correo Corporativo" />
                            <x-forms.text-input id="edit-correo-corp" type="email" class="w-full mt-1" />
                        </div>
                    </div>

                    <!-- Fila 5 -->
                    <div class="mb-2">
                        <x-forms.input-label for="edit-direccion" value="Dirección" />
                        <x-forms.text-input id="edit-direccion" type="text" class="w-full mt-1" />
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 dark:bg-gray-800 px-8 py-5 flex flex-row-reverse border-t border-light-border dark:border-dark-border gap-4">
                <x-forms.primary-button>Guardar Cambios</x-forms.primary-button>
                <x-forms.secondary-button type="button" onclick="closeModal('edit-modal')">Cancelar</x-forms.secondary-button>
            </div>
        </div>
    </div>
</div>