<div id="modal-create" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-primary"></i> Nueva Asignación
            </h2>
            <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition cursor-pointer">
                <i class="fa-solid fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Rol <span class="text-red-500">*</span></label>
                    <select id="create-rol" onchange="onRolChange()" class="form-input w-full text-sm">
                        <option value="">Seleccionar rol...</option>
                        @if($miNivelMax > 4)<option value="Jefe Operaciones">Jefe Operaciones</option>@endif
                        @if($miNivelMax > 3)<option value="Coordinador">Coordinador</option>@endif
                        @if($miNivelMax > 2)<option value="Supervisor">Supervisor</option>@endif
                        @if($miNivelMax > 1)<option value="Colaborador">Colaborador</option>@endif
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Fecha Inicio <span class="text-red-500">*</span></label>
                    <input type="date" id="create-fecha" class="form-input w-full text-sm" value="{{ now()->toDateString() }}" min="{{ now()->subDays(20)->toDateString() }}">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Campaña <span class="text-red-500">*</span></label>
                    <select id="create-campana" onchange="onCampanaChange()" class="form-input w-full text-sm" disabled>
                        <option value="">Selecciona un rol primero...</option>
                        @foreach($campanas as $c)
                        <option value="{{ $c->id }}" data-tiene-subs="{{ $c->subcampanas_count > 0 ? '1' : '0' }}">
                            {{ $c->nombre }}{{ $c->subcampanas_count > 0 ? ' (grupo)' : '' }}
                        </option>
                        @endforeach
                    </select>
                    <p id="campana-hint" class="hidden mt-1 text-xs text-amber-600 dark:text-amber-400">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                        Las campañas marcadas como <em>(grupo)</em> solo aplican para Jefe de Operaciones.
                    </p>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Usuario <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" id="create-usuario-search"
                            class="form-input w-full text-sm"
                            placeholder="Selecciona una campaña primero..."
                            autocomplete="off" disabled>
                        <input type="hidden" id="create-usuario">
                        <ul id="usuario-dropdown"
                            class="absolute z-50 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded shadow-lg max-h-48 overflow-y-auto hidden text-sm mt-1">
                        </ul>
                    </div>
                    <div id="usuario-chips" class="flex flex-wrap gap-1 mt-1 hidden"></div>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                        Superior <span class="text-gray-400 font-normal">(opcional)</span>
                    </label>
                    <select id="create-superior" class="form-input w-full text-sm" disabled>
                        <option value="">Selecciona campaña y rol primero...</option>
                    </select>
                </div>
            </div>
            <div id="auto-approve-note" class="hidden text-xs text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg p-3 flex items-center gap-2">
                <i class="fa-solid fa-circle-check flex-shrink-0"></i>
                Tienes autoridad suficiente — será aprobada automáticamente.
            </div>
            <div id="pending-note" class="hidden text-xs text-yellow-700 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3 flex items-center gap-2">
                <i class="fa-solid fa-clock flex-shrink-0"></i>
                Quedará pendiente de aprobación por un superior.
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t dark:border-gray-700">
            <button onclick="closeCreateModal()" class="btn btn-secondary text-sm px-4 py-2 cursor-pointer">Cancelar</button>
            <button onclick="submitCreate()" class="btn btn-primary text-sm px-4 py-2 cursor-pointer">
                <i class="fa-solid fa-save mr-1"></i> Guardar
            </button>
        </div>
    </div>
</div>
