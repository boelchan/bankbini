<div class="space-y-6">
    {{-- Header Sapaan & Aksi Cepat --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-800 border border-rose-200 mb-2">
                <x-ti-sparkles class="size-3.5 text-rose-600" />
                <span>Dompet Keluarga Cerdas & Rapi</span>
            </div>
            <h1 class="text-lg font-bold text-slate-900 sm:text-xl">
                Halo, {{ auth()->user()->name }}!
            </h1>
            <p class="mt-0.5 text-sm text-slate-600">
                Kelola alokasi saldo kantong keluarga hari ini dengan tenang dan terukur.
            </p>
        </div>

        {{-- Tombol Aksi Cepat --}}
        <div class="flex flex-wrap items-center gap-2.5">
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
                Transfer Kantong
            </x-button>

            <x-button color="slate" outline wire:click="openPocketModal" class="rounded-xl">
                <x-ti-wallet class="size-4 mr-1" />
                Kantong Baru
            </x-button>
        </div>
    </div>

    {{-- Ringkasan Saldo Utama (4 Kartu Metrik) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {{-- Total Seluruh Saldo --}}
        <x-card class="bg-emerald-300 border-emerald-400">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-950">
                        Total Seluruh Saldo
                    </p>
                    <p class="mt-2 text-2xl font-bold text-emerald-950 tabular-nums truncate">
                        Rp{{ number_format((float) $this->summary['total_balance'], 0, ',', '.') }}
                    </p>
                    <p class="mt-1.5 text-xs font-medium text-emerald-900">
                        Gabungan {{ $this->summary['active_pockets'] }} kantong aktif & {{ $this->summary['frozen_pockets'] }} dibekukan
                    </p>
                </div>
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/75 text-emerald-900 border border-emerald-400">
                    <x-ti-wallet class="size-5" />
                </div>
            </div>
        </x-card>

        {{-- Saldo Masuk Bulan Ini --}}
        <x-card class="bg-sky-300 border-sky-400">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-sky-950">
                        Pemasukan Bulan Ini
                    </p>
                    <p class="mt-2 text-2xl font-bold text-sky-950 tabular-nums truncate">
                        +Rp{{ number_format((float) $this->summary['monthly_income'], 0, ',', '.') }}
                    </p>
                    <p class="mt-1.5 text-xs font-medium text-sky-900">
                        Periode {{ now()->translatedFormat('F Y') }}
                    </p>
                </div>
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/75 text-sky-900 border border-sky-400">
                    <x-ti-arrow-down-left class="size-5" />
                </div>
            </div>
        </x-card>

        {{-- Saldo Keluar Bulan Ini --}}
        <x-card class="bg-rose-300 border-rose-400">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-rose-950">
                        Pengeluaran Bulan Ini
                    </p>
                    <p class="mt-2 text-2xl font-bold text-rose-950 tabular-nums truncate">
                        -Rp{{ number_format((float) $this->summary['monthly_expense'], 0, ',', '.') }}
                    </p>
                    <p class="mt-1.5 text-xs font-medium text-rose-900">
                        Selisih bersih: Rp{{ number_format((float) $this->summary['monthly_net'], 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/75 text-rose-900 border border-rose-400">
                    <x-ti-arrow-up-right class="size-5" />
                </div>
            </div>
        </x-card>

        {{-- Jumlah Kantong Aktif --}}
        <x-card class="bg-amber-300 border-amber-400">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-950">
                        Kantong Keuangan
                    </p>
                    <p class="mt-2 text-2xl font-bold text-amber-950 tabular-nums">
                        {{ $this->summary['active_pockets'] }} Aktif
                    </p>
                    <p class="mt-1.5 text-xs font-medium text-amber-900">
                        {{ $this->summary['frozen_pockets'] }} kantong sedang dibekukan
                    </p>
                </div>
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/75 text-amber-900 border border-amber-400">
                    <x-ti-pig-money class="size-5" />
                </div>
            </div>
        </x-card>
    </div>

    {{-- Seksi Daftar Kantong (Pocket Grid) --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm sm:text-base font-semibold text-slate-900">
                    Daftar Kantong Keluarga
                </h2>
                <p class="text-xs font-medium text-slate-500">
                    Setiap kantong memiliki saldo dan histori pencatatan terpisah.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <x-button sm color="emerald" flat href="{{ route('pockets.index') }}" wire:navigate>
                    Kelola Semua Kantong
                    <x-ti-arrow-right class="size-4 ml-1" />
                </x-button>
            </div>
        </div>

        @if ($this->wallets->isEmpty())
            <x-card>
                <div class="py-8 text-center space-y-3">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <x-ti-wallet class="size-6" />
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-slate-900">Belum Ada Kantong Keuangan</p>
                        <p class="text-xs text-slate-500 max-w-md mx-auto">
                            Mulai pisahkan uang belanja, tabungan rumah, dan dana liburan dengan membuat kantong pertama Anda.
                        </p>
                    </div>
                    <div>
                        <x-button color="emerald" sm wire:click="openPocketModal">
                            <x-ti-plus class="size-4 mr-1" />
                            Buat Kantong Pertama
                        </x-button>
                    </div>
                </div>
            </x-card>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($this->wallets as $wallet)
                    @php
                        $colorEnum = $wallet->colorEnum();
                    @endphp
                    <x-card class="{{ $colorEnum->cardClasses() }} transition-colors">
                        <div class="flex flex-col justify-between h-full space-y-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $colorEnum->iconBadgeClasses() }}">
                                        <x-wallet-icon :icon="$wallet->icon" class="size-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <h3 class="text-sm font-bold text-slate-900 truncate">
                                                {{ $wallet->name }}
                                            </h3>
                                            @if ($wallet->is_primary)
                                                <span class="rounded-md bg-white/80 px-1.5 py-0.5 text-xs font-semibold text-emerald-800 border border-emerald-200">
                                                    Utama
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-600 truncate">
                                            {{ $wallet->description ?: 'Kantong alokasi keluarga' }}
                                        </p>
                                    </div>
                                </div>

                                <x-badge
                                    :text="$wallet->status->label()"
                                    :color="$wallet->status->color()"
                                    sm
                                    light
                                />
                            </div>

                            <div class="pt-1 border-t border-slate-200/70 flex items-end justify-between gap-2">
                                <div>
                                    <p class="text-xs font-medium text-slate-500">Saldo Saat Ini</p>
                                    <p class="text-lg font-bold text-slate-900 tabular-nums">
                                        {{ $wallet->formattedBalance() }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-1">
                                    @if ($wallet->isActive())
                                        <x-button.circle
                                            color="emerald"
                                            flat
                                            sm
                                            wire:click="openIncomeModal({{ $wallet->id }})"
                                            title="Tambah Pemasukan ke {{ $wallet->name }}"
                                        >
                                            <x-ti-plus class="size-4" />
                                        </x-button.circle>
                                    @endif

                                    <x-button
                                        color="slate"
                                        flat
                                        sm
                                        href="{{ route('pockets.show', $wallet) }}"
                                        wire:navigate
                                    >
                                        Detail
                                        <x-ti-chevron-right class="size-3.5 ml-0.5" />
                                    </x-button>
                                </div>
                            </div>
                        </div>
                    </x-card>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Grafik Arus Kas & Transaksi Terbaru --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        {{-- Grafik Pemasukan vs Pengeluaran 6 Bulan Terakhir --}}
        <div class="lg:col-span-5">
            <x-card header="Grafik Pemasukan & Pengeluaran (6 Bulan)">
                <div class="space-y-3">
                    <p class="text-xs text-slate-500">
                        Perbandingan arus kas masuk dan keluar keluarga per bulan (tidak termasuk transfer antar kantong).
                    </p>
                    <x-chart
                        bar
                        :labels="$this->cashflowChart['labels']"
                        :series="$this->cashflowChart['series']"
                        legend
                        grid
                        tooltip
                        :height="260"
                    />
                </div>
            </x-card>
        </div>

        {{-- Daftar Transaksi Terbaru --}}
        <div class="lg:col-span-7">
            <x-card paddingless>
                <x-slot:header>
                    <div class="flex items-center justify-between w-full">
                        <div>
                            <h2 class="text-sm sm:text-base font-semibold text-slate-900">Transaksi Terbaru</h2>
                            <p class="text-xs font-medium text-slate-500">8 aktivitas keuangan terakhir di seluruh kantong</p>
                        </div>
                        <x-button sm color="emerald" flat href="{{ route('transactions.index') }}" wire:navigate>
                            Lihat Semua Transaksi
                            <x-ti-arrow-right class="size-4 ml-1" />
                        </x-button>
                    </div>
                </x-slot:header>

                @if ($this->recentTransactions->isEmpty())
                    <div class="p-8 text-center space-y-2">
                        <p class="text-sm font-semibold text-slate-800">Belum Ada Riwayat Transaksi</p>
                        <p class="text-xs text-slate-500">Catat pemasukan atau pengeluaran pertama Anda melalui tombol di atas.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <th class="py-3 pl-5 pr-3">Transaksi & Kategori</th>
                                    <th class="px-3 py-3">Kantong</th>
                                    <th class="px-3 py-3">Jenis</th>
                                    <th class="px-3 py-3 text-right">Nominal</th>
                                    <th class="py-3 pl-3 pr-5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/70 text-sm">
                                @foreach ($this->recentTransactions as $tx)
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="py-3 pl-5 pr-3">
                                            <div class="font-semibold text-slate-900">{{ $tx->description }}</div>
                                            <div class="mt-0.5 flex items-center gap-2 text-xs text-slate-500">
                                                <span>{{ $tx->transaction_date?->translatedFormat('d M Y, H:i') }}</span>
                                                @if ($tx->category)
                                                    <span>•</span>
                                                    <span class="font-medium text-slate-600">{{ $tx->category->name }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="text-xs font-semibold text-slate-800">
                                                {{ $tx->resolvedWalletName() }}
                                            </span>
                                            @if ($tx->counterpart_wallet_name_snapshot)
                                                <div class="text-xs text-slate-500">
                                                    Lawan: {{ $tx->resolvedCounterpartWalletName() }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            <x-badge
                                                :text="$tx->type->label()"
                                                :color="$tx->type->color()"
                                                sm
                                                light
                                            />
                                        </td>
                                        <td class="px-3 py-3 text-right font-bold tabular-nums {{ $tx->type->isPositive() ? 'text-emerald-700' : 'text-rose-700' }}">
                                            {{ $tx->formattedSignedAmount() }}
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
                @endif
            </x-card>
        </div>
    </div>

    {{-- Modal Tambah Pemasukan Cepat --}}
    <x-modal wire="incomeModal" title="Tambah Pemasukan ke Kantong" size="lg">
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
                    :decimals="0"
                    :precision="0"
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
                placeholder="Contoh: Gaji bulanan suami, Bonus proyek"
            />

            <x-textarea
                label="Catatan Tambahan (Opsional)"
                wire:model="income_notes"
                rows="2"
                placeholder="Catatan opsional untuk pemasukan ini"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('incomeModal', false)">
                    Batal
                </x-button>
                <x-button submit color="emerald" loading="saveIncome">
                    <x-ti-check class="size-4 mr-1" />
                    Simpan Pemasukan
                </x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Catat Pengeluaran Cepat --}}
    <x-modal wire="expenseModal" title="Catat Pengeluaran dari Kantong" size="lg">
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
                    :decimals="0"
                    :precision="0"
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
                placeholder="Contoh: Belanja sayur & lauk mingguan"
            />

            <x-textarea
                label="Catatan Tambahan (Opsional)"
                wire:model="expense_notes"
                rows="2"
                placeholder="Detail barang atau toko (opsional)"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('expenseModal', false)">
                    Batal
                </x-button>
                <x-button submit color="rose" loading="saveExpense">
                    <x-ti-check class="size-4 mr-1" />
                    Simpan Pengeluaran
                </x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Transfer Antar Kantong Cepat --}}
    <x-modal wire="transferModal" title="Transfer Saldo Antar Kantong" size="lg">
        <form wire:submit="saveTransfer" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-select.styled
                    label="Kantong Pengirim *"
                    placeholder="Pilih kantong asal"
                    wire:model="transfer_source_wallet_id"
                    :options="$this->activeWalletOptions"
                    searchable
                />

                <x-select.styled
                    label="Kantong Penerima *"
                    placeholder="Pilih kantong tujuan"
                    wire:model="transfer_destination_wallet_id"
                    :options="$this->activeWalletOptions"
                    searchable
                />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-currency
                    label="Nominal Transfer (Rp) *"
                    wire:model="transfer_amount"
                    locale="id-ID"
                    symbol="Rp"
                    :decimals="0"
                    :precision="0"
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
                placeholder="Contoh: Sisihkan dana tabungan bulan ini"
            />

            <x-textarea
                label="Catatan Tambahan (Opsional)"
                wire:model="transfer_notes"
                rows="2"
                placeholder="Catatan internal keluarga"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('transferModal', false)">
                    Batal
                </x-button>
                <x-button submit color="emerald" loading="saveTransfer">
                    <x-ti-arrows-exchange class="size-4 mr-1" />
                    Proses Transfer
                </x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Buat Kantong Baru Cepat --}}
    <x-modal wire="pocketModal" title="Buat Kantong Keuangan Baru" size="lg">
        <form wire:submit="savePocket" class="space-y-4">
            <x-input
                label="Nama Kantong *"
                wire:model="pocket_name"
                placeholder="Contoh: Kantong Pendidikan Anak, Kantong Belanja Bulanan"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-select.styled
                    label="Ikon Kantong *"
                    wire:model="pocket_icon"
                    :options="\App\Enums\WalletIconEnum::choices()"
                />

                <x-select.styled
                    label="Warna Pastel Kantong *"
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
                hint="Jika diisi lebih dari Rp0, otomatis tercatat sebagai transaksi Saldo Awal."
                placeholder="0"
            />

            <x-textarea
                label="Deskripsi Kantong"
                wire:model="pocket_description"
                rows="2"
                placeholder="Tujuan penggunaan kantong ini"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('pocketModal', false)">
                    Batal
                </x-button>
                <x-button submit color="emerald" loading="savePocket">
                    <x-ti-plus class="size-4 mr-1" />
                    Buat Kantong
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
                    @if ($tx->transfer_reference)
                        <div>
                            <p class="text-xs font-medium text-slate-500">Kode Referensi Transfer</p>
                            <p class="font-semibold text-slate-800 tabular-nums">{{ $tx->transfer_reference }}</p>
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
                    <x-button color="slate" flat wire:click="$set('detailModal', false)">
                        Tutup
                    </x-button>
                </div>
            </div>
        @endif
    </x-modal>
</div>
