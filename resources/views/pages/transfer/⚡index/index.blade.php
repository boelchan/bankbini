@php
    $sourceWallet = $this->selectedSourceWallet;
    $destWallet = $this->selectedDestinationWallet;
    $numericAmount = is_numeric($amount) ? (float) $amount : 0.0;
    $sourceAfter = $sourceWallet ? ((float) $sourceWallet->balance - $numericAmount) : 0.0;
    $destAfter = $destWallet ? ((float) $destWallet->balance + $numericAmount) : 0.0;
    $isInsufficient = $sourceWallet && $numericAmount > (float) $sourceWallet->balance;
@endphp

<div class="space-y-6">
    {{-- Header & Breadcrumb --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-bold text-slate-900 sm:text-xl">Transfer Antar Kantong</h1>
            <nav class="flex items-center gap-1.5 text-sm text-slate-500 mt-1">
                <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-emerald-600 transition-colors">
                    <x-ti-home class="size-4" />
                </a>
                <span>/</span>
                <span class="text-slate-700 font-medium">Transfer Antar Kantong</span>
            </nav>
        </div>

        <x-button color="slate" outline sm href="{{ route('pockets.index') }}" wire:navigate class="rounded-xl">
            <x-ti-wallet class="size-4 mr-1" />
            Lihat Semua Kantong
        </x-button>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        {{-- Kolom Kiri: Formulir Transfer Antar Kantong --}}
        <div class="lg:col-span-5 space-y-4">
            <x-card header="Formulir Pemindahan Saldo">
                @if ($this->activeWallets->count() < 2)
                    <div class="py-6 text-center space-y-3">
                        <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 border border-amber-200">
                            <x-ti-alert-circle class="size-6" />
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-slate-900">Butuh Minimal 2 Kantong Aktif</p>
                            <p class="text-xs text-slate-500">
                                Untuk melakukan transfer antar kantong, pastikan Anda memiliki minimal dua kantong berstatus Aktif.
                            </p>
                        </div>
                        <x-button color="emerald" sm href="{{ route('pockets.index') }}" wire:navigate>
                            Kelola Kantong
                        </x-button>
                    </div>
                @else
                    <form wire:submit="submitTransfer" class="space-y-4">
                        <x-select.styled
                            label="Kantong Pengirim (Sumber Dana) *"
                            placeholder="Pilih kantong pengirim"
                            wire:model.live="source_wallet_id"
                            :options="$this->activeWalletOptions"
                            searchable
                        />

                        {{-- Tombol Tukar Kantong --}}
                        <div class="flex items-center justify-center">
                            <button
                                type="button"
                                wire:click="swapWallets"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 border border-slate-200 transition-colors"
                                title="Tukar Kantong Pengirim dan Penerima"
                            >
                                <x-ti-arrows-up-down class="size-4" />
                                Tukar Pengirim & Penerima
                            </button>
                        </div>

                        <x-select.styled
                            label="Kantong Penerima (Tujuan Dana) *"
                            placeholder="Pilih kantong penerima"
                            wire:model.live="destination_wallet_id"
                            :options="$this->activeWalletOptions"
                            searchable
                        />

                        {{-- Simulasi Saldo Sebelum & Sesudah Transfer --}}
                        @if ($sourceWallet || $destWallet)
                            <div class="rounded-2xl bg-slate-50 p-3.5 border border-slate-200 space-y-2.5 text-xs">
                                <p class="font-semibold text-slate-700 uppercase tracking-wider">Simulasi Perubahan Saldo</p>

                                @if ($sourceWallet)
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-600 font-medium">Pengirim: {{ $sourceWallet->name }}</span>
                                        <span class="font-bold tabular-nums {{ $isInsufficient ? 'text-rose-700' : 'text-slate-900' }}">
                                            {{ $sourceWallet->formattedBalance() }} → Rp{{ number_format($sourceAfter, 0, ',', '.') }}
                                        </span>
                                    </div>
                                @endif

                                @if ($destWallet)
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-600 font-medium">Penerima: {{ $destWallet->name }}</span>
                                        <span class="font-bold text-emerald-700 tabular-nums">
                                            {{ $destWallet->formattedBalance() }} → Rp{{ number_format($destAfter, 0, ',', '.') }}
                                        </span>
                                    </div>
                                @endif

                                @if ($isInsufficient)
                                    <div class="rounded-lg bg-rose-100/80 px-2.5 py-1.5 text-rose-900 font-semibold border border-rose-200">
                                        Saldo kantong pengirim tidak mencukupi untuk nominal ini.
                                    </div>
                                @endif
                            </div>
                        @endif

                        <x-currency
                            label="Nominal Transfer (Rp) *"
                            wire:model.live.debounce.250ms="amount"
                            locale="id-ID"
                            symbol="Rp"
                            :decimals="0" :precision="0"
                            placeholder="0"
                        />

                        <x-input
                            type="datetime-local"
                            label="Tanggal & Waktu Transfer *"
                            wire:model="transaction_date"
                        />

                        <x-input
                            label="Keterangan Transfer"
                            wire:model="description"
                            placeholder="Contoh: Alokasi tabungan pendidikan bulan ini"
                        />

                        <x-textarea
                            label="Catatan Tambahan (Opsional)"
                            wire:model="notes"
                            rows="2"
                            placeholder="Catatan internal keluarga"
                        />

                        <div class="pt-2">
                            <x-button submit color="emerald" loading="submitTransfer" class="w-full rounded-xl">
                                <x-ti-arrows-exchange class="size-4 mr-1.5" />
                                Proses Transfer Sekarang
                            </x-button>
                        </div>
                    </form>
                @endif
            </x-card>

            <x-card class="bg-sky-50 border-sky-200">
                <div class="flex items-start gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white text-sky-700 border border-sky-200">
                        <x-ti-shield-check class="size-5" />
                    </div>
                    <div class="space-y-1 text-xs text-sky-950">
                        <p class="font-bold">Jaminan Integritas Transfer</p>
                        <p class="text-sky-900">
                            Setiap transfer memproses transaksi keluar dan transaksi masuk secara atomik dengan satu kode referensi unik. Total saldo seluruh kantong tidak berubah karena merupakan perpindahan dana internal.
                        </p>
                    </div>
                </div>
            </x-card>
        </div>

        {{-- Kolom Kanan: Riwayat Transfer Antar Kantong --}}
        <div class="lg:col-span-7">
            <x-card paddingless>
                <x-slot:header>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between w-full">
                        <div>
                            <h2 class="text-sm sm:text-base font-semibold text-slate-900">Riwayat Transfer Antar Kantong</h2>
                            <p class="text-xs text-slate-500">Daftar perpindahan saldo internal beserta pasangan transaksinya</p>
                        </div>
                        <div class="sm:w-64">
                            <x-input
                                wire:model.live.debounce.300ms="search"
                                placeholder="Cari kode TRF / kantong..."
                            />
                        </div>
                    </div>
                </x-slot:header>

                @if ($this->transfers->isEmpty())
                    <div class="p-10 text-center space-y-2">
                        <p class="text-sm font-semibold text-slate-800">Belum Ada Riwayat Transfer</p>
                        <p class="text-xs text-slate-500">Gunakan formulir di sebelah kiri untuk memindahkan dana antar kantong aktif.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <th class="py-3.5 pl-5 pr-3">Kode & Waktu</th>
                                    <th class="px-3 py-3.5">Alur Perpindahan Kantong</th>
                                    <th class="px-3 py-3.5 text-right">Nominal</th>
                                    <th class="py-3.5 pl-3 pr-5 text-right">Rincian</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/70 text-sm">
                                @foreach ($this->transfers as $trf)
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="py-3.5 pl-5 pr-3 whitespace-nowrap">
                                            <div class="font-bold text-slate-900 tabular-nums text-xs">{{ $trf->transfer_code }}</div>
                                            <div class="text-xs text-slate-500 mt-0.5">
                                                {{ $trf->transaction_date?->translatedFormat('d M Y, H:i') }}
                                            </div>
                                        </td>

                                        <td class="px-3 py-3.5">
                                            <div class="flex flex-wrap items-center gap-1.5 font-semibold text-slate-900 text-xs">
                                                <span class="rounded-lg bg-amber-50 px-2 py-0.5 text-amber-900 border border-amber-200">
                                                    {{ $trf->source_wallet_name_snapshot ?: ($trf->sourceWallet?->name ?? 'Kantong') }}
                                                </span>
                                                <x-ti-arrow-right class="size-3.5 text-slate-400" />
                                                <span class="rounded-lg bg-emerald-50 px-2 py-0.5 text-emerald-900 border border-emerald-200">
                                                    {{ $trf->destination_wallet_name_snapshot ?: ($trf->destinationWallet?->name ?? 'Kantong') }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-slate-600 mt-1">
                                                {{ $trf->description }}
                                            </div>
                                        </td>

                                        <td class="px-3 py-3.5 text-right font-bold text-slate-900 tabular-nums whitespace-nowrap">
                                            {{ $trf->formattedAmount() }}
                                        </td>

                                        <td class="py-3.5 pl-3 pr-5 text-right">
                                            <x-button.circle
                                                color="slate"
                                                flat
                                                sm
                                                wire:click="showTransferDetail({{ $trf->id }})"
                                                title="Lihat Pasangan Transaksi"
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
                        {{ $this->transfers->links() }}
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    {{-- Modal Detail Pasangan Transaksi Transfer --}}
    <x-modal wire="detailModal" title="Rincian Pasangan Transaksi Transfer" size="lg">
        @if ($this->selectedTransfer)
            @php
                $trf = $this->selectedTransfer;
                $outTx = $trf->outTransaction;
                $inTx = $trf->inTransaction;
            @endphp
            <div class="space-y-4 text-sm">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div>
                        <p class="text-xs font-medium text-slate-500">Kode Referensi Transfer</p>
                        <p class="text-base font-bold text-slate-900 tabular-nums">{{ $trf->transfer_code }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-medium text-slate-500">Nominal Dipindahkan</p>
                        <p class="text-base font-bold text-emerald-700 tabular-nums">{{ $trf->formattedAmount() }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    {{-- Sisi Kantong Pengirim (Transfer Keluar) --}}
                    <div class="rounded-xl bg-amber-50/70 p-3.5 border border-amber-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-950">1. Kantong Pengirim</span>
                            <x-badge text="Transfer Keluar" color="amber" sm light />
                        </div>
                        <p class="font-bold text-slate-900">
                            {{ $trf->source_wallet_name_snapshot ?: ($trf->sourceWallet?->name ?? '-') }}
                        </p>
                        @if ($outTx)
                            <div class="text-xs space-y-1 pt-1 border-t border-amber-200/80">
                                <div class="flex justify-between">
                                    <span class="text-slate-600">ID Transaksi:</span>
                                    <span class="font-semibold text-slate-900 tabular-nums">{{ $outTx->transaction_code }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Saldo Sebelum:</span>
                                    <span class="font-semibold text-slate-800 tabular-nums">{{ $outTx->formattedBalanceBefore() }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Saldo Sesudah:</span>
                                    <span class="font-bold text-slate-900 tabular-nums">{{ $outTx->formattedBalanceAfter() }}</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Sisi Kantong Penerima (Transfer Masuk) --}}
                    <div class="rounded-xl bg-emerald-50/70 p-3.5 border border-emerald-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-950">2. Kantong Penerima</span>
                            <x-badge text="Transfer Masuk" color="emerald" sm light />
                        </div>
                        <p class="font-bold text-slate-900">
                            {{ $trf->destination_wallet_name_snapshot ?: ($trf->destinationWallet?->name ?? '-') }}
                        </p>
                        @if ($inTx)
                            <div class="text-xs space-y-1 pt-1 border-t border-emerald-200/80">
                                <div class="flex justify-between">
                                    <span class="text-slate-600">ID Transaksi:</span>
                                    <span class="font-semibold text-slate-900 tabular-nums">{{ $inTx->transaction_code }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Saldo Sebelum:</span>
                                    <span class="font-semibold text-slate-800 tabular-nums">{{ $inTx->formattedBalanceBefore() }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Saldo Sesudah:</span>
                                    <span class="font-bold text-slate-900 tabular-nums">{{ $inTx->formattedBalanceAfter() }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="text-xs text-slate-600 space-y-1">
                    <div><strong>Waktu Transaksi:</strong> {{ $trf->transaction_date?->translatedFormat('d F Y, H:i') }}</div>
                    <div><strong>Keterangan:</strong> {{ $trf->description }}</div>
                </div>

                <div class="flex justify-end pt-2 border-t border-slate-200">
                    <x-button color="slate" flat wire:click="$set('detailModal', false)">Tutup</x-button>
                </div>
            </div>
        @endif
    </x-modal>
</div>

