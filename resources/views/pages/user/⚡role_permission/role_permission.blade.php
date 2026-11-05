<div>
    <h1 class="text-2xl font-medium text-slate-900">Role dan Permission</h1>
    <nav class="flex items-center gap-1.5 text-xs text-slate-500 mt-1">
        <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition-colors"><x-ti-home class="size-4" /></a>
        <span>/</span>
        <span class="text-slate-700 font-medium">Role dan Permission</span>
    </nav>

    <div class="mt-6">
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="col-span-3 lg:col-span-1 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col">
                <h2 class="text-lg font-bold text-slate-800 mb-4">Role</h2>
                <div class="space-y-2">
                    @foreach ($roles as $role)
                        <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-100 p-2 hover:border-slate-300 transition-colors bg-slate-50/50">
                            <div class="flex flex-col">
                                <span class="font-medium text-slate-800 text-sm">{{ $role->name }}</span>
                                <span class="text-xs text-slate-500">{{ $role->permissions->count() }} permission</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <x-button.circle color="emerald" light sm title="Edit Permission"
                                    wire:click="dispatch('role-permission-edit', { role_id: {{ $role->id }} })"
                                >
                                    <x-ti-key class="size-4" />
                                </x-button.circle>
                                <x-button.circle color="sky" light sm title="Lihat User"
                                    wire:click="lihatUser({{ $role->id }})"
                                >
                                    <x-ti-users class="size-4" />
                                </x-button.circle>

                                @if (env('APP_ENV') == 'local' || !in_array($role->name, ['administrator', 'dokter', 'bidan', 'koordinator-bidan', 'apoteker']))
                                    <x-button.circle color="emerald" flat sm title="Edit Role"
                                        wire:click="dispatch('role-edit', { role_id: {{ $role->id }} })"
                                    >
                                        <x-ti-edit class="size-4" />
                                    </x-button.circle>
                                    <x-button.circle color="red" flat sm title="Hapus Role"
                                        wire:click="deleteRole({{ $role->id }})"
                                    >
                                        <x-ti-trash class="size-4" />
                                    </x-button.circle>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100">
                    <livewire:user.role_form />
                </div>
            </div>

            <div class="col-span-3 lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col">
                <h2 class="text-lg font-bold text-slate-800 mb-4">Permission</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach ($permissions as $permission)
                        <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-100 p-2 hover:border-slate-300 transition-colors bg-slate-50/50">
                            <span class="text-xs font-medium text-slate-700 truncate" title="{{ $permission->name }}">{{ $permission->name }}</span>
                            <div class="flex items-center gap-1 shrink-0">
                                @if (env('APP_ENV') == 'local')
                                    <x-button.circle color="emerald" flat sm title="Edit Permission"
                                        wire:click="dispatch('permission-edit', { permission_id: {{ $permission->id }} })"
                                    >
                                        <x-ti-edit class="size-4" />
                                    </x-button.circle>
                                    <x-button.circle color="red" flat sm title="Hapus"
                                        wire:click="deletePermission({{ $permission->id }})"
                                    >
                                        <x-ti-trash class="size-4" />
                                    </x-button.circle>
                                @endif
                                <x-button.circle color="emerald" light sm title="Lihat Role"
                                    wire:click="lihatRole({{ $permission->id }})">
                                    <x-ti-list class="size-4" />
                                </x-button.circle>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if (env('APP_ENV') == 'local')
                    <div class="mt-6 pt-4 border-t border-slate-100">
                        <livewire:user.permission_form />
                    </div>
                @endif
            </div>

        </div>

        <livewire:user.role_permission_form />

    </div>

    {{-- Modal: Lihat Role yang menggunakan Permission --}}
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: @entangle('showRolesModal') }" x-show="open" x-cloak>
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" x-show="open" @click="$wire.closeRolesModal()"></div>
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl overflow-hidden" x-show="open" @click.away="$wire.closeRolesModal()">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">Role yang Menggunakan <span class="text-emerald-600">{{ $permissionName }}</span></h3>
                    <x-button.circle color="gray" flat sm type="button" wire:click="closeRolesModal">
                        <x-ti-x class="size-4" />
                    </x-button.circle>
                </div>
                <div class="max-h-[60vh] overflow-y-auto p-6">
                    @if (count($permissionRoles) > 0)
                        <div class="space-y-2">
                            @foreach ($permissionRoles as $pr)
                                <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-3">
                                    <div class="flex items-center gap-2">
                                        <x-ti-shield class="size-5 text-emerald-600" />
                                        <span class="font-medium text-slate-800 text-sm">{{ $pr['name'] }}</span>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-800 border border-sky-200">{{ $pr['users_count'] }} user</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <x-ti-shield-off class="size-10 text-slate-300" />
                            <p class="mt-2 text-sm text-slate-500">Tidak ada role yang menggunakan permission ini</p>
                        </div>
                    @endif
                </div>
                <div class="flex justify-end border-t border-slate-200 px-6 py-4 bg-slate-50">
                    <x-button color="gray" flat type="button" wire:click="closeRolesModal">Tutup</x-button>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Modal: Lihat User yang Memiliki Role --}}
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: @entangle('showUsersModal') }" x-show="open" x-cloak>
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" x-show="open" @click="$wire.closeUsersModal()"></div>
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl overflow-hidden" x-show="open" @click.away="$wire.closeUsersModal()">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">User dengan Role <span class="text-emerald-600">{{ $roleName }}</span></h3>
                    <x-button.circle color="gray" flat sm type="button" wire:click="closeUsersModal">
                        <x-ti-x class="size-4" />
                    </x-button.circle>
                </div>
                <div class="max-h-[60vh] overflow-y-auto p-6">
                    @if (count($roleUsers) > 0)
                        <div class="space-y-2">
                            @foreach ($roleUsers as $user)
                                <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/50 p-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                        <x-ti-user class="size-5" />
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-800 text-sm">{{ $user['name'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $user['email'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <x-ti-user-off class="size-10 text-slate-300" />
                            <p class="mt-2 text-sm text-slate-500">Tidak ada user yang memiliki role ini</p>
                        </div>
                    @endif
                </div>
                <div class="flex justify-end border-t border-slate-200 px-6 py-4 bg-slate-50">
                    <x-button color="gray" flat type="button" wire:click="closeUsersModal">Tutup</x-button>
                </div>
            </div>
        </div>
    </div>
</div>
