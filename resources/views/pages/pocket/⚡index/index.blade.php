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
    @if ($this->wallets->isEmpty())
        <x-card>
            <div class="py-8 text-center space-y-2">
                <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 border border-rose-200">
                    <x-ti-wallet class="size-6" />
                </div>
                <p class="text-sm font-semibold text-slate-800">Belum Ada Kantong</p>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    Buat kantong pertama untuk mulai memisahkan uang belanja, tabungan, dan kebutuhan keluarga.
                </p>
                <x-button color="rose" sm wire:click="openPocketModal" class="rounded-xl mt-2">
                    <x-ti-plus class="size-4 mr-1" />
                    Tambah Kantong Pertama
                </x-button>
            </div>
        </x-card>
    @else
        <div class="grid grid-cols-2 gap-3 sm:gap-4">
            @foreach ($this->wallets as $wallet)
                @php
                    $colorEnum = $wallet->colorEnum();
                @endphp
                <a
                    href="{{ route('pockets.show', $wallet) }}"
                    wire:navigate
                    class="block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2"
                    aria-label="Lihat detail kantong {{ $wallet->name }}"
                >
                    <x-card round="2xl" class="h-full min-h-20 {{ $colorEnum->cardClasses() }}">
                        <div class="flex h-full flex-col justify-between gap-3">
                            <h3 class="truncate text-sm font-semibold {{ $colorEnum->accentTextClasses() }}">
                                {{ $wallet->name }}
                            </h3>
                            <p class="truncate text-lg font-bold text-slate-900 tabular-nums sm:text-xl">
                                {{ $wallet->formattedBalance() }}
                            </p>
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif

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
