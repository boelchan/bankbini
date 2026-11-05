<div>
    <h1 class="text-lg sm:text-xl font-bold text-slate-900">Tambah Pengguna</h1>
    <nav class="flex items-center gap-1.5 text-sm text-slate-500 mt-1">
        <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition-colors" wire:navigate>
            <x-ti-home class="size-4" />
        </a>
        <span>/</span>
        <a href="{{ route('user') }}" class="hover:text-emerald-600 transition-colors" wire:navigate>Pengguna</a>
        <span>/</span>
        <span class="text-slate-700 font-medium">Tambah Baru</span>
    </nav>

    <div class="mt-6 max-w-3xl">
        <form wire:submit="store">
            <x-card class="bg-white border border-slate-200/80 shadow-xs" header="Formulir Tambah Pengguna">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <x-input wire:model="name" label="Nama Lengkap *" placeholder="Masukkan nama lengkap" />
                        <x-input type="email" wire:model="email" label="Alamat Email *" placeholder="nama@bankbini.id" />
                        <x-input wire:model="phone" label="Nomor WhatsApp / HP" placeholder="0812xxxxxxxx" />
                        <x-input wire:model="jabatan" label="Peran di Keluarga" placeholder="Contoh: Istri Tercinta / Suami" />
                    </div>

                    <div class="space-y-4">
                        <x-select.styled
                            wire:model="role"
                            label="Hak Akses Sistem *"
                            placeholder="Pilih peran"
                            :options="\App\Enums\RolesEnum::choices()"
                            select="label:label|value:value"
                        />

                        <x-select.styled
                            wire:model="status"
                            label="Status Akun *"
                            placeholder="Pilih status"
                            :options="\App\Enums\UserStatusEnum::choices()"
                            select="label:label|value:value"
                        />

                        <x-password wire:model="password" label="Kata Sandi *" placeholder="Minimal 6 karakter" />
                        <x-password wire:model="password_confirmation" label="Konfirmasi Kata Sandi *" placeholder="Ulangi kata sandi" />
                    </div>
                </div>

                <x-slot:footer between>
                    <x-button flat color="slate" href="{{ route('user') }}" wire:navigate>
                        Batal
                    </x-button>
                    <x-button submit color="emerald" loading="store">
                        <x-ti-device-floppy class="size-4 mr-1 inline" />
                        Simpan Pengguna
                    </x-button>
                </x-slot:footer>
            </x-card>
        </form>
    </div>
</div>
