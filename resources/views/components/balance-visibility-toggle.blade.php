<x-button.circle
    flat
    color="gray"
    wire:ignore
    @click="toggleBalances()"
    x-cloak
>
    <x-ti-eye-off x-show="!balancesHidden" class="text-md" aria-hidden="true" />
    <x-ti-eye x-show="balancesHidden" class="text-md" aria-hidden="true" />
</x-button.circle>
