@props(['id', 'maxWidth' => '960px'])

<div id="{{ $id }}" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60 transition-opacity" onclick="closeModal('{{ $id }}')" style="backdrop-filter: blur(5px);"></div>
        <div class="relative z-10 w-full transform overflow-hidden rounded-2xl bg-white dark:bg-dark-card text-left shadow-2xl border border-light-border dark:border-dark-border" style="max-width: {{ $maxWidth }};">
            {{ $slot }}
        </div>
    </div>
</div>
