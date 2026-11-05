<div>
    <!-- MODAL 4: SELESAIKAN KEHAMILAN (MELAHIRKAN / ABORTUS / MENINGGAL) ATAU EDIT PERSALINAN -->
    <x-modal :title="$is_edit ? 'Ubah Data Persalinan / Resolusi' : 'Selesaikan Kehamilan Pasien'" wire="showSelesaiModal" z-index="z-50" size="lg">
        <form class="space-y-4" wire:submit="simpanSelesaiKehamilan">

            <!-- Pilihan Status Akhir Kehamilan -->
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Hasil Akhir Kehamilan *
                    </label>
                    @if ($is_edit)
                        <x-badge text=" Edit Hasil" color="emerald" light round />
                    @endif
                </div>
                

                @if (!$is_edit)
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                        <!-- Opsi 1: Selesai Melahirkan -->
                        <label class="{{ $selesai_status_akhir === 'selesai' ? 'border-emerald-500 bg-emerald-50/60 ring-1 ring-emerald-500/20' : 'border-slate-200 bg-white hover:bg-slate-50' }} flex cursor-pointer items-center rounded-xl border p-3.5 transition-all">
                            <x-radio value="selesai" wire:model.live="selesai_status_akhir" color="emerald">
                                <x-slot:label>
                                    <span class="block text-sm font-bold text-slate-800">Persalinan</span>
                                </x-slot:label>
                            </x-radio>
                        </label>

                        <!-- Opsi 2: Abortus -->
                        <label class="{{ $selesai_status_akhir === 'abortus' ? 'border-amber-500 bg-amber-50/60 ring-1 ring-amber-500/20' : 'border-slate-200 bg-white hover:bg-slate-50' }} flex cursor-pointer items-center rounded-xl border p-3.5 transition-all">
                            <x-radio value="abortus" wire:model.live="selesai_status_akhir" color="amber">
                                <x-slot:label>
                                    <span class="block text-sm font-bold text-slate-800">Abortus</span>
                                </x-slot:label>
                            </x-radio>
                        </label>

                        <!-- Opsi 3: Pasien Meninggal -->
                        <label class="{{ $selesai_status_akhir === 'meninggal' ? 'border-rose-500 bg-rose-50/60 ring-1 ring-rose-500/20' : 'border-slate-200 bg-white hover:bg-slate-50' }} flex cursor-pointer items-center rounded-xl border p-3.5 transition-all">
                            <x-radio value="meninggal" wire:model.live="selesai_status_akhir" color="red">
                                <x-slot:label>
                                    <span class="block text-sm font-bold text-slate-800">Meninggal</span>
                                </x-slot:label>
                            </x-radio>
                        </label>
                    </div>
                @else
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm font-bold capitalize text-slate-700">
                        Status Saat Ini:
                        <span class="{{ $selesai_status_akhir === 'selesai' ? 'text-emerald-700' : ($selesai_status_akhir === 'abortus' ? 'text-amber-700' : 'text-rose-700') }}">
                            {{ $selesai_status_akhir === 'selesai' ? 'Persalinan (Melahirkan)' : ucfirst($selesai_status_akhir) }}
                        </span>
                    </div>
                @endif
            </div>

            <!-- Banner Keterangan Sesuai Opsi -->
            @if (!$is_edit)
                @if ($selesai_status_akhir === 'selesai')
                    <div wire:key="banner-selesai" class="flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 shadow-2xs">
                        <x-ti-info-circle class="mt-0.5 size-4 shrink-0 text-emerald-600" />
                        <span>Status kehamilan diubah menjadi <strong>Selesai</strong> dan kelahiran bayi otomatis dicatat ke <strong>Riwayat Persalinan</strong> pasien.</span>
                    </div>
                @elseif ($selesai_status_akhir === 'abortus')
                    <div wire:key="banner-abortus" class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 shadow-2xs">
                        <x-ti-alert-triangle class="mt-0.5 size-4 shrink-0 text-amber-600" />
                        <span>Status kehamilan diubah menjadi <strong>Abortus</strong>. Catatan dan riwayat abortus pasien otomatis diperbarui.</span>
                    </div>
                @elseif ($selesai_status_akhir === 'meninggal')
                    <div wire:key="banner-meninggal" class="flex items-start gap-2 rounded-xl border border-rose-300 bg-rose-50 p-3 text-sm text-rose-900 shadow-2xs">
                        <x-ti-alert-octagon class="mt-0.5 size-4 shrink-0 text-rose-600" />
                        <div>
                            <strong class="block font-bold">Peringatan Kematian Ibu (Maternal Death):</strong>
                            <span>Status kehamilan diubah menjadi <strong>Meninggal</strong> dan data pasien akan <strong>dinonaktifkan</strong> dari sistem.</span>
                        </div>
                    </div>
                @endif
            @endif

            <!-- Form Input Dinamis Sesuai Status Akhir -->
            <div class="space-y-3.5">
                @if ($selesai_status_akhir === 'selesai')
                    <div wire:key="form-section-selesai" class="space-y-3.5">
                        <x-date wire:key="lahir-tanggal-date" format="YYYY-MM-DD" wire:model="lahir_tanggal" label="Tanggal Persalinan *" />

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <x-select.styled wire:key="lahir-cara-melahirkan-select" wire:model="lahir_cara_melahirkan" label="Jenis Persalinan *" :options="\App\Enums\PartusTypeEnum::choices()"
                                select="label:label|value:value"
                            />

                            <x-select.styled wire:key="lahir-tempat-select" wire:model="lahir_tempat" label="Tempat Persalinan *" :options="\App\Enums\DeliveryFacilityEnum::choices()"
                                select="label:label|value:value"
                            />
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <x-select.styled wire:key="lahir-kondisi-bayi-select" wire:model="lahir_kondisi_bayi" label="Kondisi Lahir Bayi *" :options="\App\Enums\InfantConditionEnum::choices()"
                                select="label:label|value:value"
                            />

                            <x-select.styled wire:key="lahir-jenis-kelamin-select" wire:model="lahir_jenis_kelamin" label="Jenis Kelamin Bayi *" :options="\App\Enums\GenderEnum::choices()"
                                select="label:label|value:value"
                            />
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <x-input wire:key="lahir-bb-bayi-input" wire:model="lahir_bb_bayi" label="Berat Badan Bayi (kg) *" placeholder="Contoh: 3.20" suffix="kg" />

                            <x-select.styled wire:key="lahir-penolong-select" wire:model="lahir_penolong" label="Penolong Persalinan *" :options="\App\Enums\BirthAttendantEnum::choices()"
                                select="label:label|value:value"
                            />
                        </div>

                        <div class="space-y-2.5">
                            <x-select.styled wire:key="lahir-masalah-select" wire:model.live="lahir_masalah" label="Komplikasi Persalinan"
                                :options="\App\Enums\MaternalComplicationEnum::choices('intranatal')"
                                select="label:label|value:value"
                                placeholder="Tidak Ada / Pilih Komplikasi"
                            />

                            @if ($lahir_masalah === 'Lainnya')
                                <x-input wire:key="lahir-masalah-lainnya-input" wire:model="lahir_masalah_lainnya" label="Sebutkan Masalah Persalinan Lainnya *"
                                    placeholder="Contoh: KPD > 12 jam, Atonia uteri, dll..."
                                />
                            @endif
                        </div>
                    </div>
                @elseif ($selesai_status_akhir === 'abortus')
                    <div wire:key="form-section-abortus" class="space-y-3.5">
                        <x-date wire:key="abortus-tanggal-date" format="YYYY-MM-DD" wire:model="abortus_tanggal" label="Tanggal Kejadian Abortus *" />

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <x-select.styled wire:key="abortus-tempat-select" wire:model="abortus_tempat" label="Tempat Penanganan *" :options="\App\Enums\DeliveryFacilityEnum::choices()"
                                select="label:label|value:value"
                            />

                            <x-select.styled wire:key="abortus-penolong-select" wire:model="abortus_penolong" label="Penolong / Tenaga Medis *" :options="\App\Enums\BirthAttendantEnum::choices()"
                                select="label:label|value:value"
                            />
                        </div>

                        <x-textarea wire:key="abortus-keterangan-textarea" wire:model="abortus_keterangan" label="Keterangan" rows="2" />
                    </div>
                @elseif ($selesai_status_akhir === 'meninggal')
                    <div wire:key="form-section-meninggal" class="space-y-3.5">
                        <x-date wire:key="meninggal-tanggal-date" format="YYYY-MM-DD" wire:model="meninggal_tanggal" label="Tanggal Pasien Meninggal *" />

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <x-select.styled wire:key="meninggal-tempat-select" wire:model="meninggal_tempat" label="Tempat Kejadian *" :options="\App\Enums\DeliveryFacilityEnum::choices()"
                                select="label:label|value:value"
                            />

                            <x-select.styled wire:key="meninggal-penolong-select" wire:model="meninggal_penolong" label="Tenaga Medis yang Menangani" :options="\App\Enums\BirthAttendantEnum::choices()"
                                select="label:label|value:value"
                            />
                        </div>

                        <x-textarea wire:key="meninggal-keterangan-textarea" wire:model="meninggal_keterangan" label="Keterangan" rows="2" />
                    </div>
                @endif
            </div>

            <!-- Tombol Aksi -->
            <div class="flex items-center justify-between border-t-2 border-slate-100 pt-3">
                <div>
                    @if ($is_edit && $can_batal)
                        <x-button type="button" color="rose" flat wire:click="promptBatalkanPersalinan">
                            <x-ti-arrow-back-up class="mr-1 inline size-4" />
                            {{ $selesai_status_akhir === 'abortus' ? 'Batalkan Status Abortus' : ($selesai_status_akhir === 'meninggal' ? 'Batalkan Status Meninggal' : 'Batalkan Persalinan') }}
                        </x-button>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <x-button type="button" color="gray" flat x-on:click="$wire.showSelesaiModal = false">Batal</x-button>
                    @if ($selesai_status_akhir === 'selesai')
                        <x-button submit color="emerald" loading="simpanSelesaiKehamilan">
                            {{ $is_edit ? 'Simpan Perubahan Persalinan' : 'Simpan Persalinan & Selesaikan' }}
                        </x-button>
                    @elseif ($selesai_status_akhir === 'abortus')
                        <x-button submit color="amber" loading="simpanSelesaiKehamilan">
                            {{ $is_edit ? 'Simpan Perubahan Abortus' : 'Konfirmasi Abortus' }}
                        </x-button>
                    @else
                        <x-button submit color="red" loading="simpanSelesaiKehamilan">
                            {{ $is_edit ? 'Simpan Perubahan Data' : 'Konfirmasi Pasien Meninggal' }}
                        </x-button>
                    @endif
                </div>
            </div>
        </form>
    </x-modal>
</div>