<div class="space-y-6">
    {{-- Header & Breadcrumbs --}}
    <div>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Laporan LB3 Neonatal</h1>
        <nav class="flex items-center gap-1.5 text-sm text-slate-500 mt-1">
            <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition-colors">
                <x-ti-home class="size-4" />
            </a>
            <span>/</span>
            <span>Laporan</span>
            <span>/</span>
            <span class="text-slate-700 font-medium">LB3 Neonatal</span>
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

    {{-- Main Table (Kesamping per Desa) --}}
    <x-card paddingless>
        <div class="p-4 border-b border-slate-200/80 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-base font-bold text-slate-800">Tabel Rekapitulasi LB3 Neonatal Per Desa</h2>
                <p class="text-sm text-slate-500 mt-0.5">Puskesmas Pragaan - Periode {{ $this->selectedMonthLabel }}</p>
            </div>
            <x-badge text="Tabel Kesamping" color="slate" light round />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-slate-100 text-slate-700 font-semibold border-b border-slate-200 text-center">
                    <tr>
                        <th rowspan="3" class="p-3 border-r border-slate-200 w-12 text-center">No</th>
                        <th rowspan="3" class="p-3 border-r border-slate-200 text-left min-w-[160px]">Desa / Kelurahan</th>
                        
                        {{-- Lahir Hidup --}}
                        <th colspan="3" rowspan="2" class="p-2 border-r border-slate-200 bg-emerald-50 text-emerald-800">Lahir Hidup</th>

                        {{-- Prematur --}}
                        <th colspan="3" rowspan="2" class="p-2 border-r border-slate-200 bg-amber-50 text-amber-800">Lahir Bayi Prematur (&lt;37 Mgg)</th>

                        {{-- SHK --}}
                        <th colspan="3" rowspan="2" class="p-2 border-r border-slate-200 bg-sky-50 text-sky-800">Bayi di-SHK (KN1)</th>

                        {{-- Kasus Neonatal --}}
                        <th colspan="18" class="p-2 bg-rose-50 text-rose-800">Kasus Masalah Neonatal</th>
                    </tr>
                    <tr class="bg-slate-50/90 text-slate-700 font-semibold text-center border-b border-slate-200">
                        {{-- Sub-header nama 9 kasus neonatal --}}
                        <th colspan="2" class="p-1.5 border-r border-slate-200 text-xs min-w-[70px]" title="Trauma Lahir">Trauma</th>
                        <th colspan="2" class="p-1.5 border-r border-slate-200 text-xs min-w-[70px]" title="Asfiksia">Asfiksia</th>
                        <th colspan="2" class="p-1.5 border-r border-slate-200 text-xs min-w-[70px]" title="BBLR <2500 gr">BBLR</th>
                        <th colspan="2" class="p-1.5 border-r border-slate-200 text-xs min-w-[70px]" title="Infeksi">Infeksi</th>
                        <th colspan="2" class="p-1.5 border-r border-slate-200 text-xs min-w-[60px]" title="Tetanus Neonatorum">TN</th>
                        <th colspan="2" class="p-1.5 border-r border-slate-200 text-xs min-w-[70px]" title="Kelainan Bawaan">Bawaan</th>
                        <th colspan="2" class="p-1.5 border-r border-slate-200 text-xs min-w-[65px]" title="Hipotiroid Kongenital Positif">HK (+)</th>
                        <th colspan="2" class="p-1.5 border-r border-slate-200 text-xs min-w-[65px]" title="Konfirmasi Covid-19">Covid</th>
                        <th colspan="2" class="p-1.5 text-xs min-w-[70px]" title="Kasus Lainnya">Lainnya</th>
                    </tr>
                    <tr class="bg-slate-50/70 text-slate-600 font-medium text-center text-xs">
                        {{-- Lahir Hidup --}}
                        <th class="p-1.5 border-r border-slate-200 w-10 bg-emerald-50/50">L</th>
                        <th class="p-1.5 border-r border-slate-200 w-10 bg-emerald-50/50">P</th>
                        <th class="p-1.5 border-r border-slate-200 w-12 font-bold bg-emerald-100/50 text-emerald-900">Total</th>

                        {{-- Prematur --}}
                        <th class="p-1.5 border-r border-slate-200 w-10 bg-amber-50/50">L</th>
                        <th class="p-1.5 border-r border-slate-200 w-10 bg-amber-50/50">P</th>
                        <th class="p-1.5 border-r border-slate-200 w-12 font-bold bg-amber-100/50 text-amber-900">Total</th>

                        {{-- SHK --}}
                        <th class="p-1.5 border-r border-slate-200 w-10 bg-sky-50/50">L</th>
                        <th class="p-1.5 border-r border-slate-200 w-10 bg-sky-50/50">P</th>
                        <th class="p-1.5 border-r border-slate-200 w-12 font-bold bg-sky-100/50 text-sky-900">Total</th>

                        {{-- Kasus Neonatal: L & P untuk 9 jenis kasus --}}
                        {{-- Trauma --}}
                        <th class="p-1.5 border-r border-slate-200 w-9 bg-rose-50/40">L</th>
                        <th class="p-1.5 border-r border-slate-200 w-9 bg-rose-50/40">P</th>

                        {{-- Asfiksia --}}
                        <th class="p-1.5 border-r border-slate-200 w-9 bg-rose-50/40">L</th>
                        <th class="p-1.5 border-r border-slate-200 w-9 bg-rose-50/40">P</th>

                        {{-- BBLR --}}
                        <th class="p-1.5 border-r border-slate-200 w-9 bg-amber-50/40">L</th>
                        <th class="p-1.5 border-r border-slate-200 w-9 bg-amber-50/40">P</th>

                        {{-- Infeksi --}}
                        <th class="p-1.5 border-r border-slate-200 w-9 bg-rose-50/40">L</th>
                        <th class="p-1.5 border-r border-slate-200 w-9 bg-rose-50/40">P</th>

                        {{-- TN --}}
                        <th class="p-1.5 border-r border-slate-200 w-9 bg-rose-50/40">L</th>
                        <th class="p-1.5 border-r border-slate-200 w-9 bg-rose-50/40">P</th>

                        {{-- Bawaan --}}
                        <th class="p-1.5 border-r border-slate-200 w-9 bg-rose-50/40">L</th>
                        <th class="p-1.5 border-r border-slate-200 w-9 bg-rose-50/40">P</th>

                        {{-- HK (+) --}}
                        <th class="p-1.5 border-r border-slate-200 w-9 bg-rose-50/40">L</th>
                        <th class="p-1.5 border-r border-slate-200 w-9 bg-rose-50/40">P</th>

                        {{-- Covid --}}
                        <th class="p-1.5 border-r border-slate-200 w-9 bg-rose-50/40">L</th>
                        <th class="p-1.5 border-r border-slate-200 w-9 bg-rose-50/40">P</th>

                        {{-- Lainnya --}}
                        <th class="p-1.5 border-r border-slate-200 w-9">L</th>
                        <th class="p-1.5 w-9">P</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800">
                    @forelse ($this->rows as $index => $row)
                        <tr class="hover:bg-slate-50 transition-colors {{ $index % 2 === 0 ? 'bg-white' : 'bg-slate-50/40' }}">
                            <td class="p-2.5 text-center border-r border-slate-100 text-slate-500 font-medium">{{ $index + 1 }}</td>
                            <td class="p-2.5 border-r border-slate-100 font-semibold text-slate-900">{{ $row['village_name'] }}</td>

                            {{-- Lahir Hidup --}}
                            <td class="p-2 text-center border-r border-slate-100">{{ $row['lahir_hidup_l'] ?: '-' }}</td>
                            <td class="p-2 text-center border-r border-slate-100">{{ $row['lahir_hidup_p'] ?: '-' }}</td>
                            <td class="p-2 text-center border-r border-slate-100 font-bold bg-emerald-50/40 text-emerald-800">
                                {{ $row['lahir_hidup_total'] ?: '-' }}
                            </td>

                            {{-- Prematur --}}
                            <td class="p-2 text-center border-r border-slate-100">{{ $row['prematur_l'] ?: '-' }}</td>
                            <td class="p-2 text-center border-r border-slate-100">{{ $row['prematur_p'] ?: '-' }}</td>
                            <td class="p-2 text-center border-r border-slate-100 font-bold bg-amber-50/40 text-amber-800">
                                {{ $row['prematur_total'] ?: '-' }}
                            </td>

                            {{-- SHK --}}
                            <td class="p-2 text-center border-r border-slate-100">{{ $row['shk_l'] ?: '-' }}</td>
                            <td class="p-2 text-center border-r border-slate-100">{{ $row['shk_p'] ?: '-' }}</td>
                            <td class="p-2 text-center border-r border-slate-100 font-bold bg-sky-50/40 text-sky-800">
                                {{ $row['shk_total'] ?: '-' }}
                            </td>

                            {{-- Kasus Neonatal --}}
                            {{-- Trauma --}}
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['trauma_lahir_l'] > 0 ? 'font-bold text-rose-600 bg-rose-50/30' : '' }}">
                                {{ $row['trauma_lahir_l'] ?: '-' }}
                            </td>
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['trauma_lahir_p'] > 0 ? 'font-bold text-rose-600 bg-rose-50/30' : '' }}">
                                {{ $row['trauma_lahir_p'] ?: '-' }}
                            </td>

                            {{-- Asfiksia --}}
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['asfiksia_l'] > 0 ? 'font-bold text-rose-600 bg-rose-50/30' : '' }}">
                                {{ $row['asfiksia_l'] ?: '-' }}
                            </td>
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['asfiksia_p'] > 0 ? 'font-bold text-rose-600 bg-rose-50/30' : '' }}">
                                {{ $row['asfiksia_p'] ?: '-' }}
                            </td>

                            {{-- BBLR --}}
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['bblr_l'] > 0 ? 'font-bold text-amber-600 bg-amber-50/30' : '' }}">
                                {{ $row['bblr_l'] ?: '-' }}
                            </td>
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['bblr_p'] > 0 ? 'font-bold text-amber-600 bg-amber-50/30' : '' }}">
                                {{ $row['bblr_p'] ?: '-' }}
                            </td>

                            {{-- Infeksi --}}
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['infeksi_l'] > 0 ? 'font-bold text-rose-600 bg-rose-50/30' : '' }}">
                                {{ $row['infeksi_l'] ?: '-' }}
                            </td>
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['infeksi_p'] > 0 ? 'font-bold text-rose-600 bg-rose-50/30' : '' }}">
                                {{ $row['infeksi_p'] ?: '-' }}
                            </td>

                            {{-- TN --}}
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['tn_l'] > 0 ? 'font-bold text-rose-600 bg-rose-50/30' : '' }}">
                                {{ $row['tn_l'] ?: '-' }}
                            </td>
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['tn_p'] > 0 ? 'font-bold text-rose-600 bg-rose-50/30' : '' }}">
                                {{ $row['tn_p'] ?: '-' }}
                            </td>

                            {{-- Kelainan Bawaan --}}
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['kelainan_bawaan_l'] > 0 ? 'font-bold text-rose-600 bg-rose-50/30' : '' }}">
                                {{ $row['kelainan_bawaan_l'] ?: '-' }}
                            </td>
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['kelainan_bawaan_p'] > 0 ? 'font-bold text-rose-600 bg-rose-50/30' : '' }}">
                                {{ $row['kelainan_bawaan_p'] ?: '-' }}
                            </td>

                            {{-- HK (+) --}}
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['hk_positif_l'] > 0 ? 'font-bold text-rose-600 bg-rose-50/30' : '' }}">
                                {{ $row['hk_positif_l'] ?: '-' }}
                            </td>
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['hk_positif_p'] > 0 ? 'font-bold text-rose-600 bg-rose-50/30' : '' }}">
                                {{ $row['hk_positif_p'] ?: '-' }}
                            </td>

                            {{-- Covid --}}
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['covid19_l'] > 0 ? 'font-bold text-rose-600 bg-rose-50/30' : '' }}">
                                {{ $row['covid19_l'] ?: '-' }}
                            </td>
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['covid19_p'] > 0 ? 'font-bold text-rose-600 bg-rose-50/30' : '' }}">
                                {{ $row['covid19_p'] ?: '-' }}
                            </td>

                            {{-- Lainnya --}}
                            <td class="p-2 text-center border-r border-slate-100 {{ $row['lainnya_l'] > 0 ? 'font-bold text-slate-700' : '' }}">
                                {{ $row['lainnya_l'] ?: '-' }}
                            </td>
                            <td class="p-2 text-center {{ $row['lainnya_p'] > 0 ? 'font-bold text-slate-700' : '' }}">
                                {{ $row['lainnya_p'] ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="29" class="p-8 text-center text-slate-500">
                                Tidak ada data rekapitulasi untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-300">
                    <tr>
                        <td colspan="2" class="p-3 text-center border-r border-slate-200">TOTAL PUSKESMAS</td>
                        
                        {{-- Total Lahir Hidup --}}
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['lahir_hidup_l'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['lahir_hidup_p'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200 bg-emerald-100 text-emerald-900">
                            {{ $this->totals['lahir_hidup_total'] }}
                        </td>

                        {{-- Total Prematur --}}
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['prematur_l'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['prematur_p'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200 bg-amber-100 text-amber-900">
                            {{ $this->totals['prematur_total'] }}
                        </td>

                        {{-- Total SHK --}}
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['shk_l'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['shk_p'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200 bg-sky-100 text-sky-900">
                            {{ $this->totals['shk_total'] }}
                        </td>

                        {{-- Total Kasus Neonatal --}}
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['trauma_lahir_l'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['trauma_lahir_p'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['asfiksia_l'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['asfiksia_p'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['bblr_l'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['bblr_p'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['infeksi_l'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['infeksi_p'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['tn_l'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['tn_p'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['kelainan_bawaan_l'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['kelainan_bawaan_p'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['hk_positif_l'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['hk_positif_p'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['covid19_l'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['covid19_p'] }}</td>
                        <td class="p-2 text-center border-r border-slate-200">{{ $this->totals['lainnya_l'] }}</td>
                        <td class="p-2 text-center">{{ $this->totals['lainnya_p'] }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-card>
</div>
