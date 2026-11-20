<div class="space-y-6">
    {{-- Header Halaman --}}
    <div class="flex flex-wrap items-center gap-2">
        <h1 class="text-lg font-bold text-slate-900 sm:text-xl">Kantong</h1>
        <x-button.circle color="rose" sm wire:click="openPocketModal" class="rounded-xl">
            <x-ti-plus />
        </x-button.circle>
    </div>

    {{-- Ringkasan Total Saldo (Tanpa Card - Bersih & Hangat) --}}
    <div class="py-2 text-center space-y-2">
        <span class="font-medium">Total Aset</span>

        <div class="flex items-center justify-center gap-2 sm:gap-3">
            <p class="text-3xl sm:text-4xl font-extrabold text-slate-900 tabular-nums tracking-tight">
                <x-masked-amount :value="number_format((float) $this->summary['total_balance'], 0, ',', '.')" />
            </p>
            <x-balance-visibility-toggle />
        </div>
    </div>

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
                <a
                    href="{{ route('pockets.show', $wallet) }}"
                    wire:navigate
                    class="block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2"
                    aria-label="Lihat detail kantong {{ $wallet->name }}"
                >
                    <x-card round="2xl" class="glass-tile h-full min-h-24">
                        <div class="flex h-full flex-col items-center justify-between gap-2 text-center">
                            <span class="text-4xl leading-none sm:text-4xl" role="img" aria-label="{{ $wallet->iconLabel() }}">
                                {{ $wallet->iconEmoji() }}
                            </span>
                            <h3 class="w-full truncate text-sm font-semibold text-slate-800">
                                {{ $wallet->name }}
                            </h3>
                            <p class="w-full truncate text-lg font-bold text-slate-900 tabular-nums sm:text-xl">
                                <x-masked-amount :value="$wallet->formattedBalance()" />
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
                <x-input
                    label="Ikon Kantong *"
                    wire:model="pocket_icon"
                    class="text-2xl! sm:text-2xl!"
                    placeholder="👛"
                    hint="Ketik atau tempel emoji favoritmu."
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
