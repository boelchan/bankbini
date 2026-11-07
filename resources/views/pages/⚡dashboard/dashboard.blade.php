<div class="space-y-6">
    {{-- Header Sapaan --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-bold text-slate-900 sm:text-xl">
                Halo, {{ auth()->user()->name }}!
            </h1>
        </div>
    </div>

    {{-- Ringkasan Saldo Sederhana --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-card class="bg-emerald-300 border-emerald-400">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-950">Total Seluruh Saldo</p>
                    <p class="mt-1.5 text-2xl font-bold text-emerald-950 tabular-nums">
                        Rp{{ number_format((float) $this->summary['total_balance'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/75 text-emerald-900 border border-emerald-400">
                    <x-ti-wallet class="size-5" />
                </div>
            </div>
        </x-card>

        <x-card class="bg-sky-300 border-sky-400">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-sky-950">Pemasukan Bulan Ini</p>
                    <p class="mt-1.5 text-2xl font-bold text-sky-950 tabular-nums">
                        +Rp{{ number_format((float) $this->summary['monthly_income'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/75 text-sky-900 border border-sky-400">
                    <x-ti-arrow-down-left class="size-5" />
                </div>
            </div>
        </x-card>

        <x-card class="bg-rose-300 border-rose-400">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-rose-950">Pengeluaran Bulan Ini</p>
                    <p class="mt-1.5 text-2xl font-bold text-rose-950 tabular-nums">
                        -Rp{{ number_format((float) $this->summary['monthly_expense'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/75 text-rose-900 border border-rose-400">
                    <x-ti-arrow-up-right class="size-5" />
                </div>
            </div>
        </x-card>
    </div>

    {{-- Kotak-Kotak Kantong + Kotak Terakhir "+ Tambah Kantong" --}}
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
                                        color="emerald"
                                        sm
                                        flat
                                        wire:click="openTransactionModal({{ $wallet->id }})"
                                        title="Catat Pengeluaran / Pemasukan"
                                        aria-label="Catat Transaksi {{ $wallet->name }}"
                                        class="bg-white/85 hover:bg-white shadow-2xs"
                                    >
                                        <x-ti-pencil class="size-4 font-bold" />
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
                                    class="bg-white/85 hover:bg-white shadow-2xs"
                                >
                                    <x-ti-dots class="size-4" />
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
                                                <p class="text-slate-500 truncate flex items-center gap-1.5 flex-wrap">
                                                    <span>{{ $tx->transaction_date?->translatedFormat('d M, H:i') }}</span>
                                                    <span>•</span>
                                                    <span class="font-medium {{ $tx->type->isPositive() ? 'text-emerald-700' : 'text-rose-700' }}">
                                                        {{ $tx->type->label() }}
                                                    </span>
                                                    @if ($tx->category)
                                                        <span>•</span>
                                                        <span>{{ $tx->category->name }}</span>
                                                    @elseif ($tx->notes)
                                                        <span>•</span>
                                                        <span>{{ $tx->notes }}</span>
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

            {{-- Kotak Kantong Terakhir: Kotak Kosong + Tambah Kantong --}}
            <button
                type="button"
                wire:click="openPocketModal"
                class="group flex min-h-52 flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-rose-300 bg-rose-50/60 p-6 text-center transition-all hover:border-rose-400 hover:bg-rose-100/70 focus-visible:outline-2 focus-visible:outline-emerald-600 cursor-pointer shadow-2xs"
            >
                <div class="flex size-12 items-center justify-center rounded-2xl bg-white text-rose-600 border border-rose-200 shadow-xs group-hover:scale-110 transition-transform">
                    <x-ti-plus class="size-6" />
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-bold text-rose-950">
                        + Tambah Kantong
                    </p>
                    <p class="text-xs text-rose-900/80 max-w-xs">
                        Buat kantong baru untuk belanja dapur, tabungan jajan, atau liburan
                    </p>
                </div>
            </button>
        </div>
    </div>

    {{-- Modal Transaksi Kantong Terpadu (Pengeluaran / Pemasukan) --}}
    <x-modal wire="transactionModal" title="Catat Transaksi Kantong" size="lg">
        <form wire:submit="saveTransaction" class="space-y-4">
            <x-radio.group
                id="dash-tx-mode"
                wire:model.live="transaction_mode"
                inline
                :color="$transaction_mode === \App\Enums\CategoryTypeEnum::EXPENSE->value ? 'rose' : 'emerald'"
                :options="\App\Enums\CategoryTypeEnum::choices()"
            />

            {{-- Pilihan Khusus Pengeluaran (Radio Tanpa Label) --}}
            @if ($transaction_mode === \App\Enums\CategoryTypeEnum::EXPENSE->value)
                <div wire:key="dash-tx-expense-category-wrap-{{ $income_wallet_id ?? '0' }}" class="space-y-4">
                    <x-radio.group
                        id="dash-expense-source-type"
                        wire:model.live="expense_source_type"
                        inline
                        color="rose"
                        :options="\App\Enums\ExpenseSourceEnum::choices()"
                    />

                    @if ($expense_source_type === \App\Enums\ExpenseSourceEnum::TO_POCKET->value)
                        <div wire:key="dash-expense-to-pocket-wrap-{{ $income_wallet_id ?? '0' }}">
                            @if (empty($this->otherActiveWalletOptions))
                                <div class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800 border border-amber-200">
                                    Belum ada kantong aktif lain yang dapat dipilih.
                                </div>
                            @else
                                <x-radio.group
                                    id="dash-expense-to-pocket-wallet"
                                    wire:model.live="income_source_wallet_id"
                                    panel
                                    :columns="2"
                                    color="rose"
                                    :options="$this->otherActiveWalletOptions"
                                />
                            @endif
                        </div>
                    @else
                        <div wire:key="dash-expense-direct-category-wrap">
                            <x-radio.group
                                id="dash-expense-category"
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
                <div wire:key="dash-tx-income-fields-wrap-{{ $income_wallet_id ?? '0' }}" class="space-y-4">
                    <x-radio.group
                        id="dash-income-source-type"
                        wire:model.live="income_source_type"
                        inline
                        color="emerald"
                        :options="\App\Enums\IncomeSourceEnum::choices()"
                    />

                    @if ($income_source_type === \App\Enums\IncomeSourceEnum::FROM_POCKET->value)
                        <div wire:key="dash-income-pocket-wrap-{{ $income_wallet_id ?? '0' }}">
                            @if (empty($this->otherActiveWalletOptions))
                                <div class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800 border border-amber-200">
                                    Belum ada kantong aktif lain yang dapat dipilih.
                                </div>
                            @else
                                <x-radio.group
                                    id="dash-income-pocket-wallet"
                                    wire:model.live="income_source_wallet_id"
                                    panel
                                    :columns="2"
                                    color="emerald"
                                    :options="$this->otherActiveWalletOptions"
                                />
                            @endif
                        </div>
                    @else
                        <div wire:key="dash-income-direct-category-wrap">
                            <x-radio.group
                                id="dash-income-category"
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
                symbol="Rp"
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
                <x-button submit color="emerald" loading="savePocket">
                    <x-ti-plus class="size-4 mr-1" />
                    Buat Kantong
                </x-button>
            </div>
        </form>
    </x-modal>
</div>
