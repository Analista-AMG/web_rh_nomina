@props([
    'title',
    'icon'      => 'fa-circle',
    'iconClass' => '',
    'modalId',
    'id'        => null,
])

<div @if($id) id="{{ $id }}" @endif
    class="px-8 py-6 border-b border-light-border dark:border-dark-border flex items-center justify-between gap-4 transition-all duration-300">
    <h3 class="text-lg font-bold text-light-text dark:text-dark-text flex items-center gap-2">
        <i class="fa-solid {{ $icon }} {{ $iconClass }} text-sm"></i>
        {{ $title }}
    </h3>
    <button onclick="closeModal('{{ $modalId }}')"
        class="w-8 h-8 rounded-full flex items-center justify-center text-light-muted hover:text-light-text dark:hover:text-dark-text hover:bg-black/8 dark:hover:bg-white/10 transition-colors">
        <i class="fa-solid fa-times"></i>
    </button>
</div>
