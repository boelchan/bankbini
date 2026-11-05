<div>
    @php
        $selectedPregnancy = $this->selectedPregnancy;
    @endphp

    @if ($selectedPregnancy)
        <div class="space-y-4 pt-3.5">
            <x-button color="emerald" sm wire:click="openNeonatalModal">
                <x-ti-plus class="mr-1 inline size-4" /> Tambah Kunjungan Neonatal
            </x-button>

            <div class="space-y-3">
                @forelse ($selectedPregnancy->neonatalEncounters as $neonatal)
                    <div
                        class="hover:shadow-xs rounded-xl border border-slate-200/90 bg-slate-50/50 p-4 transition-all hover:border-emerald-300 hover:bg-white">
                        <div
                            class="flex flex-col justify-between gap-2 border-b border-slate-200 pb-3 sm:flex-row sm:items-center">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :text="$neonatal->kn_type ?: 'KN'" color="emerald" round />
                                <span class="text-sm font-bold text-slate-900">
                                    Hari ke-{{ $neonatal->hari_ke ?? 0 }}
                                    ({{ $neonatal->encounter_date ? $neonatal->encounter_date->translatedFormat('d F Y') : '-' }})
                                </span>

                                {{-- Indikator PJB jika KN1 --}}
                                @if ($neonatal->kn_type === 'KN1')
                                    <x-badge :text="'PJB: ' . $neonatal->pjb" :color="$neonatal->pjb === 'Normal' ? 'emerald' : 'rose'" light round />
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-sm text-slate-500">Bidan:
                                    <strong class="text-slate-700">{{ $neonatal->creator?->name ?? 'Bidan' }}</strong></span>
                                @if ($neonatal->canManage())
                                    <x-button.circle
                                        title="Ubah Kunjungan Neonatal"
                                        color="emerald"
                                        flat
                                        sm
                                        wire:click="openNeonatalModal({{ $neonatal->id }})"
                                    >
                                        <x-ti-edit class="size-4" />
                                    </x-button.circle>
                                    <x-button.circle
                                        title="Hapus Kunjungan Neonatal"
                                        color="red"
                                        flat
                                        sm
                                        wire:confirm="Yakin ingin menghapus data kunjungan neonatal ini?"
                                        wire:click="deleteNeonatal({{ $neonatal->id }})"
                                    >
                                        <x-ti-trash class="size-4" />
                                    </x-button.circle>
                                @endif
                            </div>
                        </div>

                        <!-- Grid Antropometri & Fisik Bayi -->
                        <div class="mt-3 grid grid-cols-2 gap-2.5 text-sm sm:grid-cols-4 lg:grid-cols-6">
                            <div class="shadow-2xs rounded-xl border border-slate-200 bg-white p-2.5">
                                <span class="block text-xs font-medium text-slate-500">Berat Badan:</span>
                                <strong
                                    class="{{ $neonatal->bb && $neonatal->bb < 2.5 ? 'text-rose-600 font-bold' : '' }} text-sm text-slate-800"
                                >{{ $neonatal->bb !== null ? $neonatal->bb . ' kg' : '-' }}</strong>
                            </div>
                            <div class="shadow-2xs rounded-xl border border-slate-200 bg-white p-2.5">
                                <span class="block text-xs font-medium text-slate-500">Panjang Badan:</span>
                                <strong
                                    class="text-sm text-slate-800">{{ $neonatal->tb !== null ? $neonatal->tb . ' cm' : '-' }}</strong>
                            </div>
                            <div class="shadow-2xs rounded-xl border border-slate-200 bg-white p-2.5">
                                <span class="block text-xs font-medium text-slate-500">Pernapasan (RR):</span>
                                <strong class="text-sm text-slate-800">{{ $neonatal->rr ?? '-' }} <span
                                        class="text-xs font-normal text-slate-500"
                                    >x/mnt</span></strong>
                            </div>
                            <div class="shadow-2xs rounded-xl border border-slate-200 bg-white p-2.5">
                                <span class="block text-xs font-medium text-slate-500">Suhu Tubuh:</span>
                                <strong
                                    class="text-sm text-slate-800">{{ $neonatal->suhu !== null ? $neonatal->suhu . ' °C' : '-' }}</strong>
                            </div>
                            <div class="shadow-2xs rounded-xl border border-slate-200 bg-white p-2.5">
                                <span class="block text-xs font-medium text-slate-500">Warna Kulit:</span>
                                <strong
                                    class="block truncate text-sm text-slate-800"
                                    title="{{ \App\Enums\NeonatalSkinColorEnum::tryFrom($neonatal->warna_kulit)?->label() ?? ($neonatal->warna_kulit ?: '-') }}"
                                >{{ \App\Enums\NeonatalSkinColorEnum::tryFrom($neonatal->warna_kulit)?->label() ?? ($neonatal->warna_kulit ?: '-') }}</strong>
                            </div>
                            <div class="shadow-2xs rounded-xl border border-slate-200 bg-white p-2.5">
                                <span class="block text-xs font-medium text-slate-500">Tali Pusat:</span>
                                <strong
                                    class="block truncate text-sm text-slate-800"
                                    title="{{ \App\Enums\UmbilicalCordConditionEnum::tryFrom($neonatal->tali_pusat)?->label() ?? ($neonatal->tali_pusat ?: '-') }}"
                                >{{ \App\Enums\UmbilicalCordConditionEnum::tryFrom($neonatal->tali_pusat)?->label() ?? ($neonatal->tali_pusat ?: '-') }}</strong>
                            </div>
                        </div>

                        @if ($neonatal->kn_type === 'KN1')
                            <!-- Ringkasan Pelayanan KN1 -->
                            <div
                                class="mt-2.5 flex flex-wrap items-center gap-2 rounded-xl border border-slate-200/60 bg-white/80 p-2.5 text-xs text-slate-600">
                                <span class="font-semibold text-slate-700">Pelayanan KN1:</span>
                                <x-badge :text="'Salep Mata: ' . strtoupper($neonatal->salep_mata ?? '-')" :color="$neonatal->salep_mata === 'ya' ? 'emerald' : 'slate'" light round />
                                <x-badge :text="'Vit K: ' . strtoupper($neonatal->vit_k ?? '-')" :color="$neonatal->vit_k === 'ya' ? 'emerald' : 'slate'" light round />
                                <x-badge :text="'IMD: ' . strtoupper($neonatal->imd ?? '-')" :color="$neonatal->imd === 'ya' ? 'emerald' : 'slate'" light round />
                                <x-badge :text="'HB0: ' . strtoupper($neonatal->hb0 ?? '-')" :color="$neonatal->hb0 === 'ya' ? 'emerald' : 'slate'" light round />
                                <x-badge :text="'SHK: ' . strtoupper($neonatal->shk ?? '-')" :color="$neonatal->shk === 'ya' ? 'emerald' : 'slate'" light round />
                                <x-badge :text="'HBiG: ' . strtoupper($neonatal->hbig ?? '-')" :color="$neonatal->hbig === 'ya' ? 'amber' : 'slate'" light round />
                                <x-badge :text="'PJB: ' . (\App\Enums\NeonatalPjbEnum::tryFrom($neonatal->pjb)?->label() ?? ($neonatal->pjb ?: '-'))" :color="strtolower((string)$neonatal->pjb) === 'normal' ? 'emerald' : 'rose'" light round />
                            </div>
                        @endif

                        @if ($neonatal->catatan)
                            <div class="mt-2 text-xs italic text-slate-500">
                                Catatan: {{ $neonatal->catatan }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-6 text-center">
                        <x-ti-baby-carriage class="mx-auto mb-2 size-8 text-slate-400" />
                        <p class="text-sm font-semibold text-slate-800">Belum Ada Kunjungan Neonatal Tercatat</p>
                        <p class="mt-1 text-xs text-slate-500">Klik tombol di bawah untuk mencatat kunjungan kontrol
                            bayi baru lahir (KN1, KN2, atau KN3).</p>
                        <div class="mt-3">
                            <x-button color="emerald" sm wire:click="openNeonatalModal">
                                <x-ti-plus class="mr-1 inline size-4" /> Catat Kunjungan Neonatal
                            </x-button>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- MODAL FORM PENCATATAN KUNJUNGAN NEONATAL (KN1 - KN3) -->
        <x-modal
            wire="showNeonatalModal"
            :title="$editingNeonatalId ? 'Ubah Pemeriksaan Kunjungan Neonatal' : 'Catat Pemeriksaan Kunjungan Neonatal'"
            z-index="z-[60]"
            size="5xl"
            persistent
            scrollable
        >
            <form class="space-y-5" wire:submit="saveNeonatal">
                <!-- 1. Waktu Kunjungan & Klasifikasi Neonatal Otomatis -->
                <div class="grid grid-cols-1 items-center gap-4 sm:grid-cols-2">
                    <x-date
                        format="YYYY-MM-DD"
                        wire:model.live="neonatal_encounter_date"
                        label="Tanggal Kunjungan Neonatal *"
                    />

                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-3">
                        <span class="block text-xs font-medium text-slate-500">Klasifikasi Kunjungan Neonatal Otomatis:</span>
                        <div class="mt-1.5 flex items-center gap-2">
                            <x-badge :text="$neonatal_kn_type" color="emerald" round />
                            <x-badge :text="'Hari ke-' . ($neonatal_hari_ke ?? 0)" color="slate" light round />
                        </div>
                    </div>
                </div>

                <!-- 2. Antropometri & Pemeriksaan Fisik Bayi -->
                <div class="space-y-3 border-t border-slate-200/80 pt-4">
                    <h5 class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-600">
                        <x-ti-heart-rate-monitor class="size-4 text-emerald-600" />
                        Antropometri & Tanda Vital Bayi
                    </h5>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        <x-input
                            wire:model.live.debounce.400ms="neonatal_bb"
                            label="Berat Badan (kg)"
                            placeholder="3.2"
                            suffix="kg"
                            hint="BBLR jika < 2.5 kg"
                        />
                        <x-input
                            wire:model="neonatal_tb"
                            label="Panjang Badan (cm)"
                            placeholder="49.0"
                            suffix="cm"
                        />
                        <x-number
                            wire:model="neonatal_rr"
                            label="Pernapasan (RR)"
                            placeholder="40"
                            suffix="x/mnt"
                            min="20"
                            max="120"
                            hint="Normal: 40-60 x/mnt"
                        />
                        <x-input
                            wire:model="neonatal_suhu"
                            label="Suhu Tubuh (°C)"
                            placeholder="36.5"
                            suffix="°C"
                        />
                        <x-select.styled
                            wire:model="neonatal_warna_kulit"
                            label="Warna Kulit"
                            placeholder="Pilih warna kulit..."
                            :options="\App\Enums\NeonatalSkinColorEnum::choices()"
                            select="label:label|value:value"
                        />
                        <x-select.styled
                            wire:model="neonatal_tali_pusat"
                            label="Tali Pusat"
                            placeholder="Pilih kondisi tali pusat..."
                            :options="\App\Enums\UmbilicalCordConditionEnum::choices()"
                            select="label:label|value:value"
                        />
                    </div>
                </div>

                <!-- 3. Pelayanan & Skrining Standar KN1 -->
                <div class="space-y-3 border-t border-slate-200/80 pt-4">
                    <div class="flex items-center justify-between">
                        <h5 class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-600">
                            <x-ti-shield-check class="size-4 text-emerald-600" />
                            Pelayanan & Skrining Standar KN1 (0 - 2 Hari)
                        </h5>
                        @if ($neonatal_kn_type === 'KN1')
                            <x-badge text="Wajib pada KN1" color="emerald" light round />
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-4">
                        <x-select.styled
                            wire:model="neonatal_salep_mata"
                            label="Salep Mata"
                            placeholder="Pilih..."
                            :options="\App\Enums\YesNoEnum::choices('Diberikan (Ya)', 'Tidak')"
                            select="label:label|value:value"
                        />
                        <x-select.styled
                            wire:model="neonatal_vit_k"
                            label="Vitamin K1"
                            placeholder="Pilih..."
                            :options="\App\Enums\YesNoEnum::choices('Diberikan (Ya)', 'Tidak')"
                            select="label:label|value:value"
                        />
                        <x-select.styled
                            wire:model="neonatal_imd"
                            label="IMD"
                            placeholder="Pilih..."
                            :options="\App\Enums\YesNoEnum::choices('Dilakukan (Ya)', 'Tidak')"
                            select="label:label|value:value"
                        />
                        <x-select.styled
                            wire:model="neonatal_hb0"
                            label="Imunisasi HB0"
                            placeholder="Pilih..."
                            :options="\App\Enums\YesNoEnum::choices('Diberikan (Ya)', 'Tidak')"
                            select="label:label|value:value"
                        />
                        <x-select.styled
                            wire:model="neonatal_shk"
                            label="Skrining SHK"
                            placeholder="Pilih..."
                            :options="\App\Enums\YesNoEnum::choices('Dilakukan (Ya)', 'Tidak')"
                            select="label:label|value:value"
                        />
                        <x-select.styled
                            wire:model="neonatal_hbig"
                            label="Injeksi HBiG"
                            placeholder="Pilih..."
                            :options="\App\Enums\YesNoEnum::choices('Diberikan (Ya)', 'Tidak Diberikan')"
                            select="label:label|value:value"
                        />
                        <x-select.styled
                            wire:model="neonatal_pjb"
                            label="Skrining PJB"
                            placeholder="Pilih..."
                            :options="\App\Enums\NeonatalPjbEnum::choices()"
                            select="label:label|value:value"
                        />
                    </div>
                </div>

                <!-- 4. Catatan / Edukasi / Terapi Kunjungan -->
                <div class="space-y-3 border-t border-slate-200/80 pt-4">
                    <x-textarea
                        wire:model="neonatal_catatan"
                        label="Catatan"
                        placeholder="Tuliskan tindakan, terapi yang diberikan, KIE perawatan tali pusat/ASI, atau anjuran kontrol..."
                        rows="2"
                    />
                </div>

                <!-- Footer Buttons -->
                <div class="flex justify-end gap-2 border-t border-slate-200/80 pt-4">
                    <x-button color="gray" flat x-on:click="$wire.showNeonatalModal = false">Batal</x-button>
                    <x-button submit color="emerald" loading="saveNeonatal">
                        <x-ti-device-floppy class="mr-1 inline size-4" />
                        {{ $editingNeonatalId ? 'Simpan Perubahan' : 'Simpan Kunjungan' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>