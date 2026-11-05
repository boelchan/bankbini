<div>
    <h1 class="text-2xl font-medium text-slate-900">Akun Saya</h1>
    <nav class="flex items-center gap-1.5 text-xs text-slate-500 mt-1">
        <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition-colors"><x-ti-home class="size-4" /></a>
        <span>/</span>
        <span class="text-slate-700 font-medium">Profil</span>
    </nav>

    <div class="mt-6">
        {{-- Alerts --}}
        @if (auth()->user()->status == 'inactive')
            <div class="mb-6">
                <x-alert color="red" icon="exclamation-triangle">
                    Akun Anda ditangguhkan. Hubungi admin untuk bantuan.
                </x-alert>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
            {{-- Profile Sidebar --}}
            <div class="lg:col-span-1">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    {{-- Avatar --}}
                    <div class="flex flex-col items-center text-center">
                        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 ring-4 ring-emerald-50">
                            <span class="text-2xl font-bold uppercase text-emerald-700">
                                {{ substr(auth()->user()->name, 0, 2) }}
                            </span>
                        </div>
                        <div class="mt-3">
                            <h2 class="text-lg font-semibold text-slate-800">{{ auth()->user()->name }}</h2>
                            <p class="text-sm text-slate-500">{{ auth()->user()->email }}</p>
                            <span class="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ auth()->user()->status == 'active' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-rose-100 text-rose-800 border border-rose-200' }}">
                                {{ auth()->user()->status == 'active' ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </div>
                    </div>

                    {{-- Navigation --}}
                    <div class="mt-6 space-y-1">
                        <button
                            class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium transition-colors {{ $tab === 'summary' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                            wire:click="setTab('summary')"
                            type="button"
                        >
                            <x-ti-user class="size-5" />
                            Profil
                        </button>
                        <button
                            class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium transition-colors {{ $tab === 'edit_name' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                            wire:click="setTab('edit_name')"
                            type="button"
                        >
                            <x-ti-edit class="size-5" />
                            Ubah Profil
                        </button>
                        <button
                            class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium transition-colors {{ $tab === 'edit_email' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                            wire:click="setTab('edit_email')"
                            type="button"
                        >
                            <x-ti-mail class="size-5" />
                            Email
                        </button>
                        <button
                            class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium transition-colors {{ $tab === 'edit_password' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                            wire:click="setTab('edit_password')"
                            type="button"
                        >
                            <x-ti-lock class="size-5" />
                            Password
                        </button>
                    </div>
                </div>
            </div>

            {{-- Main Content --}}
            <div class="lg:col-span-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    {{-- Tab: Summary --}}
                    @if ($tab === 'summary')
                        <div>
                            <h3 class="mb-4 text-xl font-semibold text-slate-800">Informasi Profil</h3>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 p-4">
                                    <div>
                                        <div class="text-xs font-medium text-slate-500">Nama Lengkap</div>
                                        <div class="mt-1 font-bold text-slate-800">{{ auth()->user()->name }}</div>
                                    </div>
                                    <div class="text-emerald-600">
                                        <x-ti-user-circle class="size-6" />
                                    </div>
                                </div>

                                <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 p-4">
                                    <div>
                                        <div class="text-xs font-medium text-slate-500">NIK</div>
                                        <div class="mt-1 font-bold text-slate-800">{{ auth()->user()->nik ?? '-' }}</div>
                                    </div>
                                    <div class="text-emerald-600">
                                        <x-ti-id class="size-6" />
                                    </div>
                                </div>

                                <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 p-4">
                                    <div>
                                        <div class="text-xs font-medium text-slate-500">Email</div>
                                        <div class="mt-1 font-bold text-slate-800">{{ auth()->user()->email }}</div>
                                    </div>
                                    <div class="text-sky-600">
                                        <x-ti-mail class="size-6" />
                                    </div>
                                </div>

                                <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 p-4">
                                    <div>
                                        <div class="text-xs font-medium text-slate-500">Status Akun</div>
                                        <div class="mt-1 font-bold text-slate-800">{{ auth()->user()->status == 'active' ? 'Aktif' : 'Tidak Aktif' }}</div>
                                    </div>
                                    <div class="text-emerald-600">
                                        <x-ti-shield-check class="size-6" />
                                    </div>
                                </div>

                                <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 p-4">
                                    <div>
                                        <div class="text-xs font-medium text-slate-500">Bergabung</div>
                                        <div class="mt-1 font-bold text-slate-800">{{ auth()->user()->created_at->format('d M Y H:i') }}</div>
                                    </div>
                                    <div class="text-amber-500">
                                        <x-ti-calendar class="size-6" />
                                    </div>
                                </div>
                            </div>

                            @if (auth()->user()->roles->count() > 0)
                                <div class="mt-6">
                                    <h4 class="mb-3 text-lg font-semibold text-slate-800">Peran Akses</h4>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach (auth()->user()->roles as $role)
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold uppercase text-emerald-700 border border-emerald-200">
                                                {{ $role->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Form: Edit Name --}}
                    @if ($tab === 'edit_name')
                        <div>
                            <x-button color="gray" flat sm class="mb-4" wire:click="setTab('summary')">
                                <x-ti-arrow-left class="size-4 mr-1 inline" /> Kembali
                            </x-button>

                            <h3 class="mb-1 text-xl font-semibold text-slate-800">Ubah Profil</h3>
                            <p class="text-slate-500 text-sm mb-6">Perbarui informasi profil Anda</p>

                            <form class="space-y-4" wire:submit="updateName">
                                <x-input wire:model="name" label="Nama" />
                                <x-input wire:model="nik" label="NIK" />

                                <div class="flex items-center gap-3 pt-4">
                                    <x-button submit color="emerald">
                                        <x-ti-check class="size-4 mr-1 inline" /> Simpan
                                    </x-button>
                                    <x-button color="gray" flat type="button" wire:click="setTab('summary')">
                                        Batal
                                    </x-button>
                                </div>
                            </form>
                        </div>
                    @endif

                    {{-- Form: Edit Email --}}
                    @if ($tab === 'edit_email')
                        <div>
                            <x-button color="gray" flat sm class="mb-4" wire:click="setTab('summary')">
                                <x-ti-arrow-left class="size-4 mr-1 inline" /> Kembali
                            </x-button>

                            <h3 class="mb-1 text-xl font-semibold text-slate-800">Ubah Email</h3>
                            <p class="text-slate-500 text-sm mb-6">Perbarui alamat email utama Anda</p>

                            <div class="mb-6">
                                <x-alert color="amber" icon="exclamation-triangle">
                                    Mengubah email akan mengakhiri semua sesi login
                                </x-alert>
                            </div>

                            <form class="space-y-4" wire:submit="updateEmail">
                                <x-input type="email" label="Email Baru" placeholder="email@domain.com" wire:model="email" />

                                <x-password placeholder="Masukkan password Anda" label="Masukkan Password saat ini"
                                    wire:model="password_for_email"
                                />

                                <div class="flex items-center gap-3 pt-4">
                                    <x-button submit color="emerald">
                                        <x-ti-check class="size-4 mr-1 inline" /> Update Email
                                    </x-button>
                                    <x-button color="gray" flat type="button" wire:click="setTab('summary')">
                                        Batal
                                    </x-button>
                                </div>
                            </form>
                        </div>
                    @endif

                    {{-- Form: Edit Password --}}
                    @if ($tab === 'edit_password')
                        <div>
                            <x-button color="gray" flat sm class="mb-4" wire:click="setTab('summary')">
                                <x-ti-arrow-left class="size-4 mr-1 inline" /> Kembali
                            </x-button>

                            <h3 class="mb-1 text-xl font-semibold text-slate-800">Ubah Password</h3>
                            <p class="text-slate-500 text-sm mb-6">Perbarui password keamanan akun Anda</p>

                            <form class="space-y-4" wire:submit="updatePassword">
                                <x-password label="Password saat ini" wire:model="current_password" />

                                <div class="relative my-6 flex items-center">
                                    <div class="w-full border-t border-slate-200"></div>
                                    <span class="absolute left-1/2 -translate-x-1/2 bg-white px-3 text-xs font-medium text-slate-500">Password Baru</span>
                                </div>

                                <x-password label="Password Baru" wire:model="password" />
                                <x-password label="Konfirmasi Password" wire:model="password_confirmation" />

                                <div class="flex items-center gap-3 pt-4">
                                    <x-button submit color="red">
                                        <x-ti-lock class="size-4 mr-1 inline" /> Update Password
                                    </x-button>
                                    <x-button color="gray" flat type="button" wire:click="setTab('summary')">
                                        Batal
                                    </x-button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
