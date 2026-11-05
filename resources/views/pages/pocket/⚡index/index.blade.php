<div class="space-y-6">
    {{-- Header & Breadcrumb --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-bold text-slate-900 sm:text-xl">Manajemen Kantong (Pocket)</h1>
            <nav class="flex items-center gap-1.5 text-sm text-slate-500 mt-1">
                <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-emerald-600 transition-colors">
                    <x-ti-home class="size-4" />
                </a>
                <span>/</span>
                <span class="text-slate-700 font-medium">Kantong Keuangan</span>
            </nav>
        </div>

        <div class="flex items-center gap-2">
            <x-button color="sky" light href="{{ route('transfers.index') }}" wire:navigate class="rounded-xl">
                <x-ti-arrows-exchange class="size-4 mr-1" />
                Transfer Antar Kantong
            </x-button>

            <x-button color="emerald" wire:click="openCreateModal" class="rounded-xl">
                <x-ti-plus class="size-4 mr-1" />
                Tambah Kantong Baru
            </x-button>
        </div>
    </div>

    {{-- Ringkasan Saldo Kantong --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-card class="bg-emerald-300 border-emerald-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-950">Total Saldo Seluruh Kantong</p>
                    <p class="mt-1.5 text-2xl font-bold text-emerald-950 tabular-nums">
                        Rp{{ number_format((float) $this->pocketSummary['total_balance'], 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs font-medium text-emerald-900">
                        Termasuk kantong aktif & dibekukan
                    </p>
                </div>
                <div class="flex size-10 items-center justify-center rounded-xl bg-white/75 text-emerald-900 border border-emerald-400">
                    <x-ti-wallet class="size-5" />
                </div>
            </div>
        </x-card>

        <x-card class="bg-sky-300 border-sky-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-sky-950">Saldo Kantong Aktif ({{ $this->pocketSummary['active_count'] }})</p>
                    <p class="mt-1.5 text-2xl font-bold text-sky-950 tabular-nums">
                        Rp{{ number_format((float) $this->pocketSummary['active_balance'], 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs font-medium text-sky-900">
                        Siap digunakan untuk transaksi harian
                    </p>
                </div>
                <div class="flex size-10 items-center justify-center rounded-xl bg-white/75 text-sky-900 border border-sky-400">
                    <x-ti-check class="size-5" />
                </div>
            </div>
        </x-card>

        <x-card class="bg-amber-300 border-amber-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-950">Saldo Kantong Dibekukan ({{ $this->pocketSummary['frozen_count'] }})</p>
                    <p class="mt-1.5 text-2xl font-bold text-amber-950 tabular-nums">
                        Rp{{ number_format((float) $this->pocketSummary['frozen_balance'], 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs font-medium text-amber-900">
                        Terkunci aman dari pengeluaran & transfer
                    </p>
                </div>
                <div class="flex size-10 items-center justify-center rounded-xl bg-white/75 text-amber-900 border border-amber-400">
                    <x-ti-snowflake class="size-5" />
                </div>
            </div>
        </x-card>
    </div>

    {{-- Filter & Pencarian Kantong --}}
    <x-card>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-12 items-end">
            <div class="sm:col-span-7">
                <x-input
                    label="Cari Kantong"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Ketik nama atau deskripsi kantong..."
                />
            </div>

            <div class="sm:col-span-4">
                <x-select.styled
                    label="Filter Status"
                    placeholder="Semua Status (Aktif & Dibekukan)"
                    wire:model.live="statusFilter"
                    :options="\App\Enums\WalletStatusEnum::activeAndFrozenChoices()"
                />
            </div>

            <div class="sm:col-span-1 flex justify-end">
                <x-button
                    color="slate"
                    outline
                    wire:click="$set('search', ''); $set('statusFilter', '')"
                    title="Reset Filter"
                    class="w-full"
                >
                    Reset
                </x-button>
            </div>
        </div>
    </x-card>

    {{-- Grid Kartu Kantong --}}
    @if ($this->wallets->isEmpty())
        <x-card>
            <div class="py-10 text-center space-y-3">
                <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-700 border border-rose-200">
                    <x-ti-wallet class="size-6" />
                </div>
                <div class="space-y-1">
                    <h3 class="text-sm font-semibold text-slate-900">Tidak Ada Kantong yang Ditemukan</h3>
                    <p class="text-xs text-slate-500 max-w-md mx-auto">
                        Belum ada kantong yang sesuai dengan kata kunci pencarian Anda, atau buat kantong baru untuk pos keuangan keluarga.
                    </p>
                </div>
                <x-button color="emerald" sm wire:click="openCreateModal">
                    <x-ti-plus class="size-4 mr-1" />
                    Tambah Kantong Baru
                </x-button>
            </div>
        </x-card>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->wallets as $wallet)
                @php
                    $colorEnum = $wallet->colorEnum();
                @endphp
                <x-card class="{{ $colorEnum->cardClasses() }}">
                    <div class="flex flex-col justify-between h-full space-y-4">
                        {{-- Bagian Atas: Ikon, Nama, Status --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl {{ $colorEnum->iconBadgeClasses() }}">
                                    <x-wallet-icon :icon="$wallet->icon" class="size-5" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <h2 class="text-base font-bold text-slate-900 truncate">
                                            {{ $wallet->name }}
                                        </h2>
                                        @if ($wallet->is_primary)
                                            <span class="rounded-md bg-white/90 px-2 py-0.5 text-xs font-semibold text-emerald-800 border border-emerald-200">
                                                Utama
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-600 line-clamp-2">
                                        {{ $wallet->description ?: 'Pos penyimpanan dana keluarga' }}
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

                        {{-- Bagian Tengah: Saldo & Statistik Ringkas --}}
                        <div class="rounded-xl bg-white/80 p-3.5 border border-slate-200/80 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-slate-500">Saldo Saat Ini</p>
                                <p class="text-xl font-bold text-slate-900 tabular-nums mt-0.5">
                                    {{ $wallet->formattedBalance() }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-medium text-slate-500">Total Aktivitas</p>
                                <p class="text-sm font-semibold text-slate-800 tabular-nums mt-0.5">
                                    {{ $wallet->transactions_count }} transaksi
                                </p>
                            </div>
                        </div>

                        {{-- Bagian Bawah: Tombol Aksi --}}
                        <div class="flex items-center justify-between gap-2 pt-1">
                            <x-button
                                color="emerald"
                                flat
                                sm
                                href="{{ route('pockets.show', $wallet) }}"
                                wire:navigate
                            >
                                <x-ti-eye class="size-4 mr-1" />
                                Detail & Histori
                            </x-button>

                            <div class="flex items-center gap-1.5">
                                <x-button.circle
                                    color="emerald"
                                    flat
                                    sm
                                    wire:click="openEditModal({{ $wallet->id }})"
                                    title="Ubah Kantong"
                                >
                                    <x-ti-edit class="size-4" />
                                </x-button.circle>

                                <x-button.circle
                                    :color="$wallet->isFrozen() ? 'emerald' : 'amber'"
                                    flat
                                    sm
                                    wire:click="toggleFreeze({{ $wallet->id }})"
                                    :title="$wallet->isFrozen() ? 'Aktifkan Kembali Kantong' : 'Bekukan Kantong'"
                                >
                                    @if ($wallet->isFrozen())
                                        <x-ti-player-play class="size-4" />
                                    @else
                                        <x-ti-snowflake class="size-4" />
                                    @endif
                                </x-button.circle>

                                <x-button.circle
                                    color="red"
                                    flat
                                    sm
                                    wire:click="openDeleteModal({{ $wallet->id }})"
                                    title="Hapus / Tutup Kantong"
                                >
                                    <x-ti-trash class="size-4" />
                                </x-button.circle>
                            </div>
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif

    {{-- Modal Tambah Kantong --}}
    <x-modal wire="createModal" title="Tambah Kantong Keuangan Baru" size="lg">
        <form wire:submit="createPocket" class="space-y-4">
            <x-input
                label="Nama Kantong *"
                wire:model="name"
                placeholder="Contoh: Kantong Liburan, Kantong Pendidikan Anak"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-select.styled
                    label="Ikon Kantong *"
                    wire:model="icon"
                    :options="\App\Enums\WalletIconEnum::choices()"
                />

                <x-select.styled
                    label="Warna Kartu Kantong *"
                    wire:model="color"
                    :options="\App\Enums\WalletColorEnum::choices()"
                />
            </div>

            <x-currency
                label="Saldo Awal (Opsional)"
                wire:model="initial_balance"
                locale="id-ID"
                symbol="Rp"
                :decimals="0"
                :precision="0"
                hint="Kosongkan atau isi 0 jika ingin memulai tanpa saldo awal."
                placeholder="0"
            />

            <x-textarea
                label="Deskripsi Kantong"
                wire:model="description"
                rows="2"
                placeholder="Penjelasan singkat penggunaan kantong ini"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('createModal', false)">
                    Batal
                </x-button>
                <x-button submit color="emerald" loading="createPocket">
                    <x-ti-check class="size-4 mr-1" />
                    Simpan Kantong
                </x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Ubah Kantong --}}
    <x-modal wire="editModal" title="Ubah Informasi Kantong" size="lg">
        <form wire:submit="updatePocket" class="space-y-4">
            <div class="rounded-xl bg-sky-50 p-3 text-xs text-sky-900 border border-sky-200">
                Perubahan nama, ikon, warna, atau deskripsi kantong tidak akan mengubah catatan histori transaksi sebelumnya.
            </div>

            <x-input
                label="Nama Kantong *"
                wire:model="edit_name"
                placeholder="Nama kantong"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-select.styled
                    label="Ikon Kantong *"
                    wire:model="edit_icon"
                    :options="\App\Enums\WalletIconEnum::choices()"
                />

                <x-select.styled
                    label="Warna Kartu Kantong *"
                    wire:model="edit_color"
                    :options="\App\Enums\WalletColorEnum::choices()"
                />
            </div>

            <x-textarea
                label="Deskripsi Kantong"
                wire:model="edit_description"
                rows="2"
                placeholder="Penjelasan penggunaan kantong"
            />

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <x-button color="slate" flat wire:click="$set('editModal', false)">
                    Batal
                </x-button>
                <x-button submit color="emerald" loading="updatePocket">
                    <x-ti-check class="size-4 mr-1" />
                    Simpan Perubahan
                </x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Konfirmasi Hapus / Tutup Kantong --}}
    <x-modal wire="deleteModal" title="Konfirmasi Penutupan & Penghapusan Kantong" size="lg">
        @if ($this->deletingWallet)
            @php
                $targetWallet = $this->deletingWallet;
                $hasBalance = (float) $targetWallet->balance > 0;
            @endphp
            <form wire:submit="confirmDeletePocket" class="space-y-4">
                <div class="rounded-xl bg-rose-50 p-4 border border-rose-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-rose-900">Kantong yang Akan Ditutup</span>
                        <x-badge :text="$targetWallet->status->label()" :color="$targetWallet->status->color()" sm light />
                    </div>
                    <p class="text-base font-bold text-rose-950">{{ $targetWallet->name }}</p>
                    <p class="text-sm font-semibold text-rose-900 tabular-nums">
                        Sisa Saldo Saat Ini: {{ $targetWallet->formattedBalance() }}
                    </p>
                </div>

                @if ($hasBalance)
                    <div class="rounded-xl bg-amber-50 p-3.5 border border-amber-200 text-xs text-amber-950 space-y-1">
                        <p class="font-semibold">Perhatian: Kantong Masih Memiliki Saldo</p>
                        <p>
                            Sesuai aturan integritas keuangan, saldo kantong yang ditutup harus menjadi <strong>Rp0</strong>.
                            Pilih kantong aktif tujuan di bawah ini untuk memindahkan seluruh sisa saldo secara otomatis sebelum kantong ditutup.
                        </p>
                        @if ($targetWallet->isFrozen())
                            <p class="text-rose-700 font-semibold mt-1">
                                Catatan: Kantong ini sedang dibekukan. Aktifkan kembali kantong terlebih dahulu jika ingin memindahkan saldonya.
                            </p>
                        @endif
                    </div>

                    <x-select.styled
                        label="Pindahkan Sisa Saldo ke Kantong *"
                        placeholder="Pilih kantong aktif penerima saldo"
                        wire:model="delete_destination_wallet_id"
                        :options="$this->destinationWalletOptions"
                        searchable
                    />
                @else
                    <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-200 text-xs text-slate-700 space-y-1">
                        <p class="font-semibold text-slate-900">Ketentuan Penghapusan Kantong:</p>
                        <ul class="list-disc pl-4 space-y-1">
                            <li>Kantong yang dihapus tidak lagi tampil pada daftar kantong aktif dan dasbor.</li>
                            <li>Kantong tidak dapat digunakan kembali untuk transaksi baru.</li>
                            <li>Seluruh histori transaksi lama tetap tersimpan utuh di database dan dapat dilihat pada Riwayat Transaksi Global.</li>
                        </ul>
                    </div>
                @endif

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                    <x-button color="slate" flat wire:click="$set('deleteModal', false)">
                        Batal
                    </x-button>
                    <x-button submit color="red" loading="confirmDeletePocket">
                        <x-ti-trash class="size-4 mr-1" />
                        {{ $hasBalance ? 'Pindahkan Saldo & Hapus Kantong' : 'Tutup & Hapus Kantong' }}
                    </x-button>
                </div>
            </form>
        @endif
    </x-modal>
</div>

