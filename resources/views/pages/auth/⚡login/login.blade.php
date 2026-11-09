<div>
    @push('styles')
    <style>
        .bankbini-login-bg {
            background-color: #fffbfa;
            background-image:
                radial-gradient(ellipse 70% 50% at 50% 0%, rgba(244, 63, 94, 0.10) 0%, rgba(251, 113, 133, 0.08) 50%, transparent 80%);
        }
    </style>
    @endpush

    <div class="bankbini-login-bg relative min-h-screen flex items-center justify-center p-4 py-12">
        <div class="w-full max-w-md">
            <!-- Identitas Brand Bank BINI -->
            <div class="mb-6 text-center">
                <div class="inline-flex items-center justify-center size-16 rounded-2xl bg-rose-100 text-rose-700 border border-rose-200 shadow-xs mb-3.5">
                    <x-ti-wallet class="size-8" />
                </div>

                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold mb-2">
                    <x-ti-heart class="size-3.5 text-rose-600" />
                    <span>Dompet Manis Keluarga</span>
                </div>

                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Bank BINI
                </h1>
                <p class="text-sm text-slate-600 mt-1">
                    Kelola saldo, kantong belanja, dan tabungan impian dengan rapi dan menyenangkan.
                </p>
            </div>

            <!-- Kartu Masuk -->
            <x-card class="bg-white border border-rose-200/80 shadow-xs rounded-2xl">
                <div class="mb-5 pb-3.5 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <x-ti-sparkles class="size-5 text-rose-600" />
                        <span>Selamat Datang Kembali</span>
                    </h2>
                    <p class="text-xs text-slate-600 mt-1">
                        Masukkan alamat email dan kata sandi untuk membuka kantong keuangan Anda.
                    </p>
                </div>

                @if (session('error'))
                    <div class="mb-4">
                        <x-alert color="red" light :text="session('error')" />
                    </div>
                @endif

                <form class="space-y-4" wire:submit="authenticate">
                    <div>
                        <x-input
                            type="email"
                            label="Alamat Email"
                            placeholder="istri@bankbini.id"
                            wire:model="email"
                        />
                    </div>

                    <div>
                        <x-password
                            label="Kata Sandi"
                            placeholder="Masukkan kata sandi"
                            wire:model="password"
                        />
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <x-checkbox
                            label="Ingat sesi saya di perangkat ini"
                            wire:model="remember"
                            color="rose"
                        />
                    </div>

                    <div class="pt-2">
                        <x-button
                            submit
                            color="rose"
                            loading="authenticate"
                            class="w-full justify-center text-sm font-semibold py-2.5 rounded-xl cursor-pointer"
                        >
                            <x-ti-login-2 class="size-4 mr-1.5" />
                            <span>Buka Dompet Saya</span>
                        </x-button>
                    </div>
                </form>

            </x-card>

            <p class="mt-5 text-center text-xs text-slate-500">
                &copy; {{ date('Y') }} Bank BINI &bull; Pencatatan Kantong Keuangan Rumah Tangga
            </p>
        </div>
    </div>
</div>
