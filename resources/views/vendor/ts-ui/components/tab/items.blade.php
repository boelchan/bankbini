<div x-show="selected === @js($tab)" role="tabpanel" x-init="(() => {
    const item = {
        tab: @js($tab),
        title: @js($title),
        right: @js($content['right']),
        left: @js($content['left']),
        href: @js($href),
        navigate: @js((bool) $navigate),
        navigateHover: @js((bool) $navigateHover)
    };
    const idx = tabs.findIndex(t => t.tab === @js($tab));
    if (idx >= 0) {
        tabs.splice(idx, 1, item);
    } else {
        tabs.push(item);
    }
    @if($shouldRender && $href) selected = @js($tab); @endif

    if (typeof $cleanup === 'function') {
        $cleanup(() => {
            const removeIdx = tabs.findIndex(t => t.tab === @js($tab));
            if (removeIdx >= 0) {
                tabs.splice(removeIdx, 1);
            }
        });
    }
})()" aria-labelledby="{{ $tab }}">
    @if ($shouldRender)
        {{ $slot }}
    @endif
</div>
