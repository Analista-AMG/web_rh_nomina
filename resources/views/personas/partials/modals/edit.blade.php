<!-- MODAL EDITAR -->
<x-ui.modal-shell id="edit-modal">
    <x-ui.modal-header id="edit-header" modal-id="edit-modal" title="EDITAR PERSONA" icon="fa-pen" icon-class="edit-status-icon" />

    <form id="edit-form">
        <input type="hidden" id="edit-id">

        <div class="p-8 space-y-7">

            <x-ui.modal-section label="Identificación" icon="fa-id-card" icon-class="edit-status-icon">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <x-forms.field label="Tipo Documento" for="edit-tdoc">
                        <x-forms.select id="edit-tdoc">
                            <option value="DNI">DNI</option>
                            <option value="CE">CE</option>
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="N° Documento" for="edit-doc">
                        <x-forms.text-input id="edit-doc" type="text" />
                        <p id="edit-doc-feedback" class="mt-1 text-xs"></p>
                    </x-forms.field>

                    <x-forms.field label="Fecha Nacimiento" for="edit-nac">
                        <x-forms.text-input id="edit-nac" type="date"
                            max="{{ now()->subYears(18)->toDateString() }}" />
                    </x-forms.field>

                    <x-forms.field label="Género" for="edit-genero">
                        <x-forms.select id="edit-genero">
                            <option value="1">Masculino</option>
                            <option value="2">Femenino</option>
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Nacionalidad" for="edit-nacionalidad">
                        <x-forms.select id="edit-nacionalidad">
                            <option value="">Seleccione</option>
                            @foreach ($paises as $pais)
                                <option value="{{ $pais->id }}">{{ $pais->nombre }} ({{ $pais->codigo_pais }})</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                </div>
            </x-ui.modal-section>

            <x-ui.modal-section label="Nombre completo" icon="fa-user" icon-class="edit-status-icon">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-forms.field label="Apellido Paterno" for="edit-paterno">
                        <x-forms.text-input id="edit-paterno" type="text" />
                    </x-forms.field>

                    <x-forms.field label="Apellido Materno" for="edit-materno">
                        <x-forms.text-input id="edit-materno" type="text" />
                    </x-forms.field>

                    <x-forms.field label="Nombres" for="edit-nombres">
                        <x-forms.text-input id="edit-nombres" type="text" />
                    </x-forms.field>
                </div>
            </x-ui.modal-section>

            <x-ui.modal-section label="Ubicación Actual" icon="fa-location-dot" icon-class="edit-status-icon">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                    <x-forms.field label="Departamento" for="edit-departamento">
                        <x-forms.select id="edit-departamento">
                            <option value="">Seleccione un departamento</option>
                            @foreach ($departamentos as $departamento)
                                <option value="{{ $departamento->id }}" data-pais="{{ $departamento->pais_id }}">
                                    {{ $departamento->nombre }}
                                </option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Provincia" for="edit-provincia">
                        <x-forms.select id="edit-provincia">
                            <option value="">Seleccione una provincia</option>
                            @foreach ($provincias as $provincia)
                                <option value="{{ $provincia->id }}" data-departamento="{{ $provincia->departamento_id }}">
                                    {{ $provincia->nombre }}
                                </option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Distrito" for="edit-distrito">
                        <x-forms.select id="edit-distrito">
                            <option value="">Seleccione un distrito</option>
                        </x-forms.select>
                    </x-forms.field>
                </div>

                <x-forms.field label="Dirección" for="edit-direccion">
                    <x-forms.text-input id="edit-direccion" type="text" />
                </x-forms.field>
            </x-ui.modal-section>

            <x-ui.modal-section label="Contacto" icon="fa-address-book" icon-class="edit-status-icon">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-forms.field label="Número Telefónico" for="edit-telefono">
                        <x-forms.text-input id="edit-telefono" type="tel"
                            inputmode="numeric" pattern="\d{1,9}" maxlength="9"
                            oninput="this.value = this.value.replace(/\D/g, '').slice(0, 9)" />
                    </x-forms.field>

                    <x-forms.field label="Correo Personal" for="edit-correo-pers">
                        <x-forms.text-input id="edit-correo-pers" type="email" />
                        <p id="edit-correo-pers-feedback" class="mt-1 text-xs"></p>
                    </x-forms.field>

                    <x-forms.field label="Correo Corporativo" for="edit-correo-corp">
                        <x-forms.text-input id="edit-correo-corp" type="email" />
                        <p id="edit-correo-corp-feedback" class="mt-1 text-xs"></p>
                    </x-forms.field>
                </div>
            </x-ui.modal-section>

        </div>

        <x-ui.modal-footer modal-id="edit-modal">
            <x-forms.primary-button id="btn-save-persona">
                <i class="fa-solid fa-floppy-disk"></i>
                Guardar Cambios
            </x-forms.primary-button>
        </x-ui.modal-footer>
    </form>
</x-ui.modal-shell>
