<div class="space-y-6">
    {{-- Header & Breadcrumbs --}}
    <div>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Laporan LB3 Maternal</h1>
        <nav class="flex items-center gap-1.5 text-sm text-slate-500 mt-1">
            <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition-colors">
                <x-ti-home class="size-4" />
            </a>
            <span>/</span>
            <span>Laporan</span>
            <span>/</span>
            <span class="text-slate-700 font-medium">LB3 Maternal</span>
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
            <table class="w-full min-w-650 text-left text-sm text-slate-700 border-collapse">
                <!-- Multi-Level Header -->
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-100/90 text-sm font-bold text-slate-800">
                        <th rowspan="2" class="lg:sticky left-0 z-20 bg-slate-100 px-4 py-3 text-center w-12 border-r border-slate-200">No</th>
                        <th rowspan="2" class="lg:sticky left-12 z-20 bg-slate-100 px-4 py-3 min-w-44 border-r border-slate-200">Desa / Kelurahan</th>
                        <th colspan="2" class="px-3 py-2 text-center border-r border-slate-200 bg-emerald-100/60 text-emerald-950">Capaian Bumil</th>
                        <th colspan="4" class="px-3 py-2 text-center border-r border-slate-200 bg-amber-100/60 text-amber-950">Ibu Hamil 4T</th>
                        <th colspan="3" class="px-3 py-2 text-center border-r border-slate-200 bg-rose-100/60 text-rose-950">Hb K1</th>
                        <th colspan="2" class="px-3 py-2 text-center border-r border-slate-200 bg-rose-100/60 text-rose-950">Hb K4</th>
                        <th colspan="5" class="px-3 py-2 text-center border-r border-slate-200 bg-teal-100/60 text-teal-950">Pemberian Fe</th>
                        <th colspan="2" class="px-3 py-2 text-center border-r border-slate-200 bg-amber-100/60 text-amber-950">KEK (LILA)</th>
                        <th colspan="2" class="px-3 py-2 text-center border-r border-slate-200">Protein Urin</th>
                        <th colspan="2" class="px-3 py-2 text-center border-r border-slate-200">Gula Darah</th>
                        <th colspan="2" class="px-3 py-2 text-center border-r border-slate-200">HIV</th>
                        <th colspan="2" class="px-3 py-2 text-center border-r border-slate-200">HBsAg</th>
                        <th colspan="2" class="px-3 py-2 text-center border-r border-slate-200">Sifilis</th>
                        <th colspan="15" class="px-3 py-2 text-center border-r border-slate-200 bg-rose-100/80 text-rose-950">Kasus Maternal</th>
                        <th colspan="3" class="px-3 py-2 text-center border-r border-slate-200 bg-indigo-100/70 text-indigo-950">Jumlah Komplikasi</th>
                        <th colspan="5" class="px-3 py-2 text-center border-r border-slate-200 bg-sky-100/60 text-sky-950">Jenis Persalinan</th>
                        <th rowspan="2" class="px-3 py-2 text-center border-r border-slate-200 bg-orange-100/70 text-orange-950">Dukun</th>
                        <th colspan="7" class="px-3 py-2 text-center bg-slate-200/70 text-slate-900">Tempat Persalinan</th>
                    </tr>
                    <tr class="border-b border-slate-200 bg-slate-50 text-sm font-semibold text-slate-600">
                        <!-- Capaian ANC -->
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">K1 Akses</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">K4</th>
                        <!-- 4T -->
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">&le;16 Th</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">&ge;35 Th</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">&le;2 Th</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">&gt;3 Anak</th>
                        <!-- Hb K1 -->
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Periksa</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">8-11</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">&lt;8</th>
                        <!-- Hb K4 -->
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">8-11</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">&lt;8</th>
                        <!-- Fe -->
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Fe-1</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Fe-2</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Fe-3</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">&lt;90</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">&gt;90</th>
                        <!-- KEK -->
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Periksa</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">&lt;23.5</th>
                        <!-- Lab -->
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Periksa</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">(+)</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Periksa</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">&gt;140</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Periksa</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">(+)</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Periksa</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">(+)</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Periksa</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">(+)</th>
                        <!-- Kasus Maternal (15 Kategori) -->
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Hiperemesis Gravidarum">Hiper.</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Abortus">Abortus</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Preeklamsi/Eklamsi">PE/Ekl.</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Perdarahan Kehamilan (ANC)">Perd. Hamil</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Perdarahan Persalinan">Perd. Salin</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Perdarahan Nifas">Perd. Nifas</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Partus Lama">Partus Lama</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Infeksi">Infeksi</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Tuberkulosis (TB)">TB</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Malaria">Malaria</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Penyakit Jantung">Jantung</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Diabetes Melitus (DM)">DM</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Positif Covid-19">Covid</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Obesitas">Obesitas</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Kasus Lainnya">Lainnya</th>
                        <!-- Jumlah Komplikasi -->
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Komplikasi Kehamilan (ANC)">Hamil</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Komplikasi Persalinan (Termasuk Abortus)">Persalinan</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200" title="Komplikasi Masa Nifas">Nifas</th>
                        <!-- Jenis Persalinan (5 Kategori Sesuai MD) -->
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Normal</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">SC</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Drip</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Curatage</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Manual</th>
                        <!-- Tempat Persalinan -->
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Polindes</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">RS</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Pusk. PONED</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Pusk. Non PONED</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">Klinik</th>
                        <th class="px-2.5 py-2 text-center border-r border-slate-200">PMB</th>
                        <th class="px-2.5 py-2 text-center">Rumah</th>
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
                            <!-- Capaian ANC -->
                            <td class="px-2.5 py-2.5 text-center font-semibold text-slate-800 border-r border-slate-200">{{ $row['k1_akses'] }}</td>
                            <td class="px-2.5 py-2.5 text-center font-semibold text-slate-800 border-r border-slate-200">{{ $row['k4'] }}</td>
                            <!-- 4T -->
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['t_muda'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['t_tua'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['t_cepat'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['t_banyak'] }}</td>
                            <!-- Hb K1 -->
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['hb_k1_periksa'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-amber-700 font-semibold border-r border-slate-200">{{ $row['hb_k1_anemia_ringan'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['hb_k1_anemia_berat'] }}</td>
                            <!-- Hb K4 -->
                            <td class="px-2.5 py-2.5 text-center text-amber-700 font-semibold border-r border-slate-200">{{ $row['hb_k4_anemia_ringan'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['hb_k4_anemia_berat'] }}</td>
                            <!-- Fe -->
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['fe1'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['fe2'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['fe3'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['fe_kurang_90'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['fe_lebih_90'] }}</td>
                            <!-- KEK -->
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['kek_periksa'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['kek_lila_kurang'] }}</td>
                            <!-- Lab -->
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['protein_urin_periksa'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['protein_urin_positif'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['gd_periksa'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['gd_tinggi'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['hiv_periksa'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['hiv_positif'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['hbsag_periksa'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['hbsag_positif'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['sipilis_periksa'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['sipilis_positif'] }}</td>
                            <!-- Kasus Maternal (15 Kategori) -->
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['hiperemesis'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['abortus'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['preeklamsia'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['perdarahan_kehamilan'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['perdarahan_persalinan'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['perdarahan_nifas'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['partus_lama'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['infeksi'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['tb'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['malaria'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['jantung'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['dm'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['covid19'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['obesitas'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['lainnya'] }}</td>
                            <!-- Jumlah Komplikasi Tiga Fase -->
                            <td class="px-2.5 py-2.5 text-center text-indigo-700 font-semibold border-r border-slate-200">{{ $row['komplikasi_kehamilan'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-indigo-700 font-semibold border-r border-slate-200">{{ $row['komplikasi_persalinan'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-indigo-700 font-semibold border-r border-slate-200">{{ $row['komplikasi_nifas'] }}</td>
                            <!-- Jenis Persalinan (5 Kategori Sesuai MD) -->
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['partus_normal'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['partus_sc'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['partus_drip'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['partus_curatage'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['partus_plasenta_manual'] }}</td>
                            <!-- Penolong Dukun -->
                            <td class="px-2.5 py-2.5 text-center text-rose-700 font-semibold border-r border-slate-200">{{ $row['partus_dukun'] }}</td>
                            <!-- Tempat Persalinan -->
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['tempat_polindes'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['tempat_rs'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['tempat_puskesmas_poned'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['tempat_puskesmas_non_poned'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['tempat_klinik'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700 border-r border-slate-200">{{ $row['tempat_pmb'] }}</td>
                            <td class="px-2.5 py-2.5 text-center text-slate-700">{{ $row['tempat_rumah'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="61" class="px-4 py-8 text-center text-slate-400">
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
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-emerald-800">{{ $tot['k1_akses'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-emerald-800">{{ $tot['k4'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['t_muda'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['t_tua'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['t_cepat'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['t_banyak'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['hb_k1_periksa'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-amber-800">{{ $tot['hb_k1_anemia_ringan'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['hb_k1_anemia_berat'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-amber-800">{{ $tot['hb_k4_anemia_ringan'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['hb_k4_anemia_berat'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['fe1'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['fe2'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['fe3'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['fe_kurang_90'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['fe_lebih_90'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['kek_periksa'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['kek_lila_kurang'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['protein_urin_periksa'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['protein_urin_positif'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['gd_periksa'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['gd_tinggi'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['hiv_periksa'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['hiv_positif'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['hbsag_periksa'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['hbsag_positif'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['sipilis_periksa'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['sipilis_positif'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['hiperemesis'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['abortus'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['preeklamsia'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['perdarahan_kehamilan'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['perdarahan_persalinan'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['perdarahan_nifas'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['partus_lama'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['infeksi'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['tb'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['malaria'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['jantung'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['dm'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['covid19'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['obesitas'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['lainnya'] ?? 0 }}</td>
                        <!-- Jumlah Komplikasi -->
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-indigo-900">{{ $tot['komplikasi_kehamilan'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-indigo-900">{{ $tot['komplikasi_persalinan'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-indigo-900">{{ $tot['komplikasi_nifas'] ?? 0 }}</td>
                        <!-- Jenis Persalinan -->
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['partus_normal'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['partus_sc'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['partus_drip'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['partus_curatage'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['partus_plasenta_manual'] ?? 0 }}</td>
                        <!-- Dukun -->
                        <td class="px-2.5 py-3 text-center border-r border-slate-300 text-rose-800">{{ $tot['partus_dukun'] ?? 0 }}</td>
                        <!-- Tempat Persalinan -->
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['tempat_polindes'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['tempat_rs'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['tempat_puskesmas_poned'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['tempat_puskesmas_non_poned'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['tempat_klinik'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center border-r border-slate-300">{{ $tot['tempat_pmb'] ?? 0 }}</td>
                        <td class="px-2.5 py-3 text-center">{{ $tot['tempat_rumah'] ?? 0 }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-card>
</div>
