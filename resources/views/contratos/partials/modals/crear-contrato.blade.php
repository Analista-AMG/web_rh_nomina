{{-- MODAL CREAR CONTRATO — Paso 3 del flujo Nuevo Contrato --}}
<x-ui.modal-shell id="crear-contrato-modal" max-width="1000px">

    {{-- Header con datos del colaborador (llenado por JS — sección K.4) --}}
    <div class="px-8 py-5 border-b border-light-border dark:border-dark-border sticky top-0 bg-white dark:bg-dark-card z-10">
        <div class="flex justify-between items-start">
            <div class="flex items-center gap-4">
                <div id="crear-avatar"
                    class="h-14 w-14 rounded-2xl bg-primary/10 flex items-center justify-center text-primary text-xl font-black flex-shrink-0">
                </div>
                <div>
                    <p id="crear-persona-nombre-header" class="text-lg font-bold text-light-text dark:text-dark-text">Colaborador</p>
                    <div class="flex items-center gap-2.5 mt-1">
                        <p id="crear-persona-documento" class="text-sm font-mono font-semibold text-light-muted dark:text-dark-muted"></p>
                        <span id="crear-tipo-movimiento"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary"></span>
                    </div>
                </div>
            </div>
            <button onclick="closeModal('crear-contrato-modal')"
                class="w-8 h-8 rounded-full flex items-center justify-center text-light-muted hover:text-light-text dark:hover:text-dark-text hover:bg-black/8 dark:hover:bg-white/10 transition-colors">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
    </div>

    <form id="form-crear-contrato">
        <input type="hidden" id="crear-token" name="token">
        <input type="hidden" id="crear-id-persona" name="persona_id">

        <div class="p-8 space-y-8">

            {{-- Sección: Datos del Contrato --}}
            <x-ui.modal-section label="Datos del Contrato" icon="fa-file-contract">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
                    <x-forms.field label="Fecha de Inicio" for="crear-inicio-contrato">
                        <x-forms.text-input id="crear-inicio-contrato" name="inicio_contrato" type="date" class="w-full bg-light-bg dark:bg-dark-bg" readonly required />
                    </x-forms.field>

                    <x-forms.field label="Fecha de Fin" for="crear-fin-contrato">
                        <x-forms.text-input id="crear-fin-contrato" name="fin_contrato" type="date" class="w-full" required />
                    </x-forms.field>

                    <x-forms.field label="Condición" for="crear-condicion-id">
                        <x-forms.select id="crear-condicion-id" name="condicion_id" class="w-full" required>
                            <option value="">Cargando...</option>
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Periodo de Prueba" for="crear-periodo-prueba">
                        <x-forms.select id="crear-periodo-prueba" name="periodo_prueba" class="w-full">
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Suspensión Renta" for="crear-suspension-renta">
                        <x-forms.select id="crear-suspension-renta" name="suspension_renta" class="w-full">
                            <option value="0">No</option>
                            <option value="1">Sí</option>
                        </x-forms.select>
                    </x-forms.field>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <x-forms.field label="Cargo" for="crear-cargo-id">
                        <x-forms.select id="crear-cargo-id" name="cargo_id" class="w-full" required>
                            <option value="">Cargando...</option>
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Planilla" for="crear-planilla-id">
                        <x-forms.select id="crear-planilla-id" name="planilla_id" class="w-full" required>
                            <option value="">Cargando...</option>
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Centro de Costo" for="crear-centro-costo-id">
                        <x-forms.select id="crear-centro-costo-id" name="centro_costo_id" class="w-full" required>
                            <option value="">Cargando...</option>
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Familia" for="crear-familia-id">
                        <x-forms.select id="crear-familia-id" name="familia_id" class="w-full" required>
                            <option value="">Cargando...</option>
                        </x-forms.select>
                    </x-forms.field>
                </div>
            </x-ui.modal-section>

            {{-- Sección: Remuneración --}}
            <x-ui.modal-section label="Remuneración" icon="fa-money-bill-wave">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <x-forms.field label="Haber Básico (S/)" for="crear-haber-basico">
                        <x-forms.text-input id="crear-haber-basico" name="haber_basico" type="number" step="0.01" class="w-full font-mono" placeholder="0.00" required />
                    </x-forms.field>

                    <x-forms.field label="Movilidad (S/)" for="crear-movilidad">
                        <x-forms.text-input id="crear-movilidad" name="movilidad" type="number" step="0.01" class="w-full font-mono" placeholder="0.00" />
                    </x-forms.field>

                    <x-forms.field label="Asignación Familiar" for="crear-asignacion-familiar">
                        <x-forms.select id="crear-asignacion-familiar" name="asignacion_familiar" class="w-full">
                            <option value="0">No</option>
                            <option value="1">Sí</option>
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Moneda" for="crear-moneda-id">
                        <x-forms.select id="crear-moneda-id" name="moneda_id" class="w-full" required>
                            <option value="">Cargando...</option>
                        </x-forms.select>
                    </x-forms.field>
                </div>
            </x-ui.modal-section>

            {{-- Sección: Datos Bancarios y Pensiones --}}
            <x-ui.modal-section label="Datos Bancarios y Pensiones" icon="fa-building-columns">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-forms.field label="Fondo de Pensiones" for="crear-fp-id">
                        <x-forms.select id="crear-fp-id" name="fondo_pensiones_id" class="w-full" required>
                            <option value="">Cargando...</option>
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Banco" for="crear-banco-id">
                        <x-forms.select id="crear-banco-id" name="banco_id" class="w-full" required>
                            <option value="">Cargando...</option>
                        </x-forms.select>
                    </x-forms.field>

                    <x-forms.field label="Número de Cuenta *" for="crear-numero-cuenta">
                        <x-forms.text-input id="crear-numero-cuenta" name="numero_cuenta" class="w-full" placeholder="Ej: 123-456-789" required />
                    </x-forms.field>

                    <x-forms.field label="Código Interbancario (CCI) *" for="crear-codigo-interbancario">
                        <x-forms.text-input id="crear-codigo-interbancario" name="codigo_interbancario" class="w-full" placeholder="20 dígitos" maxlength="20" required />
                    </x-forms.field>

                    <x-forms.field label="Número de Cuenta CTS" for="crear-numero-cuenta-cts">
                        <x-forms.text-input id="crear-numero-cuenta-cts" name="numero_cuenta_cts" class="w-full" placeholder="Opcional" maxlength="50" />
                    </x-forms.field>

                    <x-forms.field label="Código Interbancario CTS" for="crear-codigo-interbancario-cts">
                        <x-forms.text-input id="crear-codigo-interbancario-cts" name="codigo_interbancario_cts" class="w-full" placeholder="Opcional" maxlength="500" />
                    </x-forms.field>
                </div>
            </x-ui.modal-section>

        </div>

        <x-ui.modal-footer modal-id="crear-contrato-modal">
            <x-forms.primary-button type="submit" id="btn-guardar-contrato">
                <i class="fa-solid fa-save mr-2"></i>Guardar Contrato
            </x-forms.primary-button>
        </x-ui.modal-footer>

    </form>

</x-ui.modal-shell>
