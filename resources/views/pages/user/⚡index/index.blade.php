<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-slate-900">Kelola Pengguna</h1>
            <nav class="flex items-center gap-1.5 text-sm text-slate-500 mt-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition-colors" wire:navigate>
                    <x-ti-home class="size-4" />
                </a>
                <span>/</span>
                <span>Pengaturan</span>
                <span>/</span>
                <span class="text-slate-700 font-medium">Pengguna</span>
            </nav>
        </div>
        <x-button color="emerald" sm href="{{ route('user.create') }}" wire:navigate>
            <x-ti-plus class="size-4 mr-1 inline" /> Tambah Pengguna
        </x-button>
    </div>

    <x-card class="bg-white border border-slate-200/80 shadow-xs">
        <div class="flex justify-between items-center mb-3">
            <div class="flex items-center gap-2">
                <span class="font-semibold text-slate-800 text-sm">Pencarian & Filter</span>
                <x-button.circle color="red" flat sm type="button" wire:click="resetFilters" title="Reset filter">
                    <x-ti-filter-x class="size-4" />
                </x-button.circle>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-input clearable label="Nama" placeholder="Cari nama..." wire:model.live.debounce.500ms="search_name" />
            <x-input clearable label="Email" placeholder="Cari email..." wire:model.live.debounce.500ms="search_email" />
            <x-select.styled
                clearable
                label="Peran"
                placeholder="Pilih peran"
                wire:model.live="search_role"
                :options="$this->roleOptions"
                select="label:label|value:value"
            />
            <x-select.styled
                clearable
                label="Status"
                placeholder="Pilih status"
                wire:model.live="search_status"
                :options="$this->statusOptions"
                select="label:label|value:value"
            />
        </div>
    </x-card>

    <x-table :paginate="$this->dataTable">
        <x-table.thead class="bg-slate-100" :sortDirection="$sortDirection" :sortField="$sortField">
            <x-table.th label="No" width="5%" />
            <x-table.th label="Nama" sort="name" width="25%" />
            <x-table.th label="Jabatan / Peran Keluarga" width="20%" />
            <x-table.th label="Email" sort="email" width="20%" />
            <x-table.th label="Kantong" />
            <x-table.th label="Status" />
            <x-table.th label="Aksi" />
        </x-table.thead>

        <tbody>
            @forelse ($this->dataTable as $index => $d)
                <tr class="bg-white hover:bg-slate-50 transition-colors" wire:key="user-{{ $d->id }}">
                    <td class="p-3 text-center text-slate-600 text-sm">{{ $perPage * ($this->dataTable->currentPage() - 1) + $index + 1 }}</td>
                    <td class="p-3 text-sm font-semibold text-slate-900">{{ $d->name }}</td>
                    <td class="p-3 text-sm text-slate-700">{{ $d->jabatan ?? ($d->roles->pluck('name')->implode(', ') ?: '-') }}</td>
                    <td class="p-3 text-sm text-slate-700">{{ $d->email }}</td>
                    <td class="p-3 text-sm text-slate-700">{{ $d->wallets_count }} kantong</td>
                    <td class="p-3">
                        @if ($d->status === 'active')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">Aktif</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200">Non-Aktif</span>
                        @endif
                    </td>
                    <td class="p-3">
                        <div class="flex items-center gap-1.5">
                            <x-button.circle color="emerald" sm href="{{ route('user.edit', $d->id) }}" title="Ubah" wire:navigate>
                                <x-ti-edit class="size-4" />
                            </x-button.circle>

                            @if ($d->id !== 1 && $d->id !== auth()->id())
                                <x-button.circle
                                    color="red"
                                    flat
                                    sm
                                    title="Hapus"
                                    wire:click="$js.confirmDelete({{ $d->id }}, '{{ e($d->name) }}')"
                                >
                                    <x-ti-trash class="size-4" />
                                </x-button.circle>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="p-6 text-center text-sm text-slate-500" colspan="7">
                        Belum ada data pengguna yang cocok dengan filter pencarian.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>
</div>
