@props(['sort' => null, 'label' => '', 'width' => 'auto'])

@aware(['sortField', 'sortDirection'])

<th class="p-2" width="{{ $width }}">
    @if ($sort)
        <button class="flex cursor-pointer items-center gap-1" type="button" wire:click="sortBy('{{ $sort }}')">
            {{ $label }}
            @if ($sortField === $sort)
                @if ($sortDirection === 'asc')
                    <x-ti-arrow-up class="size-4" />
                @else
                    <x-ti-arrow-down class="size-4" />
                @endif
            @else
                <x-ti-arrows-sort class="size-4 text-neutral-400" />
            @endif
        </button>
    @else
        {{ $label }}
    @endif
</th>
