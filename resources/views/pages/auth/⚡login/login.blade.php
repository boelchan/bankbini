<div>
    @push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
        }

        /* Latar belakang arsitektural halus & tenang */
        .bg-grid-subtle {
            background-color: #f8fafc;
            background-image: 
                radial-gradient(rgba(16, 185, 129, 0.15) 1px, transparent 1px),
                linear-gradient(to right, rgba(226, 232, 240, 0.65) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(226, 232, 240, 0.65) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        .ambient-header-glow {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 900px;
            height: 480px;
            background: radial-gradient(ellipse 65% 50% at 50% 0%, rgba(16, 185, 129, 0.14) 0%, rgba(209, 250, 229, 0.15) 45%, transparent 75%);
            pointer-events-none;
            z-index: 0;
        }
    </style>
    @endpush

    <!-- Lapisan Latar Belakang -->
    <div class="fixed inset-0 pointer-events-none -z-10 bg-grid-subtle [mask-image:radial-gradient(ellipse_80%_65%_at_50%_15%,#000_50%,transparent_100%)] opacity-75"></div>
    <div class="ambient-header-glow"></div>

    <!-- Tombol Kembali ke Beranda -->
    {{-- <div class="fixed top-4 left-4 z-50">
        <a href="/" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 hover:text-emerald-700 border border-slate-200/80 shadow-xs text-sm font-medium transition-colors">
            <x-ti-arrow-left class="size-4" />
            <span>Kembali ke Beranda</span>
        </a>
    </div> --}}

    <!-- Konten Utama Halaman Masuk -->
    <div class="relative z-10 flex min-h-screen items-center justify-center p-4 py-16">
        <div class="w-full max-w-md">
            
            <!-- Logo & Identitas Faskes -->
            <div class="mb-6 text-center">
                <div class="inline-flex items-center justify-center size-16 rounded-2xl bg-emerald-600 text-white shadow-xs mb-4">
                    <x-ti-hospital class="size-8" />
                </div>
                
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 mb-1">
                    UPTD Puskesmas Pragaan
                </h1>
                <p class="text-sm font-medium text-slate-600">
                    Sistem Informasi Pemantauan Kantong Kehamilan
                </p>
            </div>

            <!-- Kartu Masuk / Formulir Autentikasi -->
            <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
                
                <div class="mb-6 pb-4 border-b border-slate-100">
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <x-ti-lock class="size-5 text-emerald-700" />
                        Masuk ke Akun
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Silakan masukkan kredensial akun tenaga medis atau administrator Anda.
                    </p>
                </div>

                @if (session('error'))
                    <div class="mb-5">
                        <x-alert color="red" light :text="session('error')" />
                    </div>
                @endif

                <form class="space-y-4" wire:submit="authenticate">
                    <div>
                        <x-input 
                            type="email" 
                            label="Alamat Email" 
                            placeholder="nama@puskesmaspragaan.id"
                            wire:model="email" 
                        />
                    </div>
                    
                    <div>
                        <x-password 
                            label="Kata Sandi" 
                            placeholder="••••••••"
                            wire:model="password" 
                        />
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <x-checkbox 
                            label="Ingat saya" 
                            wire:model="remember" 
                            color="emerald" 
                        />
                    </div>

                    <div class="pt-2">
                        <x-button 
                            submit 
                            color="emerald" 
                            loading="authenticate" 
                            class="w-full justify-center text-sm font-semibold py-3"
                        >
                            <span>Masuk ke Sistem</span>
                            <x-ti-arrow-right class="size-4 ml-1.5" />
                        </x-button>
                    </div>
                </form>

                <!-- Informasi Keamanan Akses -->
                <div class="mt-6 p-4 rounded-xl bg-slate-50 border border-slate-200 text-left flex items-start gap-3">
                    <x-ti-shield-check class="size-5 text-emerald-700 shrink-0 mt-0.5" />
                    <p class="text-sm leading-relaxed text-slate-600">
                        Akses sistem terproteksi khusus untuk <strong>Bidan Desa</strong>, <strong>Dokter</strong>, dan <strong>Administrator</strong> UPTD Puskesmas Pragaan.
                        Pendaftaran dan penyetelan ulang kata sandi dikelola oleh administrator.
                    </p>
                </div>

                <!-- Hak Cipta & Informasi Pengembang -->
                <div class="mt-6 text-center border-t border-slate-100 pt-4">
                    <p class="text-sm text-slate-500">
                        &copy; {{ date('Y') }} UPTD Puskesmas Pragaan &bull; Kabupaten Sumenep<br>
                        Dikembangkan oleh <a href="https://wa.me/081934955229" target="_blank" class="text-emerald-700 hover:text-emerald-800 font-semibold transition-colors">BulyanDev</a>
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>
