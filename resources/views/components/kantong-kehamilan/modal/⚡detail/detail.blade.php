<div>
    <!-- MODAL 3: DETAIL KANTONG KEHAMILAN & HISTORI KONTROL -->
    <x-modal title="Riwayat Kunjungan" wire="showDetailModal" size="7xl">

        @php
            $selectedPregnancy = $this->selectedPregnancy;
        @endphp

        @if ($selectedPregnancy)
            @php
                $patient = $selectedPregnancy->patient;
                $selSkor = $selectedPregnancy->latest_skor_puji_rochyati;
                $riskColor = $selSkor > 10 ? 'rose' : ($selSkor >= 6 ? 'amber' : 'emerald');
                $statusColor = match ($selectedPregnancy->status) {
                    'aktif'     => 'emerald',
                    'selesai'   => 'slate',
                    'abortus'   => 'amber',
                    'meninggal' => 'rose',
                    default     => 'slate',
                };

                $latestEncounter = $selectedPregnancy->latestEncounter ?? $selectedPregnancy->encounters->first();
                $abnormalLabs = $latestEncounter ? $latestEncounter->getAbnormalLabResults() : [];
                $latestFrList = $latestEncounter && is_array($latestEncounter->faktor_risiko_json) ? $latestEncounter->faktor_risiko_json : [];
            @endphp

            <div class="space-y-4">
                <!-- 3 GRID BAGIAN ATAS: INFORMASI PASIEN (1), INFORMASI KEHAMILAN (2), & HASIL LAB / RISIKO (3) -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <!-- KARTU 1: INFORMASI PASIEN (MURNI DATA PASIEN, GRID 3: 1 & 2) -->
                    <x-card>
                        <x-slot:header>
                            <div class="flex items-center gap-2">
                                <x-ti-user class="size-4.5 text-emerald-600" />
                                <h5 class="text-sm font-bold text-slate-800">Informasi Pasien</h5>
                            </div>
                        </x-slot:header>

                        <div class="grid grid-cols-3 gap-x-2 gap-y-2.5 text-sm">
                            <span class="col-span-1 text-slate-500">Nama:</span>
                            <div class="col-span-2">
                                <span class="font-bold text-slate-900">{{ $patient?->full_name }}</span>
                                @if ($patient?->medical_record_number_label || $patient?->medical_record_number)
                                    <span class="ml-1 text-xs font-semibold text-emerald-700">({{ $patient->medical_record_number_label ?: $patient->medical_record_number }})</span>
                                @endif
                            </div>

                            <span class="col-span-1 text-slate-500">NIK:</span>
                            <span class="col-span-2 font-medium text-slate-800">{{ $patient?->nik ?: '-' }}</span>

                            <span class="col-span-1 text-slate-500">Suami:</span>
                            <span class="col-span-2 font-medium text-slate-800">{{ $patient?->family_name ?: '-' }}</span>

                            <span class="col-span-1 text-slate-500">Usia Sekarang:</span>
                            <span class="col-span-2 font-medium text-slate-800">{{ $patient?->umur_sekarang ?: '-' }}</span>

                            <span class="col-span-1 text-slate-500">Usia saat HPHT:</span>
                            <span class="col-span-2 font-medium text-slate-800">{{ $selectedPregnancy->usia_saat_hpht ?: '-' }}</span>

                            <span class="col-span-1 text-slate-500">No. HP:</span>
                            <span class="col-span-2 font-medium text-slate-800">{{ $patient?->mobile_phone ?: '-' }}</span>

                            <span class="col-span-1 text-slate-500">Alamat:</span>
                            <div class="col-span-2 font-medium text-slate-700">
                                <div>{{ $patient?->address ?: '-' }}</div>
                                @if ($patient?->village || $patient?->district)
                                    <div class="">Desa {{ $patient?->village?->name ?? '-' }}, Kec. {{ $patient?->district?->name ?? '-' }}</div>
                                @endif
                            </div>
                        </div>
                    </x-card>

                    <!-- KARTU 2: INFORMASI KEHAMILAN SEKARANG (2 GRID) -->
                    <x-card>
                        <x-slot:header>
                            <div class="flex flex-wrap items-center justify-between gap-1.5">
                                <div class="flex items-center gap-2">
                                    <x-ti-heart class="size-4.5 text-emerald-600" />
                                    <h5 class="text-sm font-bold text-slate-800">Kehamilan Sekarang</h5>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-bold text-slate-700">{{ $selectedPregnancy->gpa_label }}</span>
                                    <x-badge :text="strtoupper($selectedPregnancy->status)" :color="$statusColor" light round />
                                </div>
                            </div>
                        </x-slot:header>

                        <div class="grid grid-cols-2 gap-x-3 gap-y-2.5 text-sm">
                            <div class="col-span-2">
                                <span class="block text-xs font-medium text-slate-500">Usia Kehamilan:</span>
                                <span class="font-bold text-emerald-700">Minggu ke-{{ $selectedPregnancy->minggu_ke ?? '-' }}</span>
                                <span class="text-xs text-slate-500">({{ $selectedPregnancy->usia_kehamilan_saat_ini }})</span>
                            </div>

                            <div class="col-span-2">
                                <span class="block text-xs font-medium text-slate-500">Bidan Pengampu:</span>
                                <span class="block truncate font-semibold text-slate-800" title="{{ $selectedPregnancy->creator?->name ?? '-' }}">{{ $selectedPregnancy->creator?->name ?? '-' }}</span>
                            </div>

                            <div>
                                <span class="block text-xs font-medium text-slate-500">HPHT:</span>
                                <span class="font-semibold text-slate-800">{{ $selectedPregnancy->hpht ? $selectedPregnancy->hpht->format('d/m/Y') : '-' }}</span>
                            </div>

                            <div>
                                <span class="block text-xs font-medium text-slate-500">HPL:</span>
                                <span class="font-semibold text-slate-800">{{ $selectedPregnancy->hpl ? $selectedPregnancy->hpl->format('d/m/Y') : '-' }}</span>
                            </div>

                            <div>
                                <span class="block text-xs font-medium text-slate-500">Kontrol Terakhir:</span>
                                @if ($latestEncounter)
                                    <span class="font-semibold text-slate-800">{{ $latestEncounter->encounter_date?->format('d/m/Y') }}</span>
                                    <span class="text-xs text-slate-500">({{ $selectedPregnancy->encounters->count() }}x periksa)</span>
                                @else
                                    <span class="text-slate-400">Belum ada</span>
                                @endif
                            </div>

                            <div>
                                <span class="block text-xs font-medium text-slate-500">Kontrol Selanjutnya:</span>
                                @if ($latestEncounter?->next_encounter_date && $selectedPregnancy->status === 'aktif')
                                    <span class="font-semibold text-emerald-700">{{ $latestEncounter->next_encounter_date->format('d/m/Y') }}</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </div>
                        </div>
                    </x-card>

                    <!-- KARTU 3: HASIL LAB & FAKTOR RISIKO -->
                    <x-card>
                        <x-slot:header>
                            <div class="flex flex-wrap items-center justify-between gap-1.5">
                                <div class="flex items-center gap-2">
                                    <x-ti-alert-circle class="size-4.5 text-amber-600" />
                                    <h5 class="text-sm font-bold text-slate-800">Hasil Lab &amp; Risiko</h5>
                                </div>
                            </div>
                        </x-slot:header>

                        <div class="space-y-3 text-sm">
                            <x-badge :text="$selectedPregnancy->kategori_puji_rochyati_label" :color="$riskColor" light round />

                            @if (!empty($abnormalLabs))
                                <div>
                                    <span class="mb-2 block text-xs font-semibold uppercase tracking-wider text-rose-700">Hasil Lab Abnormal:</span>
                                    <div class="grid grid-cols-2 gap-x-3 gap-y-2.5 text-sm">
                                        @foreach ($abnormalLabs as $abLab)
                                            <div>
                                                <span class="block text-xs font-medium text-slate-500">{{ $abLab['param'] }}:</span>
                                                <span class="font-bold text-rose-600">{{ $abLab['value'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if (!empty($abnormalLabs) && !empty($latestFrList))
                                <hr class="border-slate-100" />
                            @endif

                            @if (!empty($latestFrList))
                                <div>
                                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-amber-800">Faktor Risiko (KPR):</span>
                                    <div class="space-y-1">
                                        @foreach ($latestFrList as $frKey)
                                            <div class="flex items-start gap-1.5">
                                                <span class="font-bold leading-tight text-amber-600">&bull;</span>
                                                <span class="font-medium leading-snug text-slate-800">{{ \App\Enums\PoedjiRochyatiEnum::getLabelAlias($frKey) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if (empty($abnormalLabs) && empty($latestFrList))
                                <div class="flex items-center gap-2 py-3 text-slate-400">
                                    <x-ti-circle-check class="size-5 text-emerald-500" />
                                    <span class="text-xs italic text-slate-500">Tidak ada temuan lab abnormal atau faktor risiko khusus.</span>
                                </div>
                            @endif
                        </div>
                    </x-card>
                </div>

                <!-- Bagian 1.5: Informasi Resolusi Kehamilan (Persalinan / Abortus / Meninggal) -->
                @if ($selectedPregnancy->status !== 'aktif' && $selectedPregnancy->pregnancyHistory)
                    @php
                        $hist = $selectedPregnancy->pregnancyHistory;
                        $canBatal = $selectedPregnancy->postnatalEncounters->isEmpty() && $selectedPregnancy->neonatalEncounters->isEmpty();
                    @endphp
                    @if ($selectedPregnancy->status === 'selesai')
                        <x-card>
                            <x-slot:header>
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <h5 class="flex items-center gap-1.5 text-sm font-bold text-slate-800">
                                        <x-ti-baby-carriage class="size-4 text-emerald-600" />
                                        Informasi Persalinan / Kelahiran
                                    </h5>
                                    <div class="flex items-center gap-2">
                                        <x-button sm color="emerald" outline
                                            wire:click="$dispatch('open-selesai-modal', { pregnancyId: {{ $selectedPregnancy->id }} })">
                                            <x-ti-edit class="mr-1 inline size-4" /> Ubah Data Persalinan
                                        </x-button>
                                        @if ($canBatal)
                                            <x-button sm color="rose" outline
                                                wire:click="promptBatalkanPersalinan({{ $selectedPregnancy->id }})">
                                                <x-ti-arrow-back-up class="mr-1 inline size-4" /> Batalkan Persalinan
                                            </x-button>
                                        @endif
                                    </div>
                                </div>
                            </x-slot:header>

                            <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                                <div>
                                    <span class="block text-xs text-slate-500">Tanggal &amp; Cara:</span>
                                    <span class="font-bold text-slate-800">{{ $hist->tanggal_persalinan ? $hist->tanggal_persalinan->format('d/m/Y') : '-' }} ({{ $hist->cara_melahirkan ?? 'Normal' }})</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-slate-500">Tempat &amp; Penolong:</span>
                                    <span class="font-bold text-slate-800">{{ $hist->tempat_persalinan ?? '-' }} / {{ $hist->penolong ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-slate-500">Kondisi &amp; JK Bayi:</span>
                                    <span class="font-bold text-slate-800">{{ $hist->kondisi_bayi ?? 'Lahir Hidup' }} &bull; {{ $hist->jenis_kelamin ?? '-' }} ({{ $hist->bb_bayi ? $hist->bb_bayi . ' kg' : '-' }})</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-slate-500">Komplikasi:</span>
                                    <span class="font-bold {{ $hist->masalah_persalinan && $hist->masalah_persalinan !== 'Tidak Ada' ? 'text-rose-700' : 'text-slate-800' }}">
                                        {{ $hist->masalah_persalinan ?? 'Tidak Ada' }}
                                        @if ($hist->masalah_persalinan === 'Lainnya' && $hist->masalah_persalinan_lainnya)
                                            <span class="font-normal text-slate-600">({{ $hist->masalah_persalinan_lainnya }})</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </x-card>
                    @elseif ($selectedPregnancy->status === 'abortus')
                        <x-card>
                            <x-slot:header>
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <h5 class="flex items-center gap-1.5 text-sm font-bold text-amber-800">
                                        <x-ti-alert-triangle class="size-4 text-amber-600" />
                                        Informasi Kejadian Abortus (Keguguran)
                                    </h5>
                                    <div class="flex items-center gap-2">
                                        <x-button sm color="amber" outline
                                            wire:click="$dispatch('open-selesai-modal', { pregnancyId: {{ $selectedPregnancy->id }} })">
                                            <x-ti-edit class="mr-1 inline size-4" /> Ubah Data Abortus
                                        </x-button>
                                        @if ($canBatal)
                                            <x-button sm color="rose" outline
                                                wire:click="promptBatalkanPersalinan({{ $selectedPregnancy->id }})">
                                                <x-ti-arrow-back-up class="mr-1 inline size-4" /> Batalkan Status Abortus
                                            </x-button>
                                        @endif
                                    </div>
                                </div>
                            </x-slot:header>

                            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                                <div>
                                    <span class="block text-xs text-slate-500">Tanggal Kejadian:</span>
                                    <span class="font-bold text-slate-800">{{ $hist->tanggal_persalinan ? $hist->tanggal_persalinan->format('d/m/Y') : '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-slate-500">Tempat Penanganan &amp; Penolong:</span>
                                    <span class="font-bold text-slate-800">{{ $hist->tempat_persalinan ?? '-' }} / {{ $hist->penolong ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-slate-500">Keterangan:</span>
                                    <span class="font-bold text-slate-800">{{ $hist->masalah_persalinan ?: 'Keguguran (Abortus)' }}</span>
                                </div>
                            </div>
                        </x-card>
                    @elseif ($selectedPregnancy->status === 'meninggal')
                        <x-card>
                            <x-slot:header>
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <h5 class="flex items-center gap-1.5 text-sm font-bold text-rose-800">
                                        <x-ti-alert-octagon class="size-4 text-rose-600" />
                                        Informasi Kematian Pasien (Maternal Death)
                                    </h5>
                                    <div class="flex items-center gap-2">
                                        <x-button sm color="red" outline
                                            wire:click="$dispatch('open-selesai-modal', { pregnancyId: {{ $selectedPregnancy->id }} })">
                                            <x-ti-edit class="mr-1 inline size-4" /> Ubah Data Kematian
                                        </x-button>
                                        @if ($canBatal)
                                            <x-button sm color="rose" outline
                                                wire:click="promptBatalkanPersalinan({{ $selectedPregnancy->id }})">
                                                <x-ti-arrow-back-up class="mr-1 inline size-4" /> Batalkan Status Meninggal
                                            </x-button>
                                        @endif
                                    </div>
                                </div>
                            </x-slot:header>

                            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                                <div>
                                    <span class="block text-xs text-slate-500">Tanggal Kematian:</span>
                                    <span class="font-bold text-slate-800">{{ $hist->tanggal_persalinan ? $hist->tanggal_persalinan->format('d/m/Y') : '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-slate-500">Tempat Kejadian &amp; Tenaga Medis:</span>
                                    <span class="font-bold text-slate-800">{{ $hist->tempat_persalinan ?? '-' }} / {{ $hist->penolong ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-slate-500">Keterangan:</span>
                                    <span class="font-bold text-rose-900">{{ $hist->keterangan ?? '-' }}</span>
                                </div>
                            </div>
                        </x-card>
                    @endif
                @endif

                <!-- 4 TAB HISTORI & PENCATATAN KUNJUNGAN: KEHAMILAN, NIFAS, NEONATAL, KOMPLIKASI NEONATAL -->
                <x-card paddingless class="mt-4" wire:key="detail-tabs-wrapper-{{ $selectedPregnancy->id }}">
                    <x-tab wire:model="detail_active_tab" scroll-on-mobile paddingless>
                        <!-- TAB 1: KUNJUNGAN KEHAMILAN (ANC) -->
                        <x-tab.items title="Kunjungan Kehamilan" tab="kehamilan">
                            <x-slot:left>
                                <x-ti-stethoscope class="size-4 text-emerald-600" />
                            </x-slot:left>
                            <x-slot:right>
                                <x-badge :text="(string) $selectedPregnancy->encounters->count()" color="emerald" light round />
                            </x-slot:right>

                            <livewire:kantong-kehamilan.tab.kehamilan :pregnancy-id="$selectedPregnancy->id" :key="'tab-kehamilan-'.$selectedPregnancy->id" />
                        </x-tab.items>

                        @if ($selectedPregnancy->status === 'selesai')
                            <!-- TAB 2: KUNJUNGAN NIFAS (PNC / KF1-KF4) -->
                            <x-tab.items title="Kunjungan Nifas" tab="nifas">
                                <x-slot:left>
                                    <x-ti-heartbeat class="size-4 text-purple-600" />
                                </x-slot:left>
                                <x-slot:right>
                                    <x-badge :text="(string) $selectedPregnancy->postnatalEncounters->count()" color="purple" light round />
                                </x-slot:right>

                                <livewire:kantong-kehamilan.tab.nifas :pregnancy-id="$selectedPregnancy->id" :key="'tab-nifas-'.$selectedPregnancy->id" />
                            </x-tab.items>

                            <!-- TAB 3: KUNJUNGAN NEONATAL (BAYI / KN1-KN3) -->
                            <x-tab.items title="Kunjungan Neonatal" tab="neonatal">
                                <x-slot:left>
                                    <x-ti-baby-carriage class="size-4 text-cyan-600" />
                                </x-slot:left>
                                <x-slot:right>
                                    <x-badge :text="(string) $selectedPregnancy->neonatalEncounters->count()" color="cyan" light round />
                                </x-slot:right>

                                <livewire:kantong-kehamilan.tab.neonatal :pregnancy-id="$selectedPregnancy->id" :key="'tab-neonatal-'.$selectedPregnancy->id" />
                            </x-tab.items>

                            <!-- TAB 4: KASUS / KOMPLIKASI NEONATAL (1 KALI PENCATATAN) -->
                            <x-tab.items title="Komplikasi Neonatal" tab="komplikasi_neonatal">
                                <x-slot:left>
                                    <x-ti-alert-triangle class="size-4 text-amber-600" />
                                </x-slot:left>
                                <livewire:kantong-kehamilan.tab.komplikasi :pregnancy-id="$selectedPregnancy->id" :key="'tab-komplikasi-'.$selectedPregnancy->id" />
                            </x-tab.items>
                        @endif
                    </x-tab>
                </x-card>

                <div class="flex justify-end gap-2 border-t-2 border-slate-100 pt-3">
                    <x-button color="gray" flat x-on:click="$wire.showDetailModal = false">Tutup</x-button>
                </div>
            </div>
        @endif
    </x-modal>
</div>