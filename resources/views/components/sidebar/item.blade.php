@props([
    'href' => '#',
    'icon' => null,
    'title' => null,
    'active' => null,
    'activePattern' => null,
    'external' => false,
    'badge' => null,
])

@php
    if ($active === null) {
        if ($activePattern) {
            $active = request()->is($activePattern) || request()->routeIs($activePattern);
        } else {
            $currentUrl = url()->current();
            $targetUrl = url($href);
            $active = $currentUrl === $targetUrl || Str::startsWith($currentUrl, $targetUrl . '/');
        }
    }

    $tooltipLabel = trim((string) ($title ?? strip_tags((string) $slot)));
@endphp

<li>
    <a
        href="{{ $href }}"
        @if(!$external)
            wire:navigate
            @click="closeMobile()"
        @else
            target="_blank"
            rel="noopener noreferrer"
        @endif
        @if($tooltipLabel !== '')
            x-tooltip="{{ e($tooltipLabel) }}"
            data-position="right"
            data-tooltip-delay="flash"
            x-bind:data-tooltip-disabled="sidebarOpen || mobileSidebarOpen"
            aria-label="{{ $tooltipLabel }}"
        @endif
        {{ $attributes->merge([
            'class' => 'sidebar-link group flex min-h-10 items-center rounded-xl text-sm transition-all duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 ' . (
                $active
                    ? 'bg-white text-emerald-800 font-semibold border border-slate-200 shadow-xs'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70 border border-transparent'
            )
        ]) }}
        :class="sidebarOpen ? 'gap-2.5 px-3' : 'lg:justify-center lg:px-0 gap-2.5 px-3'"
    >
        @if (isset($customIcon))
            {{ $customIcon }}
        @elseif ($icon)
            <x-dynamic-component
                :component="'ti-' . $icon"
                class="size-4.5 shrink-0 transition-colors {{ $active ? 'text-emerald-600' : 'text-slate-500 group-hover:text-slate-800' }}"
                aria-hidden="true"
            />
        @endif

        <span :class="sidebarOpen ? 'inline' : 'lg:hidden'" class="truncate">
            {{ $title ?? $slot }}
        </span>
    </a>
</li>
