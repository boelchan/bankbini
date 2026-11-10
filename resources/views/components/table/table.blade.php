@props([
    'paginate' => null, // data collection (paginator)
    'tableClass' => 'w-full text-left text-sm',
    'flush' => false, // hapus margin atas saat tabel berdiri sendiri sebagai satu kartu
])

<div {{ $attributes->merge(['class' => $flush ? '' : 'mt-4']) }}>
    <x-card paddingless>
        @isset($header)
            <div class="border-b border-slate-200/80 px-4 py-3">
                {{ $header }}
            </div>
        @endisset

        @isset($empty)
            {{ $empty }}
        @else
            <div class="overflow-x-auto">
                <table class="{{ $tableClass }}">
                    {{ $slot }}
                </table>
            </div>
        @endisset
    </x-card>
    @if ($paginate)
        <div class="mt-3">
            {{ $paginate->onEachSide(1)->links() }}
        </div>
    @endif
</div>
