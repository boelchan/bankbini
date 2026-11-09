@php
    $colorEnum = $wallet->colorEnum();
@endphp

<div class="space-y-6">
    {{-- Header Sapaan & Kantong Utama --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-bold text-slate-900 sm:text-xl">
                Halo, {{ auth()->user()->name }}!
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Ini adalah <strong class="text-emerald-800 font-semibold">Kantong Utama</strong> operasional keluarga Anda.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-button color="slate" outline sm href="{{ route('pockets.index') }}" wire:navigate class="rounded-xl">
                <x-ti-wallet class="size-4 mr-1" />
                <span>Lihat Semua Kantong</span>
            </x-button>
        </div>
    </div>

    {{-- Kartu Utama Kantong Utama & Tombol Aksi --}}
    <x-card class="{{ $colorEnum->cardClasses() }}">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-3.5">
                <div class="flex size-12 sm:size-14 shrink-0 items-center justify-center rounded-2xl {{ $colorEnum->iconBadgeClasses() }}">
                    <x-wallet-icon :icon="$wallet->icon" class="size-6 sm:size-7" />
                </div>
                <div class="space-y-1 min-w-0 flex-1">
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
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 pt-1 text-xs text-slate-600">
                        <span>Total Masuk: <strong class="text-emerald-700 tabular-nums">+{{ number_format((float) $this->walletStats['total_in'], 0, ',', '.') }}</strong></span>
                        <span>Total Keluar: <strong class="text-rose-700 tabular-nums">-{{ number_format((float) $this->walletStats['total_out'], 0, ',', '.') }}</strong></span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t lg:border-t-0 pt-3 lg:pt-0 border-slate-200/80">
                <div>
                    <span class="text-xs font-medium text-slate-500 block">Saldo Kantong Utama</span>
                    <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 tabular-nums tracking-tight">
                        {{ $wallet->formattedBalance() }}
                    </p>
                </div>

                @if ($wallet->isActive())
                    <div class="grid grid-cols-3 gap-2 sm:flex sm:flex-wrap sm:items-center">
                        <x-button color="emerald" sm wire:click="openIncomeModal('direct')" class="rounded-xl w-full sm:w-auto justify-center px-2 sm:px-3 text-xs">
                            <x-ti-plus class="size-4 mr-1 shrink-0" />
                            <span>Tambah Uang</span>
                        </x-button>

                        <x-button color="rose" light sm wire:click="openExpenseModal" class="rounded-xl w-full sm:w-auto justify-center px-2 sm:px-3 text-xs">
                            <x-ti-minus class="size-4 mr-1 shrink-0" />
                            <span>Pengeluaran</span>
                        </x-button>

                        <x-button color="sky" light sm wire:click="openTransferModal" class="rounded-xl w-full sm:w-auto justify-center px-2 sm:px-3 text-xs">
                            <x-ti-arrows-exchange class="size-4 mr-1 shrink-0" />
                            <span>Pindah Uang</span>
                        </x-button>
                    </div>
                @endif
            </div>
        </div>
    </x-card>

    {{-- Histori Masuk & Keluar Kantong Utama --}}
    <x-card paddingless>
        <x-slot:header>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between w-full">
                <div>
                    <h3 class="text-sm sm:text-base font-semibold text-slate-900">Histori Transaksi Kantong Utama</h3>
                    <p class="text-xs text-slate-500">Seluruh mutasi uang masuk, keluar, dan transfer pada {{ $wallet->name }}</p>
                </div>
                @if ($search !== '' || $flowFilter !== '' || $typeFilter !== '' || $monthFilter !== '' || $categoryFilter !== null)
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition-colors cursor-pointer self-start sm:self-auto"
                    >
                        <x-ti-refresh class="size-3.5" />
                        <span>Reset Filter</span>
                    </button>
                @endif
            </div>
        </x-slot:header>

        {{-- Toolbar Filter Transaksi --}}
        <div class="border-b border-slate-200/80 bg-slate-50/50 p-3 sm:p-4 space-y-3">
            <div class="flex items-center justify-between gap-2">
                {{-- Quick Filter Aliran Dana (Flex-wrap agar mengalir ke bawah di ponsel tanpa terpotong) --}}
                <div class="flex flex-wrap items-center gap-1.5 w-full">
                    <button
                        type="button"
                        wire:click="setFlowFilter('')"
                        class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors cursor-pointer {{ $flowFilter === '' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100' }}"
                    >
                        Semua Aliran
                    </button>
                    <button
                        type="button"
                        wire:click="setFlowFilter('income')"
                        class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors cursor-pointer {{ $flowFilter === 'income' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100' }}"
                    >
                        Pemasukan (+)
                    </button>
                    <button
                        type="button"
                        wire:click="setFlowFilter('expense')"
                        class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors cursor-pointer {{ $flowFilter === 'expense' ? 'bg-rose-600 text-white shadow-2xs' : 'bg-rose-50 text-rose-800 border border-rose-200 hover:bg-rose-100' }}"
                    >
                        Pengeluaran (-)
                    </button>
                    <button
                        type="button"
                        wire:click="setFlowFilter('transfer')"
                        class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-semibold transition-colors cursor-pointer {{ $flowFilter === 'transfer' ? 'bg-sky-600 text-white shadow-2xs' : 'bg-sky-50 text-sky-800 border border-sky-200 hover:bg-sky-100' }}"
                    >
                        Pindah Uang (⇄)
                    </button>
                </div>
            </div>

            {{-- Input Filter: Cari Keterangan, Bulan, Jenis Transaksi, Kategori --}}
            <div class="grid grid-cols-1 gap-2.5 sm:gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <x-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari keterangan..."
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
                />
            </div>
        </div>

        @if ($this->transactions->isEmpty())
            <div class="p-8 text-center space-y-2">
                <p class="text-sm font-semibold text-slate-800">Belum Ada Transaksi di Kantong Utama</p>
                <p class="text-xs text-slate-500">Gunakan tombol Tambah Uang, Pengeluaran, atau Pindah Uang di atas untuk mulai mencatat.</p>
            </div>
        @else
            {{-- List Transaksi Kantong Utama (Simple & Clear Bank-Style) --}}
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
                                    {{-- Kiri: Tanggal & Kategori / Lawan Transfer --}}
                                    <div class="flex items-center gap-1.5 truncate">
                                        <span>{{ $tx->transaction_date?->translatedFormat('d M Y, H:i') }}</span>
                                        <span>•</span>
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

                                    {{-- Kanan: Saldo Sisa Sesudah Transaksi --}}
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="hidden sm:inline text-slate-400">Sisa:</span>
                                        <span class="font-medium text-slate-600 tabular-nums">{{ $tx->formattedBalanceAfter() }}</span>
                                        @if ($loop->first && $this->transactions->currentPage() === 1)
                                            <button
                                                type="button"
                                                wire:click="askDeleteTransaction({{ $tx->id }})"
                                                class="text-slate-400 hover:text-rose-600 p-0.5 rounded transition-colors cursor-pointer"
                                                title="Hapus Transaksi Terakhir"
                                                aria-label="Hapus transaksi terakhir {{ $tx->displayDescription() }}"
                                            >
                                                <x-ti-trash class="size-3.5" />
                                            </button>
                                        @endif
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
                <x-button submit color="emerald" loading="saveIncome">Simpan</x-button>
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
                class="[&>div]:!grid-cols-3 [&>div]:gap-2 sm:[&>div]:gap-3 [&_label]:p-2.5 sm:[&_label]:p-3.5 [&_label]:rounded-xl"
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
                    color="emerald"
                    :options="$this->otherActiveWalletOptions"
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
                <x-button submit color="emerald" loading="saveTransfer">Kirim Uang</x-button>
            </div>
        </form>
    </x-modal>
</div>
