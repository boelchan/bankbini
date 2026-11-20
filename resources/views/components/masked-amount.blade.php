@props([
    'value',
])

<span wire:ignore wire:key="masked-amount-{{ $value }}" {{ $attributes }}>
    <span x-show="!balancesHidden" x-cloak>{{ $value }}</span>
    <span x-show="balancesHidden" x-cloak>***</span>
</span>
