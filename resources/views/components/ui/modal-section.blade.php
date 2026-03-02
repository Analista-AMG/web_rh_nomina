@props(['label', 'icon', 'iconClass' => ''])

<div>
    <p class="text-[11px] font-bold uppercase tracking-widest text-light-muted dark:text-dark-muted mb-3 flex items-center gap-1.5">
        <i class="fa-solid {{ $icon }} {{ $iconClass }} text-[13px] text-primary"></i>
        {{ $label }}
    </p>
    {{ $slot }}
</div>
