<!-- MODAL CREAR (Estructura Unificada) -->
<div id="create-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/60 transition-opacity" onclick="closeModal('create-modal')" style="backdrop-filter: blur(5px);"></div>
        
        <!-- Panel -->
        <div class="relative z-10 w-full transform overflow-hidden rounded-xl bg-white dark:bg-dark-card text-left shadow-2xl border border-light-border dark:border-dark-border" style="max-width: 800px;">
            
            <!-- Header -->
            <div class="bg-white dark:bg-dark-card px-8 py-6 border-b border-light-border dark:border-dark-border flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Registrar Nueva Persona</h3>
                <button onclick="closeModal('create-modal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-8">
                <form action="{{ route('personas.store') }}" method="POST">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nombres -->
                        <div>
                            <x-forms.input-label for="new-nombres" value="Nombres" />
                            <x-forms.text-input id="new-nombres" name="nombres" type="text" class="w-full mt-1" required />
                            <x-forms.input-error :messages="$errors->get('nombres')" class="mt-2" />
                        </div>

                        <!-- Apellido Paterno -->
                        <div>
                            <x-forms.input-label for="new-apellido_paterno" value="Apellido Paterno" />
                            <x-forms.text-input id="new-apellido_paterno" name="apellido_paterno" type="text" class="w-full mt-1" required />
                            <x-forms.input-error :messages="$errors->get('apellido_paterno')" class="mt-2" />
                        </div>

                        <!-- Apellido Materno -->
                        <div>
                            <x-forms.input-label for="new-apellido_materno" value="Apellido Materno" />
                            <x-forms.text-input id="new-apellido_materno" name="apellido_materno" type="text" class="w-full mt-1" />
                        </div>

                        <!-- Tipo Documento -->
                        <div>
                            <x-forms.input-label for="new-tipo_documento" value="Tipo de Documento" />
                            <select id="new-tipo_documento" name="tipo_documento" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm w-full mt-1">
                                <option value="DNI">DNI</option>
                                <option value="CE">Carnet Extranjería</option>
                                <option value="PASAPORTE">Pasaporte</option>
                            </select>
                        </div>

                        <!-- Número Documento -->
                        <div>
                            <x-forms.input-label for="new-numero_documento" value="Número de Documento" />
                            <x-forms.text-input id="new-numero_documento" name="numero_documento" type="text" class="w-full mt-1" required />
                            <x-forms.input-error :messages="$errors->get('numero_documento')" class="mt-2" />
                        </div>

                        <!-- Correo Personal -->
                        <div>
                            <x-forms.input-label for="new-correo_electronico_personal" value="Correo Personal" />
                            <x-forms.text-input id="new-correo_electronico_personal" name="correo_electronico_personal" type="email" class="w-full mt-1" />
                        </div>
                    </div>

                    <!-- Footer / Botones -->
                    <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-light-border dark:border-dark-border">
                        <x-forms.secondary-button type="button" onclick="closeModal('create-modal')">
                            {{ __('Cancelar') }}
                        </x-forms.secondary-button>

                        <x-forms.primary-button class="ms-3">
                            {{ __('Guardar') }}
                        </x-forms.primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>