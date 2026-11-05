@props([
    'title' => null,
])

<li class="pt-4 pb-1">
    <span class="px-2 text-xs font-medium text-neutral-500" :class="sidebarOpen ? 'block' : 'lg:hidden'">
        {{ $title ?? $slot }}
    </span>
    <div class="hidden border-t border-neutral-200 my-1 mx-2" :class="!sidebarOpen ? 'lg:block' : ''"></div>
</li>
