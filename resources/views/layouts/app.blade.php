<!DOCTYPE html>
<html class="light" lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link type="image/png" href="{{ asset('favicon/favicon-96x96.png') }}" rel="icon" sizes="96x96" />
    <link type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}" rel="icon" />
    <link href="{{ asset('favicon/favicon.ico') }}" rel="shortcut icon" />
    <link href="{{ asset('favicon/apple-touch-icon.png') }}" rel="apple-touch-icon" sizes="180x180" />
    <meta name="apple-mobile-web-app-title" content="Kantong Kehamilan" />
    <link href="{{ asset('favicon/manifest.json') }}" rel="manifest" />

    <title>{{ $title ?? config('app.name') }}</title>

    <!-- Tipografi: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    @livewireMapStyles
    <tallstackui:script />
    @stack('styles')
</head>

<body class="bg-neutral-100 text-slate-900 antialiased font-sans">
    <x-toast />
    <x-dialog />

    <div
        class="relative min-h-screen w-full bg-neutral-100 flex flex-col lg:flex-row"
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

        <!-- Sidebar Navigation -->
        @if (Auth::check())
            <aside
                id="sidebar"
                class="fixed inset-y-0 left-0 z-50 flex flex-col bg-white border-r border-neutral-200 p-3 transition-all duration-300 ease-in-out lg:static lg:h-screen lg:sticky lg:top-0 lg:z-30 select-none overflow-hidden"
                :class="[
                    mobileSidebarOpen ? 'translate-x-0 shadow-lg w-72 max-w-[85vw]' : '-translate-x-full lg:translate-x-0',
                    sidebarOpen ? 'lg:w-64 lg:p-3' : 'lg:w-16 lg:p-2'
                ]"
            >
                {{-- Scrollable content wrapper --}}
                <div class="relative z-10 flex h-full flex-col min-h-0">
                    {{-- Header when Sidebar is Expanded (or on Mobile) --}}
                    <div class="flex items-center justify-between pb-3 mb-1" x-show="sidebarOpen || window.innerWidth < 1024">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 min-w-0 rounded-md p-1 hover:bg-neutral-100 transition-colors" wire:navigate @click="closeMobile()">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-md border border-neutral-200 bg-white">
                                <img class="size-5 object-contain" src="{{ asset('favicon/web-app-manifest-192x192.png') }}" alt="logo">
                            </div>
                            <div class="leading-tight truncate">
                                <span class="block text-sm font-semibold text-neutral-900 truncate">Kantong Kehamilan</span>
                                <span class="block text-xs text-neutral-500">Puskesmas Pragaan</span>
                            </div>
                        </a>

                        <button
                            type="button"
                            @click="closeMobile()"
                            class="inline-flex size-7 shrink-0 items-center justify-center rounded-md text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 transition-colors focus:outline-none lg:hidden"
                            title="Tutup Menu"
                        >
                            <x-ti-x class="size-4" />
                        </button>
                        <button
                            type="button"
                            @click="toggleSidebar()"
                            class="hidden lg:inline-flex size-7 shrink-0 items-center justify-center rounded-md text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 transition-colors focus:outline-none"
                            title="Perkecil Sidebar"
                        >
                            <x-ti-layout-sidebar-left-collapse class="size-4" />
                        </button>
                    </div>

                    {{-- Header when Sidebar is Collapsed on Desktop (Icon-only mode) --}}
                    <div class="hidden lg:flex flex-col items-center gap-2 pb-3 mb-1" x-show="!sidebarOpen" x-cloak>
                        <a
                            href="{{ route('dashboard') }}"
                            class="flex size-8 shrink-0 items-center justify-center rounded-md border border-neutral-200 bg-white hover:bg-neutral-100 transition-colors"
                            wire:navigate
                            x-tooltip="Kantong Kehamilan"
                            data-position="right"
                            data-tooltip-delay="flash"
                            x-bind:data-tooltip-disabled="sidebarOpen || mobileSidebarOpen"
                            aria-label="Kantong Kehamilan"
                        >
                            <img class="size-5 object-contain" src="{{ asset('favicon/web-app-manifest-192x192.png') }}" alt="logo">
                        </a>
                        <button
                            type="button"
                            @click="toggleSidebar()"
                            class="inline-flex size-7 items-center justify-center rounded-md text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 transition-colors focus:outline-none"
                            x-tooltip="Perbesar Sidebar"
                            data-position="right"
                            data-tooltip-delay="flash"
                            x-bind:data-tooltip-disabled="sidebarOpen || mobileSidebarOpen"
                            aria-label="Perbesar Sidebar"
                        >
                            <x-ti-layout-sidebar-left-expand class="size-4.5" />
                        </button>
                    </div>

                    {{-- Navigation Links --}}
                    <nav class="flex-1 overflow-y-auto space-y-1">
                        <ul class="flex flex-col gap-1 w-full ps-0">
                            @auth
                                <x-sidebar.item
                                    href="{{ route('dashboard') }}"
                                    icon="layout-dashboard"
                                    title="Dasbor"
                                />
                            @endauth

                            @can('pasien-list')
                                <x-sidebar.item
                                    href="{{ route('patient.index') }}"
                                    icon="users"
                                    title="Pasien"
                                />
                            @endcan

                                <x-sidebar.item
                                    href="{{ route('kantong-kehamilan.index') }}"
                                    icon="heart-handshake"
                                    title="Kantong Kehamilan"
                                />

                            <x-sidebar.heading title="Laporan" />

                                <x-sidebar.item
                                    href="{{ route('laporan.pws-ibu') }}"
                                    icon="file-analytics"
                                    title="PWS Ibu"
                                    :active="request()->routeIs('laporan.pws-ibu')"
                                />

                                <x-sidebar.item
                                    href="{{ route('laporan.lb3-maternal') }}"
                                    icon="file-report"
                                    title="LB3 Maternal"
                                    :active="request()->routeIs('laporan.lb3-maternal')"
                                />

                                <x-sidebar.item
                                    href="{{ route('laporan.pws-bayi') }}"
                                    icon="baby-bottle"
                                    title="PWS Bayi"
                                    :active="request()->routeIs('laporan.pws-bayi')"
                                />
                                
                                <x-sidebar.item
                                    href="{{ route('laporan.lb3-neonatal') }}"
                                    icon="first-aid-kit"
                                    title="LB3 Neonatal"
                                    :active="request()->routeIs('laporan.lb3-neonatal')"
                                />


                            @can('posting-pamflet')
                            
                                <x-sidebar.heading title="Informasi" />

                                <x-sidebar.item
                                    href="{{ route('post.index') }}"
                                    icon="news"
                                    title="Pamflet"
                                />
                            @endcan

                            @role('administrator')
                                <x-sidebar.heading title="Pengaturan Sistem" />

                                <x-sidebar.item
                                    href="{{ route('user') }}"
                                    icon="users"
                                    title="Pengguna"
                                    :active="Str::startsWith(url()->current(), url('user')) && !Str::startsWith(url()->current(), url('user/role-permission')) && !Str::startsWith(url()->current(), url('role-permission'))"
                                />

                                <x-sidebar.item
                                    href="{{ route('user.role_permission') }}"
                                    icon="lock-access"
                                    title="Peran & Hak Akses"
                                    :active="Str::startsWith(url()->current(), url('user/role-permission')) || Str::startsWith(url()->current(), url('role-permission'))"
                                />
                            @endrole
                        </ul>
                    </nav>

                    {{-- User Profile Card at Sidebar Bottom --}}
                    <div class="mt-auto pt-2">
                        <div class="relative w-full" x-data="{ sidebarUserMenuOpen: false }">
                            <button
                                type="button"
                                x-ref="userMenuTrigger"
                                class="flex w-full items-center rounded-xl p-2 bg-white/70 hover:bg-white border border-slate-200/80 shadow-2xs transition-all text-left focus:outline-none"
                                :class="sidebarOpen ? 'justify-between' : 'lg:justify-center'"
                                @click="sidebarUserMenuOpen = !sidebarUserMenuOpen"
                                x-tooltip="{{ e(Auth::user()?->name) }}"
                                data-position="right"
                                data-tooltip-delay="flash"
                                x-bind:data-tooltip-disabled="sidebarOpen || mobileSidebarOpen || sidebarUserMenuOpen"
                                aria-label="{{ Auth::user()?->name }}"
                            >
                                <div class="flex items-center gap-2.5 min-w-0" :class="sidebarOpen ? '' : 'lg:justify-center'">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-neutral-800 font-semibold text-xs uppercase">
                                        {{ substr(auth()->user()->name, 0, 2) }}
                                    </div>
                                    <div class="truncate leading-tight" :class="sidebarOpen ? 'block' : 'lg:hidden'">
                                        <p class="text-sm font-medium text-neutral-900 truncate">{{ Auth::user()?->name }}</p>
                                        <p class="text-xs text-neutral-500 truncate">{{ Auth::user()?->email }}</p>
                                    </div>
                                </div>
                                <div :class="sidebarOpen ? 'block' : 'lg:hidden'">
                                    <x-ti-selector class="size-4 text-neutral-500 shrink-0" />
                                </div>
                            </button>

                            <template x-teleport="body">
                                <div
                                    data-floating
                                    class="absolute z-50 w-56 rounded-lg border border-neutral-200 bg-white p-1 shadow-md"
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
                                        <p class="font-semibold text-sm text-slate-800 truncate">{{ Auth::user()?->name }}</p>
                                        <p class="text-sm text-slate-500 truncate">{{ Auth::user()?->email }}</p>
                                    </div>
                                    <div class="mt-1 space-y-0.5">
                                        <a class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100/70 transition-colors" href="{{ route('account') }}" wire:navigate @click="sidebarUserMenuOpen = false; closeMobile()">
                                            <x-ti-user-edit class="size-4 text-slate-500" /> Profil & Akun
                                        </a>
                                        <a class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 transition-colors" href="#"
                                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                        >
                                            <x-ti-logout class="size-4 text-rose-500" /> Keluar
                                        </a>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </aside>
        @endif

        <!-- Form logout tersembunyi -->
        <form class="hidden" id="logout-form" action="/logout" method="POST">
            @csrf
        </form>

        <div class="flex flex-1 flex-col min-w-0">
            <!-- Mobile Header (Sticky at top, clean, flush, minimal) -->
            @auth
                <header
                    class="sticky top-0 z-30 flex h-14 w-full shrink-0 items-center justify-between border-b border-slate-200/80 bg-white/95 px-3.5 sm:px-5 backdrop-blur-md lg:hidden select-none"
                >
                    <div class="flex items-center gap-2.5">
                        <button
                            type="button"
                            @click="toggleSidebar()"
                            class="inline-flex size-9 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors focus:outline-none"
                            id="hamburger-btn"
                            aria-label="Buka Menu Sidebar"
                        >
                            <x-ti-menu-2 class="size-5" />
                        </button>
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2" wire:navigate>
                            <img class="size-7 object-contain" src="{{ asset('favicon/web-app-manifest-192x192.png') }}" alt="logo">
                            <span class="text-sm font-bold text-slate-900 tracking-tight">Kantong Kehamilan</span>
                        </a>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <a href="{{ route('kantong-kehamilan.index') }}" class="inline-flex size-9 items-center justify-center rounded-xl text-slate-600 hover:bg-emerald-50 hover:text-emerald-700 transition-colors" title="Kantong Kehamilan" wire:navigate>
                            <x-ti-heart-handshake class="size-5" />
                        </a>
                        <a href="{{ route('patient.index') }}" class="inline-flex size-9 items-center justify-center rounded-xl text-slate-600 hover:bg-emerald-50 hover:text-emerald-700 transition-colors" title="Pasien" wire:navigate>
                            <x-ti-users class="size-5" />
                        </a>

                        <div class="relative ml-1" x-data="{ mobileUserOpen: false }">
                            <button
                                type="button"
                                class="flex size-9 items-center justify-center rounded-lg bg-emerald-600 text-white font-bold text-sm shadow-xs hover:opacity-90 transition-opacity focus:outline-none"
                                @click="mobileUserOpen = !mobileUserOpen"
                                aria-label="Menu Pengguna"
                            >
                                {{ substr(auth()->user()->name, 0, 2) }}
                            </button>
                            <div
                                class="absolute right-0 top-full z-50 mt-2 w-56 rounded-2xl border border-slate-200/80 bg-white p-1.5 shadow-xl"
                                x-show="mobileUserOpen"
                                @click.outside="mobileUserOpen = false"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 translate-y-1"
                                x-cloak
                            >
                                <div class="px-3 py-2 border-b border-slate-100">
                                    <span class="block font-semibold text-sm text-slate-800 truncate">{{ Auth::user()?->name }}</span>
                                    <span class="text-sm text-slate-500 truncate block">{{ Auth::user()?->email }}</span>
                                </div>
                                <div class="mt-1 space-y-0.5">
                                    <a class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100/70 transition-colors" href="{{ route('account') }}" wire:navigate>
                                        <x-ti-user-edit class="size-4 text-slate-500" /> Profil & Akun
                                    </a>
                                    <a class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 transition-colors" href="#"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                    >
                                        <x-ti-logout class="size-4 text-rose-500" /> Keluar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>
            @endauth

            <!-- Main Content -->
            <main class="w-full flex-1 overflow-y-auto flex flex-col min-h-screen lg:min-h-0">
                <div class="flex-1 p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </div>

                <!-- Footer Hak Cipta & Informasi Pengembang -->
                <footer class="mt-auto border-t border-slate-200/80 bg-white/60 backdrop-blur-xs py-3.5 px-4 sm:px-6 lg:px-8 text-center text-xs text-slate-500 select-none">
                    <p class="leading-relaxed">
                        &copy; {{ date('Y') }} UPTD Puskesmas Pragaan &bull; Kabupaten Sumenep
                        <span class="hidden sm:inline mx-1 text-slate-300">&bull;</span>
                        <br class="sm:hidden">
                        Dikembangkan oleh <a href="https://wa.me/081934955229" target="_blank" rel="noopener noreferrer" class="text-emerald-700 hover:text-emerald-800 font-semibold transition-colors">BulyanDev</a>
                    </p>
                </footer>
            </main>
        </div>

        <script>
            document.addEventListener('livewire:navigating', () => {
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                }
            });
            window.addEventListener('beforeunload', () => {
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                }
            });
        </script>

        @livewireScripts
        @livewireMapScripts
        @stack('scripts')
        <livewire:modal-preview />
        <livewire:modal-riwayat-kehamilan />
    </div>
</body>

</html>
