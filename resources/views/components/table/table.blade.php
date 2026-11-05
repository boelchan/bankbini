@props([
    'paginate' => null, // data collection (paginator)
    'tableClass' => 'w-full text-left text-sm',
])

<div {{ $attributes->merge(['class' => 'mt-4']) }}>
    <x-card class="overflow-x-auto" paddingless>
        <table class="{{ $tableClass }}">
            {{ $slot }}
        </table>
    </x-card>
    @if ($paginate)
        <div class="mt-3">
            {{ $paginate->onEachSide(1)->links() }}
        </div>
    @endif
</div>
