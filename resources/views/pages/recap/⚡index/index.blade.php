<div class="space-y-6">
    {{-- Header Rekap --}}
    <div>
        <h1 class="text-lg font-bold text-slate-900 sm:text-xl">Rekap Transaksi</h1>
        <p class="text-xs text-slate-500 mt-0.5">
            Rekapitulasi uang masuk, uang keluar, dan pembagian per kategori serta kata kunci.
        </p>
    </div>

    {{-- Filter Rekap --}}
    <x-card>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
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

            @if ($monthFilter !== '' || $walletFilter !== null)
                <x-button color="slate" flat sm wire:click="resetFilters" class="self-start sm:self-auto">
                    <x-ti-refresh class="size-4 mr-1" />
                    Reset
                </x-button>
            @endif
        </div>
    </x-card>

    {{-- Ringkasan Metrik Rekap (3 Bagian) --}}
    <x-card paddingless>
        <div class="grid grid-cols-1 divide-y divide-slate-200/80 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="flex items-center gap-2.5 px-4 py-3">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <x-ti-arrow-down-left class="size-4" />
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-500">Total Pemasukan</p>
                    <p class="text-sm font-bold text-emerald-700 tabular-nums truncate">
                        +{{ number_format((float) $this->recapSummary['total_income'], 0, ',', '.') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 px-4 py-3">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-700">
                    <x-ti-arrow-up-right class="size-4" />
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-500">Total Pengeluaran</p>
                    <p class="text-sm font-bold text-rose-600 tabular-nums truncate">
                        -{{ number_format((float) $this->recapSummary['total_expense'], 0, ',', '.') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 px-4 py-3">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg {{ $this->recapSummary['net_cashflow'] >= 0 ? 'bg-sky-50 text-sky-700' : 'bg-amber-50 text-amber-700' }}">
                    <x-ti-scale class="size-4" />
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-500">Arus Kas Bersih</p>
                    <p class="text-sm font-bold tabular-nums truncate {{ $this->recapSummary['net_cashflow'] >= 0 ? 'text-sky-700' : 'text-amber-700' }}">
                        {{ $this->recapSummary['net_cashflow'] >= 0 ? '+' : '' }}{{ number_format((float) $this->recapSummary['net_cashflow'], 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>
    </x-card>

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
