<div class="space-y-6">
    {{-- Header & Breadcrumbs --}}
    <div>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Laporan PWS KIA Ibu</h1>
        <nav class="flex items-center gap-1.5 text-sm text-slate-500 mt-1">
            <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition-colors">
                <x-ti-home class="size-4" />
            </a>
            <span>/</span>
            <span>Laporan</span>
            <span>/</span>
            <span class="text-slate-700 font-medium">PWS Ibu</span>
        </nav>
    </div>

    {{-- Filter Card --}}
    <x-card>
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div class="flex flex-wrap items-end gap-2.5">
                <div class="w-48">
                    <x-select.styled
                        label="Bulan"
                        wire:model.live="bulan"
                        :options="$this->monthOptions"
                        select="label:label|value:value"
                        required
                    />
                </div>
                <div class="w-36">
                    <x-select.styled
                        label="Tahun"
                        wire:model.live="tahun"
                        :options="$this->yearOptions"
                        select="label:label|value:value"
                        required
                    />
                </div>
                <div>
                    <x-button.circle
                        color="emerald"
                        wire:click="refresh"
                        loading="refresh"
                        title="Segarkan Data"
                    >
                        <x-ti-refresh class="size-4.5" />
                    </x-button.circle>
                </div>
                <div>
                    <x-button
                        color="emerald"
                        sm
                        wire:click="exportExcel"
                        loading="exportExcel"
                        title="Export Excel"
                        class="gap-1.5"
                    >
                        <x-ti-file-spreadsheet class="size-4" />
                        <span>Export Excel</span>
                    </x-button>
                </div>
            </div>

            <div class="flex items-center gap-2 text-sm text-slate-600 bg-slate-50 px-3 py-2 rounded-lg border border-slate-200/80">
                <x-ti-calendar-event class="size-4 text-emerald-600" />
                <span>Periode: <strong class="text-slate-800">{{ $this->selectedMonthLabel }}</strong></span>
                <span class="mx-1 text-slate-300">|</span>
                <x-badge :text="count($this->rows) . ' Desa'" color="emerald" light round />
            </div>
        </div>
    </x-card>

    <!-- Tabel Rekap Kesamping Menggunakan x-card paddingless -->
    <x-card paddingless>
        <div class="overflow-x-auto">
            <table class="w-full min-w-450 text-left text-sm text-slate-700 border-collapse">
                <!-- Multi-Level Header -->
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-100/90 text-sm font-bold text-slate-800">
                        <th rowspan="2" class="lg:sticky left-0 z-20 bg-slate-100 px-4 py-3 text-center w-12 border-r border-slate-200">No</th>
                        <th rowspan="2" class="lg:sticky left-12 z-20 bg-slate-100 px-4 py-3 min-w-44 border-r border-slate-200">Desa</th>
                        <th colspan="4" class="px-3 py-2 text-center border-r border-slate-200 bg-emerald-100/60 text-emerald-950">Kelompok K1 (Akses & Murni)</th>
                        <th rowspan="2" class="px-3 py-3 text-center border-r border-slate-200">K4</th>
                        <th colspan="3" class="px-3 py-2 text-center border-r border-slate-200 bg-sky-100/60 text-sky-950">Kelompok K5</th>
                        <th rowspan="2" class="px-3 py-3 text-center border-r border-slate-200">K6</th>
                        <th rowspan="2" class="px-3 py-3 text-center border-r border-slate-200">K7</th>
                        <th rowspan="2" class="px-3 py-3 text-center border-r border-slate-200">K8</th>
                        <th colspan="2" class="px-3 py-2 text-center border-r border-slate-200 bg-amber-100/60 text-amber-950">Deteksi Risiko (DRT)</th>
                        <th rowspan="2" class="px-3 py-3 text-center border-r border-slate-200">Komplikasi Kebidanan</th>
                        <th colspan="3" class="px-3 py-2 text-center border-r border-slate-200 bg-teal-100/60 text-teal-950">Persalinan</th>
                        <th colspan="4" class="px-3 py-2 text-center bg-purple-100/60 text-purple-950">Pelayanan Nifas (PNC)</th>
                    </tr>
                    <tr class="border-b border-slate-200 bg-slate-50 text-sm font-semibold text-slate-600">
                        <th class="px-3 py-2 text-center border-r border-slate-200">Akses</th>
                        <th class="px-3 py-2 text-center border-r border-slate-200">Murni</th>
                        <th class="px-3 py-2 text-center border-r border-slate-200">Dokter</th>
                        <th class="px-3 py-2 text-center border-r border-slate-200">USG</th>
                        <th class="px-3 py-2 text-center border-r border-slate-200">Jml K5</th>
                        <th class="px-3 py-2 text-center border-r border-slate-200">Dokter</th>
                        <th class="px-3 py-2 text-center border-r border-slate-200">USG</th>
                        <th class="px-3 py-2 text-center border-r border-slate-200">DRTM</th>
                        <th class="px-3 py-2 text-center border-r border-slate-200">DRTN</th>
                        <th class="px-3 py-2 text-center border-r border-slate-200">Linakes</th>
                        <th class="px-3 py-2 text-center border-r border-slate-200">Di Faskes</th>
                        <th class="px-3 py-2 text-center border-r border-slate-200">Polindes</th>
                        <th class="px-3 py-2 text-center border-r border-slate-200">KF1</th>
                        <th class="px-3 py-2 text-center border-r border-slate-200">KF2</th>
                        <th class="px-3 py-2 text-center border-r border-slate-200">KF3</th>
                        <th class="px-3 py-2 text-center">KF4</th>
                    </tr>
                </thead>

                <!-- Body Data -->
                <tbody class="divide-y divide-slate-200/80 bg-white">
                    @forelse ($this->rows as $index => $row)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="lg:sticky left-0 z-10 bg-white hover:bg-slate-50 px-4 py-2.5 text-center font-medium text-slate-500 border-r border-slate-200">
                                {{ $index + 1 }}
                            </td>
                            <td class="lg:sticky left-12 z-10 bg-white hover:bg-slate-50 px-4 py-2.5 font-bold text-slate-900 border-r border-slate-200 whitespace-nowrap">
                                {{ $row['village_name'] }}
                            </td>
                            <td class="px-3 py-2.5 text-center font-semibold text-slate-800 border-r border-slate-200">{{ $row['k1_akses'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['k1_murni'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['k1_akses'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['k1_usg'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['k4'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['k5'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['k5_dokter'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['k5_usg'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['k6'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['k7'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['k8'] }}</td>
                            <td class="px-3 py-2.5 text-center font-semibold text-amber-800 border-r border-slate-200">{{ $row['drtm'] }}</td>
                            <td class="px-3 py-2.5 text-center font-semibold text-amber-800 border-r border-slate-200">{{ $row['drtn'] }}</td>
                            <td class="px-3 py-2.5 text-center font-semibold text-rose-800 border-r border-slate-200">{{ $row['komplikasi'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['linakes'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['linakes_faskes'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['polindes'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['kf1'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['kf2'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['kf3'] }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-700">{{ $row['kf4'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="23" class="px-4 py-8 text-center text-slate-400">
                                Tidak ada data untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <!-- Summary Footer Total -->
                @php $tot = $this->totals; @endphp
                <tfoot>
                    <tr class="border-t-2 border-slate-300 bg-slate-100 font-bold text-slate-900">
                        <td colspan="2" class="lg:sticky left-0 z-10 bg-slate-100 px-4 py-3 text-center uppercase tracking-wider border-r border-slate-300">
                            Total Puskesmas
                        </td>
                        <td class="px-3 py-3 text-center border-r border-slate-300 text-emerald-800">{{ $tot['k1_akses'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300">{{ $tot['k1_murni'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300">{{ $tot['k1_dokter'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300">{{ $tot['k1_usg'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300">{{ $tot['k4'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300">{{ $tot['k5'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300">{{ $tot['k5_dokter'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300">{{ $tot['k5_usg'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300">{{ $tot['k6'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300">{{ $tot['k7'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300">{{ $tot['k8'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300 text-amber-800">{{ $tot['drtm'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300 text-amber-800">{{ $tot['drtn'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['komplikasi'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300">{{ $tot['linakes'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300">{{ $tot['linakes_faskes'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300">{{ $tot['polindes'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300">{{ $tot['kf1'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300">{{ $tot['kf2'] }}</td>
                        <td class="px-3 py-3 text-center border-r border-slate-300">{{ $tot['kf3'] }}</td>
                        <td class="px-3 py-3 text-center">{{ $tot['kf4'] }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-card>
</div>