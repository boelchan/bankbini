<button
    type="button"
    wire:ignore
    @click="toggleBalances()"
    class="inline-flex min-h-11 min-w-11 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-emerald-600 cursor-pointer sm:min-w-0"
    :aria-label="balancesHidden ? 'Tampilkan nominal saldo dan transaksi' : 'Sembunyikan nominal saldo dan transaksi'"
    :title="balancesHidden ? 'Tampilkan nominal' : 'Sembunyikan nominal'"
    x-cloak
>
    <x-ti-eye x-show="!balancesHidden" class="size-3.5" aria-hidden="true" />
    <x-ti-eye-off x-show="balancesHidden" class="size-3.5" aria-hidden="true" />
    <span class="hidden sm:inline" x-text="balancesHidden ? 'Tampilkan nominal' : 'Sembunyikan nominal'"></span>
</button>
