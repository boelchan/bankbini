@props([
    'icon' => 'wallet',
    'class' => 'size-5',
])

@php
    $iconValue = $icon instanceof \App\Enums\WalletIconEnum ? $icon->value : (string) $icon;
@endphp

@switch($iconValue)
    @case('piggy-bank')
        <x-ti-pig-money :class="$class" />
        @break
    @case('shopping-bag')
        <x-ti-shopping-bag :class="$class" />
        @break
    @case('home-heart')
        <x-ti-home-heart :class="$class" />
        @break
    @case('plane')
        <x-ti-plane-departure :class="$class" />
        @break
    @case('sparkles')
        <x-ti-sparkles :class="$class" />
        @break
    @case('school')
        <x-ti-school :class="$class" />
        @break
    @case('heart-handshake')
        <x-ti-heart-handshake :class="$class" />
        @break
    @case('FirstAidKit')
        <x-ti-first-aid-kit :class="$class" />
        @break
    @case('gift')
        <x-ti-gift :class="$class" />
        @break
    @case('coffee')
        <x-ti-coffee :class="$class" />
        @break
    @case('car')
        <x-ti-car :class="$class" />
        @break
    @default
        <x-ti-wallet :class="$class" />
@endswitch

