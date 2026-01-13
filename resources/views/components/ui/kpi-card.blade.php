@props(['title', 'value', 'color' => 'text-gray-800 dark:text-white'])

<div class="bg-white dark:bg-dark-card p-6 rounded-xl shadow-sm border border-light-border dark:border-dark-border hover:shadow-md transition-shadow">
    <h3 class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider mb-2">{{ $title }}</h3>
    <div class="text-3xl font-bold {{ $color }}">{{ $value }}</div>
</div>
