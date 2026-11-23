@props([
    'date',
    'rows',
    'expanded' => false,
])

@php
    $dayIn = (float) $rows->filter(fn ($tx) => $tx->type->isPositive())->sum('amount');
    $dayOut = (float) $rows->filter(fn ($tx) => ! $tx->type->isPositive())->sum('amount');
    $categoryDetails = $expanded
        ? $rows
            ->groupBy(fn ($tx) => $tx->displayCategoryName())
            ->sortKeys(SORT_STRING)
            ->map(fn ($items, string $name): array => [
                'name' => $name,
                'net' => (float) $items->sum(fn ($tx) => $tx->type->isPositive() ? (float) $tx->amount : -(float) $tx->amount),
            ])
        : [];
@endphp

<button
    type="button"
    wire:click="toggleDateDetail('{{ $date }}')"
    wire:key="date-header-{{ $date }}"
    aria-expanded="{{ $expanded ? 'true' : 'false' }}"
    aria-controls="date-detail-{{ $date }}"
    title="Lihat rincian per kategori"
    class="flex w-full cursor-pointer items-center justify-between gap-2 bg-slate-50/70 px-3.5 py-1.5 text-left transition-colors hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-emerald-600 sm:px-4"
>
    <span class="inline-flex min-w-0 items-center gap-1.5 text-xs font-semibold text-slate-500">
        <x-ti-chevron-down class="size-3.5 shrink-0 transition-transform {{ $expanded ? 'rotate-180' : '' }}" aria-hidden="true" />
        <span class="truncate">
            {{ \App\Models\Transaction::dateGroupLabel($rows->first()->transaction_date) }}
        </span>
    </span>

    <div class="inline-flex items-center gap-2 text-xs font-semibold tabular-nums">
        @if ($dayIn > 0)
            <span class="text-emerald-700" title="Total masuk tanggal ini">
                <x-masked-amount :value="'+' . number_format($dayIn, 0, ',', '.')" />
            </span>
        @endif
        @if ($dayOut > 0)
            <span class="text-rose-700" title="Total keluar tanggal ini">
                <x-masked-amount :value="'-' . number_format($dayOut, 0, ',', '.')" />
            </span>
        @endif
        @if ($dayIn == 0 && $dayOut == 0)
            <span class="text-slate-400">
                <x-masked-amount value="0" />
            </span>
        @endif
    </div>
</button>

@if ($expanded)
    <div id="date-detail-{{ $date }}" wire:key="date-detail-{{ $date }}" class="bg-white px-3.5 py-2.5 sm:px-4">
        <div class="space-y-1">
            @foreach ($categoryDetails as $detail)
                @php
                    $net = (float) $detail['net'];
                @endphp
                <div class="flex items-center justify-between gap-2 text-xs">
                    <span class="min-w-0 truncate text-slate-600">
                        {{ $detail['name'] }}
                    </span>
                    <span class="shrink-0 font-semibold tabular-nums {{ $net >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        <x-masked-amount :value="($net >= 0 ? '+' : '-') . number_format(abs($net), 0, ',', '.')" />
                    </span>
                </div>
            @endforeach
        </div>
    </div>
@endif
