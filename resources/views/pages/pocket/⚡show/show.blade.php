@php
    $colorEnum = $wallet->colorEnum();
@endphp

<div class="space-y-6">
    {{-- Header & Breadcrumb --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-bold text-slate-900 sm:text-xl">Detail Kantong: {{ $wallet->name }}</h1>
            <nav class="flex items-center gap-1.5 text-sm text-slate-500 mt-1">
                <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-emerald-600 transition-colors">
                    <x-ti-home class="size-4" />
                </a>
                <span>/</span>
                <a href="{{ route('pockets.index') }}" wire:navigate class="hover:text-emerald-600 transition-colors">
                    Kantong Keuangan
                </a>
                <span>/</span>
                <span class="text-slate-700 font-medium">{{ $wallet->name }}</span>
            </nav>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-button color="slate" outline sm href="{{ route('pockets.index') }}" wire:navigate>
                <x-ti-arrow-left class="size-4 mr-1" />
                Kembali ke Daftar
            </x-button>

            <x-button color="emerald" flat sm wire:click="openEditModal">
                <x-ti-edit class="size-4 mr-1" />
                Ubah Info
            </x-button>

            <x-button
                :color="$wallet->isFrozen() ? 'emerald' : 'amber'"
                flat
                sm
                wire:click="toggleFreeze"
            >
                @if ($wallet->isFrozen())
                    <x-ti-player-play class="size-4 mr-1" />
                    Aktifkan Kantong
                @else
                    <x-ti-snowflake class="size-4 mr-1" />
                    Bekukan Kantong
                @endif
            </x-button>

            <x-button color="red" flat sm wire:click="openDeleteModal">
                <x-ti-trash class="size-4 mr-1" />
                Hapus Kantong
            </x-button>
        </div>
    </div>

    {{-- Banner Peringatan Jika Kantong Dibekukan --}}
    @if ($wallet->isFrozen())
        <div class="rounded-2xl bg-amber-50 p-4 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-200/70 text-amber-900">
                    <x-ti-snowflake class="size-5" />
                </div>
                <div>
                    <p class="text-sm font-bold text-amber-950">Kantong Ini Sedang Dibekukan</p>
                    <p class="text-xs text-amber-900 mt-0.5">
                        Selama dibekukan, kantong ini tidak dapat menerima pemasukan, melakukan pengeluaran, ataupun menjadi pengirim/penerima transfer. Saldo tetap tersimpan aman dan dihitung dalam total saldo.
                    </p>
                </div>
            </div>
            <x-button color="emerald" sm wire:click="toggleFreeze" class="shrink-0">
                Aktifkan Kembali
            </x-button>
        </div>
    @endif

    {{-- Kartu Utama Kantong & Tombol Transaksi --}}
    <x-card class="{{ $colorEnum->cardClasses() }}">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl {{ $colorEnum->iconBadgeClasses() }}">
                    <x-wallet-icon :icon="$wallet->icon" class="size-7" />
                </div>
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-lg font-bold text-slate-900">{{ $wallet->name }}</h2>
                        <x-badge :text="$wallet->status->label()" :color="$wallet->status->color()" sm light />
                        @if ($wallet->is_primary)
                            <span class="rounded-md bg-white/90 px-2 py-0.5 text-xs font-semibold text-emerald-800 border border-emerald-200">
                                Kantong Utama
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-700">
                        {{ $wallet->description ?: 'Pos alokasi keuangan keluarga' }}
                    </p>
                    <p class="text-xs text-slate-500">
                        Saldo awal pembuatan: <span class="font-semibold text-slate-700 tabular-nums">{{ $wallet->formattedInitialBalance() }}</span>
                    </p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-4 lg:gap-6 border-t lg:border-t-0 pt-4 lg:pt-0 border-slate-200/80">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Saldo Saat Ini</p>
                    <p class="text-2xl font-bold text-slate-900 tabular-nums mt-0.5">
                        {{ $wallet->formattedBalance() }}
                    </p>
                </div>

                @if ($wallet->isActive())
                    <div class="flex flex-wrap items-center gap-2">
                        <x-button color="emerald" sm wire:click="openIncomeModal" class="rounded-xl">
                            <x-ti-plus class="size-4 mr-1" />
                            Pemasukan
                        </x-button>
                        <x-button color="rose" light sm wire:click="openExpenseModal" class="rounded-xl">
                            <x-ti-minus class="size-4 mr-1" />
                            Pengeluaran
                        </x-button>
                        <x-button color="sky" light sm wire:click="openTransferModal" class="rounded-xl">
                            <x-ti-arrows-exchange class="size-4 mr-1" />
                            Transfer Keluar
                        </x-button>
                    </div>
                @endif
            </div>
        </div>
    </x-card>

    {{-- Statistik Arus Dana Kantong --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-card>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Pemasukan</p>
            <p class="mt-1.5 text-lg font-bold text-emerald-700 tabular-nums">
                +Rp{{ number_format((float) $this->walletStats['income_total'], 0, ',', '.') }}
            </p>
            <p class="mt-0.5 text-xs text-slate-500">Dana masuk eksternal</p>
        </x-card>

        <x-card>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Pengeluaran</p>
            <p class="mt-1.5 text-lg font-bold text-rose-700 tabular-nums">
                -Rp{{ number_format((float) $this->walletStats['expense_total'], 0, ',', '.') }}
            </p>
            <p class="mt-0.5 text-xs text-slate-500">Pembayaran & belanja</p>
        </x-card>

        <x-card>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Transfer Masuk</p>
            <p class="mt-1.5 text-lg font-bold text-sky-700 tabular-nums">
                +Rp{{ number_format((float) $this->walletStats['transfer_in_total'], 0, ',', '.') }}
            </p>
            <p class="mt-0.5 text-xs text-slate-500">Kiriman dari kantong lain</p>
        </x-card>

        <x-card>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Transfer Keluar</p>
            <p class="mt-1.5 text-lg font-bold text-amber-700 tabular-nums">
                -Rp{{ number_format((float) $this->walletStats['transfer_out_total'], 0, ',', '.') }}
            </p>
            <p class="mt-0.5 text-xs text-slate-500">Dipindahkan ke kantong lain</p>
        </x-card>
    </div>

    {{-- Tabel Histori Transaksi Kantong & Log Aktivitas --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        {{-- Histori Transaksi Kantong --}}
        <div class="lg:col-span-8">
            <x-card paddingless>
                <x-slot:header>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between w-full">
                        <div>
                            <h3 class="text-sm sm:text-base font-semibold text-slate-900">Histori Transaksi Kantong</h3>
                            <p class="text-xs text-slate-500">Catatan mutasi saldo lengkap beserta saldo sebelum dan sesudah</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2 sm:w-80">
                            <x-input
                                wire:model.live.debounce.300ms="search"
                                placeholder="Cari keterangan / kode..."
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
                        <p class="text-sm font-semibold text-slate-800">Belum Ada Transaksi pada Kantong Ini</p>
                        <p class="text-xs text-slate-500">Gunakan tombol Pemasukan, Pengeluaran, atau Transfer untuk mulai mencatat transaksi.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <th class="py-3 pl-5 pr-3">Tanggal & Keterangan</th>
                                    <th class="px-3 py-3">Jenis</th>
                                    <th class="px-3 py-3 text-right">Nominal</th>
                                    <th class="px-3 py-3 text-right">Saldo Akhir</th>
                                    <th class="py-3 pl-3 pr-5 text-right">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/70 text-sm">
                                @foreach ($this->transactions as $tx)
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="py-3 pl-5 pr-3">
                                            <div class="font-semibold text-slate-900">{{ $tx->description }}</div>
                                            <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                <span class="tabular-nums">{{ $tx->transaction_code }}</span>
                                                <span>•</span>
                                                <span>{{ $tx->transaction_date?->translatedFormat('d M Y, H:i') }}</span>
                                                @if ($tx->category)
                                                    <span>•</span>
                                                    <span class="font-medium text-slate-700">{{ $tx->category->name }}</span>
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
                                            <x-button.circle
                                                color="slate"
                                                flat
                                                sm
                                                wire:click="showTransactionDetail({{ $tx->id }})"
                                                title="Detail Transaksi"
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
        </div>

        {{-- Log Aktivitas Pengelolaan Kantong --}}
        <div class="lg:col-span-4">
            <x-card header="Log Aktivitas Kantong">
                @if ($this->activityLogs->isEmpty())
                    <p class="text-xs text-slate-500 py-4 text-center">Belum ada riwayat aktivitas pengelolaan.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($this->activityLogs as $log)
                            <div class="rounded-xl bg-slate-50 p-3 border border-slate-200/80 space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-emerald-800">
                                        {{ str_replace('_', ' ', $log->activity) }}
                                    </span>
                                    <span class="text-xs text-slate-500 tabular-nums">
                                        {{ $log->created_at?->translatedFormat('d M Y, H:i') }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-700">{{ $log->description }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    {{-- Modal Tambah Pemasukan --}}
    <x-modal wire="incomeModal" title="Tambah Pemasukan ke {{ $wallet->name }}" size="lg">
        <form wire:submit="saveIncome" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-select.styled
                    label="Kategori Pemasukan *"
                    placeholder="Pilih kategori"
                    wire:model="income_category_id"
                    :options="$this->incomeCategoryOptions"
                    searchable
                />

                <x-input
                    type="datetime-local"
                    label="Tanggal & Waktu *"
                    wire:model="income_date"
                />
            </div>

            <x-currency
                label="Nominal Pemasukan (Rp) *"
                wire:model="income_amount"
                locale="id-ID"
                symbol="Rp"
                :decimals="0" :precision="0"
                placeholder="0"
            />

            <x-input
                label="Keterangan Pemasukan *"
                wire:model="income_description"
                placeholder="Contoh: Gaji bulanan, Bonus"
            />

            <x-textarea
                label="Catatan Tambahan (Opsional)"
                wire:model="income_notes"
                rows="2"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('incomeModal', false)">Batal</x-button>
                <x-button submit color="emerald" loading="saveIncome">Simpan Pemasukan</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Catat Pengeluaran --}}
    <x-modal wire="expenseModal" title="Catat Pengeluaran dari {{ $wallet->name }}" size="lg">
        <form wire:submit="saveExpense" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-select.styled
                    label="Kategori Pengeluaran *"
                    placeholder="Pilih kategori"
                    wire:model="expense_category_id"
                    :options="$this->expenseCategoryOptions"
                    searchable
                />

                <x-input
                    type="datetime-local"
                    label="Tanggal & Waktu *"
                    wire:model="expense_date"
                />
            </div>

            <x-currency
                label="Nominal Pengeluaran (Rp) *"
                wire:model="expense_amount"
                locale="id-ID"
                symbol="Rp"
                :decimals="0" :precision="0"
                hint="Saldo tersedia: {{ $wallet->formattedBalance() }}"
                placeholder="0"
            />

            <x-input
                label="Keterangan Pengeluaran *"
                wire:model="expense_description"
                placeholder="Contoh: Belanja kebutuhan dapur"
            />

            <x-textarea
                label="Catatan Tambahan (Opsional)"
                wire:model="expense_notes"
                rows="2"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('expenseModal', false)">Batal</x-button>
                <x-button submit color="rose" loading="saveExpense">Simpan Pengeluaran</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Transfer Keluar dari Kantong Ini --}}
    <x-modal wire="transferModal" title="Transfer dari {{ $wallet->name }}" size="lg">
        <form wire:submit="saveTransfer" class="space-y-4">
            <x-select.styled
                label="Kantong Tujuan *"
                placeholder="Pilih kantong aktif penerima"
                wire:model="transfer_destination_wallet_id"
                :options="$this->otherActiveWalletOptions"
                searchable
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-currency
                    label="Nominal Transfer (Rp) *"
                    wire:model="transfer_amount"
                    locale="id-ID"
                    symbol="Rp"
                    :decimals="0" :precision="0"
                    hint="Saldo tersedia: {{ $wallet->formattedBalance() }}"
                    placeholder="0"
                />

                <x-input
                    type="datetime-local"
                    label="Tanggal & Waktu Transfer *"
                    wire:model="transfer_date"
                />
            </div>

            <x-input
                label="Keterangan Transfer"
                wire:model="transfer_description"
                placeholder="Contoh: Alokasi dana tabungan"
            />

            <x-textarea
                label="Catatan Tambahan (Opsional)"
                wire:model="transfer_notes"
                rows="2"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('transferModal', false)">Batal</x-button>
                <x-button submit color="emerald" loading="saveTransfer">Proses Transfer</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Ubah Kantong --}}
    <x-modal wire="editModal" title="Ubah Informasi Kantong" size="lg">
        <form wire:submit="updatePocket" class="space-y-4">
            <x-input label="Nama Kantong *" wire:model="edit_name" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-select.styled
                    label="Ikon Kantong *"
                    wire:model="edit_icon"
                    :options="\App\Enums\WalletIconEnum::choices()"
                />

                <x-select.styled
                    label="Warna Kartu *"
                    wire:model="edit_color"
                    :options="\App\Enums\WalletColorEnum::choices()"
                />
            </div>

            <x-textarea label="Deskripsi Kantong" wire:model="edit_description" rows="2" />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('editModal', false)">Batal</x-button>
                <x-button submit color="emerald" loading="updatePocket">Simpan Perubahan</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Konfirmasi Hapus Kantong --}}
    <x-modal wire="deleteModal" title="Konfirmasi Hapus Kantong {{ $wallet->name }}" size="lg">
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
                    <p class="font-semibold">Wajib Pindahkan Sisa Saldo Terlebih Dahulu</p>
                    <p>
                        Pilih kantong aktif tujuan di bawah ini agar sisa saldo sebesar <strong>{{ $wallet->formattedBalance() }}</strong> dipindahkan otomatis dan saldo kantong ini menjadi Rp0 sebelum ditutup.
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
                    Kantong ini memiliki saldo Rp0 dan siap ditutup. Seluruh riwayat transaksi lama tetap tersimpan di database dan dapat dilihat pada Riwayat Transaksi Global.
                </p>
            @endif

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('deleteModal', false)">Batal</x-button>
                <x-button submit color="red" loading="confirmDeletePocket">
                    {{ $hasBalance ? 'Pindahkan Saldo & Hapus Kantong' : 'Hapus Kantong' }}
                </x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Detail Transaksi --}}
    <x-modal wire="detailModal" title="Rincian Transaksi" size="md">
        @if ($this->selectedTransaction)
            @php
                $tx = $this->selectedTransaction;
            @endphp
            <div class="space-y-4 text-sm">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div>
                        <p class="text-xs font-medium text-slate-500">Kode Transaksi</p>
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
                    @if ($tx->counterpart_wallet_name_snapshot)
                        <div>
                            <p class="text-xs font-medium text-slate-500">Kantong Terkait</p>
                            <p class="font-semibold text-slate-800">{{ $tx->resolvedCounterpartWalletName() }}</p>
                        </div>
                    @endif
                </div>

                <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Nominal</span>
                        <span class="text-base font-bold tabular-nums {{ $tx->type->isPositive() ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $tx->formattedSignedAmount() }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Saldo Sebelum</span>
                        <span class="font-semibold text-slate-700 tabular-nums">{{ $tx->formattedBalanceBefore() }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Saldo Sesudah</span>
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

