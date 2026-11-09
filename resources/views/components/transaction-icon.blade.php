@props([
    'icon' => null,
    'type' => null,
    'class' => 'size-5',
])

@php
    $iconName = $icon;
    if (!$iconName) {
        $iconName = match ($type instanceof \App\Enums\TransactionTypeEnum ? $type : null) {
            \App\Enums\TransactionTypeEnum::INCOME => 'arrow-down-left',
            \App\Enums\TransactionTypeEnum::EXPENSE => 'arrow-up-right',
            \App\Enums\TransactionTypeEnum::TRANSFER_IN,
            \App\Enums\TransactionTypeEnum::TRANSFER_OUT => 'arrows-exchange',
            default => 'receipt',
        };
    }
@endphp

<x-dynamic-component :component="'ti-' . $iconName" :class="$class" />
