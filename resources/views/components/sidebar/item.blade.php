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
    // Auto-detect active state if not explicitly passed
    if ($active === null) {
        if ($activePattern) {
            $active = request()->is($activePattern) || request()->routeIs($activePattern);
        } else {
            $currentUrl = url()->current();
            $targetUrl = url($href);
            $active = Str::startsWith($currentUrl, $targetUrl);
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
            'class' => 'sidebar-link group flex h-9 items-center rounded-md text-sm transition-colors duration-150 ' . (
                $active
                    ? 'bg-neutral-300 text-neutral-900 font-medium'
                    : 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100'
            )
        ]) }}
        :class="sidebarOpen ? 'gap-2.5 px-2' : 'lg:justify-center lg:px-0 gap-2.5 px-2'"
    >
        @if (isset($customIcon))
            {{ $customIcon }}
        @elseif ($icon)
            <x-dynamic-component
                :component="'ti-' . $icon"
                class="size-4.5 shrink-0 transition-colors {{ $active ? 'text-neutral-900' : 'text-neutral-500 group-hover:text-neutral-700' }}"
            />
        @endif

        <span :class="sidebarOpen ? 'inline' : 'lg:hidden'" class="truncate">
            {{ $title ?? $slot }}
        </span>

    </a>
</li>
