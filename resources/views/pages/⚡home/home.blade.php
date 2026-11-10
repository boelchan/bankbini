@php
    $colorEnum = $wallet->colorEnum();
@endphp

<div class="space-y-6 pb-14 sm:pb-0">
    {{-- Header Sapaan & Kantong Utama --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-bold text-slate-900 sm:text-xl">
                Halo, {{ auth()->user()->name }}!
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Ini adalah <strong class="text-rose-800 font-semibold">Kantong Utama</strong> operasional keluarga Anda.
            </p>
        </div>
    </div>

    {{-- Kartu Utama Kantong Utama & Tombol Aksi --}}
    <x-card class="{{ $colorEnum->cardClasses() }}">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-2 min-w-0 flex-1">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <h2 class="text-base sm:text-lg font-bold {{ $colorEnum->accentTextClasses() }}">{{ $wallet->name }}</h2>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs">
                        <x-ti-star-filled class="size-3 text-amber-500" />
                        <span>Kantong Utama</span>
                    </span>
                    @if (!$wallet->isActive())
                        <x-badge :text="$wallet->status->label()" :color="$wallet->status->color()" sm light />
                    @endif
                </div>
                @if ($wallet->description)
                    <p class="text-xs sm:text-sm text-slate-700">
                        {{ $wallet->description }}
                    </p>
                @endif
                <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 tabular-nums tracking-tight">
                    {{ $wallet->formattedBalance() }}
                </p>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600">
                    <span class="inline-flex items-center gap-1 font-semibold text-emerald-700 tabular-nums">
                        <x-ti-arrow-down-left class="size-3.5 shrink-0" aria-hidden="true" />
                        +{{ number_format((float) $this->walletStats['total_in'], 0, ',', '.') }}
                    </span>
                    <span class="inline-flex items-center gap-1 font-semibold text-rose-700 tabular-nums">
                        <x-ti-arrow-up-right class="size-3.5 shrink-0" aria-hidden="true" />
                        -{{ number_format((float) $this->walletStats['total_out'], 0, ',', '.') }}
                    </span>
                </div>
            </div>

            @if ($wallet->isActive())
                <div class="fixed inset-x-3 bottom-20 z-40 grid grid-cols-3 gap-1.5 rounded-2xl border border-slate-200/80 bg-white/95 p-1.5 shadow-md shadow-slate-900/5 backdrop-blur-md sm:static sm:inset-auto sm:z-auto sm:flex sm:flex-wrap sm:items-center sm:gap-2 sm:rounded-none sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none sm:backdrop-blur-none">
                    <x-button color="emerald" sm wire:click="openIncomeModal('direct')" class="rounded-xl w-full sm:w-auto justify-center px-2 sm:px-3 text-xs">
                        <x-ti-plus class="size-4 mr-1 shrink-0" />
                        <span>Masuk</span>
                    </x-button>

                    <x-button color="rose" light sm wire:click="openExpenseModal" class="rounded-xl w-full sm:w-auto justify-center px-2 sm:px-3 text-xs">
                        <x-ti-minus class="size-4 mr-1 shrink-0" />
                        <span>Keluar</span>
                    </x-button>

                    <x-button color="sky" light sm wire:click="openTransferModal" class="rounded-xl w-full sm:w-auto justify-center px-2 sm:px-3 text-xs">
                        <x-ti-arrows-exchange class="size-4 mr-1 shrink-0" />
                        <span>Pindah</span>
                    </x-button>
                </div>
            @endif
        </div>
    </x-card>

    {{-- Filter Riwayat Transaksi --}}
    <x-card>
        <div class="space-y-3">
            {{-- Filter Cepat Aliran Dana --}}
            <div class="flex items-center gap-1 self-start rounded-xl bg-slate-100 p-1 sm:self-auto">
                <button
                    type="button"
                    wire:click="setFlowFilter('')"
                    class="rounded-lg px-3 py-1 text-xs font-semibold transition-colors cursor-pointer {{ $flowFilter === '' ? 'bg-white text-rose-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}"
                >
                    Semua
                </button>
                <button
                    type="button"
                    wire:click="setFlowFilter('income')"
                    class="rounded-lg px-3 py-1 text-xs font-semibold transition-colors cursor-pointer {{ $flowFilter === 'income' ? 'bg-white text-rose-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}"
                >
                    Masuk
                </button>
                <button
                    type="button"
                    wire:click="setFlowFilter('expense')"
                    class="rounded-lg px-3 py-1 text-xs font-semibold transition-colors cursor-pointer {{ $flowFilter === 'expense' ? 'bg-white text-rose-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}"
                >
                    Keluar
                </button>
                <button
                    type="button"
                    wire:click="setFlowFilter('transfer')"
                    class="rounded-lg px-3 py-1 text-xs font-semibold transition-colors cursor-pointer {{ $flowFilter === 'transfer' ? 'bg-white text-rose-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}"
                >
                    Pindah
                </button>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="grid flex-1 grid-cols-2 gap-3">
                    <x-input
                        wire:model.live.debounce.300ms="search"
                        placeholder="keterangan"
                    />

                    <x-select.styled
                        placeholder="Semua Bulan"
                        wire:model.live="monthFilter"
                        :options="$this->monthOptions"
                    />

                    <x-select.styled
                        placeholder="Semua Jenis"
                        wire:model.live="typeFilter"
                        :options="\App\Enums\TransactionTypeEnum::choices()"
                    />

                    <x-select.styled
                        placeholder="Semua Kategori"
                        wire:model.live="categoryFilter"
                        :options="$this->categoryFilterOptions"
                        searchable
                    />
                </div>

                @if ($search !== '' || $flowFilter !== '' || $typeFilter !== '' || $monthFilter !== '' || $categoryFilter !== null)
                    <x-button color="slate" flat sm wire:click="resetFilters" class="self-start sm:self-auto">
                        <x-ti-refresh class="size-4 mr-1" />
                        Reset
                    </x-button>
                @endif
            </div>
        </div>
    </x-card>

    {{-- Histori Masuk & Keluar Kantong Utama --}}
    <x-card paddingless>
        @if ($this->transactions->isEmpty())
            <div class="p-8 text-center space-y-2">
                <p class="text-sm font-semibold text-slate-800">Belum Ada Transaksi di Kantong Utama</p>
                <p class="text-xs text-slate-500">Gunakan tombol Masuk, Keluar, atau Pindah di atas untuk mulai mencatat.</p>
            </div>
        @else
            {{-- List Transaksi Kantong Utama (Simple & Clear Bank-Style) --}}
            <div class="divide-y divide-slate-100">
                @foreach ($this->transactions->getCollection()->groupBy(fn ($tx) => $tx->transaction_date?->format('Y-m-d')) as $date => $rows)
                    {{-- Header Grup Tanggal --}}
                    <div class="bg-slate-50/70 px-3.5 py-1.5 sm:px-4">
                        <span class="text-xs font-semibold text-slate-500">
                            {{ $rows->first()->transaction_date?->translatedFormat('d M Y') ?? 'Tanpa Tanggal' }}
                        </span>
                    </div>

                    @foreach ($rows as $tx)
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
                                        {{-- Kiri: Kategori / Lawan Transfer --}}
                                        <div class="flex items-center gap-1.5 truncate">
                                            @if ($tx->category)
                                                <span class="truncate font-medium text-slate-700">{{ $tx->category->name }}</span>
                                            @elseif ($tx->counterpart_wallet_name_snapshot)
                                                <span class="truncate font-medium text-sky-800">
                                                    {{ $tx->type === \App\Enums\TransactionTypeEnum::TRANSFER_IN ? 'Dari: ' : 'Ke: ' }}{{ $tx->resolvedCounterpartWalletName() }}
                                                </span>
                                            @else
                                                <span class="truncate">{{ $tx->type->label() }}</span>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-1 shrink-0">
                                            @if (! in_array($tx->type, [\App\Enums\TransactionTypeEnum::TRANSFER_IN, \App\Enums\TransactionTypeEnum::TRANSFER_OUT], true) && empty($tx->transfer_reference))
                                                <button
                                                    type="button"
                                                    wire:click="openEditTransaction({{ $tx->id }})"
                                                    class="text-slate-400 hover:text-slate-700 p-0.5 rounded transition-colors cursor-pointer"
                                                    title="Ubah Transaksi"
                                                    aria-label="Ubah transaksi {{ $tx->displayDescription() }}"
                                                >
                                                    <x-ti-edit class="size-3.5" />
                                                </button>
                                            @endif
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
                @endforeach
            </div>

            <div class="p-4 border-t border-slate-200">
                {{ $this->transactions->links() }}
            </div>
        @endif
    </x-card>

    {{-- Modal Tambah Uang (Dari Luar / Langsung ATAU Ambil dari Kantong Lain) --}}
    <x-modal wire="incomeModal" title="Tambah Uang ke {{ $wallet->name }}" size="lg">
        <form wire:submit="saveIncome" class="space-y-4">
            <x-radio.group
                wire:model.live="income_source_type"
                inline
                class="[&>div]:flex-wrap sm:[&>div]:flex-nowrap [&>div]:gap-2"
                color="emerald"
                :options="\App\Enums\IncomeSourceEnum::choices()"
            />

            @if ($income_source_type === \App\Enums\IncomeSourceEnum::FROM_POCKET->value)
                <div wire:key="home-income-from-pocket-wrap-{{ $wallet->id }}">
                    @if (empty($this->otherActiveWalletOptions))
                        <div class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800 border border-amber-200">
                            Belum ada kantong aktif lain yang dapat dipilih.
                        </div>
                    @else
                        <x-radio.group
                            id="home-income-pocket-wallet"
                            wire:model.live="income_source_wallet_id"
                            card
                            :columns="2"
                            color="emerald"
                            :options="$this->otherActiveWalletOptions"
                            class="[&_input]:sr-only"
                        />
                    @endif
                </div>
            @else
                <div wire:key="home-income-direct-category-wrap-{{ $wallet->id }}">
                    <x-radio.group
                        id="home-income-category"
                        wire:model.live="income_category_id"
                        card
                        :columns="3"
                        color="emerald"
                        :options="$this->incomeCategoryOptions"
                        class="[&>div]:!grid-cols-3 [&>div]:gap-2 sm:[&>div]:gap-3 [&_label]:p-2.5 sm:[&_label]:p-3.5 [&_label]:rounded-xl [&_input]:sr-only"
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
                <x-button submit color="rose" loading="saveIncome">Simpan</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Catat Pengeluaran --}}
    <x-modal wire="expenseModal" title="Catat Pengeluaran dari {{ $wallet->name }}" size="lg">
        <form wire:submit="saveExpense" class="space-y-4">
            <x-radio.group
                id="home-expense-category"
                wire:model.live="expense_category_id"
                card
                :columns="3"
                color="rose"
                :options="$this->expenseCategoryOptions"
                class="[&>div]:!grid-cols-3 [&>div]:gap-2 sm:[&>div]:gap-3 [&_label]:p-2.5 sm:[&_label]:p-3.5 [&_label]:rounded-xl [&_input]:sr-only"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-currency
                    wire:model="expense_amount"
                    locale="id-ID"
                    symbol=""
                    :decimals="0"
                    :precision="0"
                    hint="Saldo tersedia: {{ $wallet->formattedBalance() }}"
                    placeholder="Nominal"
                />

                <x-input
                    type="datetime-local"
                    wire:model="expense_date"
                />
            </div>

            <x-input
                wire:model="expense_description"
                placeholder="Keterangan pengeluaran (contoh: Belanja kebutuhan dapur)"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('expenseModal', false)">Batal</x-button>
                <x-button submit color="rose" loading="saveExpense">Simpan Pengeluaran</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Pindah Uang dari Kantong Ini ke Kantong Lain --}}
    <x-modal wire="transferModal" title="Pindah Uang dari {{ $wallet->name }}" size="lg">
        <form wire:submit="saveTransfer" class="space-y-4">
            @if (empty($this->otherActiveWalletOptions))
                <div class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800 border border-amber-200">
                    Belum ada kantong aktif lain yang dapat dipilih sebagai tujuan.
                </div>
            @else
                <x-radio.group
                    wire:model.live="transfer_destination_wallet_id"
                    card
                    :columns="2"
                    color="sky"
                    :options="$this->otherActiveWalletOptions"
                    class="[&_input]:sr-only"
                />
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-currency
                    wire:model="transfer_amount"
                    locale="id-ID"
                    symbol=""
                    :decimals="0"
                    :precision="0"
                    hint="Saldo tersedia: {{ $wallet->formattedBalance() }}"
                    placeholder="Nominal"
                />

                <x-input
                    type="datetime-local"
                    wire:model="transfer_date"
                />
            </div>

            <x-input
                wire:model="transfer_description"
                placeholder="Keterangan pindah uang (contoh: Sisihkan untuk tabungan jajan)"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('transferModal', false)">Batal</x-button>
                <x-button submit color="rose" loading="saveTransfer">Kirim Uang</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Ubah Transaksi --}}
    <x-modal wire="editTransactionModal" :title="$edit_tx_type === 'expense' ? 'Ubah Pengeluaran' : 'Ubah Pemasukan'" size="lg">
        <form wire:submit="saveEditTransaction" class="space-y-4">
            <x-radio.group
                id="home-edit-tx-category"
                wire:model.live="edit_tx_category_id"
                card
                :columns="3"
                :color="$edit_tx_type === 'expense' ? 'rose' : 'emerald'"
                :options="$this->editTxCategoryOptions"
                class="[&>div]:!grid-cols-3 [&>div]:gap-2 sm:[&>div]:gap-3 [&_label]:p-2.5 sm:[&_label]:p-3.5 [&_label]:rounded-xl [&_input]:sr-only"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-currency
                    wire:model="edit_tx_amount"
                    locale="id-ID"
                    symbol=""
                    :decimals="0"
                    :precision="0"
                    placeholder="Nominal"
                />

                <x-input
                    type="datetime-local"
                    wire:model="edit_tx_date"
                />
            </div>

            <x-input
                wire:model="edit_tx_description"
                placeholder="Keterangan transaksi"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('editTransactionModal', false)">Batal</x-button>
                <x-button submit :color="$edit_tx_type === 'expense' ? 'rose' : 'emerald'" loading="saveEditTransaction">Simpan Perubahan</x-button>
            </div>
        </form>
    </x-modal>
</div>
