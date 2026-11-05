<nav class="flex flex-col items-center justify-between gap-2 sm:flex-row" role="navigation">
    @if ($paginator->total() > 10)
        <select class="w-16 flex-none rounded-lg border bg-white px-2 py-1 text-sm focus:border-emerald-500 focus:ring-emerald-500" wire:model.live="perPage">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
    @endif
    <div class="flex-1">
        @if ($paginator->total() === $paginator->lastItem())
            <p class="text-sm leading-5 text-gray-700">
                Tampil
                <span class="font-medium">{{ $paginator->total() }}</span>
                data
            </p>
        @elseif ($paginator->total() === 0)
        @else
            <p class="text-sm leading-5 text-gray-700">
                Tampil
                @if ($paginator->firstItem())
                    <span class="font-medium">{{ $paginator->firstItem() }}</span>
                    -
                    <span class="font-medium">{{ $paginator->lastItem() }}</span>
                @else
                    {{ $paginator->count() }}
                @endif
                dari
                <span class="font-medium">{{ $paginator->total() }}</span>
                data
            </p>

        @endif
    </div>
    @if ($paginator->hasPages())
        <div class="inline-flex items-center">
            {{-- Previous Page --}}
            @if ($paginator->onFirstPage())
            @else
                <button class="inline-flex h-8 w-8 items-center justify-center rounded-full text-emerald-600 hover:bg-emerald-50 transition-colors" type="button" wire:click="previousPage">
                    <x-ti-chevron-left class="size-5" />
                </button>
            @endif

            {{-- Numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <button class="inline-flex h-8 w-8 items-center justify-center text-sm text-slate-400" type="button">{{ $element }}</button>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <button class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white" type="button">{{ $page }}</button>
                        @else
                            <button class="inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium text-emerald-600 hover:bg-emerald-50 transition-colors" type="button"
                                wire:click="gotoPage({{ $page }})"
                            >{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page --}}
            @if ($paginator->hasMorePages())
                <button class="inline-flex h-8 w-8 items-center justify-center rounded-full text-emerald-600 hover:bg-emerald-50 transition-colors" type="button" wire:click="nextPage">
                    <x-ti-chevron-right class="size-5" />
                </button>
            @else
            @endif
        </div>
    @endif
</nav>
