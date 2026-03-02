@props(['modalId', 'cancelLabel' => 'Cancelar'])

<div class="px-8 py-4 border-t border-light-border dark:border-dark-border flex justify-end gap-3 bg-light-bg dark:bg-dark-bg">
    <x-forms.secondary-button type="button" onclick="closeModal('{{ $modalId }}')">
        {{ $cancelLabel }}
    </x-forms.secondary-button>
    {{ $acciones ?? $slot }}
</div>
