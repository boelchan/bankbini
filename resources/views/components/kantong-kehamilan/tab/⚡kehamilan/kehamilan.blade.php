<div>
    @php
        $selectedPregnancy = $this->selectedPregnancy;
    @endphp

    @if ($selectedPregnancy)
        <div class="space-y-4 p-3">
            @if ($selectedPregnancy->status === 'aktif')
                <x-button color="emerald" sm wire:click="openPemeriksaanModal">
                    <x-ti-plus class="mr-1 inline size-4" /> Tambah Baru
                </x-button>
            @endif

            @if ($selectedPregnancy->attending_midwives->isNotEmpty())
                <div
                    class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm">
                    <span class="flex shrink-0 items-center gap-1.5 font-bold text-slate-800">
                        <x-ti-users class="size-4 text-emerald-600" />
                        Bidan yang Menangani:
                    </span>
                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach ($selectedPregnancy->attending_midwives as $attMidwife)
                            <span class="me-4">{{ $attMidwife->name }}, </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- RINGKASAN CAPAIAN INDIKATOR KIA (K1 MURNI/AKSES, K4, K5, K6) -->
            @php $milestones = $selectedPregnancy->kia_milestones; @endphp
            <div class="rounded-xl border border-slate-200/90 bg-white p-3 shadow-2xs">
                <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-between">
                    <span class="flex shrink-0 items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-600">
                        <x-ti-award class="size-4 text-emerald-600" />
                        Capaian Standar Pelayanan KIA:
                    </span>
                    <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center sm:gap-2">
                        <!-- K1 -->
                        <div class="flex items-center gap-1.5 rounded-lg border {{ $milestones['k1']['achieved'] ? ($milestones['k1']['type'] === 'murni' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800') : 'border-slate-200 bg-slate-50 text-slate-400' }} px-2.5 py-1 text-xs">
                            <span class="font-bold">{{ $milestones['k1']['label'] }}:</span>
                            <span class="font-medium">{{ $milestones['k1']['achieved'] ? 'Bulan ' . $milestones['k1']['bulan'] : 'Belum Ada' }}</span>
                        </div>

                        <!-- K4 -->
                        <div class="flex items-center gap-1.5 rounded-lg border {{ $milestones['k4']['achieved'] ? 'border-purple-200 bg-purple-50 text-purple-800' : 'border-slate-200 bg-slate-50 text-slate-400' }} px-2.5 py-1 text-xs">
                            <span class="font-bold">K4:</span>
                            <span class="font-medium">{{ $milestones['k4']['achieved'] ? 'Bulan ' . $milestones['k4']['bulan'] : 'Belum' }}</span>
                        </div>

                        <!-- K5 -->
                        <div class="flex items-center gap-1.5 rounded-lg border {{ $milestones['k5']['achieved'] ? 'border-sky-200 bg-sky-50 text-sky-800' : 'border-slate-200 bg-slate-50 text-slate-400' }} px-2.5 py-1 text-xs">
                            <span class="font-bold">K5:</span>
                            <span class="font-medium">{{ $milestones['k5']['achieved'] ? 'Bulan ' . $milestones['k5']['bulan'] : 'Belum' }}</span>
                        </div>

                        <!-- K6 -->
                        <div class="flex items-center gap-1.5 rounded-lg border {{ $milestones['k6']['achieved'] ? 'border-teal-200 bg-teal-50 text-teal-800' : 'border-slate-200 bg-slate-50 text-slate-400' }} px-2.5 py-1 text-xs">
                            <span class="font-bold">K6:</span>
                            <span class="font-medium">{{ $milestones['k6']['achieved'] ? 'Bulan ' . $milestones['k6']['bulan'] : 'Belum' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                @forelse ($selectedPregnancy->encounters as $index => $enc)
                    @php
                        $eSkor = $enc->skor_puji_rochyati;
                        $eRiskColor = $eSkor > 12 ? 'rose' : ($eSkor >= 6 ? 'amber' : 'emerald');
                        $eRiskLabel = $eSkor <= 5 ? 'KRR' : ($eSkor <= 10 ? 'KRT' : 'KRST');

                        $isNegative = fn (?string $v): bool => str_contains(strtolower((string) $v), 'non') || str_contains(strtolower((string) $v), 'negatif');
                        $isExamined = fn (?string $v): bool => filled($v) && $v !== 'Belum Diperiksa';

                        $ttvRows = [
                            'LiLA' => $enc->lila ? number_format($enc->lila, 1) . ' cm' : '-',
                            'Tekanan Darah' => $enc->td_sistolik && $enc->td_diastolik ? "{$enc->td_sistolik}/{$enc->td_diastolik} mmHg" : '-',
                            'MAP' => $enc->map ? number_format($enc->map, 1) . ' mmHg' : '-',
                            'BB / TB' => ($enc->bb ? number_format($enc->bb, 1) . ' kg' : '-') . ' / ' . ($enc->tb ? number_format($enc->tb, 0) . ' cm' : '-'),
                            'IMT' => $enc->imt ? number_format($enc->imt, 1) : '-',
                            'TFU / DJJ' => ($enc->tfu ? number_format($enc->tfu, 1) . ' cm' : '-') . ' / ' . ($enc->djj ? $enc->djj . ' x/m' : '-'),
                            'Skrining Jiwa' => $enc->skor_skrining_jiwa !== null
                                ? 'Skor ' . $enc->skor_skrining_jiwa . ($enc->kategori_skrining_jiwa ? ' · ' . $enc->kategori_skrining_jiwa : '')
                                : '-',
                        ];

                        $labRows = [];

                        // Hemoglobin
                        if ($enc->lab_hb !== null) {
                            [$hbStatus, $hbClass] = match (true) {
                                $enc->lab_hb < 12 => ['Rendah', 'text-rose-600'],
                                $enc->lab_hb > 16 => ['Tinggi', 'text-amber-600'],
                                default => ['Normal', 'text-emerald-600'],
                            };
                            $labRows[] = ['Hemoglobin (Hb)', number_format($enc->lab_hb, 1) . ' g/dL', $hbStatus, $hbClass];
                        } else {
                            $labRows[] = ['Hemoglobin (Hb)', null, null, null];
                        }

                        // Gula Darah
                        if ($enc->lab_gda) {
                            $gdaNum = intval($enc->lab_gda);
                            [$gdaStatus, $gdaClass] = match (true) {
                                $gdaNum > 125 => ['Tinggi', 'text-rose-600'],
                                $gdaNum < 70 => ['Rendah', 'text-amber-600'],
                                default => ['Normal', 'text-emerald-600'],
                            };
                            $labRows[] = ['Gula Darah (GDA)', $enc->lab_gda . ' mg/dL', $gdaStatus, $gdaClass];
                        } else {
                            $labRows[] = ['Gula Darah (GDA)', null, null, null];
                        }

                        // Protein Urine
                        if ($enc->lab_protein_urine) {
                            $pu = strtolower($enc->lab_protein_urine);
                            [$puText, $puClass] = match (true) {
                                str_contains($pu, 'negatif') => ['Negatif', 'text-emerald-600'],
                                str_contains($pu, 'trace') => ['Trace', 'text-amber-600'],
                                default => [$enc->lab_protein_urine, 'text-rose-600'],
                            };
                            $labRows[] = ['Protein Urine', null, $puText, $puClass];
                        } else {
                            $labRows[] = ['Protein Urine', null, null, null];
                        }

                        // Reduksi Urine
                        if ($enc->lab_reduksi_urine) {
                            $labRows[] = str_contains(strtolower($enc->lab_reduksi_urine), 'negatif')
                                ? ['Reduksi Urine', null, 'Negatif', 'text-emerald-600']
                                : ['Reduksi Urine', null, $enc->lab_reduksi_urine, 'text-rose-600'];
                        } else {
                            $labRows[] = ['Reduksi Urine', null, null, null];
                        }

                        // Triple Eliminasi
                        foreach (['HIV' => $enc->lab_hiv, 'Sifilis' => $enc->lab_syphilis, 'HBsAg' => $enc->lab_hbsag] as $triLabel => $triValue) {
                            $labRows[] = $isExamined($triValue)
                                ? ($isNegative($triValue)
                                    ? [$triLabel, null, 'Negatif', 'text-emerald-600']
                                    : [$triLabel, null, 'Positif', 'text-rose-600'])
                                : [$triLabel, null, null, null];
                        }
                    @endphp

                    <x-card>
                        <x-slot:header>
                            <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-between w-full">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-slate-800">Kunjungan {{ $selectedPregnancy->encounters->count() - $index }}</span>
                                    <span class="text-slate-300">&bull;</span>
                                    <x-badge :text="$enc->kia_status_badge['detail']" :color="$enc->kia_status_badge['color']" light round />
                                    <span class="text-slate-300">&bull;</span>
                                    <span class="flex items-center gap-1 text-sm text-slate-600">
                                        <x-ti-calendar class="size-3.5 text-emerald-600" />
                                        {{ $enc->encounter_date ? $enc->encounter_date->translatedFormat('d F Y') : '-' }}
                                    </span>
                                    <span class="text-slate-300">&bull;</span>
                                    <span class="flex items-center gap-1 text-sm text-slate-600">
                                        <x-ti-clock class="size-3.5 text-emerald-600" />
                                        Usia Hamil: <strong class="text-emerald-700">{{ $enc->usia_kehamilan ?: '-' }}</strong>
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 self-end sm:self-auto">
                                    <span class="inline-flex items-center gap-1.5 text-sm text-slate-600">
                                        <x-ti-stethoscope class="size-3.5 text-emerald-600" />
                                        Pemeriksa: <strong class="font-semibold text-slate-800">{{ $enc->creator?->name ?? '-' }}</strong>
                                    </span>

                                    @if ($enc->canManage())
                                        <div class="flex items-center gap-1 border-l border-slate-200 pl-2.5">
                                            <x-button.circle
                                                title="Ubah Pemeriksaan Kontrol Ini"
                                                color="emerald"
                                                flat
                                                sm
                                                wire:click="openEditPemeriksaanModal({{ $enc->id }})"
                                            >
                                                <x-ti-edit class="size-4" />
                                            </x-button.circle>

                                            <x-button.circle
                                                title="Hapus Pemeriksaan Kontrol Ini"
                                                color="red"
                                                flat
                                                sm
                                                wire:click="deleteEncounter({{ $enc->id }})"
                                                wire:confirm="Yakin ingin menghapus data pemeriksaan kontrol ini?"
                                            >
                                                <x-ti-trash class="size-4" />
                                            </x-button.circle>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </x-slot:header>

                        <!-- TIGA CARD: TTV, LAB, & RISIKO / MASALAH KEHAMILAN -->
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            <!-- CARD 1: TANDA VITAL & FISIK -->
                            <x-card shadowless class="border border-slate-200/80 bg-slate-50/40">
                                <x-slot:header>
                                    <div class="flex items-center gap-1.5">
                                        <x-ti-activity class="size-4 text-emerald-600" />
                                        <h6 class="text-xs font-semibold uppercase tracking-wider text-slate-700">Tanda Vital &amp; Fisik</h6>
                                    </div>
                                </x-slot:header>
                                <dl class="space-y-1.5 text-sm">
                                    @foreach ($ttvRows as $ttvLabel => $ttvValue)
                                        <div class="flex items-baseline justify-between gap-2 border-b border-slate-100 pb-1.5 last:border-0 last:pb-0">
                                            <dt class="text-slate-500">{{ $ttvLabel }}:</dt>
                                            <dd class="text-right font-semibold text-slate-800">{{ $ttvValue }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </x-card>

                            <!-- CARD 2: HASIL LABORATORIUM -->
                            <x-card shadowless class="border border-slate-200/80 bg-slate-50/40">
                                <x-slot:header>
                                    <div class="flex items-center gap-1.5">
                                        <x-ti-flask class="size-4 text-emerald-600" />
                                        <h6 class="text-xs font-semibold uppercase tracking-wider text-slate-700">Hasil Laboratorium</h6>
                                    </div>
                                </x-slot:header>
                                <dl class="space-y-1.5 text-sm">
                                    @foreach ($labRows as [$labLabel, $labValue, $labStatus, $labClass])
                                        <div class="flex items-baseline justify-between gap-2 border-b border-slate-100 pb-1.5 last:border-0 last:pb-0">
                                            <dt class="text-slate-500">{{ $labLabel }}:</dt>
                                            <dd class="text-right">
                                                @if ($labValue === null && $labStatus === null)
                                                    <span class="italic text-slate-400">Belum diperiksa</span>
                                                @else
                                                    @if ($labValue)
                                                        <span class="font-semibold text-slate-800">{{ $labValue }}</span>
                                                    @endif
                                                    @if ($labStatus)
                                                        <span class="{{ $labValue ? 'ml-1 text-xs' : '' }} font-semibold {{ $labClass }}">{{ $labStatus }}</span>
                                                    @endif
                                                @endif
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </x-card>

                            <!-- CARD 3: RISIKO & MASALAH KEHAMILAN (POEDJI ROCHYATI, MASALAH, USG) -->
                            <x-card shadowless class="border border-slate-200/80 bg-slate-50/40">
                                <x-slot:header>
                                    <div class="flex flex-wrap items-center justify-between gap-1.5">
                                        <div class="flex items-center gap-1.5">
                                            <x-ti-alert-circle class="size-4 text-amber-600" />
                                            <h6 class="text-xs font-semibold uppercase tracking-wider text-slate-700">Risiko &amp; Temuan Khusus</h6>
                                        </div>
                                        <x-badge :text="'Skor ' . $eSkor . ' (' . $eRiskLabel . ')'" :color="$eRiskColor" light round />
                                    </div>
                                </x-slot:header>
                                <div class="space-y-3 text-sm">
                                    <!-- Faktor Risiko Poedji Rochyati -->
                                    <div>
                                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-amber-800">Faktor Risiko (KPR):</span>
                                        @if (!empty($enc->faktor_risiko_json))
                                            <div class="space-y-1">
                                                @foreach ($enc->faktor_risiko_json as $fr)
                                                    <div class="flex items-start gap-1.5">
                                                        <span class="font-bold leading-tight text-amber-600">&bull;</span>
                                                        <span class="font-medium leading-snug text-slate-800">{{ \App\Enums\PoedjiRochyatiEnum::getLabelAlias($fr) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-xs italic text-slate-400">Tidak ada faktor risiko terdeteksi.</p>
                                        @endif
                                    </div>

                                    <hr class="border-slate-100" />

                                    <!-- Status KIA, Pemeriksaan USG, Masalah Kehamilan & Kontrol Selanjutnya -->
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-1.5">
                                            <span class="text-slate-500">Capaian KIA:</span>
                                            <x-badge :text="$enc->kia_status_badge['detail']" :color="$enc->kia_status_badge['color']" light round />
                                        </div>

                                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-1.5">
                                            <span class="text-slate-500">Pemeriksaan USG:</span>
                                            @if ($enc->is_usg)
                                                <x-badge text="USG Dilakukan" color="teal" light round>
                                                    <x-slot:left>
                                                        <x-ti-scan class="mr-1 size-3.5" />
                                                    </x-slot:left>
                                                </x-badge>
                                            @else
                                                <span class="text-xs text-slate-400">Tidak Dilakukan</span>
                                            @endif
                                        </div>

                                        <div class="flex items-baseline justify-between gap-2 border-b border-slate-100 pb-1.5">
                                            <span class="text-slate-500">Masalah Kehamilan:</span>
                                            @if ($enc->masalah_kehamilan && $enc->masalah_kehamilan !== 'Tidak Ada')
                                                <span class="text-right text-xs font-bold text-rose-700">
                                                    {{ $enc->masalah_kehamilan }}
                                                    @if ($enc->masalah_kehamilan_lainnya)
                                                        ({{ $enc->masalah_kehamilan_lainnya }})
                                                    @endif
                                                </span>
                                            @else
                                                <span class="text-xs font-medium text-emerald-700">Tidak Ada</span>
                                            @endif
                                        </div>

                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-slate-500">Kontrol Selanjutnya:</span>
                                            @if ($enc->next_encounter_date)
                                                <span class="flex items-center gap-1 font-semibold text-emerald-800">
                                                    <x-ti-calendar-event class="size-3.5 text-emerald-600" />
                                                    {{ $enc->next_encounter_date->translatedFormat('d F Y') }}
                                                </span>
                                            @else
                                                <span class="text-xs text-slate-400">-</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </x-card>
                        </div>
                    </x-card>
                @empty
                    <div class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-8 text-center">
                        <x-ti-notes class="mx-auto mb-2 size-8 text-slate-400" />
                        <p class="text-sm font-semibold text-slate-700">Belum Ada Riwayat Kunjungan Kehamilan</p>
                        <p class="mt-1 text-sm text-slate-500">Belum ada data pemeriksaan kontrol ANC yang dicatat
                            untuk kehamilan ini.</p>
                        @if ($selectedPregnancy->status === 'aktif')
                            <div class="mt-3">
                                <x-button color="emerald" sm wire:click="openPemeriksaanModal">
                                    <x-ti-plus class="mr-1 inline size-4" /> Catat Pemeriksaan Pertama
                                </x-button>
                            </div>
                        @endif
                    </div>
                @endforelse
            </div>
        </div>

        {{-- =========================================================================
             MODAL: TAMBAH / UBAH PEMERIKSAAN KONTROL ANC (TERPADU DI SATU KOMPONEN)
             ========================================================================= --}}
        <x-modal
            wire="showPemeriksaanModal"
            :title="$editingEncounterId
                ? 'Ubah Pemeriksaan (Kontrol Kehamilan) - ' . $editingEncounterNumber
                : 'Tambah Pemeriksaan (Kontrol Kehamilan)'"
            z-index="z-[60]"
            size="5xl"
            persistent
        >
            <form class="space-y-6" wire:submit="savePemeriksaan">
                <!-- Info Pasien & Kehamilan Header Strip -->
                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="text-base font-bold text-slate-900">{{ $selectedPregnancy->patient?->full_name }}</h4>
                                <x-badge :text="$selectedPregnancy->gpa_label" color="emerald" light round />
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-x-2 text-sm text-slate-600">
                                <span>No. RM: <strong
                                        class="text-emerald-700">{{ $selectedPregnancy->patient?->medical_record_number_label }}</strong></span>
                                <span>&bull;</span>
                                <span>Suami: {{ $selectedPregnancy->patient?->family_name ?? '-' }}</span>
                                <span>&bull;</span>
                                <span>Umur:
                                    {{ $selectedPregnancy->patient?->umur_sekarang ? explode(' ', $selectedPregnancy->patient->umur_sekarang)[0] . ' tahun' : '-' }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <div class="shadow-xs rounded-xl border border-slate-200 bg-white px-3 py-1.5">
                                <span class="block text-sm font-semibold uppercase text-slate-500">HPHT</span>
                                <strong
                                    class="text-slate-800">{{ $selectedPregnancy->hpht ? $selectedPregnancy->hpht->format('d/m/Y') : '-' }}</strong>
                            </div>
                            <div class="shadow-xs rounded-xl border border-slate-200 bg-white px-3 py-1.5">
                                <span class="block text-sm font-semibold uppercase text-slate-500">HPL</span>
                                <strong
                                    class="text-emerald-700">{{ $selectedPregnancy->hpl ? $selectedPregnancy->hpl->format('d/m/Y') : '-' }}</strong>
                            </div>
                            <div class="shadow-xs rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5">
                                <span class="block text-sm font-semibold uppercase text-emerald-800">Usia Kehamilan</span>
                                <strong class="font-bold text-emerald-950">Minggu ke-{{ $selectedPregnancy->minggu_ke ?? '-' }}
                                    ({{ $selectedPregnancy->usia_kehamilan_saat_ini }})</strong>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($editingEncounterId)
                    <!-- Indikator Mode Ubah Kunjungan -->
                    <div class="shadow-2xs flex items-center justify-between gap-2 rounded-xl border border-amber-300 bg-amber-50/80 p-3">
                        <div class="flex items-center gap-2">
                            <x-ti-edit class="size-4 shrink-0 text-amber-700" />
                            <span class="text-sm font-bold text-amber-950">Mode Ubah Data Kontrol: {{ $editingEncounterNumber }}</span>
                        </div>
                        <span class="text-sm font-medium text-amber-800">Sedang mengubah data kontrol yang dipilih</span>
                    </div>
                @endif

                <!-- Bagian 1: Data Kunjungan & Bidan Pemeriksa -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-date format="YYYY-MM-DD" wire:model="pem_encounter_date" label="Tanggal Pemeriksaan *"
                        placeholder="Pilih tanggal periksa"
                    />

                    <x-date
                        format="YYYY-MM-DD"
                        wire:model="pem_next_encounter_date"
                        label="Tanggal Kontrol Selanjutnya"
                        placeholder="Pilih jadwal kontrol berikutnya"
                        hint="Jadwal perkiraan kontrol ANC berikutnya"
                        helpers
                    />

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Bidan Pemeriksa </label>
                        <div
                            class="shadow-2xs flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-slate-800">
                            <x-ti-user-check class="size-4.5 shrink-0 text-emerald-600" />
                            <span class="truncate">{{ auth()->user()?->name ?? 'Petugas Login' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Bagian 2: Tanda Vital & Pemeriksaan Fisik (Wajib) -->
                <div class="shadow-xs rounded-2xl border-2 border-slate-200/90 bg-white p-5">
                    <h5 class="mb-3 flex items-center gap-2 border-b-2 border-slate-100 pb-2.5 text-sm font-bold text-slate-800">
                        <x-ti-heart-rate-monitor class="size-4.5 text-emerald-600" />
                        Tanda Vital & Pengukuran Fisik (Wajib)
                    </h5>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Lila -->
                        <x-input
                            type="number"
                            wire:model="pem_lila"
                            label="Lingkar Lengan (Lila) *"
                            placeholder="Contoh: 24.5"
                            suffix="cm"
                            step="0.1"
                        />

                        <!-- BB kg -->
                        <x-input
                            type="number"
                            wire:model.live.debounce.300ms="pem_bb"
                            label="Berat Badan (BB) *"
                            placeholder="Contoh: 50.3"
                            suffix="kg"
                            step="0.1"
                        />

                        <!-- TB cm -->
                        <x-input
                            type="number"
                            wire:model.live.debounce.300ms="pem_tb"
                            label="Tinggi Badan (TB) *"
                            placeholder="Contoh: 155"
                            suffix="cm"
                        />

                        <!-- IMT (Otomatis ngitung) -->
                        <div class="flex flex-col justify-end">
                            <span class="mb-1 flex items-center justify-between text-sm font-semibold text-slate-700">
                                <span>IMT (Otomatis)</span>
                                @if ($pem_imt)
                                    @php $imtVal = floatval($pem_imt); @endphp
                                    @if ($imtVal < 18.5)
                                        <span
                                            class="rounded border border-sky-200 bg-sky-100 px-1.5 py-0.5 text-sm font-bold text-sky-700">Kurus</span>
                                    @elseif ($imtVal <= 24.9)
                                        <span
                                            class="rounded border border-emerald-200 bg-emerald-100 px-1.5 py-0.5 text-sm font-bold text-emerald-700"
                                        >Normal</span>
                                    @elseif ($imtVal <= 29.9)
                                        <span
                                            class="rounded border border-amber-200 bg-amber-100 px-1.5 py-0.5 text-sm font-bold text-amber-700"
                                        >Overweight</span>
                                    @else
                                        <span
                                            class="rounded border border-rose-200 bg-rose-100 px-1.5 py-0.5 text-sm font-bold text-rose-700"
                                        >Obesitas</span>
                                    @endif
                                @endif
                            </span>
                            <div
                                class="shadow-2xs flex h-10 w-full items-center justify-between rounded-xl border-2 border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-900">
                                <span>{{ $pem_imt ?: '-' }}</span>
                                <span class="text-sm font-normal text-slate-400">kg/m²</span>
                            </div>
                        </div>

                        <!-- TD Sistolik -->
                        <x-input
                            type="number"
                            wire:model.live.debounce.300ms="pem_td_sistolik"
                            label="TD Sistolik *"
                            placeholder="120"
                            suffix="mmHg"
                        />

                        <!-- TD Diastolik -->
                        <x-input
                            type="number"
                            wire:model.live.debounce.300ms="pem_td_diastolik"
                            label="TD Diastolik *"
                            placeholder="80"
                            suffix="mmHg"
                        />

                        <!-- MAP (Otomatis ngitung) -->
                        <x-input
                            type="number"
                            value="{{ $pem_map ?: '-' }}"
                            disabled
                            label="MAP (Otomatis)"
                            placeholder="-"
                            suffix="mmHg"
                        />

                        <!-- TFU -->
                        <x-input
                            type="number"
                            wire:model="pem_tfu"
                            label="TFU (Tinggi Fundus) *"
                            placeholder="Contoh: 28"
                            suffix="cm"
                            step="0.5"
                        />

                        <!-- DJJ -->
                        <div class="sm:col-span-2 lg:col-span-1">
                            <x-input
                                type="number"
                                wire:model="pem_djj"
                                label="DJJ (Denyut Jantung Janin) *"
                                placeholder="Contoh: 140"
                                suffix="x/mnt"
                            />
                        </div>
                    </div>
                </div>

                <!-- Bagian 3: Skrining Jiwa / Emosional (Wajib) -->
                <div class="shadow-xs rounded-2xl border-2 border-slate-200/90 bg-white p-5">
                    <div
                        class="mb-3 flex flex-col gap-2 border-b-2 border-slate-100 pb-2.5 sm:flex-row sm:items-center sm:justify-between">
                        <h5 class="flex items-center gap-2 text-sm font-bold text-slate-800">
                            <x-ti-brain class="size-4.5 text-emerald-600" />
                            Skrining Jiwa / Emosional (Wajib)
                        </h5>
                        <span class="text-sm text-slate-500">
                            Pedoman: <strong>1 - 8</strong> Normal &bull; <strong>9 - 14</strong> Sedang &bull; <strong>&gt; 14</strong>
                            Depresi
                        </span>
                    </div>

                    <div class="grid grid-cols-1 items-center gap-4 sm:grid-cols-2">
                        <div>
                            <x-input
                                type="number"
                                wire:model.live.debounce.300ms="pem_skor_skrining_jiwa"
                                label="Skor Skrining Jiwa (1 - 20)"
                                min="1"
                                max="20"
                                placeholder="Opsional (1 - 20)"
                                clearable
                            />
                        </div>

                        <div>
                            <span class="mb-1 block text-sm font-semibold text-slate-700">Hasil Evaluasi Emosional:</span>
                            @if ($pem_skor_skrining_jiwa !== null && $pem_skor_skrining_jiwa !== '')
                                @php $jiwaVal = intval($pem_skor_skrining_jiwa); @endphp
                                @if ($jiwaVal <= 8)
                                    <div
                                        class="shadow-2xs flex items-center gap-2 rounded-xl border-2 border-emerald-200 bg-emerald-50 p-2.5 text-sm font-semibold text-emerald-900">
                                        <x-ti-circle-check class="size-5 shrink-0 text-emerald-600" />
                                        <div>
                                            <p class="font-bold text-emerald-800">Normal (Skor {{ $jiwaVal }})</p>
                                            <span class="text-sm font-normal text-emerald-600">Kondisi psikologis stabil dan adaptif
                                                terhadap kehamilan.</span>
                                        </div>
                                    </div>
                                @elseif ($jiwaVal <= 14)
                                    <div
                                        class="shadow-2xs flex items-center gap-2 rounded-xl border-2 border-amber-200 bg-amber-50 p-2.5 text-sm font-semibold text-amber-900">
                                        <x-ti-alert-circle class="size-5 shrink-0 text-amber-600" />
                                        <div>
                                            <p class="font-bold text-amber-800">Sedang (Skor {{ $jiwaVal }})</p>
                                            <span class="text-sm font-normal text-amber-600">Terdapat kecemasan/stres sedang. Butuh
                                                konseling & dukungan keluarga.</span>
                                        </div>
                                    </div>
                                @else
                                    <div
                                        class="shadow-2xs flex items-center gap-2 rounded-xl border-2 border-rose-200 bg-rose-50 p-2.5 text-sm font-semibold text-rose-900">
                                        <x-ti-alert-triangle class="size-5 shrink-0 text-rose-600" />
                                        <div>
                                            <p class="font-bold text-rose-800">Depresi (Skor {{ $jiwaVal }})</p>
                                            <span class="text-sm font-normal text-rose-600">Risiko depresi perinatal tinggi. Perlu rujukan
                                                ke psikolog/dokter.</span>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div
                                    class="shadow-2xs flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-slate-50 p-2.5 text-sm text-slate-500">
                                    <x-ti-info-circle class="size-4 shrink-0" />
                                    <span>Masukkan skor untuk melihat klasifikasi kondisi kejiwaan ibu.</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Bagian 4: Skrining Poedji Rochyati (Wajib Tiap Kunjungan) -->
                <div class="shadow-xs rounded-2xl border-2 border-emerald-300/80 bg-white p-5">
                    <div
                        class="mb-3 flex flex-col gap-3 border-b-2 border-slate-100 pb-2.5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h5 class="flex items-center gap-2 text-sm font-bold text-slate-800">
                                <x-ti-alert-triangle class="size-4.5 text-emerald-600" />
                                Skrining Risiko Poedji Rochyati (Wajib Tiap Kunjungan)
                            </h5>
                            <p class="mt-0.5 text-sm text-slate-500">
                                Centang faktor risiko yang ditemukan pada kunjungan ini. Skor dasar awal ibu hamil adalah
                                <strong>2</strong>.
                            </p>
                        </div>

                        <!-- Badge Skor Akhir Reactive -->
                        <div class="flex items-center gap-2">
                            @if ($pem_skor_puji_rochyati <= 5)
                                <span
                                    class="shadow-2xs inline-flex items-center gap-1.5 rounded-full border-2 border-emerald-300 bg-emerald-100 px-3 py-1 text-sm font-black text-emerald-800"
                                >
                                    <x-ti-shield-check class="size-4 text-emerald-600" />
                                    Skor: {{ $pem_skor_puji_rochyati }} - KRR
                                </span>
                            @elseif ($pem_skor_puji_rochyati <= 10)
                                <span
                                    class="shadow-2xs inline-flex items-center gap-1.5 rounded-full border-2 border-amber-300 bg-amber-100 px-3 py-1 text-sm font-black text-amber-800"
                                >
                                    <x-ti-alert-triangle class="size-4 text-amber-600" />
                                    Skor: {{ $pem_skor_puji_rochyati }} - KRT
                                </span>
                            @else
                                <span
                                    class="shadow-2xs inline-flex items-center gap-1.5 rounded-full border-2 border-rose-300 bg-rose-100 px-3 py-1 text-sm font-black text-rose-800"
                                >
                                    <x-ti-alert-octagon class="size-4 text-rose-600" />
                                    Skor: {{ $pem_skor_puji_rochyati }} - KRST
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Checklist Faktor Risiko Sesuai docs/pojie rohyati.md -->
                    <div class="space-y-3.5">
                        @foreach (\App\Enums\PoedjiRochyatiEnum::groupedCases() as $roman => $group)
                            <div class="{{ $group['border_class'] }} {{ $group['bg_class'] }} shadow-2xs rounded-xl border-2 p-3.5">
                                <div class="mb-2.5 flex items-center justify-between">
                                    <span
                                        class="{{ $group['title_class'] }} flex items-center gap-1.5 text-sm font-bold uppercase tracking-wider"
                                    >
                                        <span
                                            class="size-4.5 {{ $group['badge_bg'] }} {{ $group['badge_text'] }} {{ $group['badge_border'] }} inline-flex items-center justify-center rounded-full border text-sm font-extrabold"
                                        >{{ $roman }}</span>
                                        {{ $group['title'] }} &bull; {{ $group['subtitle'] }}
                                    </span>
                                    <span class="{{ $group['count_class'] }} text-sm font-semibold">{{ count($group['cases']) }} Faktor
                                        Risiko</span>
                                </div>
                                <div class="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                                    @foreach ($group['cases'] as $case)
                                        <div class="{{ $loop->last && count($group['cases']) % 2 !== 0 ? 'sm:col-span-2' : '' }}">
                                            <x-checkbox value="{{ $case->value }}" wire:model.live="pem_faktor_risiko_selected"
                                                label="{{ $case->label() }}" color="{{ $case->color() }}"
                                            />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Bagian 5: Hasil Laboratorium (Opsional) -->
                <div class="p-4.5 shadow-xs rounded-2xl border-2 border-slate-200/90 bg-white" x-data="{ openLab: false }">
                    <div class="flex cursor-pointer items-center justify-between" @click="openLab = !openLab">
                        <h5 class="flex items-center gap-2 text-sm font-bold text-slate-800">
                            <x-ti-flask class="size-4.5 text-emerald-600" />
                            Hasil Laboratorium (Opsional)
                            <span class="rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-sm font-bold text-slate-600">
                                Opsional
                            </span>
                        </h5>
                        <button class="flex items-center gap-1 text-sm font-semibold text-emerald-700" type="button">
                            <span x-text="openLab ? 'Sembunyikan' : 'Buka Form Lab'"></span>
                            <x-ti-chevron-down class="size-4 transition-transform duration-200" ::class="openLab ? 'rotate-180' : ''" />
                        </button>
                    </div>

                    <div class="mt-4 border-t-2 border-slate-100 pt-4" x-show="openLab" x-transition>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <x-input
                                type="number"
                                wire:model.live.debounce.300ms="pem_lab_hb"
                                label="Hemoglobin (HB)"
                                placeholder="Contoh: 11.5"
                                suffix="g/dL"
                                step="0.1"
                            />

                            <x-input
                                type="number"
                                wire:model="pem_lab_gda"
                                label="Gula Darah Acak (GDA)"
                                placeholder="Contoh: 110"
                                suffix="mg/dL"
                            />

                            <x-select.styled wire:model="pem_lab_protein_urine" label="Protein Urine" :options="['Negatif', 'Trace', '+1', '+2', '+3', '+4']" />

                            <x-select.styled wire:model="pem_lab_reduksi_urine" label="Reduksi Urine" :options="['Negatif', '+1', '+2', '+3', '+4']" />

                            <x-select.styled wire:model="pem_lab_hiv" label="HIV" :options="['Belum Diperiksa', 'Negatif', 'Positif']" />

                            <x-select.styled wire:model="pem_lab_syphilis" label="Syphilis (Sifilis)" :options="['Belum Diperiksa', 'Negatif', 'Positif']" />

                            <x-select.styled wire:model="pem_lab_hbsag" label="HBsAG (Hepatitis B)" :options="['Belum Diperiksa', 'Negatif', 'Positif']" />
                        </div>
                    </div>
                </div>

                <!-- Pemeriksaan USG & Masalah Kehamilan -->
                <div class="p-4.5 shadow-xs rounded-2xl border-2 border-slate-200/90 bg-white">
                    <h5 class="mb-3.5 flex items-center gap-2 text-sm font-bold text-slate-800">
                        <x-ti-scan class="size-4.5 text-emerald-600" />
                        Pemeriksaan USG & Masalah Kehamilan
                    </h5>

                    <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-2">
                        <!-- 1. Form USG (ya / tidak) -->
                        <div>
                            <x-select.styled
                                wire:model="pem_is_usg"
                                label="Pemeriksaan USG Dilakukan? *"
                                hint="Tandai apakah pada kunjungan kontrol ini dilakukan pemeriksaan ultrasonografi (USG)."
                                :options="[
                                    ['label' => 'Tidak (Tidak Dilakukan USG)', 'value' => 0],
                                    ['label' => 'Ya (Dilakukan USG)', 'value' => 1],
                                ]"
                                select="label:label|value:value"
                            />
                        </div>

                        <!-- 2. Form Masalah Kehamilan (Pilihan) -->
                        <div class="space-y-2.5">
                            <x-select.styled wire:model.live="pem_masalah_kehamilan" label="Masalah Kehamilan (Jika Ada)"
                                :options="\App\Enums\MaternalComplicationEnum::choices('antenatal')"
                                select="label:label|value:value"
                            />

                            @if ($pem_masalah_kehamilan === 'Lainnya')
                                <x-input wire:model="pem_masalah_kehamilan_lainnya" label="Sebutkan Masalah Kehamilan Lainnya *"
                                    placeholder="Contoh: Plasenta previa, Anemia berat, dll..."
                                />
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="flex items-center justify-end gap-2 border-t-2 border-slate-100 pt-3.5">
                    <x-button color="gray" flat x-on:click="$wire.showPemeriksaanModal = false">
                        Batal
                    </x-button>
                    <x-button submit color="emerald" loading="savePemeriksaan">
                        <x-ti-device-floppy class="mr-1.5 inline size-4" />
                        {{ $editingEncounterId ? 'Simpan Perubahan Kontrol' : 'Simpan Pemeriksaan' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
