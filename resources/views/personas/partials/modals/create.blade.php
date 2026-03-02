<!-- MODAL CREAR -->
<x-ui.modal-shell id="create-modal">
    <x-ui.modal-header modal-id="create-modal" title="REGISTRAR PERSONA" icon="fa-user-plus" />

    <form id="create-form">
        @csrf

        <div class="p-8 space-y-7">

            <x-ui.modal-section label="Identificación" icon="fa-id-card">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <x-forms.field label="Tipo Documento" for="new-tipo_documento">
                        <x-forms.select id="new-tipo_documento" name="tipo_documento">
                            <option value="DNI">DNI</option>
                            <option value="CE">CE</option>
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="N° Documento" for="new-numero_documento">
                        <x-forms.text-input id="new-numero_documento" name="numero_documento" type="text"
                            inputmode="numeric" pattern="\d+"
                            oninput="this.value = this.value.replace(/\D/g, '')" required />
                        <p id="new-doc-feedback" class="mt-1 text-xs"></p>
                    </x-forms.field>

                    <x-forms.field label="Fecha Nacimiento" for="new-fecha_nacimiento">
                        <x-forms.text-input id="new-fecha_nacimiento" name="fecha_nacimiento" type="date"
                            max="{{ now()->subYears(18)->toDateString() }}" />
                    </x-forms.field>

                    <x-forms.field label="Género" for="new-genero">
                        <x-forms.select id="new-genero" name="genero">
                            <option value="1">Masculino</option>
                            <option value="2">Femenino</option>
                        </x-forms.select>
                    </x-forms.field>
                </div>
            </x-ui.modal-section>

            <x-ui.modal-section label="Nombre completo" icon="fa-user">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-forms.field label="Apellido Paterno" for="new-apellido_paterno">
                        <x-forms.text-input id="new-apellido_paterno" name="apellido_paterno" type="text"
                            oninput="this.value = this.value.replace(/\d/g, '')" required />
                    </x-forms.field>

                    <x-forms.field label="Apellido Materno" for="new-apellido_materno">
                        <x-forms.text-input id="new-apellido_materno" name="apellido_materno" type="text"
                            oninput="this.value = this.value.replace(/\d/g, '')" />
                    </x-forms.field>

                    <x-forms.field label="Nombres" for="new-nombres">
                        <x-forms.text-input id="new-nombres" name="nombres" type="text"
                            oninput="this.value = this.value.replace(/\d/g, '')" required />
                    </x-forms.field>
                </div>
            </x-ui.modal-section>

            <x-ui.modal-section label="Ubicación" icon="fa-location-dot">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <x-forms.field label="País" for="new-pais">
                        <x-forms.select id="new-pais" name="pais">
                            <option value="">Seleccione</option>
                            @foreach ($paises as $pais)
                                <option value="{{ $pais->id }}" data-codigo="{{ $pais->codigo_pais }}">
                                    {{ $pais->nombre }} ({{ $pais->codigo_pais }})
                                </option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Departamento" for="new-departamento">
                        <x-forms.select id="new-departamento" name="departamento">
                            <option value="">Seleccione</option>
                            @foreach ($departamentos as $departamento)
                                <option value="{{ $departamento->id }}" data-pais="{{ $departamento->pais_id }}">
                                    {{ $departamento->nombre }}
                                </option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Provincia" for="new-provincia">
                        <x-forms.select id="new-provincia" name="provincia">
                            <option value="">Seleccione</option>
                            @foreach ($provincias as $provincia)
                                <option value="{{ $provincia->id }}" data-departamento="{{ $provincia->departamento_id }}">
                                    {{ $provincia->nombre }}
                                </option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Distrito" for="new-distrito">
                        <x-forms.select id="new-distrito" name="distrito">
                            <option value="">Seleccione</option>
                        </x-forms.select>
                    </x-forms.field>
                </div>

                <x-forms.field label="Dirección" for="new-direccion">
                    <x-forms.text-input id="new-direccion" name="direccion" type="text" />
                </x-forms.field>
            </x-ui.modal-section>

            <x-ui.modal-section label="Contacto" icon="fa-address-book">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-forms.field label="Número Telefónico" for="new-numero_telefonico">
                        <x-forms.text-input id="new-numero_telefonico" name="numero_telefonico" type="tel"
                            inputmode="numeric" pattern="\d{1,9}" maxlength="9"
                            oninput="this.value = this.value.replace(/\D/g, '').slice(0, 9)" />
                    </x-forms.field>

                    <x-forms.field label="Correo Personal" for="new-correo_electronico_personal">
                        <x-forms.text-input id="new-correo_electronico_personal" name="correo_electronico_personal"
                            type="email" required />
                        <p id="new-correo-pers-feedback" class="mt-1 text-xs"></p>
                    </x-forms.field>

                    <x-forms.field label="Correo Corporativo" for="new-correo_electronico_corporativo">
                        <x-forms.text-input id="new-correo_electronico_corporativo" name="correo_electronico_corporativo"
                            type="email" />
                        <p id="new-correo-corp-feedback" class="mt-1 text-xs"></p>
                    </x-forms.field>
                </div>
            </x-ui.modal-section>

        </div>

        <x-ui.modal-footer modal-id="create-modal">
            <x-forms.primary-button type="submit">
                <i class="fa-solid fa-user-plus"></i>
                Registrar
            </x-forms.primary-button>
        </x-ui.modal-footer>
    </form>
</x-ui.modal-shell>
