@props(['disabled' => false])

<select @disabled($disabled) {{ $attributes->merge(['class' => 'form-input']) }}>
    {{ $slot }}
</select>
