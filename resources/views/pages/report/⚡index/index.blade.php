@php
    $summary = $this->report['summary'];
    $expenseByCategory = $this->report['expense_by_category'];
    $perWalletRecap = $this->report['per_wallet_recap'];
@endphp

<div class="space-y-6">
    {{-- Header, Breadcrumb & Tombol Export --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-bold text-slate-900 sm:text-xl">Laporan Keuangan Keluarga</h1>
            <nav class="flex items-center gap-1.5 text-sm text-slate-500 mt-1">
                <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-emerald-600 transition-colors">
                    <x-ti-home class="size-4" />
                </a>
                <span>/</span>
                <span class="text-slate-700 font-medium">Laporan Keuangan</span>
            </nav>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-button
                color="emerald"
                href="{{ route('reports.export.excel', ['from' => $dateFrom, 'to' => $dateTo]) }}"
                class="rounded-xl"
            >
                <x-ti-file-spreadsheet class="size-4 mr-1" />
                Export Excel (.csv)
            </x-button>

            <x-button
                color="slate"
                outline
                href="{{ route('reports.export.pdf', ['from' => $dateFrom, 'to' => $dateTo]) }}"
                target="_blank"
                class="rounded-xl"
            >
                <x-ti-printer class="size-4 mr-1" />
                Cetak / Simpan PDF
            </x-button>
        </div>
    </div>

    {{-- Filter Rentang Tanggal & Preset Cepat --}}
    <x-card>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pilih Cepat Periode Laporan</p>
                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        type="button"
                        wire:click="applyPreset('this_month')"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ $activePreset === 'this_month' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                    >
                        Bulan Ini
                    </button>
                    <button
                        type="button"
                        wire:click="applyPreset('last_month')"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ $activePreset === 'last_month' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                    >
                        Bulan Lalu
                    </button>
                    <button
                        type="button"
                        wire:click="applyPreset('last_3_months')"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ $activePreset === 'last_3_months' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                    >
                        3 Bulan Terakhir
                    </button>
                    <button
                        type="button"
                        wire:click="applyPreset('this_year')"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ $activePreset === 'this_year' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                    >
                        Tahun Ini
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:w-96">
                <x-input
                    type="date"
                    label="Dari Tanggal"
                    wire:model.live="dateFrom"
                />
                <x-input
                    type="date"
                    label="Sampai Tanggal"
                    wire:model.live="dateTo"
                />
            </div>
        </div>
    </x-card>

    {{-- 4 Kartu Ringkasan Utama Laporan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-card class="bg-emerald-300 border-emerald-400">
            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-950">Total Pemasukan</p>
            <p class="mt-2 text-2xl font-bold text-emerald-950 tabular-nums">
                +Rp{{ number_format((float) $summary['total_income'], 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs font-medium text-emerald-900">
                Tidak termasuk transfer internal
            </p>
        </x-card>

        <x-card class="bg-rose-300 border-rose-400">
            <p class="text-xs font-semibold uppercase tracking-wider text-rose-950">Total Pengeluaran</p>
            <p class="mt-2 text-2xl font-bold text-rose-950 tabular-nums">
                -Rp{{ number_format((float) $summary['total_expense'], 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs font-medium text-rose-900">
                Akumulasi belanja & tagihan
            </p>
        </x-card>

        <x-card class="bg-sky-300 border-sky-400">
            <p class="text-xs font-semibold uppercase tracking-wider text-sky-950">Selisih Arus Kas Bersih</p>
            <p class="mt-2 text-2xl font-bold text-sky-950 tabular-nums">
                {{ (float) $summary['net_cashflow'] >= 0 ? '+' : '-' }}Rp{{ number_format(abs((float) $summary['net_cashflow']), 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs font-medium text-sky-900">
                Rasio sisa dana: {{ $summary['savings_rate'] }}%
            </p>
        </x-card>

        <x-card class="bg-amber-300 border-amber-400">
            <p class="text-xs font-semibold uppercase tracking-wider text-amber-950">Total Saldo Saat Ini</p>
            <p class="mt-2 text-2xl font-bold text-amber-950 tabular-nums">
                Rp{{ number_format((float) $summary['total_balance'], 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs font-medium text-amber-900">
                Volume transfer internal: Rp{{ number_format((float) $summary['transfer_volume'], 0, ',', '.') }}
            </p>
        </x-card>
    </div>

    {{-- Grafik Arus Kas Bulanan & Grafik Pengeluaran per Kategori --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="lg:col-span-7">
            <x-card header="Grafik Arus Kas Bulanan (6 Bulan Terakhir)">
                <div class="space-y-3">
                    <p class="text-xs text-slate-500">
                        Tren perbandingan total pemasukan dan pengeluaran keluarga setiap bulan.
                    </p>
                    <x-chart
                        bar
                        :labels="$this->cashflowChart['labels']"
                        :series="$this->cashflowChart['series']"
                        legend
                        grid
                        tooltip
                        :height="280"
                    />
                </div>
            </x-card>
        </div>

        <div class="lg:col-span-5">
            <x-card header="Distribusi Pengeluaran per Kategori">
                <div class="space-y-4">
                    <x-chart
                        donut
                        :labels="$this->expenseDonutChart['labels']"
                        :series="$this->expenseDonutChart['series']"
                        legend
                        tooltip
                        :height="220"
                    />

                    @if (empty($expenseByCategory))
                        <p class="text-xs text-slate-500 text-center py-2">
                            Belum ada catatan pengeluaran pada rentang tanggal yang dipilih.
                        </p>
                    @else
                        <div class="space-y-2 pt-2 border-t border-slate-200">
                            @foreach ($expenseByCategory as $item)
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-slate-800">{{ $item['category_name'] }}</span>
                                        <span class="text-slate-500">({{ $item['count'] }}x)</span>
                                    </div>
                                    <div class="flex items-center gap-2 tabular-nums">
                                        <span class="font-bold text-slate-900">Rp{{ number_format((float) $item['total'], 0, ',', '.') }}</span>
                                        <span class="rounded bg-rose-50 px-1.5 py-0.5 text-rose-800 font-semibold border border-rose-200">
                                            {{ $item['percentage'] }}%
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-card>
        </div>
    </div>

    {{-- Tabel Rekap Transaksi per Kantong --}}
    <x-card paddingless>
        <x-slot:header>
            <div>
                <h2 class="text-sm sm:text-base font-semibold text-slate-900">Rekap Transaksi per Kantong</h2>
                <p class="text-xs text-slate-500">
                    Rincian pemasukan, pengeluaran, dan mutasi transfer pada setiap kantong (termasuk kantong historis yang telah ditutup)
                </p>
            </div>
        </x-slot:header>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 pl-5 pr-3">Nama Kantong</th>
                        <th class="px-3 py-3.5">Status</th>
                        <th class="px-3 py-3.5 text-right">Pemasukan</th>
                        <th class="px-3 py-3.5 text-right">Pengeluaran</th>
                        <th class="px-3 py-3.5 text-right">Transfer Masuk</th>
                        <th class="px-3 py-3.5 text-right">Transfer Keluar</th>
                        <th class="px-3 py-3.5 text-center">Jml Trx</th>
                        <th class="py-3.5 pl-3 pr-5 text-right">Saldo Saat Ini</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70 text-sm">
                    @forelse ($perWalletRecap as $row)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 pl-5 pr-3 font-semibold text-slate-900">
                                {{ $row['wallet_name'] }}
                            </td>
                            <td class="px-3 py-3.5">
                                <x-badge
                                    :text="$row['status']->label()"
                                    :color="$row['status']->color()"
                                    sm
                                    light
                                />
                            </td>
                            <td class="px-3 py-3.5 text-right font-semibold text-emerald-700 tabular-nums">
                                +Rp{{ number_format((float) $row['income'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-3.5 text-right font-semibold text-rose-700 tabular-nums">
                                -Rp{{ number_format((float) $row['expense'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-3.5 text-right text-sky-700 tabular-nums">
                                +Rp{{ number_format((float) $row['transfer_in'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-3.5 text-right text-amber-700 tabular-nums">
                                -Rp{{ number_format((float) $row['transfer_out'], 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-3.5 text-center text-xs font-semibold text-slate-700 tabular-nums">
                                {{ $row['tx_count'] }}
                            </td>
                            <td class="py-3.5 pl-3 pr-5 text-right font-bold text-slate-900 tabular-nums">
                                Rp{{ number_format((float) $row['current_balance'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-xs text-slate-500">
                                Belum ada data kantong untuk ditampilkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>

