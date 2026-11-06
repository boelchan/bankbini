<div class="space-y-6">
    {{-- Header & Tombol Transaksi --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-bold text-slate-900 sm:text-xl">Riwayat Transaksi</h1>
            <nav class="flex items-center gap-1.5 text-sm text-slate-500 mt-1">
                <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-emerald-600 transition-colors">
                    <x-ti-home class="size-4" />
                </a>
                <span>/</span>
                <span class="text-slate-700 font-medium">Transaksi</span>
            </nav>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-button color="emerald" wire:click="openIncomeModal" class="rounded-xl">
                <x-ti-plus class="size-4 mr-1" />
                Pemasukan
            </x-button>

            <x-button color="rose" light wire:click="openExpenseModal" class="rounded-xl">
                <x-ti-minus class="size-4 mr-1" />
                Pengeluaran
            </x-button>

            <x-button color="sky" light wire:click="openTransferModal" class="rounded-xl">
                <x-ti-arrows-exchange class="size-4 mr-1" />
                Pindah Uang
            </x-button>
        </div>
    </div>

    {{-- Filter Sederhana --}}
    <x-card>
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3">
                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        type="button"
                        wire:click="setQuickTypeFilter('')"
                        class="rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors {{ $typeFilter === '' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                    >
                        Semua
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
                        Pindah Uang
                    </button>
                </div>

                <x-button color="slate" flat sm wire:click="resetFilters">
                    <x-ti-refresh class="size-4 mr-1" />
                    Reset Filter
                </x-button>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-input
                    label="Cari Keterangan"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Ketik keterangan transaksi..."
                />

                <x-select.styled
                    label="Filter Kantong"
                    placeholder="Semua Kantong"
                    wire:model.live="walletFilter"
                    :options="$this->allWalletFilterOptions"
                    searchable
                />

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

    {{-- Tabel Riwayat Transaksi (Tanpa Kode TRX) --}}
    <x-card paddingless>
        <x-slot:header>
            <div>
                <h2 class="text-sm sm:text-base font-semibold text-slate-900">Daftar Transaksi</h2>
                <p class="text-xs text-slate-500">Catatan seluruh uang masuk, keluar, dan pindah antar kantong</p>
            </div>
        </x-slot:header>

        @if ($this->transactions->isEmpty())
            <div class="p-10 text-center space-y-2">
                <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                    <x-ti-receipt class="size-6" />
                </div>
                <p class="text-sm font-semibold text-slate-900">Belum Ada Transaksi</p>
                <p class="text-xs text-slate-500">Gunakan tombol Pemasukan, Pengeluaran, atau Pindah Uang di atas untuk mulai mencatat.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="py-3.5 pl-5 pr-3">Tanggal & Waktu</th>
                            <th class="px-3 py-3.5">Keterangan & Kategori</th>
                            <th class="px-3 py-3.5">Kantong</th>
                            <th class="px-3 py-3.5">Jenis</th>
                            <th class="px-3 py-3.5 text-right">Nominal</th>
                            <th class="px-3 py-3.5 text-right">Saldo Kantong</th>
                            <th class="py-3.5 pl-3 pr-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70 text-sm">
                        @foreach ($this->transactions as $tx)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 pl-5 pr-3 whitespace-nowrap text-xs text-slate-600">
                                    {{ $tx->transaction_date?->translatedFormat('d M Y, H:i') }}
                                </td>

                                <td class="px-3 py-3.5">
                                    <div class="font-semibold text-slate-900">{{ $tx->displayDescription() }}</div>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                        @if ($tx->notes)
                                            <span>{{ $tx->notes }}</span>
                                        @endif
                                        @if ($tx->category)
                                            @if ($tx->notes)
                                                <span>•</span>
                                            @endif
                                            <span>{{ $tx->category->name }}</span>
                                        @endif
                                        @if ($tx->user)
                                            @if ($tx->notes || $tx->category)
                                                <span>•</span>
                                            @endif
                                            <span>Oleh: {{ $tx->user->name }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-3 py-3.5">
                                    <span class="font-semibold text-slate-900 text-xs">
                                        {{ $tx->resolvedWalletName() }}
                                    </span>
                                    @if ($tx->counterpart_wallet_name_snapshot)
                                        <div class="text-xs text-slate-500 mt-0.5">
                                            {{ $tx->type === \App\Enums\TransactionTypeEnum::TRANSFER_IN ? 'Terima dari: ' : 'Pindah ke: ' }}
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
                                    <div class="text-xs font-semibold text-slate-900 tabular-nums">
                                        {{ $tx->formattedBalanceAfter() }}
                                    </div>
                                    <div class="text-xs text-slate-500 tabular-nums">
                                        Awal: {{ $tx->formattedBalanceBefore() }}
                                    </div>
                                </td>

                                <td class="py-3.5 pl-3 pr-5 text-right whitespace-nowrap">
                                    <x-button.circle
                                        color="red"
                                        flat
                                        sm
                                        wire:click="askDeleteTransaction({{ $tx->id }})"
                                        title="Hapus Transaksi"
                                        aria-label="Hapus transaksi {{ $tx->displayDescription() }}"
                                    >
                                        <x-ti-trash class="size-4" />
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
                color="emerald"
                :options="\App\Enums\IncomeSourceEnum::choices()"
            />

            @if ($income_source_type === \App\Enums\IncomeSourceEnum::FROM_POCKET->value)
                <div wire:key="tx-income-from-pocket-wrap-{{ $income_wallet_id ?? '0' }}">
                    @if (empty($this->otherActiveWalletOptions))
                        <div class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800 border border-amber-200">
                            Belum ada kantong aktif lain yang dapat dipilih.
                        </div>
                    @else
                        <x-radio.group
                            id="tx-income-pocket-wallet"
                            wire:model.live="income_source_wallet_id"
                            card
                            :columns="2"
                            color="emerald"
                            :options="$this->otherActiveWalletOptions"
                        />
                    @endif
                </div>
            @else
                <div wire:key="tx-income-direct-category-wrap">
                    <x-radio.group
                        id="tx-income-category"
                        wire:model.live="income_category_id"
                        card
                        :columns="3"
                        color="emerald"
                        :options="$this->incomeCategoryOptions"
                    />
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-currency
                    wire:model="income_amount"
                    locale="id-ID"
                    symbol="Rp"
                    :decimals="0"
                    :precision="0"
                    placeholder="Nominal (Rp)"
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
                <x-button submit color="emerald" loading="saveIncome">Simpan</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Catat Pengeluaran --}}
    <x-modal wire="expenseModal" title="Catat Pengeluaran" size="lg">
        <form wire:submit="saveExpense" class="space-y-4">
            <x-radio.group
                wire:model.live="expense_wallet_id"
                card
                :columns="2"
                color="rose"
                :options="$this->activeWalletOptions"
            />

            <x-radio.group
                wire:model.live="expense_category_id"
                card
                :columns="3"
                color="rose"
                :options="$this->expenseCategoryOptions"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-currency
                    wire:model="expense_amount"
                    locale="id-ID"
                    symbol="Rp"
                    :decimals="0"
                    :precision="0"
                    placeholder="Nominal (Rp)"
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
                color="slate"
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
                    symbol="Rp"
                    :decimals="0"
                    :precision="0"
                    placeholder="Nominal (Rp)"
                />

                <x-input
                    type="datetime-local"
                    wire:model="transfer_date"
                />
            </div>

            <x-input
                wire:model="transfer_description"
                placeholder="Keterangan pindah uang (opsional)"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('transferModal', false)">Batal</x-button>
                <x-button submit color="emerald" loading="saveTransfer">Pindahkan Sekarang</x-button>
            </div>
        </form>
    </x-modal>
</div>
