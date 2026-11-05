<div class="space-y-6">
    {{-- Header & Breadcrumb --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-bold text-slate-900 sm:text-xl">Riwayat Transaksi & Pencatatan</h1>
            <nav class="flex items-center gap-1.5 text-sm text-slate-500 mt-1">
                <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-emerald-600 transition-colors">
                    <x-ti-home class="size-4" />
                </a>
                <span>/</span>
                <span class="text-slate-700 font-medium">Riwayat Transaksi</span>
            </nav>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-button color="emerald" wire:click="openIncomeModal" class="rounded-xl">
                <x-ti-plus class="size-4 mr-1" />
                Tambah Pemasukan
            </x-button>

            <x-button color="rose" light wire:click="openExpenseModal" class="rounded-xl">
                <x-ti-minus class="size-4 mr-1" />
                Catat Pengeluaran
            </x-button>

            <x-button color="sky" light href="{{ route('transfers.index') }}" wire:navigate class="rounded-xl">
                <x-ti-arrows-exchange class="size-4 mr-1" />
                Transfer Kantong
            </x-button>
        </div>
    </div>

    {{-- Ringkasan Berdasarkan Filter Aktif --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-card class="bg-emerald-300 border-emerald-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-950">Total Pemasukan (Filter)</p>
                    <p class="mt-1.5 text-2xl font-bold text-emerald-950 tabular-nums">
                        +Rp{{ number_format((float) $this->filteredSummary['income'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex size-10 items-center justify-center rounded-xl bg-white/75 text-emerald-900 border border-emerald-400">
                    <x-ti-arrow-down-left class="size-5" />
                </div>
            </div>
        </x-card>

        <x-card class="bg-rose-300 border-rose-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-rose-950">Total Pengeluaran (Filter)</p>
                    <p class="mt-1.5 text-2xl font-bold text-rose-950 tabular-nums">
                        -Rp{{ number_format((float) $this->filteredSummary['expense'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex size-10 items-center justify-center rounded-xl bg-white/75 text-rose-900 border border-rose-400">
                    <x-ti-arrow-up-right class="size-5" />
                </div>
            </div>
        </x-card>

        <x-card class="bg-sky-300 border-sky-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-sky-950">Volume Transfer Antar Kantong</p>
                    <p class="mt-1.5 text-2xl font-bold text-sky-950 tabular-nums">
                        Rp{{ number_format((float) $this->filteredSummary['transfer_volume'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex size-10 items-center justify-center rounded-xl bg-white/75 text-sky-900 border border-sky-400">
                    <x-ti-arrows-exchange class="size-5" />
                </div>
            </div>
        </x-card>
    </div>

    {{-- Panel Filter & Pencarian Lengkap --}}
    <x-card>
        <div class="space-y-4">
            {{-- Tab Filter Cepat Jenis Transaksi --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        type="button"
                        wire:click="setQuickTypeFilter('')"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ $typeFilter === '' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                    >
                        Semua Transaksi
                    </button>
                    <button
                        type="button"
                        wire:click="setQuickTypeFilter('income')"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ $typeFilter === 'income' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}"
                    >
                        Pemasukan
                    </button>
                    <button
                        type="button"
                        wire:click="setQuickTypeFilter('expense')"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ $typeFilter === 'expense' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-800 hover:bg-rose-100' }}"
                    >
                        Pengeluaran
                    </button>
                    <button
                        type="button"
                        wire:click="setQuickTypeFilter('transfer')"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ in_array($typeFilter, ['transfer', 'transfer_in', 'transfer_out'], true) ? 'bg-sky-600 text-white' : 'bg-sky-50 text-sky-800 hover:bg-sky-100' }}"
                    >
                        Transfer Antar Kantong
                    </button>
                </div>

                <x-button color="slate" flat sm wire:click="resetFilters">
                    <x-ti-refresh class="size-4 mr-1" />
                    Reset Semua Filter
                </x-button>
            </div>

            {{-- Baris Input Filter --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
                <div class="lg:col-span-2">
                    <x-input
                        label="Cari Keterangan / Kode"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Ketik keterangan, kode TRX, atau nama kantong..."
                    />
                </div>

                <div>
                    <x-select.styled
                        label="Filter Kantong"
                        placeholder="Semua Kantong"
                        wire:model.live="walletFilter"
                        :options="$this->allWalletFilterOptions"
                        searchable
                    />
                </div>

                <div>
                    <x-select.styled
                        label="Filter Kategori"
                        placeholder="Semua Kategori"
                        wire:model.live="categoryFilter"
                        :options="$this->allCategoryFilterOptions"
                        searchable
                    />
                </div>

                <div>
                    <x-input
                        type="date"
                        label="Dari Tanggal"
                        wire:model.live="dateFrom"
                    />
                </div>

                <div>
                    <x-input
                        type="date"
                        label="Sampai Tanggal"
                        wire:model.live="dateTo"
                    />
                </div>
            </div>
        </div>
    </x-card>

    {{-- Tabel Riwayat Transaksi Global --}}
    <x-card paddingless>
        <x-slot:header>
            <div class="flex items-center justify-between w-full">
                <div>
                    <h2 class="text-sm sm:text-base font-semibold text-slate-900">Daftar Mutasi & Histori Transaksi</h2>
                    <p class="text-xs text-slate-500">
                        Data transaksi bersifat permanen (immutable) dan tetap menampilkan histori kantong yang telah ditutup.
                    </p>
                </div>
            </div>
        </x-slot:header>

        @if ($this->transactions->isEmpty())
            <div class="p-10 text-center space-y-3">
                <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                    <x-ti-receipt class="size-6" />
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-slate-900">Tidak Ada Transaksi Ditemukan</p>
                    <p class="text-xs text-slate-500 max-w-md mx-auto">
                        Sesuaikan filter tanggal, kantong, atau kata kunci pencarian Anda untuk melihat riwayat transaksi lainnya.
                    </p>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="py-3.5 pl-5 pr-3">ID & Waktu</th>
                            <th class="px-3 py-3.5">Keterangan & Kategori</th>
                            <th class="px-3 py-3.5">Kantong</th>
                            <th class="px-3 py-3.5">Jenis</th>
                            <th class="px-3 py-3.5 text-right">Nominal</th>
                            <th class="px-3 py-3.5 text-right">Saldo Sebelum → Sesudah</th>
                            <th class="py-3.5 pl-3 pr-5 text-right">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70 text-sm">
                        @foreach ($this->transactions as $tx)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 pl-5 pr-3 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 tabular-nums text-xs">{{ $tx->transaction_code }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        {{ $tx->transaction_date?->translatedFormat('d M Y, H:i') }}
                                    </div>
                                </td>

                                <td class="px-3 py-3.5">
                                    <div class="font-semibold text-slate-900">{{ $tx->description }}</div>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                                        @if ($tx->category)
                                            <span class="rounded-md bg-slate-100 px-1.5 py-0.5 font-medium text-slate-700">
                                                {{ $tx->category->name }}
                                            </span>
                                        @endif
                                        @if ($tx->transfer_reference)
                                            <span class="text-sky-700 font-medium tabular-nums">
                                                Ref: {{ $tx->transfer_reference }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-3 py-3.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-semibold text-slate-900 text-xs">
                                            {{ $tx->resolvedWalletName() }}
                                        </span>
                                        @if ($tx->wallet && ($tx->wallet->trashed() || $tx->wallet->status === \App\Enums\WalletStatusEnum::DELETED))
                                            <span class="rounded bg-slate-200 px-1.5 py-0.5 text-xs font-medium text-slate-700">
                                                Ditutup
                                            </span>
                                        @endif
                                    </div>
                                    @if ($tx->counterpart_wallet_name_snapshot)
                                        <div class="text-xs text-slate-500 mt-0.5">
                                            {{ $tx->type === \App\Enums\TransactionTypeEnum::TRANSFER_IN ? 'Dari: ' : 'Ke: ' }}
                                            {{ $tx->resolvedCounterpartWalletName() }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-3 py-3.5 whitespace-nowrap">
                                    <x-badge :text="$tx->type->label()" :color="$tx->type->color()" sm light />
                                </td>

                                <td class="px-3 py-3.5 text-right font-bold tabular-nums whitespace-nowrap {{ $tx->type->isPositive() ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ $tx->formattedSignedAmount() }}
                                </td>

                                <td class="px-3 py-3.5 text-right whitespace-nowrap">
                                    <div class="text-xs text-slate-500 tabular-nums">
                                        {{ $tx->formattedBalanceBefore() }}
                                    </div>
                                    <div class="text-xs font-semibold text-slate-900 tabular-nums">
                                        → {{ $tx->formattedBalanceAfter() }}
                                    </div>
                                </td>

                                <td class="py-3.5 pl-3 pr-5 text-right">
                                    <x-button.circle
                                        color="slate"
                                        flat
                                        sm
                                        wire:click="showTransactionDetail({{ $tx->id }})"
                                        title="Lihat Detail Transaksi"
                                    >
                                        <x-ti-eye class="size-4" />
                                    </x-button.circle>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-200">
                {{ $this->transactions->links() }}
            </div>
        @endif
    </x-card>

    {{-- Modal Tambah Pemasukan --}}
    <x-modal wire="incomeModal" title="Tambah Transaksi Pemasukan" size="lg">
        <form wire:submit="saveIncome" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-select.styled
                    label="Kantong Tujuan *"
                    placeholder="Pilih kantong aktif"
                    wire:model="income_wallet_id"
                    :options="$this->activeWalletOptions"
                    searchable
                />

                <x-select.styled
                    label="Kategori Pemasukan *"
                    placeholder="Pilih kategori"
                    wire:model="income_category_id"
                    :options="$this->incomeCategoryOptions"
                    searchable
                />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-currency
                    label="Nominal Pemasukan (Rp) *"
                    wire:model="income_amount"
                    locale="id-ID"
                    symbol="Rp"
                    :decimals="0" :precision="0"
                    placeholder="0"
                />

                <x-input
                    type="datetime-local"
                    label="Tanggal & Waktu Transaksi *"
                    wire:model="income_date"
                />
            </div>

            <x-input
                label="Keterangan Pemasukan *"
                wire:model="income_description"
                placeholder="Contoh: Gaji bulanan, Bonus proyek, Hasil investasi"
            />

            <x-textarea
                label="Catatan Tambahan (Opsional)"
                wire:model="income_notes"
                rows="2"
                placeholder="Catatan tambahan bila diperlukan"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('incomeModal', false)">Batal</x-button>
                <x-button submit color="emerald" loading="saveIncome">
                    <x-ti-check class="size-4 mr-1" />
                    Simpan Pemasukan
                </x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Catat Pengeluaran --}}
    <x-modal wire="expenseModal" title="Catat Transaksi Pengeluaran" size="lg">
        <form wire:submit="saveExpense" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-select.styled
                    label="Kantong Sumber Dana *"
                    placeholder="Pilih kantong aktif"
                    wire:model="expense_wallet_id"
                    :options="$this->activeWalletOptions"
                    searchable
                />

                <x-select.styled
                    label="Kategori Pengeluaran *"
                    placeholder="Pilih kategori"
                    wire:model="expense_category_id"
                    :options="$this->expenseCategoryOptions"
                    searchable
                />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-currency
                    label="Nominal Pengeluaran (Rp) *"
                    wire:model="expense_amount"
                    locale="id-ID"
                    symbol="Rp"
                    :decimals="0" :precision="0"
                    placeholder="0"
                />

                <x-input
                    type="datetime-local"
                    label="Tanggal & Waktu Transaksi *"
                    wire:model="expense_date"
                />
            </div>

            <x-input
                label="Keterangan Pengeluaran *"
                wire:model="expense_description"
                placeholder="Contoh: Belanja bulanan pasar, Bayar listrik & air"
            />

            <x-textarea
                label="Catatan Tambahan (Opsional)"
                wire:model="expense_notes"
                rows="2"
                placeholder="Rincian barang atau catatan kuitansi"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('expenseModal', false)">Batal</x-button>
                <x-button submit color="rose" loading="saveExpense">
                    <x-ti-check class="size-4 mr-1" />
                    Simpan Pengeluaran
                </x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Detail Transaksi --}}
    <x-modal wire="detailModal" title="Detail Lengkap Transaksi" size="md">
        @if ($this->selectedTransaction)
            @php
                $tx = $this->selectedTransaction;
            @endphp
            <div class="space-y-4 text-sm">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div>
                        <p class="text-xs font-medium text-slate-500">ID / Kode Transaksi</p>
                        <p class="font-bold text-slate-900 tabular-nums">{{ $tx->transaction_code }}</p>
                    </div>
                    <x-badge :text="$tx->type->label()" :color="$tx->type->color()" light />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs font-medium text-slate-500">Tanggal & Waktu</p>
                        <p class="font-semibold text-slate-800">{{ $tx->transaction_date?->translatedFormat('d F Y, H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500">Kantong</p>
                        <p class="font-semibold text-slate-800">{{ $tx->resolvedWalletName() }}</p>
                    </div>
                    @if ($tx->category)
                        <div>
                            <p class="text-xs font-medium text-slate-500">Kategori</p>
                            <p class="font-semibold text-slate-800">{{ $tx->category->name }}</p>
                        </div>
                    @endif
                    @if ($tx->transfer_reference)
                        <div>
                            <p class="text-xs font-medium text-slate-500">Referensi Transfer</p>
                            <p class="font-semibold text-slate-800 tabular-nums">{{ $tx->transfer_reference }}</p>
                        </div>
                    @endif
                    @if ($tx->counterpart_wallet_name_snapshot)
                        <div class="col-span-2">
                            <p class="text-xs font-medium text-slate-500">Kantong Lawan Transfer</p>
                            <p class="font-semibold text-slate-800">{{ $tx->resolvedCounterpartWalletName() }}</p>
                        </div>
                    @endif
                </div>

                <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Nominal Transaksi</span>
                        <span class="text-base font-bold tabular-nums {{ $tx->type->isPositive() ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $tx->formattedSignedAmount() }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Saldo Sebelum Transaksi</span>
                        <span class="font-semibold text-slate-700 tabular-nums">{{ $tx->formattedBalanceBefore() }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Saldo Sesudah Transaksi</span>
                        <span class="font-semibold text-slate-900 tabular-nums">{{ $tx->formattedBalanceAfter() }}</span>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-medium text-slate-500">Keterangan</p>
                    <p class="font-medium text-slate-800 mt-0.5">{{ $tx->description }}</p>
                </div>

                @if ($tx->notes)
                    <div>
                        <p class="text-xs font-medium text-slate-500">Catatan Tambahan</p>
                        <p class="text-slate-700 mt-0.5">{{ $tx->notes }}</p>
                    </div>
                @endif

                <div class="flex justify-end pt-2 border-t border-slate-200">
                    <x-button color="slate" flat wire:click="$set('detailModal', false)">Tutup</x-button>
                </div>
            </div>
        @endif
    </x-modal>
</div>

