<div class="space-y-6">
    {{-- Header Sapaan --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-800 border border-rose-200 mb-1.5">
                <x-ti-heart class="size-3.5 text-rose-600" />
                <span>Kantong Keuangan Keluarga</span>
            </div>
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
                    <p class="mt-1 text-xs font-medium text-emerald-900">
                        Dari {{ $this->summary['pocket_count'] }} kantong keuangan
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
                    <p class="mt-1 text-xs font-medium text-sky-900">
                        {{ now()->translatedFormat('F Y') }}
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
                    <p class="mt-1 text-xs font-medium text-rose-900">
                        {{ now()->translatedFormat('F Y') }}
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
        <div>
            <h2 class="text-sm sm:text-base font-semibold text-slate-900">Kantong Keuangan</h2>
            <p class="text-xs text-slate-500">
                Gunakan tombol ikon pada kantong untuk tambah uang, catat pengeluaran, atau buka pengaturan kantong.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 items-start">
            @foreach ($this->wallets as $wallet)
                @php
                    $colorEnum = $wallet->colorEnum();
                    $isExpanded = !empty($expandedWallets[$wallet->id]);
                    $totalTxCount = (int) ($wallet->transactions_count ?? $wallet->transactions->count());
                    $pocketTransactions = $wallet->transactions->take($isExpanded ? 100 : 3);
                @endphp
                <x-card class="{{ $colorEnum->cardClasses() }}">
                    <div class="flex flex-col space-y-3.5">
                        {{-- Bagian Atas: Ikon, Nama Kantong, dan Tombol Ikon Aksi di Samping/Bawah Nama --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl {{ $colorEnum->iconBadgeClasses() }}">
                                    <x-wallet-icon :icon="$wallet->icon" class="size-5" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <h3 class="text-base font-bold text-slate-900 truncate">
                                            {{ $wallet->name }}
                                        </h3>
                                        @if (!$wallet->isActive())
                                            <x-badge
                                                :text="$wallet->status->label()"
                                                :color="$wallet->status->color()"
                                                sm
                                                light
                                            />
                                        @endif
                                    </div>
                                    @if ($wallet->description)
                                        <p class="text-xs text-slate-600 truncate mt-0.5">
                                            {{ $wallet->description }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- Tombol Ikon Aksi di Samping Nama Kantong --}}
                            <div class="flex items-center gap-1.5 shrink-0">
                                @if ($wallet->isActive())
                                    <x-button.circle
                                        color="emerald"
                                        sm
                                        wire:click="openIncomeModal({{ $wallet->id }})"
                                        title="Tambah Uang / Pemasukan"
                                        aria-label="Tambah Uang ke {{ $wallet->name }}"
                                    >
                                        <x-ti-plus class="size-4" />
                                    </x-button.circle>

                                    <x-button.circle
                                        color="rose"
                                        light
                                        sm
                                        wire:click="openExpenseModal({{ $wallet->id }})"
                                        title="Catat Pengeluaran"
                                        aria-label="Catat Pengeluaran dari {{ $wallet->name }}"
                                    >
                                        <x-ti-minus class="size-4" />
                                    </x-button.circle>
                                @endif

                                <x-button.circle
                                    color="slate"
                                    outline
                                    sm
                                    href="{{ route('pockets.show', $wallet) }}"
                                    wire:navigate
                                    title="Detail & Pengaturan Kantong"
                                    aria-label="Detail Kantong {{ $wallet->name }}"
                                    class="bg-white/90"
                                >
                                    <x-ti-dots class="size-4" />
                                </x-button.circle>
                            </div>
                        </div>

                        {{-- Bagian Tengah: Saldo Saat Ini --}}
                        <div class="rounded-2xl p-3.5 border {{ $colorEnum->innerBoxClasses() }}">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="text-xs font-medium text-slate-500">Saldo Kantong</span>
                                <span class="text-xl font-bold text-slate-900 tabular-nums">
                                    {{ $wallet->formattedBalance() }}
                                </span>
                            </div>
                        </div>

                        {{-- Bagian Bawah Saldo: 3 Transaksi Terakhir (Bisa di-expand hingga 100 transaksi) --}}
                        <div class="rounded-2xl border overflow-hidden {{ $colorEnum->innerBoxClasses() }}">
                            @if ($pocketTransactions->isEmpty())
                                <div class="px-3.5 py-3 text-center">
                                    <p class="text-xs text-slate-500">Belum ada transaksi di kantong ini</p>
                                </div>
                            @else
                                <div @class([
                                    'divide-y divide-slate-100',
                                    'max-h-72 overflow-y-auto' => $isExpanded,
                                ])>
                                    @foreach ($pocketTransactions as $tx)
                                        <div class="flex items-center justify-between gap-2 px-3.5 py-2 text-xs">
                                            <div class="min-w-0 flex-1">
                                                <p class="font-medium text-slate-800 truncate">
                                                    {{ $tx->description }}
                                                </p>
                                                <p class="text-slate-500 truncate">
                                                    {{ $tx->transaction_date?->translatedFormat('d M, H:i') }}
                                                    @if ($tx->counterpart_wallet_name_snapshot)
                                                        • {{ $tx->type === \App\Enums\TransactionTypeEnum::TRANSFER_IN ? 'Dari ' : 'Ke ' }}{{ $tx->resolvedCounterpartWalletName() }}
                                                    @elseif ($tx->category)
                                                        • {{ $tx->category->name }}
                                                    @endif
                                                </p>
                                            </div>
                                            <span class="shrink-0 font-bold tabular-nums {{ $tx->type->isPositive() ? 'text-emerald-700' : 'text-rose-700' }}">
                                                {{ $tx->formattedSignedAmount() }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($totalTxCount > 3)
                                    <button
                                        type="button"
                                        wire:click="toggleExpandTransactions({{ $wallet->id }})"
                                        class="flex w-full items-center justify-center gap-1 border-t border-slate-100 bg-rose-50/40 py-2 text-xs font-semibold text-rose-800 hover:bg-rose-100/60 transition-colors cursor-pointer"
                                    >
                                        @if ($isExpanded)
                                            <span>Tampilkan 3 terakhir</span>
                                            <x-ti-chevron-up class="size-3.5" />
                                        @else
                                            <span>Lihat {{ min($totalTxCount, 100) }} transaksi terakhir</span>
                                            <x-ti-chevron-down class="size-3.5" />
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
                class="group flex min-h-52 flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-rose-300 bg-rose-50/40 ring-4 ring-white/70 ring-inset p-6 text-center transition-all hover:border-rose-400 hover:bg-rose-50/80 focus-visible:outline-2 focus-visible:outline-emerald-600 cursor-pointer"
            >
                <div class="flex size-12 items-center justify-center rounded-2xl bg-white text-rose-600 border border-rose-200 shadow-2xs group-hover:scale-105 transition-transform">
                    <x-ti-plus class="size-6" />
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-bold text-slate-900">
                        + Tambah Kantong
                    </p>
                    <p class="text-xs text-slate-500 max-w-xs">
                        Buat kantong baru untuk belanja dapur, tabungan, atau liburan
                    </p>
                </div>
            </button>
        </div>
    </div>

    {{-- Modal Tambah Uang / Pemasukan (Langsung atau Ambil dari Kantong Lain) --}}
    <x-modal wire="incomeModal" title="Tambah Uang ke Kantong" size="lg">
        <form wire:submit="saveIncome" class="space-y-4">
            <x-select.styled
                label="Kantong Tujuan *"
                placeholder="Pilih kantong tujuan"
                wire:model.live="income_wallet_id"
                :options="$this->activeWalletOptions"
                searchable
            />

            <x-select.styled
                label="Sumber Uang *"
                wire:model.live="income_source_type"
                :options="\App\Enums\IncomeSourceEnum::choices()"
            />

            <div
                wire:key="dash-income-from-pocket-wrap-{{ $income_wallet_id ?? '0' }}"
                x-show="$wire.income_source_type === '{{ \App\Enums\IncomeSourceEnum::FROM_POCKET->value }}'"
                x-cloak
            >
                <x-select.styled
                    label="Ambil dari Kantong *"
                    placeholder="Pilih kantong asal"
                    wire:model.live="income_source_wallet_id"
                    :options="$this->otherActiveWalletOptions"
                    :hint="empty($this->otherActiveWalletOptions) ? 'Belum ada kantong aktif lain yang dapat dipilih.' : null"
                    searchable
                />
            </div>

            <div
                wire:key="dash-income-direct-category-wrap"
                x-show="$wire.income_source_type !== '{{ \App\Enums\IncomeSourceEnum::FROM_POCKET->value }}'"
                x-cloak
            >
                <x-select.styled
                    label="Kategori Pemasukan *"
                    placeholder="Pilih kategori pemasukan"
                    wire:model.live="income_category_id"
                    :options="$this->incomeCategoryOptions"
                    searchable
                />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-currency
                    label="Nominal (Rp) *"
                    wire:model="income_amount"
                    locale="id-ID"
                    symbol="Rp"
                    :decimals="0"
                    :precision="0"
                    placeholder="0"
                />

                <x-input
                    type="datetime-local"
                    label="Tanggal & Waktu *"
                    wire:model="income_date"
                />
            </div>

            <x-input
                label="{{ $income_source_type === \App\Enums\IncomeSourceEnum::FROM_POCKET->value ? 'Keterangan Pindah Uang (Opsional)' : 'Keterangan Pemasukan *' }}"
                wire:model="income_description"
                placeholder="{{ $income_source_type === \App\Enums\IncomeSourceEnum::FROM_POCKET->value ? 'Contoh: Tambah uang belanja dari kantong utama' : 'Contoh: Gaji bulanan, Bonus, Hadiah' }}"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('incomeModal', false)">Batal</x-button>
                <x-button submit color="emerald" loading="saveIncome">
                    <x-ti-check class="size-4 mr-1" />
                    Simpan
                </x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Catat Pengeluaran --}}
    <x-modal wire="expenseModal" title="Catat Pengeluaran Kantong" size="lg">
        <form wire:submit="saveExpense" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-select.styled
                    label="Kantong Sumber *"
                    placeholder="Pilih kantong"
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
                    :decimals="0"
                    :precision="0"
                    placeholder="0"
                />

                <x-input
                    type="datetime-local"
                    label="Tanggal & Waktu *"
                    wire:model="expense_date"
                />
            </div>

            <x-input
                label="Keterangan Pengeluaran *"
                wire:model="expense_description"
                placeholder="Contoh: Belanja sayur & lauk harian"
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
