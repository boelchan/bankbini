<div class="space-y-6">
    <div>
        <h1 class="text-lg sm:text-xl font-bold text-slate-900">Pengaturan & Akun</h1>
        <nav class="flex items-center gap-1.5 text-sm text-slate-500 mt-1">
            <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition-colors" wire:navigate>
                <x-ti-home class="size-4" />
            </a>
            <span>/</span>
            <span class="text-slate-700 font-medium">Pengaturan</span>
        </nav>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        {{-- Menu Samping Pengaturan --}}
        <div class="lg:col-span-1">
            <x-card class="bg-white border border-slate-200/80 shadow-xs">
                <div class="flex flex-col items-center text-center">
                    <div class="flex size-16 items-center justify-center rounded-2xl bg-rose-100 text-rose-800 border border-rose-200 font-bold text-xl uppercase">
                        {{ substr(auth()->user()->name, 0, 2) }}
                    </div>
                    <div class="mt-3">
                        <h2 class="text-base font-bold text-slate-900">{{ auth()->user()->name }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">{{ auth()->user()->jabatan ?? 'Pemilik Dompet Keluarga' }}</p>
                        <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
                        <span class="mt-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <x-ti-circle-check class="size-3.5 text-emerald-600" />
                            <span>Akun Aktif</span>
                        </span>
                    </div>
                </div>

                <div class="mt-6 space-y-1">
                    <button
                        class="flex w-full items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-colors cursor-pointer {{ $tab === 'summary' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                        wire:click="setTab('summary')"
                        type="button"
                    >
                        <x-ti-user class="size-4.5" />
                        <span>Ringkasan Profil</span>
                    </button>
                    <button
                        class="flex w-full items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-colors cursor-pointer {{ $tab === 'edit_name' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                        wire:click="setTab('edit_name')"
                        type="button"
                    >
                        <x-ti-edit class="size-4.5" />
                        <span>Ubah Profil</span>
                    </button>
                    <button
                        class="flex w-full items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-colors cursor-pointer {{ $tab === 'categories' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                        wire:click="setTab('categories')"
                        type="button"
                    >
                        <x-ti-category class="size-4.5" />
                        <span>Kategori Transaksi</span>
                    </button>
                    <button
                        class="flex w-full items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-colors cursor-pointer {{ $tab === 'edit_email' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                        wire:click="setTab('edit_email')"
                        type="button"
                    >
                        <x-ti-mail class="size-4.5" />
                        <span>Ubah Email</span>
                    </button>
                    <button
                        class="flex w-full items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-colors cursor-pointer {{ $tab === 'edit_password' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                        wire:click="setTab('edit_password')"
                        type="button"
                    >
                        <x-ti-lock class="size-4.5" />
                        <span>Kata Sandi</span>
                    </button>
                </div>
            </x-card>
        </div>

        {{-- Konten Utama Pengaturan --}}
        <div class="lg:col-span-3">
            <x-card class="bg-white border border-slate-200/80 shadow-xs">
                @if ($tab === 'summary')
                    <div class="space-y-5">
                        <div class="border-b border-slate-100 pb-3">
                            <h3 class="text-base font-bold text-slate-900">Informasi Akun & Dompet</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Detail identitas pemilik dompet Bank BINI.</p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-slate-50/70 p-4">
                                <div>
                                    <div class="text-xs font-medium text-slate-500">Nama Lengkap</div>
                                    <div class="mt-1 text-sm font-bold text-slate-900">{{ auth()->user()->name }}</div>
                                </div>
                                <div class="flex size-10 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                                    <x-ti-heart class="size-5" />
                                </div>
                            </div>

                            <div class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-slate-50/70 p-4">
                                <div>
                                    <div class="text-xs font-medium text-slate-500">Peran di Keluarga</div>
                                    <div class="mt-1 text-sm font-bold text-slate-900">{{ auth()->user()->jabatan ?? 'Menteri Keuangan Keluarga' }}</div>
                                </div>
                                <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                    <x-ti-sparkles class="size-5" />
                                </div>
                            </div>

                            <div class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-slate-50/70 p-4">
                                <div>
                                    <div class="text-xs font-medium text-slate-500">Alamat Email</div>
                                    <div class="mt-1 text-sm font-bold text-slate-900">{{ auth()->user()->email }}</div>
                                </div>
                                <div class="flex size-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700">
                                    <x-ti-mail class="size-5" />
                                </div>
                            </div>

                            <div class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-slate-50/70 p-4">
                                <div>
                                    <div class="text-xs font-medium text-slate-500">Nomor WhatsApp / HP</div>
                                    <div class="mt-1 text-sm font-bold text-slate-900">{{ auth()->user()->phone ?: 'Belum diisi' }}</div>
                                </div>
                                <div class="flex size-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                                    <x-ti-phone class="size-5" />
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($tab === 'edit_name')
                    <div class="space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h3 class="text-base font-bold text-slate-900">Ubah Profil</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Perbarui nama sapaan dan informasi kontak Anda.</p>
                        </div>

                        <form class="space-y-4 max-w-xl" wire:submit="updateName">
                            <x-input wire:model="name" label="Nama Lengkap / Panggilan *" placeholder="Contoh: Bunda Sayang" />
                            <x-input wire:model="jabatan" label="Julukan / Peran di Keluarga" placeholder="Contoh: Menteri Keuangan Keluarga" />
                            <x-input wire:model="phone" label="Nomor WhatsApp / HP" placeholder="0812xxxxxxxx" />

                            <div class="flex items-center gap-3 pt-2">
                                <x-button submit color="emerald" loading="updateName">
                                    <x-ti-check class="size-4 mr-1 inline" /> Simpan Profil
                                </x-button>
                                <x-button color="slate" flat type="button" wire:click="setTab('summary')">
                                    Batal
                                </x-button>
                            </div>
                        </form>
                    </div>
                @endif

                @if ($tab === 'categories')
                    <div class="space-y-6">
                        <div class="border-b border-slate-100 pb-3">
                            <h3 class="text-base font-bold text-slate-900">Kategori Pemasukan & Pengeluaran</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Tambahkan kategori kustom sesuai kebiasaan belanja atau sumber rezeki keluarga Anda.</p>
                        </div>

                        <form wire:submit="addCategory" class="p-4 rounded-2xl bg-rose-50/50 border border-rose-200/80 space-y-4">
                            <div class="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                                <x-ti-plus class="size-4 text-rose-600" />
                                <span>Tambah Kategori Baru</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                <x-input wire:model="cat_name" label="Nama Kategori *" placeholder="Contoh: Skincare & Salon" />
                                <x-select.styled
                                    wire:model="cat_type"
                                    label="Jenis *"
                                    :options="\App\Enums\CategoryTypeEnum::choices()"
                                    select="label:label|value:value"
                                />
                                <x-select.styled
                                    wire:model="cat_icon"
                                    label="Ikon *"
                                    :options="\App\Enums\WalletIconEnum::choices()"
                                    select="label:label|value:value"
                                />
                                <x-select.styled
                                    wire:model="cat_color"
                                    label="Warna *"
                                    :options="\App\Enums\WalletColorEnum::choices()"
                                    select="label:label|value:value"
                                />
                            </div>
                            <div class="flex justify-end">
                                <x-button submit color="emerald" sm loading="addCategory">
                                    <x-ti-plus class="size-4 mr-1 inline" /> Tambah Kategori
                                </x-button>
                            </div>
                        </form>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- Kategori Pemasukan --}}
                            <div class="space-y-2.5">
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-emerald-800 flex items-center gap-1.5">
                                    <x-ti-arrow-down-left class="size-4 text-emerald-600" />
                                    <span>Kategori Pemasukan</span>
                                </h4>
                                <div class="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
                                    @foreach ($this->categories->where('type', \App\Enums\CategoryTypeEnum::INCOME) as $cat)
                                        <div class="flex items-center justify-between px-3.5 py-2.5 text-sm">
                                            <div class="flex items-center gap-2.5">
                                                <span class="flex size-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <x-dynamic-component :component="'ti-' . ($cat->icon ?: 'wallet')" class="size-4" />
                                                </span>
                                                <span class="font-medium text-slate-800">{{ $cat->name }}</span>
                                            </div>
                                            @if ($cat->is_default)
                                                <span class="text-xs text-slate-400">Bawaan</span>
                                            @else
                                                <x-button.circle color="red" flat sm wire:click="deleteCategory({{ $cat->id }})" title="Hapus kategori">
                                                    <x-ti-trash class="size-4" />
                                                </x-button.circle>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Kategori Pengeluaran --}}
                            <div class="space-y-2.5">
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-rose-800 flex items-center gap-1.5">
                                    <x-ti-arrow-up-right class="size-4 text-rose-600" />
                                    <span>Kategori Pengeluaran</span>
                                </h4>
                                <div class="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
                                    @foreach ($this->categories->where('type', \App\Enums\CategoryTypeEnum::EXPENSE) as $cat)
                                        <div class="flex items-center justify-between px-3.5 py-2.5 text-sm">
                                            <div class="flex items-center gap-2.5">
                                                <span class="flex size-8 items-center justify-center rounded-lg bg-rose-50 text-rose-700 border border-rose-200">
                                                    <x-dynamic-component :component="'ti-' . ($cat->icon ?: 'shopping-bag')" class="size-4" />
                                                </span>
                                                <span class="font-medium text-slate-800">{{ $cat->name }}</span>
                                            </div>
                                            @if ($cat->is_default)
                                                <span class="text-xs text-slate-400">Bawaan</span>
                                            @else
                                                <x-button.circle color="red" flat sm wire:click="deleteCategory({{ $cat->id }})" title="Hapus kategori">
                                                    <x-ti-trash class="size-4" />
                                                </x-button.circle>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($tab === 'edit_email')
                    <div class="space-y-4 max-w-xl">
                        <div class="border-b border-slate-100 pb-3">
                            <h3 class="text-base font-bold text-slate-900">Ubah Alamat Email</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Gunakan email aktif untuk kemudahan masuk ke akun Anda.</p>
                        </div>

                        <form class="space-y-4" wire:submit="updateEmail">
                            <x-input type="email" label="Email Baru *" placeholder="nama@bankbini.id" wire:model="email" />
                            <x-password placeholder="Masukkan kata sandi saat ini" label="Konfirmasi Kata Sandi Saat Ini *" wire:model="password_for_email" />

                            <div class="flex items-center gap-3 pt-2">
                                <x-button submit color="emerald" loading="updateEmail">
                                    <x-ti-check class="size-4 mr-1 inline" /> Simpan Email
                                </x-button>
                                <x-button color="slate" flat type="button" wire:click="setTab('summary')">
                                    Batal
                                </x-button>
                            </div>
                        </form>
                    </div>
                @endif

                @if ($tab === 'edit_password')
                    <div class="space-y-4 max-w-xl">
                        <div class="border-b border-slate-100 pb-3">
                            <h3 class="text-base font-bold text-slate-900">Ubah Kata Sandi</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Perbarui kata sandi akun dompet Anda secara berkala.</p>
                        </div>

                        <form class="space-y-4" wire:submit="updatePassword">
                            <x-password label="Kata Sandi Saat Ini *" wire:model="current_password" />
                            <x-password label="Kata Sandi Baru *" wire:model="password" />
                            <x-password label="Konfirmasi Kata Sandi Baru *" wire:model="password_confirmation" />

                            <div class="flex items-center gap-3 pt-2">
                                <x-button submit color="emerald" loading="updatePassword">
                                    <x-ti-lock class="size-4 mr-1 inline" /> Perbarui Kata Sandi
                                </x-button>
                                <x-button color="slate" flat type="button" wire:click="setTab('summary')">
                                    Batal
                                </x-button>
                            </div>
                        </form>
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</div>
