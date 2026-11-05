<div>
    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">Pengguna</h1>
    <nav class="flex items-center gap-1.5 text-xs text-slate-500 mt-1">
        <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition-colors">
            <x-ti-home class="size-4" />
        </a>
        <span>/</span>
        <a href="{{ route('user') }}" class="hover:text-emerald-600 transition-colors">Pengguna</a>
        <span>/</span>
        <span class="text-slate-700 font-medium">Tambah data baru</span>
    </nav>

    <div class="mt-6 max-w-4xl">
        <form wire:submit="store" method="post">
            @csrf
            <x-card class="bg-white border border-slate-200/80 shadow-xs" header="Formulir Tambah Pengguna">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Kolom Kiri: Informasi Pribadi & Autentikasi --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                            <x-ti-user class="size-4.5 text-emerald-600" />
                            <h2 class="text-sm font-semibold text-slate-800">Data Pribadi & Autentikasi</h2>
                        </div>

                        <x-input wire:model="name" label="Nama Lengkap *" placeholder="Masukkan nama lengkap" />
                        <x-input wire:model="nik" label="NIK" placeholder="16 digit NIK (Opsional)" maxlength="16" />
                        <x-input type="email" wire:model="email" label="Alamat Email *" placeholder="email@puskesmas.go.id" />
                        <x-password wire:model="password" label="Kata Sandi *" placeholder="Minimal 8 karakter" />
                        <x-password wire:model="password_confirmation" label="Konfirmasi Kata Sandi *" placeholder="Ulangi kata sandi" />
                    </div>

                    {{-- Kolom Kanan: Peran & Wilayah Tugas --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                            <x-ti-shield-lock class="size-4.5 text-emerald-600" />
                            <h2 class="text-sm font-semibold text-slate-800">Peran & Wilayah Tugas</h2>
                        </div>

                        <x-select.styled
                            wire:model="role"
                            label="Peran Pengguna *"
                            placeholder="Pilih peran pengguna"
                            searchable
                            multiple
                            :options="$this->roles"
                        />

                        <x-select.styled
                            wire:model="village_code"
                            label="Wilayah Desa Binaan"
                            placeholder="Pilih Desa (Opsional)"
                            searchable
                            :options="$this->villages"
                            select="label:label|value:value"
                        />

                        <x-select.styled
                            wire:model="status"
                            label="Status Akun *"
                            placeholder="Pilih status"
                            :options="[['label' => 'Aktif', 'value' => 'active'], ['label' => 'Non-Aktif', 'value' => 'inactive']]"
                            select="label:label|value:value"
                        />

                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3.5 text-xs text-slate-600 space-y-1">
                            <div class="font-semibold text-slate-800 flex items-center gap-1.5">
                                <x-ti-info-circle class="size-4 text-emerald-600" />
                                <span>Petunjuk Penugasan:</span>
                            </div>
                            <p class="leading-relaxed">
                                Untuk tenaga medis seperti <strong>Bidan Desa</strong>, silakan pilih wilayah desa binaan untuk keterkaitan pemantauan data kantong kehamilan secara otomatis.
                            </p>
                        </div>
                    </div>
                </div>

                <x-slot:footer>
                    <div class="flex items-center justify-between">
                        <x-button flat color="slate" href="{{ route('user') }}" wire:navigate>
                            Batal
                        </x-button>
                        <x-button submit color="emerald" loading="store">
                            <x-ti-device-floppy class="size-4 mr-1 inline" />
                            Simpan Pengguna
                        </x-button>
                    </div>
                </x-slot:footer>
            </x-card>
        </form>
    </div>
</div>
