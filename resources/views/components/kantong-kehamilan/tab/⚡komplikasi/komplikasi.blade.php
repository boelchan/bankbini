<div>
    @php
        $selectedPregnancy = $this->selectedPregnancy;
    @endphp

    @if ($selectedPregnancy)
        <div class="space-y-4 pt-3.5">
            <div
                class="flex flex-col justify-between gap-3 rounded-xl border border-slate-200/90 bg-slate-50/70 p-4 sm:flex-row sm:items-center">
                <div>
                    @if ($selectedPregnancy->neonatalComplication)
                    @else
                        <x-button color="emerald" sm wire:click="openKomplikasiModal">
                            <x-ti-plus class="mr-1 inline size-4" /> Catat Kasus / Komplikasi
                        </x-button>
                    @endif
                </div>
            </div>

            @if ($selectedPregnancy->neonatalComplication)
                @php
                    $komp = $selectedPregnancy->neonatalComplication;
                @endphp
                <div
                    class="border-slate-200/90 bg-white shadow-xs space-y-4 rounded-xl border p-4">
                    <div
                        class="flex flex-col justify-between gap-2 border-b border-slate-200/80 pb-3 sm:flex-row sm:items-center">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-slate-700">Tanggal Pemeriksaan:</span>
                            <span
                                class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-900">{{ $komp->encounter_date ? $komp->encounter_date->translatedFormat('d F Y') : '-' }}</span>
                           
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500">Pemeriksa:
                                <strong class="text-slate-700">{{ $komp->creator?->name ?? 'Bidan' }}</strong></span>
                            @if ($komp->canManage())
                                <x-button.circle
                                    title="Ubah Data"
                                    color="emerald"
                                    flat
                                    sm
                                    wire:click="openKomplikasiModal"
                                >
                                    <x-ti-edit class="size-4" />
                                </x-button.circle>
                                <x-button.circle
                                    title="Hapus Data"
                                    color="red"
                                    flat
                                    sm
                                    wire:confirm="Yakin ingin menghapus data kasus/komplikasi neonatal ini?"
                                    wire:click="deleteKomplikasi({{ $komp->id }})"
                                >
                                    <x-ti-trash class="size-4" />
                                </x-button.circle>
                            @endif
                        </div>
                    </div>

                    <!-- Grid Indikator Komplikasi Neonatal -->
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- 1. Trauma Lahir -->
                        <div
                            class="{{ $komp->trauma_lahir === 'ya' ? 'border-rose-300 bg-rose-50/70' : 'border-slate-200 bg-white' }} rounded-xl border p-3">
                            <span class="block text-xs font-medium text-slate-500">1. Trauma Lahir:</span>
                            <div class="mt-1 flex items-center justify-between">
                                <span
                                    class="{{ $komp->trauma_lahir === 'ya' ? 'text-rose-700 font-bold' : 'text-slate-800' }} text-sm"
                                >
                                    {{ $komp->trauma_lahir === 'ya' ? 'Ya (Ada Trauma)' : 'Tidak Ada (Normal)' }}
                                </span>
                                <x-badge :text="strtoupper($komp->trauma_lahir)" :color="$komp->trauma_lahir === 'ya' ? 'rose' : 'emerald'" light round />
                            </div>
                        </div>

                        <!-- 2. Asfiksia -->
                        <div
                            class="{{ $komp->asfiksia !== 'normal' ? 'border-rose-300 bg-rose-50/70' : 'border-slate-200 bg-white' }} rounded-xl border p-3">
                            <span class="block text-xs font-medium text-slate-500">2. Asfiksia:</span>
                            <div class="mt-1 flex items-center justify-between">
                                <span
                                    class="{{ $komp->asfiksia !== 'normal' ? 'text-rose-700 font-bold' : 'text-slate-800' }} text-sm"
                                >
                                    {{ ucfirst($komp->asfiksia) }}
                                </span>
                                <x-badge :text="$komp->asfiksia === 'normal' ? 'NORMAL' : 'ASFIKSIA'" :color="$komp->asfiksia !== 'normal' ? 'rose' : 'emerald'" light round />
                            </div>
                        </div>

                        <!-- 3. BBLR -->
                        <div
                            class="{{ $komp->bblr === 'ya' ? 'border-rose-300 bg-rose-50/70' : 'border-slate-200 bg-white' }} rounded-xl border p-3">
                            <span class="block text-xs font-medium text-slate-500">3. BBLR (&lt; 2500 gr):</span>
                            <div class="mt-1 flex items-center justify-between">
                                <span
                                    class="{{ $komp->bblr === 'ya' ? 'text-rose-700 font-bold' : 'text-slate-800' }} text-sm"
                                >
                                    {{ $komp->bblr === 'ya' ? 'Ya' : 'Tidak' }}
                                </span>
                                <x-badge :text="strtoupper($komp->bblr)" :color="$komp->bblr === 'ya' ? 'rose' : 'emerald'" light round />
                            </div>
                        </div>

                        <!-- 4. Infeksi -->
                        <div
                            class="{{ $komp->infeksi === 'ya' ? 'border-rose-300 bg-rose-50/70' : 'border-slate-200 bg-white' }} rounded-xl border p-3">
                            <span class="block text-xs font-medium text-slate-500">4. Infeksi Neonatorum:</span>
                            <div class="mt-1 flex items-center justify-between">
                                <span
                                    class="{{ $komp->infeksi === 'ya' ? 'text-rose-700 font-bold' : 'text-slate-800' }} text-sm"
                                >
                                    {{ $komp->infeksi === 'ya' ? 'Ya (Ada Infeksi)' : 'Tidak Ada' }}
                                </span>
                                <x-badge :text="strtoupper($komp->infeksi)" :color="$komp->infeksi === 'ya' ? 'rose' : 'emerald'" light round />
                            </div>
                        </div>

                        <!-- 5. TN -->
                        <div
                            class="{{ $komp->tn === 'ya' ? 'border-rose-300 bg-rose-50/70' : 'border-slate-200 bg-white' }} rounded-xl border p-3">
                            <span class="block text-xs font-medium text-slate-500">5. Tetanus Neonatorum (TN):</span>
                            <div class="mt-1 flex items-center justify-between">
                                <span
                                    class="{{ $komp->tn === 'ya' ? 'text-rose-700 font-bold' : 'text-slate-800' }} text-sm"
                                >
                                    {{ $komp->tn === 'ya' ? 'Ya (Positif TN)' : 'Tidak Ada' }}
                                </span>
                                <x-badge :text="strtoupper($komp->tn)" :color="$komp->tn === 'ya' ? 'rose' : 'emerald'" light round />
                            </div>
                        </div>

                        <!-- 6. Kelainan Bawaan -->
                        <div
                            class="{{ $komp->kelainan_bawaan === 'ya' ? 'border-rose-300 bg-rose-50/70' : 'border-slate-200 bg-white' }} rounded-xl border p-3">
                            <span class="block text-xs font-medium text-slate-500">6. Kelainan Bawaan:</span>
                            <div class="mt-1 flex items-center justify-between">
                                <span
                                    class="{{ $komp->kelainan_bawaan === 'ya' ? 'text-rose-700 font-bold' : 'text-slate-800' }} text-sm"
                                >
                                    {{ $komp->kelainan_bawaan === 'ya' ? 'Ya (Ada Kelainan)' : 'Tidak Ada' }}
                                </span>
                                <x-badge :text="strtoupper($komp->kelainan_bawaan)" :color="$komp->kelainan_bawaan === 'ya' ? 'rose' : 'emerald'" light round />
                            </div>
                        </div>

                        <!-- 7. HK (+) Hasil SHK -->
                        <div
                            class="{{ $komp->hk_positif === 'positif' ? 'border-rose-300 bg-rose-50/70' : 'border-slate-200 bg-white' }} rounded-xl border p-3">
                            <span class="block text-xs font-medium text-slate-500">7. Hasil SHK (HK +):</span>
                            <div class="mt-1 flex items-center justify-between">
                                <span
                                    class="{{ $komp->hk_positif === 'positif' ? 'text-rose-700 font-bold' : 'text-slate-800' }} text-sm"
                                >
                                    {{ ucfirst($komp->hk_positif) }}
                                </span>
                                <x-badge :text="strtoupper($komp->hk_positif)" :color="$komp->hk_positif === 'positif' ? 'rose' : ($komp->hk_positif === 'negatif' ? 'emerald' : 'slate')" light round />
                            </div>
                        </div>

                        <!-- 8. Covid-19 -->
                        <div
                            class="{{ $komp->covid19 === 'ya' ? 'border-rose-300 bg-rose-50/70' : 'border-slate-200 bg-white' }} rounded-xl border p-3">
                            <span class="block text-xs font-medium text-slate-500">8. Konfirmasi Covid-19:</span>
                            <div class="mt-1 flex items-center justify-between">
                                <span
                                    class="{{ $komp->covid19 === 'ya' ? 'text-rose-700 font-bold' : 'text-slate-800' }} text-sm"
                                >
                                    {{ $komp->covid19 === 'ya' ? 'Ya (Positif)' : 'Tidak' }}
                                </span>
                                <x-badge :text="strtoupper($komp->covid19)" :color="$komp->covid19 === 'ya' ? 'rose' : 'emerald'" light round />
                            </div>
                        </div>

                        <!-- 9. Masalah Lainnya -->
                        <div
                            class="{{ !empty($komp->masalah_lainnya) ? 'border-amber-300 bg-amber-50/70' : 'border-slate-200 bg-white' }} rounded-xl border p-3">
                            <span class="block text-xs font-medium text-slate-500">9. Masalah Lainnya:</span>
                            <div class="mt-1">
                                <span
                                    class="{{ !empty($komp->masalah_lainnya) ? 'text-amber-900 font-semibold' : 'text-slate-400' }} text-sm"
                                >
                                    {{ $komp->masalah_lainnya ?: 'Tidak Ada' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    @if ($komp->catatan)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
                            <span class="mb-0.5 block font-bold text-slate-800">Catatan:</span>
                            <p class="whitespace-pre-line">{{ $komp->catatan }}</p>
                        </div>
                    @endif
                </div>
            @else
                <!-- Empty State Kasus / Komplikasi -->
                <div class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-8 text-center">
                    <x-ti-first-aid-kit class="mx-auto mb-2 size-8 text-slate-400" />
                    <p class="text-sm font-semibold text-slate-800">Belum Ada Catatan Kasus / Komplikasi Neonatal</p>
                    <p class="mx-auto mt-1 max-w-md text-xs text-slate-500">
                        Skrining kasus dan komplikasi neonatal dicatat 1 kali untuk mendeteksi risiko komplikasi seperti
                        asfiksia, BBLR, infeksi, trauma lahir, dan kelainan bawaan pada bayi.
                    </p>
                    <div class="mt-4">
                        <x-button color="emerald" sm wire:click="openKomplikasiModal">
                            <x-ti-plus class="mr-1 inline size-4" /> Catat Kasus / Komplikasi
                        </x-button>
                    </div>
                </div>
            @endif
        </div>

        <!-- MODAL: CATAT / UBAH KASUS & KOMPLIKASI NEONATAL -->
        <x-modal
            title="Catat Kasus & Komplikasi Neonatal"
            wire="showKomplikasiModal"
            z-index="z-50"
            size="5xl"
            persistent
        >
            <form class="space-y-5" wire:submit="saveKomplikasi">
                <!-- 1. Tanggal Skrining -->
                <div class="max-w-xs">
                    <x-date
                        format="YYYY-MM-DD"
                        wire:model="komplikasi_encounter_date"
                        label="Tanggal Pemeriksaan / Skrining *"
                        hint="Tanggal dilakukannya skrining komplikasi"
                    />
                </div>

                <!-- 2. Formulir 9 Indikator Kasus / Komplikasi Neonatal -->
                <div class="space-y-3 border-t border-slate-200/80 pt-4">
                    <h5 class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-600">
                        <x-ti-shield-exclamation class="size-4 text-amber-600" />
                        Deteksi 9 Indikator Komplikasi Neonatal
                    </h5>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <x-select.styled
                            wire:model="komplikasi_trauma_lahir"
                            label="1. Trauma Lahir *"
                            :options="\App\Enums\YesNoEnum::choices()"
                            select="label:label|value:value"
                        />

                        <x-select.styled
                            wire:model="komplikasi_asfiksia"
                            label="2. Asfiksia *"
                            :options="\App\Enums\AsphyxiaLevelEnum::choices()"
                            select="label:label|value:value"
                        />

                        <x-select.styled
                            wire:model="komplikasi_bblr"
                            label="3. BBLR (< 2500 gr) *"
                            :options="\App\Enums\YesNoEnum::choices()"
                            select="label:label|value:value"
                        />

                        <x-select.styled
                            wire:model="komplikasi_infeksi"
                            label="4. Infeksi Neonatorum *"
                            :options="\App\Enums\YesNoEnum::choices()"
                            select="label:label|value:value"
                        />

                        <x-select.styled
                            wire:model="komplikasi_tn"
                            label="5. Tetanus Neonatorum (TN) *"
                            :options="\App\Enums\YesNoEnum::choices()"
                            select="label:label|value:value"
                        />

                        <x-select.styled
                            wire:model="komplikasi_kelainan_bawaan"
                            label="6. Kelainan Bawaan *"
                            :options="\App\Enums\YesNoEnum::choices()"
                            select="label:label|value:value"
                        />

                        <x-select.styled
                            wire:model="komplikasi_hk_positif"
                            label="7. Hasil SHK (HK +) *"
                            :options="\App\Enums\CongenitalHypothyroidResultEnum::choices()"
                            select="label:label|value:value"
                            hint="Skrining Hipotiroid Kongenital"
                        />

                        <x-select.styled
                            wire:model="komplikasi_covid19"
                            label="8. Konfirmasi + Covid-19 *"
                            :options="\App\Enums\YesNoEnum::choices()"
                            select="label:label|value:value"
                        />

                        <x-input
                            wire:model="komplikasi_masalah_lainnya"
                            label="9. Masalah Neonatal Lainnya"
                            placeholder="Sebutkan diagnosis atau masalah lain jika ada..."
                        />
                    </div>
                </div>

                <!-- 3. Catatan Penanganan / Terapi / Rujukan -->
                <div class="space-y-3 border-t border-slate-200/80 pt-4">
                    <x-textarea
                        wire:model="komplikasi_catatan"
                        label="Catatan"
                        placeholder="Tuliskan tindakan penanganan yang telah dilakukan, rujukan fasilitas kesehatan, atau instruksi khusus..."
                        rows="2"
                    />
                </div>

                <!-- Footer Buttons -->
                <div class="flex justify-end gap-2 border-t border-slate-200/80 pt-4">
                    <x-button color="gray" flat x-on:click="$wire.showKomplikasiModal = false">Batal</x-button>
                    <x-button submit color="emerald" loading="saveKomplikasi">
                        <x-ti-device-floppy class="mr-1 inline size-4" />
                        {{ $editingKomplikasiId ? 'Simpan Perubahan' : 'Simpan Data Komplikasi' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>