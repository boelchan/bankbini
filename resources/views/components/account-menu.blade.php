@props([
    'position' => 'top-start',
    'trigger' => '$refs.userMenuTrigger',
    'state' => 'userMenuOpen',
])

{{-- Menu akun ringkas, dipakai bersama oleh sidebar dan navbar bawah. --}}
<template x-teleport="body">
    <div
        data-floating
        class="absolute z-50 w-56 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-md"
        x-show="{{ $state }}"
        x-anchor.{{ $position }}.offset.8="{{ $trigger }}"
        @click.outside="if (!{{ $trigger }}?.contains($event.target)) {{ $state }} = false"
        @keydown.escape.window="{{ $state }} = false"
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
        <div class="mt-1 space-y-0.5" role="menu">
            <a
                role="menuitem"
                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors"
                href="{{ route('account') }}"
                wire:navigate
                @click="{{ $state }} = false; closeMobile()"
            >
                <x-ti-settings class="size-4 text-slate-500" aria-hidden="true" /> Profil & Kategori
            </a>
            <button
                type="button"
                role="menuitem"
                class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50 transition-colors"
                onclick="document.getElementById('logout-form').submit();"
            >
                <x-ti-logout class="size-4 text-rose-600" aria-hidden="true" /> Keluar
            </button>
        </div>
    </div>
</template>
