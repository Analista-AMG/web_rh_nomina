@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-center mt-4">
        <ul class="flex items-center gap-1">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li>
                    <span class="px-3 py-2 text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md cursor-not-allowed">
                        <i class="fa-solid fa-chevron-left"></i>
                    </span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-2 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li>
                        <span class="px-3 py-2 text-gray-500 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li>
                                <span class="px-3 py-2 text-white bg-[#e67e22] border border-[#e67e22] rounded-md shadow-sm">
                                    {{ $page }}
                                </span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $url }}" class="px-3 py-2 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    {{ $page }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-2 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </li>
            @else
                <li>
                    <span class="px-3 py-2 text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md cursor-not-allowed">
                        <i class="fa-solid fa-chevron-right"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
