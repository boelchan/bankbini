<div class="space-y-6">
    {{-- Header & Tombol Transaksi --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-bold text-slate-900 sm:text-xl">Rekap Transaksi</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Rekapitulasi mutasi uang masuk, uang keluar, dan pembagian per kategori belanja keluarga.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-button color="emerald" wire:click="openIncomeModal" class="rounded-xl">
                <x-ti-plus class="size-4 mr-1" />
                Masuk
            </x-button>

            <x-button color="rose" light wire:click="openExpenseModal" class="rounded-xl">
                <x-ti-minus class="size-4 mr-1" />
                Keluar
            </x-button>

            <x-button color="sky" light wire:click="openTransferModal" class="rounded-xl">
                <x-ti-arrows-exchange class="size-4 mr-1" />
                Pindah
            </x-button>
        </div>
    </div>

    {{-- Ringkasan Metrik Rekap --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-card class="bg-emerald-300 border-emerald-400">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-950">Total Pemasukan</p>
                    <p class="mt-1.5 text-2xl font-bold text-emerald-950 tabular-nums">
                        +{{ number_format((float) $this->recapSummary['total_income'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/75 text-emerald-900 border border-emerald-400">
                    <x-ti-arrow-down-left class="size-5" />
                </div>
            </div>
        </x-card>

        <x-card class="bg-rose-300 border-rose-400">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-rose-950">Total Pengeluaran</p>
                    <p class="mt-1.5 text-2xl font-bold text-rose-950 tabular-nums">
                        -{{ number_format((float) $this->recapSummary['total_expense'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/75 text-rose-900 border border-rose-400">
                    <x-ti-arrow-up-right class="size-5" />
                </div>
            </div>
        </x-card>

        <x-card class="{{ $this->recapSummary['net_cashflow'] >= 0 ? 'bg-sky-300 border-sky-400' : 'bg-amber-300 border-amber-400' }}">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider {{ $this->recapSummary['net_cashflow'] >= 0 ? 'text-sky-950' : 'text-amber-950' }}">
                        Arus Kas Bersih (Selisih)
                    </p>
                    <p class="mt-1.5 text-2xl font-bold {{ $this->recapSummary['net_cashflow'] >= 0 ? 'text-sky-950' : 'text-amber-950' }} tabular-nums">
                        {{ $this->recapSummary['net_cashflow'] >= 0 ? '+' : '' }}{{ number_format((float) $this->recapSummary['net_cashflow'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/75 {{ $this->recapSummary['net_cashflow'] >= 0 ? 'text-sky-900 border border-sky-400' : 'text-amber-900 border border-amber-400' }}">
                    <x-ti-scale class="size-5" />
                </div>
            </div>
        </x-card>
    </div>

    {{-- Seksi Rekap per Kategori --}}
    <x-card>
        <x-slot:header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between w-full">
                <div>
                    <h2 class="text-sm sm:text-base font-semibold text-slate-900 flex items-center gap-1.5">
                        <x-ti-chart-pie class="size-4.5 text-emerald-600" />
                        <span>Rekap per Kategori</span>
                    </h2>
                    <p class="text-xs text-slate-500">Distribusi alokasi dana keluarga sesuai kategori transaksi</p>
                </div>

                {{-- Tab Pilihan: Pengeluaran atau Pemasukan --}}
                <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl self-start sm:self-auto">
                    <button
                        type="button"
                        wire:click="$set('categoryRecapTab', 'expense')"
                        class="rounded-lg px-3 py-1 text-xs font-semibold transition-colors cursor-pointer {{ $categoryRecapTab === 'expense' ? 'bg-white text-rose-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}"
                    >
                        Pengeluaran
                    </button>
                    <button
                        type="button"
                        wire:click="$set('categoryRecapTab', 'income')"
                        class="rounded-lg px-3 py-1 text-xs font-semibold transition-colors cursor-pointer {{ $categoryRecapTab === 'income' ? 'bg-white text-emerald-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}"
                    >
                        Pemasukan
                    </button>
                </div>
            </div>
        </x-slot:header>

        @if ($categoryRecapTab === 'expense')
            @if (empty($this->categoryExpenseRecap))
                <div class="py-6 text-center text-xs text-slate-400">
                    Belum ada data pengeluaran berkategori pada periode ini.
                </div>
            @else
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($this->categoryExpenseRecap as $cat)
                        <div class="rounded-xl border p-3.5 space-y-2.5 transition-colors {{ $cat['is_over_limit'] ? 'border-rose-400 bg-rose-50/70' : 'border-slate-200/80 bg-slate-50/60' }}">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg {{ $cat['is_over_limit'] ? 'bg-rose-200 text-rose-800 border border-rose-300' : 'bg-rose-100 text-rose-700 border border-rose-200' }}">
                                        <x-ti-shopping-bag class="size-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <span class="text-sm font-bold text-slate-800 truncate block">{{ $cat['name'] }}</span>
                                        <span class="text-xs text-slate-500">Batas: {{ $cat['formatted_limit'] }}</span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-xs font-bold {{ $cat['is_over_limit'] ? 'text-rose-800' : 'text-slate-700' }} tabular-nums block">
                                        {{ $cat['percentage'] }}%
                                    </span>
                                    @if ($cat['is_over_limit'])
                                        <span class="inline-flex items-center gap-0.5 text-xs font-bold text-rose-700 bg-rose-100/90 px-1.5 py-0.5 rounded-md border border-rose-300">
                                            <x-ti-alert-triangle class="size-3" />
                                            <span>Lebih!</span>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Progress Bar Persentase --}}
                            <div class="w-full bg-slate-200/80 rounded-full h-2 overflow-hidden">
                                <div
                                    class="h-2 rounded-full transition-all duration-300 {{ $cat['is_over_limit'] ? 'bg-rose-600' : 'bg-rose-500' }}"
                                    style="width: {{ min(100, max(4, $cat['percentage'])) }}%"
                                ></div>
                            </div>

                            <div class="flex items-center justify-between text-xs text-slate-500 pt-0.5">
                                <span>{{ $cat['total_tx'] }} transaksi</span>
                                <strong class="font-bold tabular-nums {{ $cat['is_over_limit'] ? 'text-rose-900' : 'text-slate-900' }}">{{ $cat['formatted_amount'] }}</strong>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            @if (empty($this->categoryIncomeRecap))
                <div class="py-6 text-center text-xs text-slate-400">
                    Belum ada data pemasukan berkategori pada periode ini.
                </div>
            @else
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($this->categoryIncomeRecap as $cat)
                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3.5 space-y-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 border border-emerald-200">
                                        <x-ti-wallet class="size-4" />
                                    </div>
                                    <span class="text-sm font-bold text-slate-800 truncate">{{ $cat['name'] }}</span>
                                </div>
                                <span class="text-xs font-bold text-emerald-700 tabular-nums shrink-0">
                                    {{ $cat['percentage'] }}%
                                </span>
                            </div>

                            {{-- Progress Bar Persentase --}}
                            <div class="w-full bg-slate-200/80 rounded-full h-2 overflow-hidden">
                                <div
                                    class="bg-emerald-500 h-2 rounded-full transition-all duration-300"
                                    style="width: {{ min(100, max(4, $cat['percentage'])) }}%"
                                ></div>
                            </div>

                            <div class="flex items-center justify-between text-xs text-slate-500 pt-0.5">
                                <span>{{ $cat['total_tx'] }} transaksi</span>
                                <strong class="text-slate-900 font-bold tabular-nums">{{ $cat['formatted_amount'] }}</strong>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </x-card>

    {{-- Seksi Rekap per Kata Kunci di Keterangan --}}
    <x-card>
        <x-slot:header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between w-full">
                <div>
                    <h2 class="text-sm sm:text-base font-semibold text-slate-900 flex items-center gap-1.5">
                        <x-ti-tags class="size-4.5 text-amber-600" />
                        <span>Rekap per Kata Kunci Keterangan</span>
                    </h2>
                    <p class="text-xs text-slate-500">
                        Pantau pos belanja khusus berdasarkan kata kunci di catatan/keterangan (tersimpan otomatis)
                    </p>
                </div>

                <x-button color="emerald" sm wire:click="openAddKeywordModal" class="rounded-xl self-start sm:self-auto">
                    <x-ti-plus class="size-4 mr-1" />
                    <span>Tambah Kata Kunci</span>
                </x-button>
            </div>
        </x-slot:header>

        @if (empty($this->keywordRecap))
            <div class="py-8 text-center space-y-2">
                <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 border border-amber-200">
                    <x-ti-tags class="size-6" />
                </div>
                <p class="text-sm font-semibold text-slate-800">Belum Ada Kata Kunci yang Disimpan</p>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    Tambahkan kata kunci belanja (contoh: <em>Beras</em>, <em>Skincare</em>, <em>Kopi</em>, <em>Bensin</em>, <em>Listrik</em>) beserta batas anggarannya untuk memantau pengeluaran pos tersebut secara otomatis.
                </p>
                <x-button color="emerald" sm wire:click="openAddKeywordModal" class="rounded-xl mt-2">
                    <x-ti-plus class="size-4 mr-1" />
                    Tambah Kata Kunci Pertama
                </x-button>
            </div>
        @else
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->keywordRecap as $kw)
                    <div class="rounded-xl border p-3.5 space-y-2.5 transition-colors {{ $kw['is_over_limit'] ? 'border-rose-400 bg-rose-50/80 shadow-2xs' : 'border-slate-200/80 bg-white hover:bg-slate-50/70' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-bold {{ $kw['is_over_limit'] ? 'bg-rose-200 text-rose-900 border border-rose-300' : 'bg-amber-100 text-amber-900 border border-amber-200' }}">
                                        <x-ti-hash class="size-3 text-amber-700" />
                                        <span>{{ $kw['keyword'] }}</span>
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">
                                    Batas: <strong class="text-slate-700">{{ $kw['formatted_limit'] }}</strong>
                                </p>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <x-button.circle
                                    color="slate"
                                    flat
                                    xs
                                    wire:click="openEditKeywordModal({{ $kw['id'] }})"
                                    title="Ubah batas atas kata kunci"
                                    aria-label="Ubah {{ $kw['keyword'] }}"
                                >
                                    <x-ti-edit class="size-3.5 text-slate-500 hover:text-slate-800" />
                                </x-button.circle>
                                <x-button.circle
                                    color="red"
                                    flat
                                    xs
                                    wire:click="deleteKeyword({{ $kw['id'] }})"
                                    title="Hapus kata kunci"
                                    aria-label="Hapus {{ $kw['keyword'] }}"
                                >
                                    <x-ti-trash class="size-3.5" />
                                </x-button.circle>
                            </div>
                        </div>

                        <div class="flex items-end justify-between pt-1 border-t border-slate-100">
                            <div>
                                <span class="text-xs text-slate-500 block">{{ $kw['total_tx'] }} mutasi</span>
                                <span class="text-base font-bold tabular-nums {{ $kw['is_over_limit'] ? 'text-rose-700' : 'text-slate-900' }}">
                                    {{ $kw['formatted_amount'] }}
                                </span>
                            </div>

                            @if ($kw['is_over_limit'])
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-rose-800 bg-rose-100 px-2 py-0.5 rounded-md border border-rose-300">
                                    <x-ti-alert-triangle class="size-3 text-rose-600" />
                                    <span>Lebih {{ number_format($kw['over_amount'], 0, ',', '.') }}</span>
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

    {{-- Filter Rekap (Filter Bulan, Tanpa Tanggal) --}}
    <x-card>
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3">
                <div class="flex flex-wrap items-center gap-1.5 w-full sm:w-auto">
                    <button
                        type="button"
                        wire:click="setQuickTypeFilter('')"
                        class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ $typeFilter === '' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                    >
                        Semua
                    </button>
                    <button
                        type="button"
                        wire:click="setQuickTypeFilter('income')"
                        class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ $typeFilter === 'income' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}"
                    >
                        Pemasukan
                    </button>
                    <button
                        type="button"
                        wire:click="setQuickTypeFilter('expense')"
                        class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ $typeFilter === 'expense' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-800 hover:bg-rose-100' }}"
                    >
                        Pengeluaran
                    </button>
                    <button
                        type="button"
                        wire:click="setQuickTypeFilter('transfer')"
                        class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ in_array($typeFilter, ['transfer', 'transfer_in', 'transfer_out'], true) ? 'bg-sky-600 text-white' : 'bg-sky-50 text-sky-800 hover:bg-sky-100' }}"
                    >
                        Pindah Uang
                    </button>
                </div>

                <x-button color="slate" flat sm wire:click="resetFilters" class="self-end sm:self-auto">
                    <x-ti-refresh class="size-4 mr-1" />
                    Reset Filter
                </x-button>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-input
                    label="Cari Keterangan"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Ketik keterangan transaksi..."
                />

                <x-select.styled
                    label="Filter Bulan"
                    placeholder="Semua Bulan"
                    wire:model.live="monthFilter"
                    :options="$this->monthOptions"
                />

                <x-select.styled
                    label="Filter Kantong"
                    placeholder="Semua Kantong"
                    wire:model.live="walletFilter"
                    :options="$this->allWalletFilterOptions"
                    searchable
                />
            </div>
        </div>
    </x-card>

    {{-- Daftar Mutasi Transaksi (Menggunakan LIST Kartu, Bukan Tabel) --}}
    <x-card paddingless>
        <x-slot:header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h2 class="text-sm sm:text-base font-semibold text-slate-900">Daftar Mutasi Transaksi</h2>
                    <p class="text-xs text-slate-500">Menampilkan {{ $this->transactions->total() }} mutasi transaksi tercatat</p>
                </div>
            </div>
        </x-slot:header>

        @if ($this->transactions->isEmpty())
            <div class="p-10 text-center space-y-2">
                <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                    <x-ti-receipt class="size-6" />
                </div>
                <p class="text-sm font-semibold text-slate-900">Belum Ada Transaksi</p>
                <p class="text-xs text-slate-500">Gunakan tombol Masuk, Keluar, atau Pindah di atas untuk mulai mencatat.</p>
            </div>
        @else
            {{-- List Transaksi Bersih untuk Semua Layar (Simple & Clear Bank-Style) --}}
            <div class="divide-y divide-slate-100">
                @foreach ($this->transactions as $tx)
                    <div class="p-3.5 sm:p-4 hover:bg-slate-50/70 transition-colors">
                        <div class="flex items-center gap-3">
                            {{-- Ikon Kategori / Aliran Dana --}}
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $tx->type->isPositive() ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                <x-transaction-icon :icon="$tx->category?->icon" :type="$tx->type" class="size-5" />
                            </div>

                            {{-- Info Keterangan & Metadata --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-sm font-semibold text-slate-900 truncate" title="{{ $tx->displayDescription() }}">
                                        {{ $tx->displayDescription() }}
                                    </h4>
                                    {{-- Nominal Transaksi --}}
                                    <span class="text-sm sm:text-base font-bold tabular-nums shrink-0 {{ $tx->type->amountClass() }}">
                                        {{ $tx->formattedSignedAmount() }}
                                    </span>
                                </div>

                                <div class="mt-0.5 flex items-center justify-between gap-2 text-xs text-slate-500">
                                    {{-- Kiri: Tanggal + Kantong + Kategori --}}
                                    <div class="flex items-center gap-1.5 truncate">
                                        <span>{{ $tx->transaction_date?->translatedFormat('d M Y, H:i') }}</span>
                                        <span>•</span>
                                        <div class="inline-flex items-center gap-1 truncate">
                                            @if ($tx->wallet)
                                                <span class="size-1.5 rounded-full {{ $tx->wallet->colorEnum()->swatchClasses() }} inline-block shrink-0"></span>
                                            @endif
                                            <span class="font-medium text-slate-700 truncate">{{ $tx->resolvedWalletName() }}</span>
                                        </div>
                                        <span>•</span>
                                        @if ($tx->category)
                                            <span class="truncate text-slate-600">{{ $tx->category->name }}</span>
                                        @elseif ($tx->counterpart_wallet_name_snapshot)
                                            <span class="truncate text-sky-800">
                                                {{ $tx->type === \App\Enums\TransactionTypeEnum::TRANSFER_IN ? 'Dari: ' : 'Ke: ' }}{{ $tx->resolvedCounterpartWalletName() }}
                                            </span>
                                        @else
                                            <span class="truncate">{{ $tx->type->label() }}</span>
                                        @endif
                                    </div>

                                    {{-- Kanan: Saldo Sisa Sesudah Transaksi --}}
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="hidden sm:inline text-slate-400">Sisa:</span>
                                        <span class="font-medium text-slate-600 tabular-nums">{{ $tx->formattedBalanceAfter() }}</span>
                                        <button
                                            type="button"
                                            wire:click="askDeleteTransaction({{ $tx->id }})"
                                            class="text-slate-400 hover:text-rose-600 p-0.5 rounded transition-colors cursor-pointer"
                                            title="Hapus Transaksi"
                                            aria-label="Hapus transaksi {{ $tx->displayDescription() }}"
                                        >
                                            <x-ti-trash class="size-3.5" />
                                        </button>
                                    </div>
                                </div>

                                @if ($tx->notes)
                                    <p class="mt-1 text-xs text-slate-500 italic bg-slate-50/80 px-2 py-0.5 rounded border border-slate-100 line-clamp-1">
                                        "{{ $tx->notes }}"
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="p-4 border-t border-slate-200">
                {{ $this->transactions->links() }}
            </div>
        @endif
    </x-card>

    {{-- Modal Tambah Pemasukan (Langsung atau Ambil dari Kantong Lain) --}}
    <x-modal wire="incomeModal" title="Tambah Pemasukan / Tambah Uang" size="lg">
        <form wire:submit="saveIncome" class="space-y-4">
            <x-radio.group
                wire:model.live="income_wallet_id"
                card
                :columns="2"
                color="emerald"
                :options="$this->activeWalletOptions"
            />

            <x-radio.group
                wire:model.live="income_source_type"
                inline
                class="[&>div]:flex-wrap sm:[&>div]:flex-nowrap [&>div]:gap-2"
                color="emerald"
                :options="\App\Enums\IncomeSourceEnum::choices()"
            />

            @if ($income_source_type === \App\Enums\IncomeSourceEnum::FROM_POCKET->value)
                <div wire:key="recap-income-from-pocket-wrap-{{ $income_wallet_id ?? '0' }}">
                    @if (empty($this->otherActiveWalletOptions))
                        <div class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800 border border-amber-200">
                            Belum ada kantong aktif lain yang dapat dipilih.
                        </div>
                    @else
                        <x-radio.group
                            id="recap-income-pocket-wallet"
                            wire:model.live="income_source_wallet_id"
                            card
                            :columns="2"
                            color="emerald"
                            :options="$this->otherActiveWalletOptions"
                        />
                    @endif
                </div>
            @else
                <div wire:key="recap-income-direct-category-wrap">
                    <x-radio.group
                        id="recap-income-category"
                        wire:model.live="income_category_id"
                        card
                        :columns="3"
                        color="emerald"
                        :options="$this->incomeCategoryOptions"
                        class="[&>div]:!grid-cols-3 [&>div]:gap-2 sm:[&>div]:gap-3 [&_label]:p-2.5 sm:[&_label]:p-3.5 [&_label]:rounded-xl"
                    />
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-currency
                    wire:model="income_amount"
                    locale="id-ID"
                    symbol=""
                    :decimals="0"
                    :precision="0"
                    placeholder="Nominal"
                />

                <x-input
                    type="datetime-local"
                    wire:model="income_date"
                />
            </div>

            <x-input
                wire:model="income_description"
                placeholder="{{ $income_source_type !== \App\Enums\IncomeSourceEnum::DIRECT->value ? 'Keterangan pindah uang (opsional)' : 'Keterangan pemasukan (contoh: Gaji bulanan, Bonus)' }}"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('incomeModal', false)">Batal</x-button>
                <x-button submit color="emerald" loading="saveIncome">Simpan Pemasukan</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Catat Pengeluaran --}}
    <x-modal wire="expenseModal" title="Catat Pengeluaran Baru" size="lg">
        <form wire:submit="saveExpense" class="space-y-4">
            <x-radio.group
                wire:model.live="expense_wallet_id"
                card
                :columns="2"
                color="rose"
                :options="$this->activeWalletOptions"
            />

            <x-radio.group
                id="recap-expense-category"
                wire:model.live="expense_category_id"
                card
                :columns="3"
                color="rose"
                :options="$this->expenseCategoryOptions"
                class="[&>div]:!grid-cols-3 [&>div]:gap-2 sm:[&>div]:gap-3 [&_label]:p-2.5 sm:[&_label]:p-3.5 [&_label]:rounded-xl"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-currency
                    wire:model="expense_amount"
                    locale="id-ID"
                    symbol=""
                    :decimals="0"
                    :precision="0"
                    placeholder="Nominal"
                />

                <x-input
                    type="datetime-local"
                    wire:model="expense_date"
                />
            </div>

            <x-input
                wire:model="expense_description"
                placeholder="Keterangan pengeluaran (contoh: Belanja sayur & lauk)"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('expenseModal', false)">Batal</x-button>
                <x-button submit color="rose" loading="saveExpense">Simpan Pengeluaran</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Pindah Uang Antar Kantong --}}
    <x-modal wire="transferModal" title="Pindah Uang Antar Kantong" size="lg">
        <form wire:submit="saveTransfer" class="space-y-4">
            <x-radio.group
                wire:model.live="transfer_source_wallet_id"
                card
                :columns="2"
                color="sky"
                :options="$this->activeWalletOptions"
            />

            <x-radio.group
                wire:model.live="transfer_destination_wallet_id"
                card
                :columns="2"
                color="emerald"
                :options="$this->activeWalletOptions"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-currency
                    wire:model="transfer_amount"
                    locale="id-ID"
                    symbol=""
                    :decimals="0"
                    :precision="0"
                    placeholder="Nominal"
                />

                <x-input
                    type="datetime-local"
                    wire:model="transfer_date"
                />
            </div>

            <x-input
                wire:model="transfer_description"
                placeholder="Keterangan pindah uang (contoh: Sisihkan dana untuk tabungan jajan)"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('transferModal', false)">Batal</x-button>
                <x-button submit color="emerald" loading="saveTransfer">Kirim Uang</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Tambah / Edit Kata Kunci Rekap --}}
    <x-modal wire="keywordModal" title="{{ $editingKeywordId ? 'Ubah Kata Kunci Rekap' : 'Tambah Kata Kunci Rekap Baru' }}">
        <form wire:submit="saveKeyword" class="space-y-4">
            <x-input
                label="Kata Kunci di Keterangan *"
                placeholder="Contoh: Beras, Skincare, Bensin, Kopi, Listrik"
                wire:model="keyword_text"
                hint="Sistem akan mencocokkan kata ini di keterangan atau catatan transaksi."
            />

            <x-currency
                label="Batas Anggaran / Limit (Opsional)"
                placeholder="0 (Tanpa batas)"
                wire:model="keyword_budget_limit"
                locale="id-ID"
                symbol=""
                :decimals="0"
                :precision="0"
                hint="Jika total pengeluaran melebihi batas ini, kartu akan ditandai warna merah."
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('keywordModal', false)">Batal</x-button>
                <x-button submit color="emerald" loading="saveKeyword">
                    <x-ti-check class="size-4 mr-1" />
                    Simpan Kata Kunci
                </x-button>
            </div>
        </form>
    </x-modal>
</div>
