<div>
    <!-- Modal Edit Permission -->
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: @entangle('showModal') }" x-show="open" x-cloak>
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" x-show="open" @click="$wire.closeModal()"></div>

        <!-- Modal Content -->
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl overflow-hidden" x-show="open" @click.away="$wire.closeModal()">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Edit Permission</h3>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Atur permission untuk role <span class="text-emerald-600 font-semibold">{{ $role_name }}</span>
                        </p>
                    </div>
                    <x-button.circle color="gray" flat sm type="button" wire:click="closeModal">
                        <x-ti-x class="size-4" />
                    </x-button.circle>
                </div>

                <!-- Body -->
                <div class="max-h-[60vh] overflow-y-auto p-6">
                    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($allPermissions as $id => $name)
                            <label
                                class="hover:border-emerald-300 hover:bg-emerald-50 group flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3 transition-all duration-200"
                                :class="{
                                    'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500': $wire.selectedPermissions.includes(
                                        {{ $id }}),
                                    'border-slate-200': !$wire.selectedPermissions.includes({{ $id }})
                                }"
                            >
                                <input class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 h-4 w-4" type="checkbox" value="{{ $id }}"
                                    wire:model="selectedPermissions"
                                >
                                <span class="group-hover:text-emerald-700 text-sm font-medium text-slate-700">
                                    {{ $name }}
                                </span>
                            </label>
                        @endforeach
                    </div>

                    @if (count($allPermissions) === 0)
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <x-ti-lock-off class="size-10 text-slate-300" />
                            <p class="mt-2 text-sm text-slate-500">Belum ada permission tersedia</p>
                        </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between border-t border-slate-200 px-6 py-4 bg-slate-50">
                    <p class="text-sm text-slate-500">
                        <span class="text-emerald-600 font-semibold" wire:text="selectedPermissions.length">0</span>
                        permission dipilih
                    </p>
                    <div class="flex items-center gap-2">
                        <x-button color="gray" flat type="button" wire:click="closeModal">
                            Batal
                        </x-button>
                        <x-button
                            color="emerald"
                            type="button"
                            wire:click="save"
                            loading="save"
                        >
                            Simpan
                        </x-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
