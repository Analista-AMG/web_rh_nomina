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
                        <!-- Tipo Documento -->
                        <div>
                            <x-forms.input-label for="new-tipo_documento" value="Tipo de Documento" />
                            <select id="new-tipo_documento" name="tipo_documento" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm w-full mt-1">
                                <option value="DNI">DNI</option>
                                <option value="CE">CE</option>
                            </select>
                        </div>

                        <!-- Numero Documento -->
                        <div>
                            <x-forms.input-label for="new-numero_documento" value="Numero de Documento" />
                            <x-forms.text-input id="new-numero_documento" name="numero_documento" type="text" class="w-full mt-1" inputmode="numeric" pattern="\d+" oninput="this.value = this.value.replace(/\D/g, '')" required />
                            <x-forms.input-error :messages="$errors->get('numero_documento')" class="mt-2" />
                        </div>

                        <!-- Nombres -->
                        <div>
                            <x-forms.input-label for="new-nombres" value="Nombres" />
                            <x-forms.text-input id="new-nombres" name="nombres" type="text" class="w-full mt-1" oninput="this.value = this.value.replace(/\d/g, '')" required />
                            <x-forms.input-error :messages="$errors->get('nombres')" class="mt-2" />
                        </div>

                        <!-- Apellido Paterno -->
                        <div>
                            <x-forms.input-label for="new-apellido_paterno" value="Apellido Paterno" />
                            <x-forms.text-input id="new-apellido_paterno" name="apellido_paterno" type="text" class="w-full mt-1" oninput="this.value = this.value.replace(/\d/g, '')" required />
                            <x-forms.input-error :messages="$errors->get('apellido_paterno')" class="mt-2" />
                        </div>

                        <!-- Apellido Materno -->
                        <div>
                            <x-forms.input-label for="new-apellido_materno" value="Apellido Materno" />
                            <x-forms.text-input id="new-apellido_materno" name="apellido_materno" type="text" class="w-full mt-1" oninput="this.value = this.value.replace(/\d/g, '')" />
                        </div>

                        <!-- Genero -->
                        <div>
                            <x-forms.input-label for="new-genero" value="Genero" />
                            <select id="new-genero" name="genero" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm w-full mt-1">
                                <option value="1">Masculino</option>
                                <option value="2">Femenino</option>
                            </select>
                        </div>

                        <!-- Fecha de Nacimiento -->
                        <div>
                            <x-forms.input-label for="new-fecha_nacimiento" value="Fecha de Nacimiento" />
                            <x-forms.text-input id="new-fecha_nacimiento" name="fecha_nacimiento" type="date" class="w-full mt-1" />
                        </div>

                        <!-- Pais -->
                        <div>
                            <x-forms.input-label for="new-pais" value="Pais" />
                            <select id="new-pais" name="pais" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm w-full mt-1">
                                <option value="">Seleccione un pais</option>
                                @foreach ($paises as $pais)
                                    <option value="{{ $pais->id }}" data-codigo="{{ $pais->codigo_pais }}">{{ $pais->nombre }} ({{ $pais->codigo_pais }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Departamento -->
                        <div>
                            <x-forms.input-label for="new-departamento" value="Departamento" />
                            <select id="new-departamento" name="departamento" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm w-full mt-1">
                                <option value="">Seleccione un departamento</option>
                                @foreach ($departamentos as $departamento)
                                    <option value="{{ $departamento->id }}" data-pais="{{ $departamento->pais_id }}">{{ $departamento->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Distrito -->
                        <div>
                            <x-forms.input-label for="new-distrito" value="Distrito" />
                            <select id="new-distrito" name="distrito" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm w-full mt-1">
                                <option value="">Seleccione un distrito</option>
                                @foreach ($distritos as $distrito)
                                    <option value="{{ $distrito->id }}" data-departamento="{{ $distrito->departamento_id }}">{{ $distrito->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Direccion -->
                        <div>
                            <x-forms.input-label for="new-direccion" value="Direccion" />
                            <x-forms.text-input id="new-direccion" name="direccion" type="text" class="w-full mt-1" />
                        </div>

                        <!-- Numero Telefonico -->
                        <div>
                            <x-forms.input-label for="new-numero_telefonico" value="Numero Telefonico" />
                            <x-forms.text-input id="new-numero_telefonico" name="numero_telefonico" type="tel" class="w-full mt-1" />
                        </div>

                        <!-- Correo Personal -->
                        <div>
                            <x-forms.input-label for="new-correo_electronico_personal" value="Correo Personal" />
                            <x-forms.text-input id="new-correo_electronico_personal" name="correo_electronico_personal" type="email" class="w-full mt-1" />
                        </div>

                        <!-- Correo Corporativo -->
                        <div>
                            <x-forms.input-label for="new-correo_electronico_corporativo" value="Correo Corporativo" />
                            <x-forms.text-input id="new-correo_electronico_corporativo" name="correo_electronico_corporativo" type="email" class="w-full mt-1" />
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
