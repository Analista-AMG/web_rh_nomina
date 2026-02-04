<!-- MODAL CREAR CONTRATO COMPLETO -->
<div id="crear-contrato-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60 transition-opacity" style="backdrop-filter: blur(5px);"></div>

        <div class="relative z-10 w-full transform overflow-hidden rounded-xl bg-white dark:bg-dark-card text-left shadow-2xl border border-light-border dark:border-dark-border" style="max-width: 1000px; max-height: 90vh; overflow-y: auto;">
            <!-- Cabecera con datos del colaborador -->
            <div class="bg-white dark:bg-dark-card px-8 py-6 border-b border-light-border dark:border-dark-border sticky top-0 z-10">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xl font-bold" id="crear-avatar">
                            --
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white" id="crear-persona-nombre-header">Colaborador</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5" id="crear-persona-documento"></p>
                            <span id="crear-tipo-movimiento" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary mt-1"></span>
                        </div>
                    </div>
                    <button onclick="closeModal('crear-contrato-modal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <div class="p-8">
                <form id="form-crear-contrato">
                    <!-- Campos ocultos -->
                    <input type="hidden" id="crear-token" name="token">
                    <input type="hidden" id="crear-id-persona" name="id_persona">

                    <!-- Seccion: Datos del Contrato -->
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-file-contract text-primary"></i>
                            Datos del Contrato
                        </h4>
                        <!-- Primera fila: Fechas, Periodo y Condicion -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <!-- Fecha Inicio -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha de Inicio</label>
                                <input
                                    type="date"
                                    id="crear-inicio-contrato"
                                    name="inicio_contrato"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                    readonly
                                    required
                                >
                            </div>

                            <!-- Fecha Fin -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha de Fin</label>
                                <input
                                    type="date"
                                    id="crear-fin-contrato"
                                    name="fin_contrato"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                    required
                                >
                            </div>

                            <!-- Condicion -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Condicion</label>
                                <select
                                    id="crear-condicion"
                                    name="id_condicion"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                    required
                                >
                                    <option value="">Cargando...</option>
                                </select>
                            </div>

                            <!-- Periodo de Prueba -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Periodo de Prueba</label>
                                <select
                                    id="crear-periodo-prueba"
                                    name="periodo_prueba"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                >
                                    <option value="1">Si</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>

                        <!-- Segunda fila: Cargo, Planilla, Centro de Costo -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Cargo -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cargo</label>
                                <select
                                    id="crear-cargo"
                                    name="id_cargo"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                    required
                                >
                                    <option value="">Cargando...</option>
                                </select>
                            </div>

                            <!-- Planilla -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Planilla</label>
                                <select
                                    id="crear-planilla"
                                    name="id_planilla"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                    required
                                >
                                    <option value="">Cargando...</option>
                                </select>
                            </div>

                            <!-- Centro de Costo -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Centro de Costo</label>
                                <select
                                    id="crear-centro-costo"
                                    name="id_centro_costo"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                    required
                                >
                                    <option value="">Cargando...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Seccion: Remuneracion -->
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-money-bill-wave text-primary"></i>
                            Remuneracion
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Haber Basico -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Haber Basico (S/)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    id="crear-haber-basico"
                                    name="haber_basico"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                    placeholder="0.00"
                                    required
                                >
                            </div>

                            <!-- Asignacion Familiar -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Asignacion Familiar</label>
                                <select
                                    id="crear-asignacion-familiar"
                                    name="asignacion_familiar"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                >
                                    <option value="0">No</option>
                                    <option value="1">Si</option>
                                </select>
                            </div>

                            <!-- Moneda -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Moneda</label>
                                <select
                                    id="crear-moneda"
                                    name="id_moneda"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                    required
                                >
                                    <option value="">Cargando...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Seccion: Datos Bancarios y Pensiones -->
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-building-columns text-primary"></i>
                            Datos Bancarios y Pensiones
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Fondo de Pensiones -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fondo de Pensiones</label>
                                <select
                                    id="crear-fp"
                                    name="id_fp"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                    required
                                >
                                    <option value="">Cargando...</option>
                                </select>
                            </div>

                            <!-- Banco -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Banco</label>
                                <select
                                    id="crear-banco"
                                    name="id_banco"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                    required
                                >
                                    <option value="">Cargando...</option>
                                </select>
                            </div>

                            <!-- Numero de Cuenta -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Numero de Cuenta *</label>
                                <input
                                    type="text"
                                    id="crear-numero-cuenta"
                                    name="numero_cuenta"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                    placeholder="Ej: 123-456-789"
                                    required
                                >
                            </div>

                            <!-- Codigo Interbancario -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Codigo Interbancario (CCI) *</label>
                                <input
                                    type="text"
                                    id="crear-codigo-interbancario"
                                    name="codigo_interbancario"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                    placeholder="20 digitos"
                                    maxlength="20"
                                    required
                                >
                            </div>

                            <!-- Numero de Cuenta CTS -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Numero de Cuenta CTS</label>
                                <input
                                    type="text"
                                    id="crear-numero-cuenta-cts"
                                    name="numero_cuenta_cts"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                    placeholder="Opcional"
                                    maxlength="50"
                                >
                            </div>

                            <!-- Codigo Interbancario CTS -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Codigo Interbancario CTS</label>
                                <input
                                    type="text"
                                    id="crear-codigo-interbancario-cts"
                                    name="codigo_interbancario_cts"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-4"
                                    placeholder="Opcional"
                                    maxlength="500"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-light-border dark:border-dark-border">
                        <x-forms.secondary-button type="button" onclick="closeModal('crear-contrato-modal')">
                            Cancelar
                        </x-forms.secondary-button>
                        <x-forms.primary-button type="submit" id="btn-guardar-contrato">
                            <i class="fa-solid fa-save mr-2"></i>
                            Guardar Contrato
                        </x-forms.primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
