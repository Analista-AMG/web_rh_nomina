@props(['label' => null, 'for' => null])

<div>
    @if ($label)
        <x-forms.input-label :for="$for ?? ''" :value="$label" />
    @endif
    {{ $slot }}
</div>
