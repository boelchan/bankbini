<!DOCTYPE html>
<html class="light" lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link type="image/png" href="{{ asset('favicon/favicon-96x96.png') }}" rel="icon" sizes="96x96" />
    <link type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}" rel="icon" />
    <link href="{{ asset('favicon/favicon.ico') }}" rel="shortcut icon" />
    <link href="{{ asset('favicon/apple-touch-icon.png') }}" rel="apple-touch-icon" sizes="180x180" />
    <meta name="apple-mobile-web-app-title" content="Bank BINI" />

    <title>{{ isset($title) ? $title . ' - Bank BINI' : 'Bank BINI - Dompet & Kantong Keuangan Keluarga' }}</title>

    <!-- Tipografi: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    <tallstackui:script />
    @stack('styles')
</head>

<body class="bg-slate-50 text-slate-900 antialiased font-sans">
    <x-toast />
    <x-dialog />

    <div
        class="relative min-h-screen w-full bg-slate-50 flex flex-col lg:flex-row"
        x-data="{
            sidebarOpen: localStorage.getItem('sidebar_expanded') !== 'false',
            mobileSidebarOpen: false,
            toggleSidebar() {
                if (window.innerWidth >= 1024) {
                    this.sidebarOpen = !this.sidebarOpen;
                    localStorage.setItem('sidebar_expanded', this.sidebarOpen);
                } else {
                    this.mobileSidebarOpen = !this.mobileSidebarOpen;
                }
            },
            closeMobile() {
                this.mobileSidebarOpen = false;
            }
        }"
        x-on:keydown.escape.window="closeMobile()"
    >
        <!-- Mobile Sidebar Overlay Backdrop -->
        <div
            class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-xs transition-opacity lg:hidden"
            x-show="mobileSidebarOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closeMobile()"
            x-cloak
        ></div>

        <!-- Sidebar Navigation (Panel Slate Netral Sesuai Pedoman) -->
        @if (Auth::check())
            <aside
                id="sidebar"
                aria-label="Navigasi Utama"
                class="fixed inset-y-0 left-0 z-50 flex flex-col bg-slate-100 border-r border-slate-200 p-3 transition-all duration-300 ease-in-out lg:static lg:h-screen lg:sticky lg:top-0 lg:z-30 select-none overflow-hidden"
                :class="[
                    mobileSidebarOpen ? 'translate-x-0 shadow-lg w-72 max-w-[85vw]' : '-translate-x-full lg:translate-x-0',
                    sidebarOpen ? 'lg:w-64 lg:p-3.5' : 'lg:w-18 lg:p-2.5'
                ]"
            >
                <div class="relative z-10 flex h-full flex-col min-h-0">
                    {{-- Header saat Sidebar Terbuka --}}
                    <div class="flex items-center justify-between pb-3 mb-2 border-b border-slate-200/80" x-show="sidebarOpen || window.innerWidth < 1024">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 min-w-0 rounded-xl p-1.5 hover:bg-white/80 transition-colors" wire:navigate @click="closeMobile()">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-rose-200 bg-rose-100 text-rose-700 shadow-2xs">
                                <x-ti-wallet class="size-5" aria-hidden="true" />
                            </div>
                            <div class="leading-tight truncate">
                                <span class="flex items-center gap-1 text-sm font-bold text-slate-900 truncate">
                                    <span>Bank BINI</span>
                                    <x-ti-heart class="size-3.5 text-rose-500 shrink-0" aria-hidden="true" />
                                </span>
                                <span class="block text-xs text-slate-600 truncate">Kantong Manis Keluarga</span>
                            </div>
                        </a>

                        <button
                            type="button"
                            @click="closeMobile()"
                            class="inline-flex size-8 shrink-0 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-200/70 hover:text-slate-900 transition-colors focus-visible:outline-2 focus-visible:outline-emerald-600 lg:hidden"
                            title="Tutup Menu"
                            aria-label="Tutup Menu"
                        >
                            <x-ti-x class="size-4.5" aria-hidden="true" />
                        </button>
                        <button
                            type="button"
                            @click="toggleSidebar()"
                            class="hidden lg:inline-flex size-8 shrink-0 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-200/70 hover:text-slate-900 transition-colors focus-visible:outline-2 focus-visible:outline-emerald-600"
                            title="Perkecil Sidebar"
                            aria-label="Perkecil Sidebar"
                        >
                            <x-ti-layout-sidebar-left-collapse class="size-4.5" aria-hidden="true" />
                        </button>
                    </div>

                    {{-- Header saat Sidebar Diciutkan di Desktop --}}
                    <div class="hidden lg:flex flex-col items-center gap-2 pb-3 mb-2 border-b border-slate-200/80" x-show="!sidebarOpen" x-cloak>
                        <a
                            href="{{ route('dashboard') }}"
                            class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-rose-200 bg-rose-100 text-rose-700 hover:bg-rose-200/70 transition-colors"
                            wire:navigate
                            x-tooltip="Bank BINI"
                            data-position="right"
                            data-tooltip-delay="flash"
                            x-bind:data-tooltip-disabled="sidebarOpen || mobileSidebarOpen"
                            aria-label="Bank BINI"
                        >
                            <x-ti-wallet class="size-5" aria-hidden="true" />
                        </a>
                        <button
                            type="button"
                            @click="toggleSidebar()"
                            class="inline-flex size-8 items-center justify-center rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-200/70 transition-colors focus-visible:outline-2 focus-visible:outline-emerald-600"
                            x-tooltip="Perbesar Sidebar"
                            data-position="right"
                            data-tooltip-delay="flash"
                            x-bind:data-tooltip-disabled="sidebarOpen || mobileSidebarOpen"
                            aria-label="Perbesar Sidebar"
                        >
                            <x-ti-layout-sidebar-left-expand class="size-4.5" aria-hidden="true" />
                        </button>
                    </div>

                    {{-- Tombol Aksi Cepat di Sidebar --}}
                    <div class="mb-3" x-show="sidebarOpen || window.innerWidth < 1024">
                        <div class="grid grid-cols-3 gap-1.5 rounded-2xl bg-white p-1.5 border border-slate-200/90 shadow-2xs">
                            <a
                                href="{{ route('transactions.index', ['action' => 'income']) }}"
                                wire:navigate
                                @click="closeMobile()"
                                class="flex flex-col items-center justify-center gap-1 rounded-xl py-2 px-1 text-xs font-semibold text-emerald-800 bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200/70 transition-colors"
                                title="Tambah Pemasukan"
                            >
                                <x-ti-plus class="size-4 text-emerald-700" aria-hidden="true" />
                                <span>Masuk</span>
                            </a>
                            <a
                                href="{{ route('transactions.index', ['action' => 'expense']) }}"
                                wire:navigate
                                @click="closeMobile()"
                                class="flex flex-col items-center justify-center gap-1 rounded-xl py-2 px-1 text-xs font-semibold text-rose-800 bg-rose-50 hover:bg-rose-100/80 border border-rose-200/70 transition-colors"
                                title="Catat Pengeluaran"
                            >
                                <x-ti-minus class="size-4 text-rose-700" aria-hidden="true" />
                                <span>Keluar</span>
                            </a>
                            <a
                                href="{{ route('transfers.index') }}"
                                wire:navigate
                                @click="closeMobile()"
                                class="flex flex-col items-center justify-center gap-1 rounded-xl py-2 px-1 text-xs font-semibold text-sky-800 bg-sky-50 hover:bg-sky-100/80 border border-sky-200/70 transition-colors"
                                title="Transfer Antar Kantong"
                            >
                                <x-ti-arrows-exchange class="size-4 text-sky-700" aria-hidden="true" />
                                <span>Transfer</span>
                            </a>
                        </div>
                    </div>

                    {{-- Navigasi Utama --}}
                    <nav class="flex-1 overflow-y-auto space-y-1" aria-label="Menu Dompet">
                        <ul class="flex flex-col gap-1 w-full ps-0">
                            <x-sidebar.item
                                href="{{ route('dashboard') }}"
                                icon="layout-dashboard"
                                title="Dashboard"
                                :active="request()->routeIs('dashboard')"
                            />

                            <x-sidebar.item
                                href="{{ route('pockets.index') }}"
                                icon="wallet"
                                title="Kantong"
                                :active="request()->routeIs('pockets.*')"
                            />

                            <x-sidebar.item
                                href="{{ route('transactions.index') }}"
                                icon="list-details"
                                title="Transaksi"
                                :active="request()->routeIs('transactions.*')"
                            />

                            <x-sidebar.item
                                href="{{ route('transfers.index') }}"
                                icon="arrows-exchange"
                                title="Transfer"
                                :active="request()->routeIs('transfers.*')"
                            />

                            <x-sidebar.item
                                href="{{ route('reports.index') }}"
                                icon="chart-bar"
                                title="Laporan"
                                :active="request()->routeIs('reports.*')"
                            />

                            <x-sidebar.heading title="Akun & Sistem" />

                            <x-sidebar.item
                                href="{{ route('account') }}"
                                icon="settings"
                                title="Pengaturan"
                                :active="request()->routeIs('account')"
                            />

                            @role('administrator')
                                <x-sidebar.item
                                    href="{{ route('user') }}"
                                    icon="users"
                                    title="Pengguna"
                                    :active="request()->routeIs('user') || request()->routeIs('user.create') || request()->routeIs('user.edit')"
                                />
                            @endrole
                        </ul>
                    </nav>

                    {{-- Kartu Profil Pengguna di Bawah Sidebar --}}
                    <div class="mt-auto pt-2">
                        <div class="relative w-full" x-data="{ sidebarUserMenuOpen: false }">
                            <button
                                type="button"
                                x-ref="userMenuTrigger"
                                class="flex w-full items-center rounded-xl p-2 bg-white hover:bg-rose-50/40 border border-slate-200/90 shadow-2xs transition-all text-left focus-visible:outline-2 focus-visible:outline-emerald-600"
                                :class="sidebarOpen ? 'justify-between' : 'lg:justify-center'"
                                @click="sidebarUserMenuOpen = !sidebarUserMenuOpen"
                                x-tooltip="{{ e(Auth::user()?->name) }}"
                                data-position="right"
                                data-tooltip-delay="flash"
                                x-bind:data-tooltip-disabled="sidebarOpen || mobileSidebarOpen || sidebarUserMenuOpen"
                                aria-label="Menu akun {{ Auth::user()?->name }}"
                            >
                                <div class="flex items-center gap-2.5 min-w-0" :class="sidebarOpen ? '' : 'lg:justify-center'">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-800 border border-rose-200 font-bold text-xs uppercase">
                                        {{ substr(auth()->user()->name, 0, 2) }}
                                    </div>
                                    <div class="truncate leading-tight" :class="sidebarOpen ? 'block' : 'lg:hidden'">
                                        <p class="text-sm font-semibold text-slate-900 truncate">{{ Auth::user()?->name }}</p>
                                        <p class="text-xs text-slate-500 truncate">{{ Auth::user()?->jabatan ?? Auth::user()?->email }}</p>
                                    </div>
                                </div>
                                <div :class="sidebarOpen ? 'block' : 'lg:hidden'">
                                    <x-ti-selector class="size-4 text-slate-500 shrink-0" aria-hidden="true" />
                                </div>
                            </button>

                            <template x-teleport="body">
                                <div
                                    data-floating
                                    class="absolute z-50 w-56 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-md"
                                    x-show="sidebarUserMenuOpen"
                                    x-anchor.top-start.offset.8="$refs.userMenuTrigger"
                                    @click.outside="if (!$refs.userMenuTrigger?.contains($event.target)) sidebarUserMenuOpen = false"
                                    @keydown.escape.window="sidebarUserMenuOpen = false"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 translate-y-1"
                                    x-cloak
                                >
                                    <div class="px-3 py-2 border-b border-slate-100">
                                        <p class="font-semibold text-sm text-slate-900 truncate">{{ Auth::user()?->name }}</p>
                                        <p class="text-xs text-slate-500 truncate">{{ Auth::user()?->email }}</p>
                                    </div>
                                    <div class="mt-1 space-y-0.5">
                                        <a class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors" href="{{ route('account') }}" wire:navigate @click="sidebarUserMenuOpen = false; closeMobile()">
                                            <x-ti-settings class="size-4 text-slate-500" aria-hidden="true" /> Pengaturan & Akun
                                        </a>
                                        <button
                                            type="button"
                                            class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50 transition-colors"
                                            onclick="document.getElementById('logout-form').submit();"
                                        >
                                            <x-ti-logout class="size-4 text-rose-600" aria-hidden="true" /> Keluar
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </aside>
        @endif

        <!-- Form logout tersembunyi -->
        <form class="hidden" id="logout-form" action="{{ route('logout') }}" method="POST">
            @csrf
        </form>

        <div class="flex flex-1 flex-col min-w-0">
            <!-- Header Mobile -->
            @auth
                <header
                    class="sticky top-0 z-30 flex h-14 w-full shrink-0 items-center justify-between border-b border-slate-200/80 bg-white/95 px-3.5 sm:px-5 backdrop-blur-md lg:hidden select-none"
                >
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="toggleSidebar()"
                            class="inline-flex size-11 items-center justify-center rounded-xl text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition-colors focus-visible:outline-2 focus-visible:outline-emerald-600"
                            id="hamburger-btn"
                            aria-label="Buka Menu Navigasi"
                        >
                            <x-ti-menu-2 class="size-5" aria-hidden="true" />
                        </button>
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2" wire:navigate>
                            <div class="flex size-8 items-center justify-center rounded-xl bg-rose-100 text-rose-700 border border-rose-200">
                                <x-ti-wallet class="size-4.5" aria-hidden="true" />
                            </div>
                            <span class="text-sm font-bold text-slate-900 tracking-tight flex items-center gap-1">
                                <span>Bank BINI</span>
                                <x-ti-heart class="size-3.5 text-rose-500" aria-hidden="true" />
                            </span>
                        </a>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <a
                            href="{{ route('transactions.index', ['action' => 'income']) }}"
                            wire:navigate
                            class="inline-flex h-9 items-center gap-1 rounded-xl bg-emerald-50 px-2.5 text-xs font-semibold text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition-colors"
                            title="Tambah Pemasukan"
                        >
                            <x-ti-plus class="size-3.5 text-emerald-700" aria-hidden="true" />
                            <span class="hidden sm:inline">Pemasukan</span>
                        </a>
                        <a
                            href="{{ route('transactions.index', ['action' => 'expense']) }}"
                            wire:navigate
                            class="inline-flex h-9 items-center gap-1 rounded-xl bg-rose-50 px-2.5 text-xs font-semibold text-rose-800 border border-rose-200 hover:bg-rose-100 transition-colors"
                            title="Catat Pengeluaran"
                        >
                            <x-ti-minus class="size-3.5 text-rose-700" aria-hidden="true" />
                            <span class="hidden sm:inline">Pengeluaran</span>
                        </a>
                        <a
                            href="{{ route('account') }}"
                            wire:navigate
                            class="inline-flex size-9 items-center justify-center rounded-xl bg-rose-100 text-rose-800 border border-rose-200 font-bold text-xs uppercase"
                            aria-label="Pengaturan Akun"
                        >
                            {{ substr(auth()->user()->name, 0, 2) }}
                        </a>
                    </div>
                </header>
            @endauth

            <!-- Konten Utama (diberi ruang pb-24 pada mobile agar tidak tertutup bottom navigation) -->
            <main id="main-content" class="w-full flex-1 overflow-y-auto flex flex-col min-h-screen lg:min-h-0 pb-20 lg:pb-0">
                <div class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                    {{ $slot }}
                </div>

                <footer class="mt-auto border-t border-slate-200/80 bg-white/70 py-3.5 px-4 sm:px-6 lg:px-8 text-center text-xs text-slate-500 select-none">
                    <p class="leading-relaxed">
                        &copy; {{ date('Y') }} <strong class="text-slate-700">Bank BINI</strong> &bull; Aplikasi Kantong Keuangan Keluarga
                    </p>
                </footer>
            </main>

            <!-- Bottom Navigation Khusus Mobile (Target Sentuh >= 44px) -->
            @auth
                <nav
                    aria-label="Navigasi Bawah Ponsel"
                    class="fixed bottom-0 inset-x-0 z-30 border-t border-slate-200 bg-white/95 backdrop-blur-md px-2 py-1 lg:hidden select-none shadow-sm"
                >
                    <div class="grid grid-cols-5 gap-1 max-w-md mx-auto">
                        <a
                            href="{{ route('dashboard') }}"
                            wire:navigate
                            class="flex flex-col items-center justify-center min-h-12 rounded-xl px-1 py-1 text-xs font-medium transition-colors {{ request()->routeIs('dashboard') ? 'text-emerald-800 bg-emerald-50/90 font-semibold' : 'text-slate-600 hover:text-slate-900' }}"
                        >
                            <x-ti-layout-dashboard class="size-5 {{ request()->routeIs('dashboard') ? 'text-emerald-600' : 'text-slate-500' }}" aria-hidden="true" />
                            <span class="mt-0.5 truncate">Dasbor</span>
                        </a>

                        <a
                            href="{{ route('pockets.index') }}"
                            wire:navigate
                            class="flex flex-col items-center justify-center min-h-12 rounded-xl px-1 py-1 text-xs font-medium transition-colors {{ request()->routeIs('pockets.*') ? 'text-emerald-800 bg-emerald-50/90 font-semibold' : 'text-slate-600 hover:text-slate-900' }}"
                        >
                            <x-ti-wallet class="size-5 {{ request()->routeIs('pockets.*') ? 'text-emerald-600' : 'text-slate-500' }}" aria-hidden="true" />
                            <span class="mt-0.5 truncate">Kantong</span>
                        </a>

                        <a
                            href="{{ route('transfers.index') }}"
                            wire:navigate
                            class="flex flex-col items-center justify-center min-h-12 rounded-xl px-1 py-1 text-xs font-medium transition-colors {{ request()->routeIs('transfers.*') ? 'text-rose-800 bg-rose-50/90 font-semibold' : 'text-slate-600 hover:text-slate-900' }}"
                        >
                            <x-ti-arrows-exchange class="size-5 {{ request()->routeIs('transfers.*') ? 'text-rose-600' : 'text-slate-500' }}" aria-hidden="true" />
                            <span class="mt-0.5 truncate">Transfer</span>
                        </a>

                        <a
                            href="{{ route('transactions.index') }}"
                            wire:navigate
                            class="flex flex-col items-center justify-center min-h-12 rounded-xl px-1 py-1 text-xs font-medium transition-colors {{ request()->routeIs('transactions.*') ? 'text-emerald-800 bg-emerald-50/90 font-semibold' : 'text-slate-600 hover:text-slate-900' }}"
                        >
                            <x-ti-list-details class="size-5 {{ request()->routeIs('transactions.*') ? 'text-emerald-600' : 'text-slate-500' }}" aria-hidden="true" />
                            <span class="mt-0.5 truncate">Transaksi</span>
                        </a>

                        <a
                            href="{{ route('reports.index') }}"
                            wire:navigate
                            class="flex flex-col items-center justify-center min-h-12 rounded-xl px-1 py-1 text-xs font-medium transition-colors {{ request()->routeIs('reports.*') ? 'text-emerald-800 bg-emerald-50/90 font-semibold' : 'text-slate-600 hover:text-slate-900' }}"
                        >
                            <x-ti-chart-bar class="size-5 {{ request()->routeIs('reports.*') ? 'text-emerald-600' : 'text-slate-500' }}" aria-hidden="true" />
                            <span class="mt-0.5 truncate">Laporan</span>
                        </a>
                    </div>
                </nav>
            @endauth
        </div>

        @livewireScripts
        @stack('scripts')
    </div>
</body>

</html>
