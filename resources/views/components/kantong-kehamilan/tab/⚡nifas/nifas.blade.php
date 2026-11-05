<div>
    @php
        $selectedPregnancy = $this->selectedPregnancy;
    @endphp

    @if ($selectedPregnancy)
        <div class="space-y-4 pt-3.5">
            <x-button color="emerald" sm wire:click="openNifasModal">
                <x-ti-plus class="mr-1 inline size-4" /> Tambah Kunjungan Nifas
            </x-button>

            <div class="space-y-3">
                @forelse ($selectedPregnancy->postnatalEncounters as $nifas)
                    <div
                        class="hover:shadow-xs rounded-xl border border-slate-200/90 bg-slate-50/50 p-4 transition-all hover:border-emerald-300 hover:bg-white">
                        <div
                            class="flex flex-col justify-between gap-2 border-b border-slate-200 pb-3 sm:flex-row sm:items-center">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :text="$nifas->kf_type ?: 'KF'" color="emerald" round />
                                <span class="text-sm font-bold text-slate-900">
                                    Hari ke-{{ $nifas->hari_ke ?? 0 }}
                                    ({{ $nifas->encounter_date ? $nifas->encounter_date->translatedFormat('d F Y') : '-' }})
                                </span>
                                @if ($nifas->masalah_nifas && $nifas->masalah_nifas !== 'Tidak Ada')
                                    <x-badge :text="$nifas->masalah_nifas . ($nifas->masalah_nifas_lainnya ? ': ' . $nifas->masalah_nifas_lainnya : '')" color="rose" light round>
                                        <x-slot:left>
                                            <x-ti-alert-triangle class="mr-1 size-3.5" />
                                        </x-slot:left>
                                    </x-badge>
                                @else
                                    <x-badge text="Normal (Tanpa Komplikasi)" color="emerald" light round />
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-sm text-slate-500">Bidan:
                                    <strong class="text-slate-700">{{ $nifas->creator?->name ?? 'Bidan' }}</strong></span>
                                @if ($nifas->canManage())
                                    <x-button.circle
                                        title="Ubah Kunjungan Nifas"
                                        color="purple"
                                        flat
                                        sm
                                        wire:click="openNifasModal({{ $nifas->id }})"
                                    >
                                        <x-ti-edit class="size-4" />
                                    </x-button.circle>
                                    <x-button.circle
                                        title="Hapus Kunjungan Nifas"
                                        color="red"
                                        flat
                                        sm
                                        wire:confirm="Yakin ingin menghapus data kunjungan nifas ini?"
                                        wire:click="deleteNifas({{ $nifas->id }})"
                                    >
                                        <x-ti-trash class="size-4" />
                                    </x-button.circle>
                                @endif
                            </div>
                        </div>

                        <!-- Grid Tanda Vital & Keadaan Fisik Nifas -->
                        <div class="mt-3 grid grid-cols-2 gap-2.5 text-sm sm:grid-cols-4 lg:grid-cols-6">
                            <div class="shadow-2xs rounded-xl border border-slate-200 bg-white p-2.5">
                                <span class="block text-sm font-medium text-slate-500">Tensi Darah:</span>
                                <strong
                                    class="text-sm text-slate-800">{{ $nifas->td_sistolik ?? '-' }}/{{ $nifas->td_diastolik ?? '-' }}
                                    <span class="text-sm font-normal text-slate-500">mmHg</span></strong>
                            </div>
                            <div class="shadow-2xs rounded-xl border border-slate-200 bg-white p-2.5">
                                <span class="block text-sm font-medium text-slate-500">Suhu Tubuh:</span>
                                <strong class="text-sm text-slate-800">{{ $nifas->suhu ?? '-' }} <span
                                        class="text-xs font-normal text-slate-500"
                                    >°C</span></strong>
                            </div>
                            <div class="shadow-2xs rounded-xl border border-slate-200 bg-white p-2.5">
                                <span class="block text-xs font-medium text-slate-500">Denyut Nadi:</span>
                                <strong class="text-sm text-slate-800">{{ $nifas->nadi ?? '-' }} <span
                                        class="text-xs font-normal text-slate-500"
                                    >x/mnt</span></strong>
                            </div>
                            <div class="shadow-2xs rounded-xl border border-slate-200 bg-white p-2.5">
                                <span class="block text-xs font-medium text-slate-500">TFU:</span>
                                <strong class="block truncate text-sm text-slate-800"
                                    title="{{ \App\Enums\PostnatalTfuEnum::tryFrom($nifas->tfu)?->label() ?? ($nifas->tfu ?: '-') }}">{{ \App\Enums\PostnatalTfuEnum::tryFrom($nifas->tfu)?->label() ?? ($nifas->tfu ?: '-') }}</strong>
                            </div>
                            <div class="shadow-2xs rounded-xl border border-slate-200 bg-white p-2.5">
                                <span class="block text-xs font-medium text-slate-500">Lochea:</span>
                                <strong class="block truncate text-sm text-slate-800"
                                    title="{{ \App\Enums\LocheaTypeEnum::tryFrom($nifas->lochea)?->label() ?? ($nifas->lochea ?: '-') }}">{{ \App\Enums\LocheaTypeEnum::tryFrom($nifas->lochea)?->label() ?? ($nifas->lochea ?: '-') }}</strong>
                            </div>
                            <div class="shadow-2xs rounded-xl border border-slate-200 bg-white p-2.5">
                                <span class="block text-xs font-medium text-slate-500">ASI:</span>
                                <strong class="block truncate text-sm text-slate-800"
                                    title="{{ \App\Enums\LactationConditionEnum::tryFrom($nifas->asi)?->label() ?? ($nifas->asi ?: '-') }}">{{ \App\Enums\LactationConditionEnum::tryFrom($nifas->asi)?->label() ?? ($nifas->asi ?: '-') }}</strong>
                            </div>
                        </div>

                        <div
                            class="mt-2.5 flex flex-wrap items-center gap-3 rounded-xl border border-slate-200/60 bg-white/80 p-2.5 text-sm text-slate-600">
                            <span>Kondisi Jalan Lahir: <strong>{{ \App\Enums\PerineumConditionEnum::tryFrom($nifas->jalan_lahir)?->label() ?? ($nifas->jalan_lahir ?: '-') }}</strong></span>
                            <span>&bull;</span>
                            <span>BAB: <strong
                                     class="{{ $nifas->bab === 'ya' ? 'text-emerald-700 font-bold' : 'text-amber-700 font-bold' }}"
                                >{{ $nifas->bab === 'ya' ? 'Ya (Lancar)' : 'Tidak (Belum / Sulit)' }}</strong></span>
                            <span>&bull;</span>
                            <span>BAK: <strong
                                    class="{{ $nifas->bak === 'ya' ? 'text-emerald-700 font-bold' : 'text-amber-700 font-bold' }}"
                                >{{ $nifas->bak === 'ya' ? 'Ya (Lancar)' : 'Tidak (Sulit / Nyeri)' }}</strong></span>
                            @if ($nifas->catatan)
                                <span class="mt-0.5 w-full italic text-slate-500">Catatan / Tindakan:
                                    {{ $nifas->catatan }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-6 text-center">
                        <x-ti-heartbeat class="mx-auto mb-2 size-8 text-slate-400" />
                        <p class="text-sm font-semibold text-slate-800">Belum Ada Kunjungan Nifas Tercatat</p>
                        <p class="mt-1 text-xs text-slate-500">Klik tombol di bawah untuk mencatat kunjungan KF1 s/d
                            KF4 bagi ibu pasca persalinan ini.</p>
                        <div class="mt-3">
                            <x-button color="emerald" sm wire:click="openNifasModal">
                                <x-ti-plus class="mr-1 inline size-4" /> Catat Kunjungan Nifas
                            </x-button>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- MODAL FORM PENCATATAN KUNJUNGAN NIFAS (PNC / KF1-KF4) -->
        <x-modal
            wire="showNifasModal"
            :title="$editingNifasId ? 'Ubah Kunjungan Nifas (' . $nifas_kf_type . ')' : 'Catat Kunjungan Nifas (' . $nifas_kf_type . ')'"
            z-index="z-50"
            size="5xl"
            persistent
        >
            <form class="space-y-5" wire:submit="saveNifas">
                <!-- 1. Tanggal Kunjungan & Klasifikasi Nifas Otomatis -->
                <div class="grid grid-cols-1 items-center gap-4 sm:grid-cols-2">
                    <x-date
                        format="YYYY-MM-DD"
                        wire:model.live="nifas_encounter_date"
                        label="Tanggal Kunjungan Nifas *"
                    />

                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-3">
                        <span class="block text-xs font-medium text-slate-500">Klasifikasi Masa Nifas Otomatis:</span>
                        <div class="mt-1.5 flex items-center gap-2">
                            <x-badge :text="$nifas_kf_type" color="emerald" round />
                            <x-badge :text="'Hari ke-' . ($nifas_hari_ke ?? 0)" color="slate" light round />
                        </div>
                    </div>
                </div>

                <!-- 2. Tanda-Tanda Vital -->
                <div class="space-y-3 border-t border-slate-200/80 pt-4">
                    <h5 class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-600">
                        <x-ti-heart-rate-monitor class="size-4 text-emerald-600" />
                        Tanda-Tanda Vital
                    </h5>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <x-input
                            type="number"
                            wire:model="nifas_td_sistolik"
                            label="TD Sistolik"
                            placeholder="120"
                            suffix="mmHg"
                        />
                        <x-input
                            type="number"
                            wire:model="nifas_td_diastolik"
                            label="TD Diastolik"
                            placeholder="80"
                            suffix="mmHg"
                        />
                        <x-input
                            type="number"
                            wire:model="nifas_suhu"
                            label="Suhu Tubuh"
                            placeholder="36.5"
                            suffix="°C"
                            step="0.1"
                        />
                        <x-input
                            type="number"
                            wire:model="nifas_nadi"
                            label="Denyut Nadi"
                            placeholder="80"
                            suffix="x/mnt"
                        />
                    </div>
                </div>

                <!-- 3. Pemeriksaan Fisik Nifas -->
                <div class="space-y-3 border-t border-slate-200/80 pt-4">
                    <h5 class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-600">
                        <x-ti-stethoscope class="size-4 text-emerald-600" />
                        Pemeriksaan Fisik Nifas
                    </h5>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <x-select.styled
                            wire:model="nifas_tfu"
                            label="Tinggi Fundus Uteri (TFU)"
                            placeholder="Pilih TFU..."
                            :options="\App\Enums\PostnatalTfuEnum::choices()"
                            select="label:label|value:value"
                        />

                        <x-select.styled
                            wire:model="nifas_lochea"
                            label="Pengeluaran Lochea"
                            placeholder="Pilih jenis lochea..."
                            :options="\App\Enums\LocheaTypeEnum::choices()"
                            select="label:label|value:value"
                        />

                        <x-select.styled
                            wire:model="nifas_jalan_lahir"
                            label="Jalan Lahir / Perineum"
                            placeholder="Pilih kondisi jalan lahir..."
                            :options="\App\Enums\PerineumConditionEnum::choices()"
                            select="label:label|value:value"
                        />

                        <x-select.styled
                            wire:model="nifas_asi"
                            label="Produksi & Pengeluaran ASI"
                            placeholder="Pilih kondisi ASI..."
                            :options="\App\Enums\LactationConditionEnum::choices()"
                            select="label:label|value:value"
                        />

                        <x-select.styled
                            wire:model="nifas_bab"
                            label="Buang Air Besar (BAB)"
                            placeholder="Pilih status BAB..."
                            :options="\App\Enums\EliminationStatusEnum::choices()"
                            select="label:label|value:value"
                        />

                        <x-select.styled
                            wire:model="nifas_bak"
                            label="Buang Air Kecil (BAK)"
                            placeholder="Pilih status BAK..."
                            :options="\App\Enums\EliminationStatusEnum::choices()"
                            select="label:label|value:value"
                        />
                    </div>
                </div>

                <!-- 4. Komplikasi & Catatan Tindakan -->
                <div class="space-y-3 border-t border-slate-200/80 pt-4">
                    <h5 class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-600">
                        <x-ti-alert-triangle class="size-4 text-amber-600" />
                        Komplikasi & Catatan Tindakan
                    </h5>
                    <div class="grid grid-cols-1 gap-3 {{ $nifas_masalah === 'Lainnya' ? 'sm:grid-cols-2' : '' }}">
                        <x-select.styled
                            wire:model.live="nifas_masalah"
                            label="Komplikasi / Masalah Nifas"
                            placeholder="Pilih komplikasi (jika ada)..."
                            :options="\App\Enums\MaternalComplicationEnum::choicesForPostnatal()"
                            select="label:label|value:value"
                        />

                        @if ($nifas_masalah === 'Lainnya')
                            <x-input
                                wire:model="nifas_masalah_lainnya"
                                label="Keterangan Masalah Lainnya *"
                                placeholder="Contoh: Mastitis, Baby blues syndrome, dll..."
                            />
                        @endif
                    </div>

                    <x-textarea
                        wire:model="nifas_catatan"
                        label="Catatan / Tindakan / KIE"
                        placeholder="Anjuran gizi, konsumsi vitamin A / Fe, perawatan payudara, rencana KB, atau tindakan medis..."
                        rows="2"
                    />
                </div>

                <!-- Footer Buttons -->
                <div class="flex justify-end gap-2 border-t border-slate-200/80 pt-4">
                    <x-button color="gray" flat x-on:click="$wire.showNifasModal = false">Batal</x-button>
                    <x-button submit color="emerald" loading="saveNifas">
                        <x-ti-device-floppy class="mr-1 inline size-4" />
                        {{ $editingNifasId ? 'Simpan Perubahan' : 'Simpan Kunjungan' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>