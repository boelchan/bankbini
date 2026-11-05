<div>
    @if (env('APP_ENV') != 'production')
        <form wire:submit.prevent="store">
            <input type="hidden" wire:model="permission_id">
            <x-input placeholder="Nama permission baru" wire:model="name">
                <x-slot:suffix>
                    <button class="flex items-center justify-center h-8 w-8 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors" type="submit"><x-ti-check class="size-4" /></button>
                    <button class="flex items-center justify-center h-8 w-8 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 transition-colors ml-1" type="button" wire:click="batalEdit" wire:show="permission_id">
                        <x-ti-x class="size-4" />
                    </button>
                </x-slot:suffix>
            </x-input>
        </form>
    @endif
</div>
