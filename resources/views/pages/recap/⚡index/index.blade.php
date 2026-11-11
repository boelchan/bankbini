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
            <div class="grid flex-1 grid-cols-2 gap-3">
                <x-select.styled
                    placeholder="Bulan"
                    wire:model.live="monthFilter"
                    :options="$this->monthOptions"
                />

                <x-select.styled
                    placeholder="Kantong"
                    wire:model.live="walletFilter"
                    :options="$this->allWalletFilterOptions"
                />
            </div>

            @if ($monthFilter !== now()->format('Y-m') || $walletFilter !== $defaultWalletId)
                <x-button color="red" flat sm wire:click="resetFilters" class="self-start sm:self-auto">
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
    @php
        $isExpenseTab = $categoryRecapTab === 'expense';
        $categoryRows = $isExpenseTab ? $this->categoryExpenseRecap : $this->categoryIncomeRecap;
    @endphp

    <x-table flush>
        <x-slot:header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-sm font-semibold text-slate-900 sm:text-base">Rekap per Kategori</h2>

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

        @if (empty($categoryRows))
            <x-slot:empty>
                <div class="px-4 py-8 text-center text-xs text-slate-400">
                    {{ $isExpenseTab ? 'Belum ada data pengeluaran berkategori pada periode ini.' : 'Belum ada data pemasukan berkategori pada periode ini.' }}
                </div>
            </x-slot:empty>
        @else
            <x-table.thead class="bg-slate-100">
                <x-table.th label="Kategori" width="32%" />
                <x-table.th label="Batas" width="32%" />
                <x-table.th label="Aktual" width="36%" />
            </x-table.thead>

            <tbody>
                @foreach ($categoryRows as $cat)
                    @php
                        $isOver = $cat['is_over_limit'] ?? false;
                        $usage = (float) ($cat['budget_percentage'] ?? $cat['percentage']);
                        $usageLabel = $this->formatPercentage($usage);
                        $tone = $this->budgetTone($usage, (float) ($cat['limit'] ?? 0));
                    @endphp
                    <tr class="transition-colors {{ $isOver ? 'bg-rose-50 hover:bg-rose-100/70' : 'bg-white hover:bg-slate-50' }}">
                        <td class="px-3 py-3 align-top">
                            <div class="flex items-center gap-1.5">
                                <span class="min-w-0 truncate text-sm font-medium {{ $isOver ? 'text-rose-900' : 'text-slate-800' }}">
                                    {{ $cat['name'] }}
                                </span>
                                @if (! empty($cat['category_id']))
                                    <x-button.circle
                                        color="slate"
                                        flat
                                        xs
                                        wire:click="openEditCategoryBudget({{ $cat['category_id'] }})"
                                        title="Ubah batas kategori"
                                        aria-label="Ubah batas kategori {{ $cat['name'] }}"
                                    >
                                        <x-ti-pencil class="size-3.5" />
                                    </x-button.circle>
                                @endif
                            </div>
                        </td>

                        <td class="px-3 py-3 align-top">
                            <span class="text-sm tabular-nums {{ $isOver ? 'font-semibold text-rose-700' : 'text-slate-700' }}">
                                {{ $cat['formatted_limit'] }}
                            </span>
                        </td>

                        <td class="px-3 py-3 align-top">
                            <p class="text-sm font-semibold tabular-nums {{ $tone['text'] }}">
                                {{ $cat['formatted_amount'] }}
                            </p>
                            <span class="mt-0.5 block text-xs tabular-nums {{ $tone['text'] }}">
                                {{ $usageLabel }}%
                            </span>
                            @if ($isOver)
                                <span class="mt-0.5 inline-flex items-center gap-1 text-xs font-semibold text-rose-700">
                                    <x-ti-alert-triangle class="size-3" />
                                    <span>Lewat {{ number_format($cat['over_amount'], 0, ',', '.') }}</span>
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        @endif
    </x-table>

    {{-- Seksi Rekap per Kata Kunci di Keterangan --}}
    <x-table flush>
        <x-slot:header>
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-slate-900 sm:text-base">Rekap per Kata Kunci Keterangan</h2>

                <x-button.circle
                    color="rose"
                    sm
                    wire:click="openAddKeywordModal"
                    title="Tambah Kata Kunci"
                    aria-label="Tambah Kata Kunci"
                >
                    <x-ti-plus class="size-4" />
                </x-button.circle>
            </div>
        </x-slot:header>

        @if (empty($this->keywordRecap))
            <x-slot:empty>
                <div class="px-4 py-8 text-center text-xs text-slate-400">
                    Belum ada kata kunci. Tambahkan kata kunci belanja (contoh: Beras, Skincare, Kopi) untuk memantau pengeluaran pos tersebut.
                </div>
            </x-slot:empty>
        @else
            <x-table.thead class="bg-slate-100">
                <x-table.th label="Kata Kunci" width="55%" />
                <x-table.th label="Batas" width="20%" />
                <x-table.th label="Aktual" width="25%" />
            </x-table.thead>

            <tbody>
                @foreach ($this->keywordRecap as $kw)
                    @php
                        $kwUsage = (float) ($kw['percentage'] ?? 0);
                        $kwUsageLabel = $this->formatPercentage($kwUsage);
                        $kwTone = $this->budgetTone($kwUsage, (float) ($kw['limit'] ?? 0));
                    @endphp
                    <tr
                        class="transition-colors {{ $kw['is_over_limit'] ? 'bg-rose-50 hover:bg-rose-100/70' : 'bg-white hover:bg-slate-50' }}"
                        wire:key="keyword-{{ $kw['id'] }}"
                    >
                        <td class="px-3 py-3 align-top">
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm font-semibold {{ $kw['is_over_limit'] ? 'text-rose-900' : 'text-slate-900' }}">
                                    {{ $kw['keyword'] }}
                                </span>
                                <x-button.circle
                                    color="slate"
                                    flat
                                    xs
                                    wire:click="openEditKeywordModal({{ $kw['id'] }})"
                                    title="Ubah kata kunci"
                                    aria-label="Ubah {{ $kw['keyword'] }}"
                                >
                                    <x-ti-pencil class="size-3.5" />
                                </x-button.circle>
                            </div>
                        </td>

                        <td class="px-3 py-3 align-top">
                            <span class="text-sm tabular-nums {{ $kw['is_over_limit'] ? 'font-semibold text-rose-700' : 'text-slate-700' }}">
                                {{ $kw['formatted_limit'] }}
                            </span>
                        </td>

                        <td class="px-3 py-3 align-top">
                            <p class="text-sm font-semibold tabular-nums {{ $kwTone['text'] }}">
                                {{ $kw['formatted_amount'] }}
                            </p>
                            <span class="mt-0.5 block text-xs tabular-nums {{ $kwTone['text'] }}">
                                {{ $kwUsageLabel }}%
                            </span>
                            @if ($kw['is_over_limit'])
                                <span class="mt-0.5 inline-flex items-center gap-1 text-xs font-semibold text-rose-700">
                                    <x-ti-alert-triangle class="size-3" />
                                    <span>Lebih {{ number_format($kw['over_amount'], 0, ',', '.') }}</span>
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        @endif
    </x-table>

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
                hint="Jika total pengeluaran melebihi batas ini, baris tabel akan ditandai warna merah."
            />

            <div class="space-y-2 pt-3 border-t border-slate-200">
                <div class="flex items-center justify-end gap-2">
                    <x-button color="slate" flat wire:click="$set('keywordModal', false)">Batal</x-button>
                    <x-button submit color="rose" loading="saveKeyword">
                        <x-ti-check class="size-4 mr-1" />
                        Simpan Kata Kunci
                    </x-button>
                </div>

                @if ($editingKeywordId)
                    <x-button
                        color="red"
                        flat
                        class="w-full"
                        wire:click="askDeleteKeyword({{ $editingKeywordId }})"
                    >
                        <x-ti-trash class="size-4 mr-1" />
                        Hapus Kata Kunci
                    </x-button>
                @endif
            </div>
        </form>
    </x-modal>

    {{-- Modal Batas Anggaran Kategori --}}
    <x-modal wire="categoryBudgetModal" title="Ubah Batas Kategori">
        <form wire:submit="saveCategoryBudget" class="space-y-4">
            <p class="text-sm font-semibold text-slate-900">{{ $editingCategoryName }}</p>

            <x-currency
                label="Batas Anggaran / Limit (Opsional)"
                placeholder="0 (Tanpa batas)"
                wire:model="category_budget_limit"
                locale="id-ID"
                symbol=""
                :decimals="0"
                :precision="0"
                hint="Kosongkan bila kategori ini tidak memiliki batas anggaran."
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('categoryBudgetModal', false)">Batal</x-button>
                <x-button submit color="rose" loading="saveCategoryBudget">
                    <x-ti-check class="size-4 mr-1" />
                    Simpan Batas
                </x-button>
            </div>
        </form>
    </x-modal>
</div>
