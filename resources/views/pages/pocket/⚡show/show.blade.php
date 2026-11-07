@php
    $colorEnum = $wallet->colorEnum();
@endphp

<div class="space-y-6">
    {{-- Header & Navigasi Kembali ke Dashboard --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-bold text-slate-900 sm:text-xl">Detail Kantong: {{ $wallet->name }}</h1>
            <nav class="flex items-center gap-1.5 text-sm text-slate-500 mt-1">
                <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-emerald-600 transition-colors">
                    <x-ti-home class="size-4" />
                </a>
                <span>/</span>
                <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-emerald-600 transition-colors">
                    Dashboard
                </a>
                <span>/</span>
                <span class="text-slate-700 font-medium">{{ $wallet->name }}</span>
            </nav>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-button color="slate" outline sm href="{{ route('dashboard') }}" wire:navigate class="rounded-xl">
                <x-ti-arrow-left class="size-4 mr-1" />
                Kembali ke Dashboard
            </x-button>

            <x-button color="emerald" flat sm wire:click="openEditModal" class="rounded-xl">
                <x-ti-edit class="size-4 mr-1" />
                Ubah Kantong
            </x-button>

            <x-button
                :color="$wallet->isFrozen() ? 'emerald' : 'amber'"
                flat
                sm
                wire:click="toggleFreeze"
                class="rounded-xl"
            >
                @if ($wallet->isFrozen())
                    <x-ti-player-play class="size-4 mr-1" />
                    Aktifkan
                @else
                    <x-ti-snowflake class="size-4 mr-1" />
                    Bekukan
                @endif
            </x-button>

            <x-button color="red" flat sm wire:click="openDeleteModal" class="rounded-xl">
                <x-ti-trash class="size-4 mr-1" />
                Hapus
            </x-button>
        </div>
    </div>

    {{-- Peringatan jika Kantong Dibekukan --}}
    @if ($wallet->isFrozen())
        <div class="rounded-2xl bg-amber-50 p-4 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-200/70 text-amber-900">
                    <x-ti-snowflake class="size-5" />
                </div>
                <div>
                    <p class="text-sm font-bold text-amber-950">Kantong Ini Sedang Dibekukan</p>
                    <p class="text-xs text-amber-900 mt-0.5">
                        Selama dibekukan, kantong tidak dapat menerima uang masuk, mengeluarkan uang, ataupun memindahkan uang.
                    </p>
                </div>
            </div>
            <x-button color="emerald" sm wire:click="toggleFreeze" class="shrink-0 rounded-xl">
                Aktifkan Kembali
            </x-button>
        </div>
    @endif

    {{-- Kartu Utama Kantong & Tombol Aksi (Tambah Uang, Pengeluaran, Pindah Uang) --}}
    <x-card class="{{ $colorEnum->cardClasses() }}">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl {{ $colorEnum->iconBadgeClasses() }}">
                    <x-wallet-icon :icon="$wallet->icon" class="size-7" />
                </div>
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-lg font-bold {{ $colorEnum->accentTextClasses() }}">{{ $wallet->name }}</h2>
                        @if (!$wallet->isActive())
                            <x-badge :text="$wallet->status->label()" :color="$wallet->status->color()" sm light />
                        @endif
                    </div>
                    @if ($wallet->description)
                        <p class="text-sm text-slate-700">
                            {{ $wallet->description }}
                        </p>
                    @endif
                    <div class="flex flex-wrap items-center gap-4 pt-1 text-xs text-slate-600">
                        <span>Total Masuk: <strong class="text-emerald-700 tabular-nums">+Rp{{ number_format((float) $this->walletStats['total_in'], 0, ',', '.') }}</strong></span>
                        <span>Total Keluar: <strong class="text-rose-700 tabular-nums">-Rp{{ number_format((float) $this->walletStats['total_out'], 0, ',', '.') }}</strong></span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-4 lg:gap-6 border-t lg:border-t-0 pt-4 lg:pt-0 border-slate-200/80">
                    <p class="text-2xl font-bold text-slate-900 tabular-nums mt-0.5">
                        {{ $wallet->formattedBalance() }}
                    </p>

                @if ($wallet->isActive())
                    <div class="flex flex-wrap items-center gap-2">
                        <x-button color="emerald" sm wire:click="openIncomeModal('direct')" class="rounded-xl">
                            <x-ti-plus class="size-4 mr-1" />
                            Tambah Uang
                        </x-button>

                        <x-button color="rose" light sm wire:click="openExpenseModal" class="rounded-xl">
                            <x-ti-minus class="size-4 mr-1" />
                            Pengeluaran
                        </x-button>

                        <x-button color="sky" light sm wire:click="openTransferModal" class="rounded-xl">
                            <x-ti-arrows-exchange class="size-4 mr-1" />
                            Pindah Uang
                        </x-button>
                    </div>
                @endif
            </div>
        </div>
    </x-card>

    {{-- Histori Masuk & Keluar Kantong (Tanpa Kode TRX) --}}
    <x-card paddingless>
        <x-slot:header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between w-full">
                <div>
                    <h3 class="text-sm sm:text-base font-semibold text-slate-900">Histori Masuk & Keluar Kantong</h3>
                    <p class="text-xs text-slate-500">Catatan seluruh perubahan saldo pada kantong {{ $wallet->name }}</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 sm:w-80">
                    <x-input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari keterangan..."
                    />
                    <x-select.styled
                        placeholder="Semua Jenis"
                        wire:model.live="typeFilter"
                        :options="\App\Enums\TransactionTypeEnum::choices()"
                    />
                </div>
            </div>
        </x-slot:header>

        @if ($this->transactions->isEmpty())
            <div class="p-8 text-center space-y-2">
                <p class="text-sm font-semibold text-slate-800">Belum Ada Transaksi di Kantong Ini</p>
                <p class="text-xs text-slate-500">Gunakan tombol Tambah Uang, Pengeluaran, atau Pindah Uang di atas untuk mulai mencatat.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="py-3 pl-5 pr-3">Tanggal & Keterangan</th>
                            <th class="px-3 py-3">Jenis</th>
                            <th class="px-3 py-3 text-right">Nominal</th>
                            <th class="px-3 py-3 text-right">Saldo Kantong</th>
                            <th class="py-3 pl-3 pr-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70 text-sm">
                        @foreach ($this->transactions as $tx)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 pl-5 pr-3">
                                    <div class="font-semibold text-slate-900">{{ $tx->displayDescription() }}</div>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                        <span>{{ $tx->transaction_date?->translatedFormat('d M Y, H:i') }}</span>
                                        @if ($tx->notes)
                                            <span>•</span>
                                            <span class="font-medium text-slate-700">{{ $tx->notes }}</span>
                                        @endif
                                        @if ($tx->category)
                                            <span>•</span>
                                            <span class="font-medium text-slate-700">{{ $tx->category->name }}</span>
                                        @endif
                                        @if ($tx->counterpart_wallet_name_snapshot)
                                            <span>•</span>
                                            <span class="text-slate-600">
                                                {{ $tx->type === \App\Enums\TransactionTypeEnum::TRANSFER_IN ? 'Terima dari: ' : 'Pindah ke: ' }}
                                                {{ $tx->resolvedCounterpartWalletName() }}
                                            </span>
                                        @endif
                                        @if ($tx->user)
                                            <span>•</span>
                                            <span>Oleh: {{ $tx->user->name }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <x-badge :text="$tx->type->label()" :color="$tx->type->color()" sm light />
                                </td>
                                <td class="px-3 py-3 text-right font-bold tabular-nums {{ $tx->type->isPositive() ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ $tx->formattedSignedAmount() }}
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <div class="text-xs font-semibold text-slate-900 tabular-nums">{{ $tx->formattedBalanceAfter() }}</div>
                                    <div class="text-xs text-slate-500 tabular-nums">Awal: {{ $tx->formattedBalanceBefore() }}</div>
                                </td>
                                <td class="py-3 pl-3 pr-5 text-right">
                                    @if ($loop->first && $this->transactions->currentPage() === 1)
                                        <x-button.circle
                                            color="red"
                                            flat
                                            sm
                                            wire:click="askDeleteTransaction({{ $tx->id }})"
                                            title="Hapus Transaksi Terakhir"
                                            aria-label="Hapus transaksi terakhir {{ $tx->displayDescription() }}"
                                        >
                                            <x-ti-trash class="size-4" />
                                        </x-button.circle>
                                    @endif
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

    {{-- Modal Tambah Uang (Dari Luar / Langsung ATAU Ambil dari Kantong Lain) --}}
    <x-modal wire="incomeModal" title="Tambah Uang ke {{ $wallet->name }}" size="lg">
        <form wire:submit="saveIncome" class="space-y-4">
            <x-radio.group
                wire:model.live="income_source_type"
                inline
                color="emerald"
                :options="\App\Enums\IncomeSourceEnum::choices()"
            />

            @if ($income_source_type === \App\Enums\IncomeSourceEnum::FROM_POCKET->value)
                <div wire:key="pocket-show-income-from-pocket-wrap-{{ $wallet->id }}">
                    @if (empty($this->otherActiveWalletOptions))
                        <div class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800 border border-amber-200">
                            Belum ada kantong aktif lain yang dapat dipilih.
                        </div>
                    @else
                        <x-radio.group
                            id="pocket-show-income-pocket-wallet"
                            wire:model.live="income_source_wallet_id"
                            card
                            :columns="2"
                            color="emerald"
                            :options="$this->otherActiveWalletOptions"
                        />
                    @endif
                </div>
            @else
                <div wire:key="pocket-show-income-direct-category-wrap-{{ $wallet->id }}">
                    <x-radio.group
                        id="pocket-show-income-category"
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
    <x-modal wire="expenseModal" title="Catat Pengeluaran dari {{ $wallet->name }}" size="lg">
        <form wire:submit="saveExpense" class="space-y-4">
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
                    hint="Saldo tersedia: {{ $wallet->formattedBalance() }}"
                    placeholder="Nominal (Rp)"
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
                    symbol="Rp"
                    :decimals="0"
                    :precision="0"
                    hint="Saldo tersedia: {{ $wallet->formattedBalance() }}"
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

    {{-- Modal Ubah Kantong --}}
    <x-modal wire="editModal" title="Ubah Kantong" size="lg">
        <form wire:submit="updatePocket" class="space-y-4">
            <x-input label="Nama Kantong *" wire:model="edit_name" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-select.styled
                    label="Ikon Kantong *"
                    wire:model="edit_icon"
                    :options="\App\Enums\WalletIconEnum::choices()"
                />

                <x-select.styled
                    label="Warna Kantong *"
                    wire:model="edit_color"
                    :options="\App\Enums\WalletColorEnum::choices()"
                />
            </div>

            <x-textarea label="Deskripsi Singkat" wire:model="edit_description" rows="2" />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('editModal', false)">Batal</x-button>
                <x-button submit color="emerald" loading="updatePocket">Simpan Perubahan</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Konfirmasi Hapus Kantong --}}
    <x-modal wire="deleteModal" title="Hapus Kantong {{ $wallet->name }}" size="lg">
        @php
            $hasBalance = (float) $wallet->balance > 0;
        @endphp
        <form wire:submit="confirmDeletePocket" class="space-y-4">
            <div class="rounded-xl bg-rose-50 p-4 border border-rose-200 space-y-1">
                <p class="text-sm font-bold text-rose-950">{{ $wallet->name }}</p>
                <p class="text-xs font-semibold text-rose-900 tabular-nums">
                    Saldo Saat Ini: {{ $wallet->formattedBalance() }}
                </p>
            </div>

            @if ($hasBalance)
                <div class="rounded-xl bg-amber-50 p-3.5 border border-amber-200 text-xs text-amber-950 space-y-1">
                    <p class="font-semibold">Pindahkan Sisa Saldo Terlebih Dahulu</p>
                    <p>
                        Pilih kantong tujuan di bawah ini agar sisa saldo sebesar <strong>{{ $wallet->formattedBalance() }}</strong> dipindahkan otomatis sebelum kantong ini dihapus.
                    </p>
                </div>

                <x-select.styled
                    label="Pindahkan Sisa Saldo ke Kantong *"
                    placeholder="Pilih kantong penerima"
                    wire:model="delete_destination_wallet_id"
                    :options="$this->otherActiveWalletOptions"
                    searchable
                />
            @else
                <p class="text-xs text-slate-600">
                    Kantong ini memiliki saldo Rp0 dan siap dihapus. Riwayat transaksi lama tetap tersimpan di halaman Transaksi.
                </p>
            @endif

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('deleteModal', false)">Batal</x-button>
                <x-button submit color="red" loading="confirmDeletePocket">
                    {{ $hasBalance ? 'Pindahkan Saldo & Hapus' : 'Hapus Kantong' }}
                </x-button>
            </div>
        </form>
    </x-modal>
</div>
