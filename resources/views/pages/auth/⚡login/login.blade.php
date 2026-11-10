<div class="relative flex min-h-[calc(100dvh-10rem)] items-center justify-center px-4 py-8 lg:min-h-[calc(100dvh-7rem)]">
    <div class="w-full max-w-sm">
        {{-- Sapaan hangat bersama maskot dompet --}}
        <div class="mb-5 flex items-center gap-3 px-1">
            <svg class="size-20 shrink-0" viewBox="0 0 96 96" fill="none" aria-hidden="true">
                <ellipse cx="48" cy="86" rx="27" ry="4.5" fill="#fecdd3" opacity="0.7" />

                {{-- Hati kecil yang melayang --}}
                <path d="M82 24c-2-1.4-5-3.4-5-6.2 0-1.7 1.3-3 3-3 .9 0 1.6.4 2 1 .4-.6 1.1-1 2-1 1.7 0 3 1.3 3 3 0 2.8-3 4.8-5 6.2Z" fill="#fda4af" />
                <path d="M17 29c-1.5-1-3.6-2.5-3.6-4.5 0-1.2.9-2.1 2.1-2.1.6 0 1.1.3 1.5.7.3-.4.8-.7 1.4-.7 1.2 0 2.1.9 2.1 2.1 0 2-2.1 3.5-3.5 4.5Z" fill="#fecdd3" />

                {{-- Badan dompet --}}
                <rect x="12" y="32" width="72" height="52" rx="16" fill="#fecdd3" stroke="#fb7185" stroke-width="2.5" />
                <path d="M12 48c0-9 7-16 16-16h40c9 0 16 7 16 16v2H12v-2Z" fill="#fda4af" />
                <path d="M12 50h72" stroke="#fb7185" stroke-width="2.5" stroke-linecap="round" />

                {{-- Kancing berbentuk hati --}}
                <path d="M48 56.5c-1.8-1.3-4.8-3.3-4.8-6.2 0-1.7 1.3-3 2.9-3 1.1 0 1.9.5 2.4 1.2.4-.7 1.2-1.2 2.3-1.2 1.6 0 2.9 1.3 2.9 3 0 2.9-3 4.9-5.7 6.2Z" fill="#e11d48" />

                {{-- Wajah lucu --}}
                <path d="M31 64.5c1.7 2.2 4.3 2.2 6 0" stroke="#881337" stroke-width="2.4" stroke-linecap="round" />
                <path d="M51 64.5c1.7 2.2 4.3 2.2 6 0" stroke="#881337" stroke-width="2.4" stroke-linecap="round" />
                <path d="M41 71.5c2.3 3 7.7 3 10 0" stroke="#881337" stroke-width="2.4" stroke-linecap="round" />
                <circle cx="27.5" cy="70.5" r="3.4" fill="#fda4af" />
                <circle cx="68.5" cy="70.5" r="3.4" fill="#fda4af" />
            </svg>

            <div class="min-w-0">
                <h1 class="flex items-center gap-1.5 text-2xl font-extrabold tracking-tight text-slate-900">
                    <span>Hai, Bini!</span>
                    <x-ti-heart class="size-5 shrink-0 text-rose-500" aria-hidden="true" />
                </h1>
                <p class="mt-0.5 text-sm text-slate-600">
                    Dompet manis keluarga siap dibuka.
                </p>
            </div>
        </div>

        {{-- Kartu Masuk --}}
        <x-card>
            <div class="mb-5">
                <h2 class="text-base font-bold text-slate-900">Masuk ke dompet</h2>
                <p class="mt-1 text-sm text-slate-600">Isi email dan kata sandi, ya.</p>
            </div>

            @if (session('error'))
                <div class="mb-4">
                    <x-alert color="red" light :text="session('error')" />
                </div>
            @endif

            <form class="space-y-4" wire:submit="authenticate">
                <x-input
                    type="email"
                    label="Alamat Email"
                    placeholder="nama@email.com"
                    wire:model="email"
                />

                <x-password
                    label="Kata Sandi"
                    placeholder="Masukkan kata sandi"
                    wire:model="password"
                />

                <x-checkbox
                    label="Ingat aku di perangkat ini"
                    wire:model="remember"
                    color="rose"
                />

                <div class="pt-1">
                    <x-button
                        submit
                        color="rose"
                        loading="authenticate"
                        class="w-full justify-center rounded-xl py-2.5 text-sm font-semibold cursor-pointer"
                    >
                        <x-ti-lock-open class="mr-1.5 size-4" aria-hidden="true" />
                        <span>Buka Dompet</span>
                    </x-button>
                </div>
            </form>
        </x-card>

        <p class="mt-4 flex items-center justify-center gap-1.5 text-xs text-slate-500">
            <x-ti-shield-lock class="size-3.5 text-rose-400" aria-hidden="true" />
            <span>Aman, cuma kamu yang bisa buka.</span>
        </p>
    </div>
</div>
