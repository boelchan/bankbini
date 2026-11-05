<div>
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-medium text-slate-900">User</h1>
            <nav class="flex items-center gap-1.5 text-xs text-slate-500 mt-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition-colors"><x-ti-home class="size-4" /></a>
                <span>/</span>
                <span>User</span>
                <span>/</span>
                <span class="text-slate-700 font-medium">Data</span>
            </nav>
        </div>
        <x-button color="emerald" sm href="{{ route('user.create') }}" wire:navigate>
            <x-ti-plus class="size-4 mr-1 inline" /> Tambah
        </x-button>
    </div>

    <div class="mt-6">
        {{-- filter --}}
        <x-card>
            <div class="flex justify-between items-center mb-3">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-slate-800 text-sm">Pencarian</span>
                    <x-button.circle color="red" flat sm type="button" wire:click="resetFilters" title="Reset filter">
                        <x-ti-filter-x class="size-4" />
                    </x-button.circle>
                </div>
            </div>
            <div>
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 xl:grid-cols-5">
                    <x-input clearable label="Nama" placeholder="cari nama..." wire:model.live.debounce.500ms="search_name" />
                    <x-input clearable label="Email" placeholder="cari email..." wire:model.live.debounce.500ms="search_email" />
                    <x-select.styled
                        clearable
                        label="Role"
                        placeholder="Pilih Role"
                        wire:model.live="search_role"
                        :options="$roles"
                    />
                    <x-select.styled
                        clearable
                        label="Status"
                        placeholder="Pilih Status"
                        wire:model.live="search_status"
                        :options="$status"
                    />
                </div>
            </div>
        </x-card>
        {{-- end filter --}}

        <x-table :paginate="$this->dataTable">
            <x-table.thead class="bg-slate-100" :sortDirection="$sortDirection" :sortField="$sortField">
                <x-table.th width="5%" />
                <x-table.th label="Nama" sort="name" width="20%" />
                <x-table.th label="Desa" width="20%" />
                <x-table.th label="Email" sort="email" width="20%" />
                <x-table.th label="Role" />
                <x-table.th label="Status" />
                <x-table.th />
            </x-table.thead>

            <tbody>
                @forelse ($this->dataTable as $index => $d)
                    <tr class="bg-white hover:bg-slate-50 transition-colors" wire:key="user-{{ $d->id }}">
                        <td class="p-2 text-center text-slate-600 text-sm"> {{ $perPage * ($this->dataTable->currentPage() - 1) + $index + 1 }} </td>
                        <td class="p-2 text-sm font-medium text-slate-800"> {{ $d->name }} </td>
                        <td class="p-2 text-sm text-slate-700"> {{ $d->village?->name }} </td>
                        <td class="p-2 text-sm text-slate-700"> {{ $d->email }} </td>
                        <td class="p-2 text-sm text-slate-700"> {{ $d?->roles->pluck('name')->implode(', ') }} </td>
                        <td class="p-2">
                            @if ($d->status === 'active')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">Active</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">Inactive</span>
                            @endif
                        </td>
                        <td class="p-2">
                            <div class="flex items-center gap-1">
                                <x-button.circle color="emerald" sm href="{{ route('user.edit', $d->id) }}" title="Ubah">
                                    <x-ti-edit class="size-4" />
                                </x-button.circle>

                                @if ($d->id != 1)
                                    <x-button.circle color="red" flat sm title="Hapus"
                                        wire:click="$js.confirmDelete({{ $d->id }}, '{{ $d->name }}')"
                                    >
                                        <x-ti-trash class="size-4" />
                                    </x-button.circle>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="p-4 text-center text-slate-500" colspan="7">Tidak ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </x-table>
    </div>

</div>
