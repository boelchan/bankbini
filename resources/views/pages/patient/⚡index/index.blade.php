<div>
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-medium text-slate-900">Pasien</h1>
            <nav class="flex items-center gap-1.5 text-xs text-slate-500 mt-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition-colors"><x-ti-home class="size-4" /></a>
                <span>/</span>
                <span>Pasien</span>
                <span>/</span>
                <span class="text-slate-700 font-medium">Data</span>
            </nav>
        </div>
        @can('pasien-tambah')
            <x-button color="emerald" sm href="{{ route('patient.create') }}" wire:navigate>
                <x-ti-plus class="size-4 mr-1 inline" /> Tambah
            </x-button>
        @endcan
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
                    <x-input clearable label="No. RM" placeholder="cari No. RM..."
                        wire:model.live.debounce.500ms="search_medical_record_number"
                    />
                    <x-input clearable label="Nama" placeholder="cari nama..." wire:model.live.debounce.500ms="search_full_name" />
                    <x-input clearable label="Desa" placeholder="cari desa..." wire:model.live.debounce.500ms="search_village" />
                    <x-select.native
                        label="Status"
                        :options="[['label' => 'Semua', 'value' => ''],['label' => 'Aktif', 'value' => 'aktif'], ['label' => 'Tidak Aktif', 'value' => 'tidak_aktif']]"
                        wire:model.live="search_status"
                        placeholder="Pilih status"
                    />
                </div>
            </div>
        </x-card>
        {{-- end filter --}}

        <x-table :paginate="$this->dataTable">
            <x-table.thead class="bg-slate-50" :sortDirection="$sortDirection" :sortField="$sortField">
                <x-table.th width="5%" />
                <x-table.th label="No. RM" sort="medical_record_number" width="10%" />
                <x-table.th label="NIK" sort="nik" width="15%" />
                <x-table.th label="Nama" sort="full_name" width="25%" />
                <x-table.th label="Suami" width="15%" />
                <x-table.th label="Desa" width="15%"/>
                <x-table.th width="15%"/>
            </x-table.thead>

            <tbody>
                @forelse ($this->dataTable as $index => $d)
                    <tr class="bg-white hover:bg-slate-50 transition-colors" wire:key="patient-{{ $d->id }}">
                        <td class="p-2 text-center text-slate-600 text-sm"> {{ $perPage * ($this->dataTable->currentPage() - 1) + $index + 1 }} </td>
                        <td class="p-2"> {{ $d->medical_record_number }} </td>
                        <td class="p-2 text-sm text-slate-700"> {{ $d->nik }} </td>
                        <td class="p-2 text-sm"> 
                            <b class="text-slate-800">{{ $d->full_name }}</b> ({{ $d->gender ?? '-' }})
                            <br>
                            <span class="text-xs text-slate-500">{{ $d->umur_sekarang }}</span>
                        </td>
                        <td class="p-2 text-sm text-slate-700"> {{ $d->family_name }} </td>
                        <td class="p-2 text-sm text-slate-700"> {{ $d->village?->name ?? '' }}<br><span class="text-xs text-slate-500">{{ $d->district?->name ?? '' }}</span></td>
                        <td class="p-2">
                            <div class="flex items-center gap-1">
                                <x-button color="sky" sm title="Persalinan"
                                    wire:click="$dispatch('open-riwayat-kehamilan', { patientId: {{ $d->id }} })"
                                >
                                    <x-ti-history class="size-4" />
                                    Persalinan
                                </x-button>
                                @if ($d->is_active)

                                    @can('pasien-edit')
                                        <x-button.circle color="emerald" sm href="{{ route('patient.edit', [$d->id, $d->uuid]) }}" title="Ubah">
                                            <x-ti-edit class="size-4" />
                                        </x-button.circle>
                                    @endcan

                                    @can('pasien-hapus')
                                        <x-button.circle color="red" sm title="Hapus"
                                            wire:click="$js.confirmDelete({{ $d->id }}, '{{ addslashes($d->full_name) }}')"
                                        >
                                            <x-ti-trash class="size-4" />
                                        </x-button.circle>
                                    @endcan
                                @else
                                    @can('pasien-aktifkan')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                                            Tidak Aktif
                                        </span>
                                        <x-button.circle color="emerald" outline sm title="Aktifkan"
                                            wire:click="$js.confirmAktifkanPasien({{ $d->id }}, '{{ addslashes($d->full_name) }}')"
                                        >
                                            <x-ti-refresh class="size-4" />
                                        </x-button.circle>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="p-4 text-center text-slate-500" colspan="9">Tidak ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </x-table>
    </div>
</div>
