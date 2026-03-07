{{-- ═══════════════════════════════════════════════════════════════════════
     MODAL: ANULAR préstamo aprobado
════════════════════════════════════════════════════════════════════════ --}}
<x-ui.modal-shell id="modal-anular" max-width="480px">
    <x-ui.modal-header modal-id="modal-anular" title="Anular préstamo"
        icon="fa-rotate-left" icon-class="text-orange-500" />

    <div class="px-8 py-4">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            El colaborador <strong>regresará al equipo del supervisor origen</strong> para todo
            el rango del préstamo. La asistencia ya marcada no se verá afectada.
            El supervisor origen podrá reasignarlo si corresponde.
        </p>
    </div>

    <x-ui.modal-section label="Motivo de anulación" icon="fa-comment-dots">
        <textarea id="anular-motivo" rows="3"
            placeholder="Opcional: explica por qué se anula este préstamo..."
            class="form-input w-full resize-none"></textarea>
    </x-ui.modal-section>

    <input type="hidden" id="anular-prestamo-id" />

    <x-ui.modal-footer modal-id="modal-anular" cancel-label="Cancelar">
        <x-slot name="acciones">
            <button id="btn-anular-submit" onclick="submitAnular()"
                class="px-4 py-2 bg-orange-500 text-white rounded-lg text-sm font-medium hover:bg-orange-600 transition-colors cursor-pointer flex items-center gap-2">
                <i class="fa-solid fa-rotate-left"></i> Confirmar anulación
            </button>
        </x-slot>
    </x-ui.modal-footer>
</x-ui.modal-shell>

{{-- ═══════════════════════════════════════════════════════════════════════
     MODAL: RECHAZAR préstamo
════════════════════════════════════════════════════════════════════════ --}}
<x-ui.modal-shell id="modal-rechazar" max-width="480px">
    <x-ui.modal-header modal-id="modal-rechazar" title="Rechazar préstamo"
        icon="fa-ban" icon-class="text-red-500" />

    <x-ui.modal-section label="Motivo del rechazo" icon="fa-comment-dots">
        <textarea id="rechazar-motivo" rows="3"
            placeholder="Opcional: explica el motivo del rechazo..."
            class="form-input w-full resize-none"></textarea>
    </x-ui.modal-section>

    <input type="hidden" id="rechazar-prestamo-id" />

    <x-ui.modal-footer modal-id="modal-rechazar" cancel-label="Cancelar">
        <x-slot name="acciones">
            <x-forms.danger-button id="btn-rechazar-submit" onclick="submitRechazar()">
                <i class="fa-solid fa-ban mr-1"></i> Rechazar
            </x-forms.danger-button>
        </x-slot>
    </x-ui.modal-footer>
</x-ui.modal-shell>
