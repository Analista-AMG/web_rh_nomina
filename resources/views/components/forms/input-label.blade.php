@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold mb-1 text-light-text dark:text-dark-text']) }}>
    {{ $value ?? $slot }}
</label>
