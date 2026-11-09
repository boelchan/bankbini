<div class="space-y-6">
    {{-- Header Sapaan & Kantong --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-bold text-slate-900 sm:text-xl">
                Kantong Keuangan Keluarga
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Kelola seluruh pos belanja, tagihan, tabungan, dan anggaran keluarga Anda.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-button color="rose" sm wire:click="openPocketModal" class="rounded-xl">
                <x-ti-plus class="size-4 mr-1" />
                <span>Tambah Kantong Baru</span>
            </x-button>
        </div>
    </div>

    {{-- Ringkasan Saldo Ringkas (3 Bagian) --}}
    <x-card paddingless>
        <div class="grid grid-cols-1 divide-y divide-slate-200/80 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="flex items-center gap-2.5 px-4 py-3">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <x-ti-wallet class="size-4" />
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-500">Total Seluruh Saldo</p>
                    <p class="text-sm font-bold text-emerald-700 tabular-nums truncate">
                        {{ number_format((float) $this->summary['total_balance'], 0, ',', '.') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 px-4 py-3">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                    <x-ti-arrow-down-left class="size-4" />
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-500">Pemasukan Bulan Ini</p>
                    <p class="text-sm font-bold text-sky-700 tabular-nums truncate">
                        +{{ number_format((float) $this->summary['monthly_income'], 0, ',', '.') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 px-4 py-3">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-700">
                    <x-ti-arrow-up-right class="size-4" />
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-500">Pengeluaran Bulan Ini</p>
                    <p class="text-sm font-bold text-rose-600 tabular-nums truncate">
                        -{{ number_format((float) $this->summary['monthly_expense'], 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>
    </x-card>

    {{-- Kotak-Kotak Kantong --}}
    <div class="space-y-3">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 items-start">
            @foreach ($this->wallets as $wallet)
                @php
                    $colorEnum = $wallet->colorEnum();
                    $isExpanded = !empty($expandedWallets[$wallet->id]);
                    $totalTxCount = (int) ($wallet->transactions_count ?? $wallet->transactions->count());
                    $pocketTransactions = $wallet->transactions->take($isExpanded ? 100 : 3);
                @endphp
                <x-card class="{{ $colorEnum->cardClasses() }}">
                    <div class="flex flex-col gap-3">
                        {{-- Bagian Atas: Nama Kantong, Saldo Utama, dan Tombol Aksi --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <h3 class="text-sm font-bold {{ $colorEnum->accentTextClasses() }} truncate">
                                        {{ $wallet->name }}
                                    </h3>
                                    @if ($wallet->is_primary)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs">
                                            <x-ti-star-filled class="size-2.5 text-amber-500" />
                                            <span>Utama</span>
                                        </span>
                                    @endif
                                    @if (!$wallet->isActive())
                                        <x-badge
                                            :text="$wallet->status->label()"
                                            :color="$wallet->status->color()"
                                            xs
                                            light
                                        />
                                    @endif
                                </div>

                                <p class="mt-0.5 text-xl sm:text-2xl font-bold tracking-tight text-slate-900 tabular-nums">
                                    {{ $wallet->formattedBalance() }}
                                </p>

                                @if ($wallet->description)
                                    <p class="mt-0.5 text-xs text-slate-600 truncate">
                                        {{ $wallet->description }}
                                    </p>
                                @endif
                            </div>

                            {{-- Tombol Ikon Aksi di Samping Kanan --}}
                            <div class="flex items-center gap-1 shrink-0">
                                @if ($wallet->isActive())
                                    <x-button.circle
                                        color="rose"
                                        sm
                                        flat
                                        wire:click="openTransactionModal({{ $wallet->id }})"
                                        title="Catat Pengeluaran / Pemasukan"
                                        aria-label="Catat Transaksi {{ $wallet->name }}"
                                        class="size-9 sm:size-8 bg-white/85 hover:bg-white shadow-2xs"
                                    >
                                        <x-ti-pencil class="size-4.5 sm:size-4 font-bold" />
                                    </x-button.circle>
                                @endif

                                <x-button.circle
                                    color="slate"
                                    flat    
                                    sm
                                    href="{{ route('pockets.show', $wallet) }}"
                                    wire:navigate
                                    title="Detail & Pengaturan Kantong"
                                    aria-label="Detail Kantong {{ $wallet->name }}"
                                    class="size-9 sm:size-8 bg-white/85 hover:bg-white shadow-2xs"
                                >
                                    <x-ti-dots class="size-4.5 sm:size-4" />
                                </x-button.circle>
                            </div>
                        </div>

                        {{-- Bagian Bawah Saldo: 3 Transaksi Terakhir (Bisa di-expand hingga 100 transaksi) --}}
                        <div class="rounded-xl border overflow-hidden {{ $colorEnum->innerBoxClasses() }}">
                            @if ($pocketTransactions->isEmpty())
                                <div class="px-3 py-2.5 text-center">
                                    <p class="text-xs text-slate-400">Belum ada transaksi</p>
                                </div>
                            @else
                                <div @class([
                                    'divide-y divide-slate-100',
                                    'max-h-64 overflow-y-auto' => $isExpanded,
                                ])>
                                    @foreach ($pocketTransactions as $tx)
                                        <div class="flex items-center justify-between gap-2 px-3 py-2 text-xs">
                                            <div class="min-w-0 flex-1">
                                                <p class="font-medium text-slate-800 truncate">
                                                    {{ $tx->displayDescription() }}
                                                </p>
                                                <p class="text-slate-500 truncate">
                                                    {{ $tx->transaction_date?->translatedFormat('d M Y') }}
                                                    @if ($tx->category)
                                                        • {{ $tx->category->name }}
                                                    @elseif ($tx->notes)
                                                        • {{ $tx->notes }}
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-1.5 shrink-0">
                                                <span class="font-bold tabular-nums {{ $tx->type->isPositive() ? 'text-emerald-700' : 'text-rose-700' }}">
                                                    {{ $tx->formattedSignedAmount() }}
                                                </span>
                                                @if ($loop->first)
                                                    <x-button.circle
                                                        color="red"
                                                        flat
                                                        xs
                                                        wire:click="askDeleteTransaction({{ $tx->id }})"
                                                        title="Hapus Transaksi Terakhir"
                                                        aria-label="Hapus transaksi terakhir {{ $tx->displayDescription() }}"
                                                    >
                                                        <x-ti-trash class="size-3.5" />
                                                    </x-button.circle>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($totalTxCount > 3)
                                    <button
                                        type="button"
                                        wire:click="toggleExpandTransactions({{ $wallet->id }})"
                                        title="{{ $isExpanded ? 'Tampilkan 3 transaksi terakhir' : 'Lihat lebih banyak transaksi' }}"
                                        aria-label="{{ $isExpanded ? 'Tampilkan 3 transaksi terakhir' : 'Lihat lebih banyak transaksi' }}"
                                        class="flex w-full items-center justify-center border-t border-slate-100 {{ $colorEnum->expandButtonClasses() }} py-1.5 transition-colors cursor-pointer"
                                    >
                                        @if ($isExpanded)
                                            <x-ti-chevrons-up class="size-4 animate-pulse motion-reduce:animate-none" />
                                        @else
                                            <x-ti-chevrons-down class="size-4 animate-pulse motion-reduce:animate-none" />
                                        @endif
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>
    </div>

    {{-- Modal Transaksi Kantong Terpadu (Pengeluaran / Pemasukan) --}}
    <x-modal wire="transactionModal" title="Catat Transaksi Kantong" size="lg">
        <form wire:submit="saveTransaction" class="space-y-4">
            <x-radio.group
                id="pocket-tx-mode"
                wire:model.live="transaction_mode"
                inline
                class="[&>div]:flex-wrap sm:[&>div]:flex-nowrap [&>div]:gap-2"
                :color="$transaction_mode === \App\Enums\CategoryTypeEnum::EXPENSE->value ? 'rose' : 'emerald'"
                :options="\App\Enums\CategoryTypeEnum::choices()"
            />

            {{-- Pilihan Khusus Pengeluaran (Radio Tanpa Label) --}}
            @if ($transaction_mode === \App\Enums\CategoryTypeEnum::EXPENSE->value)
                <div wire:key="pocket-tx-expense-category-wrap-{{ $income_wallet_id ?? '0' }}" class="space-y-4">
                    <x-radio.group
                        id="pocket-expense-source-type"
                        wire:model.live="expense_source_type"
                        inline
                        class="[&>div]:flex-wrap sm:[&>div]:flex-nowrap [&>div]:gap-2"
                        color="rose"
                        :options="\App\Enums\ExpenseSourceEnum::choices()"
                    />

                    @if ($expense_source_type === \App\Enums\ExpenseSourceEnum::TO_POCKET->value)
                        <div wire:key="pocket-expense-to-pocket-wrap-{{ $income_wallet_id ?? '0' }}">
                            @if (empty($this->otherActiveWalletOptions))
                                <div class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800 border border-amber-200">
                                    Belum ada kantong aktif lain yang dapat dipilih.
                                </div>
                            @else
                                <x-radio.group
                                    id="pocket-expense-to-pocket-wallet"
                                    wire:model.live="income_source_wallet_id"
                                    panel
                                    :columns="2"
                                    color="rose"
                                    :options="$this->otherActiveWalletOptions"
                                />
                            @endif
                        </div>
                    @else
                        <div wire:key="pocket-expense-direct-category-wrap">
                            <x-radio.group
                                id="pocket-expense-category"
                                wire:model.live="expense_category_id"
                                panel
                                :columns="3"
                                color="rose"
                                :options="$this->expenseCategoryOptions"
                                class="[&>div]:!grid-cols-3 [&>div]:gap-2 sm:[&>div]:gap-3 [&_label]:p-2.5 sm:[&_label]:p-3.5 [&_label]:rounded-xl"
                            />
                        </div>
                    @endif
                </div>
            @else
                {{-- Pilihan Khusus Pemasukan (Radio Tanpa Label) --}}
                <div wire:key="pocket-tx-income-fields-wrap-{{ $income_wallet_id ?? '0' }}" class="space-y-4">
                    <x-radio.group
                        id="pocket-income-source-type"
                        wire:model.live="income_source_type"
                        inline
                        class="[&>div]:flex-wrap sm:[&>div]:flex-nowrap [&>div]:gap-2"
                        color="emerald"
                        :options="\App\Enums\IncomeSourceEnum::choices()"
                    />

                    @if ($income_source_type === \App\Enums\IncomeSourceEnum::FROM_POCKET->value)
                        <div wire:key="pocket-income-pocket-wrap-{{ $income_wallet_id ?? '0' }}">
                            @if (empty($this->otherActiveWalletOptions))
                                <div class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800 border border-amber-200">
                                    Belum ada kantong aktif lain yang dapat dipilih.
                                </div>
                            @else
                                <x-radio.group
                                    id="pocket-income-pocket-wallet"
                                    wire:model.live="income_source_wallet_id"
                                    panel
                                    :columns="2"
                                    color="emerald"
                                    :options="$this->otherActiveWalletOptions"
                                />
                            @endif
                        </div>
                    @else
                        <div wire:key="pocket-income-direct-category-wrap">
                            <x-radio.group
                                id="pocket-income-category"
                                wire:model.live="income_category_id"
                                panel
                                :columns="3"
                                color="emerald"
                                :options="$this->incomeCategoryOptions"
                                class="[&>div]:!grid-cols-3 [&>div]:gap-2 sm:[&>div]:gap-3 [&_label]:p-2.5 sm:[&_label]:p-3.5 [&_label]:rounded-xl"
                            />
                        </div>
                    @endif
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
                placeholder="{{ $transaction_mode === \App\Enums\CategoryTypeEnum::EXPENSE->value ? ($expense_source_type === \App\Enums\ExpenseSourceEnum::TO_POCKET->value ? 'Keterangan pindah uang (opsional)' : 'Keterangan pengeluaran (contoh: Belanja sayur & lauk)') : ($income_source_type !== \App\Enums\IncomeSourceEnum::DIRECT->value ? 'Keterangan pindah uang (opsional)' : 'Keterangan pemasukan (contoh: Gaji, Bonus)') }}"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('transactionModal', false)">Batal</x-button>
                <x-button
                    submit
                    :color="$transaction_mode === \App\Enums\CategoryTypeEnum::EXPENSE->value ? 'rose' : 'emerald'"
                    loading="saveTransaction"
                >
                    <x-ti-check class="size-4 mr-1" />
                    Simpan Transaksi
                </x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Tambah Kantong Baru --}}
    <x-modal wire="pocketModal" title="Tambah Kantong Baru" size="lg">
        <form wire:submit="savePocket" class="space-y-4">
            <x-input
                label="Nama Kantong *"
                wire:model="pocket_name"
                placeholder="Contoh: Kantong Utama, Kantong Dapur, Kantong Tabungan"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-select.styled
                    label="Ikon Kantong *"
                    wire:model="pocket_icon"
                    :options="\App\Enums\WalletIconEnum::choices()"
                />

                <x-select.styled
                    label="Warna Kantong *"
                    wire:model="pocket_color"
                    :options="\App\Enums\WalletColorEnum::choices()"
                />
            </div>

            <x-currency
                label="Saldo Awal (Opsional)"
                wire:model="pocket_initial_balance"
                locale="id-ID"
                symbol=""
                :decimals="0"
                :precision="0"
                placeholder="0"
            />

            <x-textarea
                label="Deskripsi Singkat (Opsional)"
                wire:model="pocket_description"
                rows="2"
                placeholder="Keterangan singkat tujuan kantong ini"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('pocketModal', false)">Batal</x-button>
                <x-button submit color="rose" loading="savePocket">
                    <x-ti-plus class="size-4 mr-1" />
                    Buat Kantong
                </x-button>
            </div>
        </form>
    </x-modal>
</div>
