<?php

use App\Models\Patient;
use App\Models\PregnancyHistory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use Interactions;

    public bool $showModal = false;
    public ?int $patientId = null;
    public ?Patient $patient = null;

    // Form State (Lengkap sesuai form Selesai Persalinan)
    public bool $isEditing = false;
    public ?int $editing_id = null;
    public string $status = 'Lahir';
    public ?string $tanggal_persalinan = null;
    public string $cara_melahirkan = 'Normal';
    public string $tempat_persalinan = 'Klinik';
    public string $kondisi_bayi = 'Lahir Hidup';
    public string $jenis_kelamin = 'Laki-laki';
    public ?string $bb_bayi = null;
    public string $penolong = 'Bidan';
    public string $masalah = 'Tidak Ada';
    public string $masalah_lainnya = '';

    #[On('open-riwayat-kehamilan')]
    public function openModal(int $patientId): void
    {
        $this->patientId = $patientId;
        $this->patient = Patient::with('pregnancyHistories')->find($patientId);
        $this->cancelEdit();
        $this->showModal = true;
    }

    public function loadHistories(): void
    {
        if ($this->patientId) {
            $this->patient = Patient::with('pregnancyHistories')->find($this->patientId);
        }
    }

    public function resetForm(): void
    {
        $this->isEditing = false;
        $this->editing_id = null;
        $this->status = 'Lahir';
        $this->tanggal_persalinan = null;
        $this->cara_melahirkan = 'Normal';
        $this->tempat_persalinan = 'Klinik';
        $this->kondisi_bayi = 'Lahir Hidup';
        $this->jenis_kelamin = 'Laki-laki';
        $this->bb_bayi = null;
        $this->penolong = 'Bidan';
        $this->masalah = 'Tidak Ada';
        $this->masalah_lainnya = '';
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function editHistory(int $id): void
    {
        $history = PregnancyHistory::findOrFail($id);
        $this->isEditing = true;
        $this->editing_id = $history->id;
        $this->status = $history->status ?? ($history->cara_melahirkan === 'Abortus' ? 'Abortus' : ($history->cara_melahirkan === 'Meninggal' ? 'Meninggal' : 'Lahir'));
        $this->tanggal_persalinan = $history->tanggal_persalinan?->format('Y-m-d');
        $this->cara_melahirkan = $history->cara_melahirkan ?? 'Normal';
        $this->tempat_persalinan = $history->tempat_persalinan ?? 'Klinik';
        $this->kondisi_bayi = $history->kondisi_bayi ?? 'Lahir Hidup';
        $this->jenis_kelamin = $history->jenis_kelamin ?? 'Laki-laki';
        $this->bb_bayi = $history->bb_bayi ? (string) $history->bb_bayi : null;
        $this->penolong = $history->penolong ?? 'Bidan';

        // Deteksi masalah persalinan & fallback dari teks legacy
        $rawMasalah = (string) ($history->masalah_persalinan ?? '');
        if (empty($history->kondisi_bayi) && preg_match('/Kondisi:\s*(Lahir Hidup|Lahir Mati)/i', $rawMasalah, $mKondisi)) {
            $this->kondisi_bayi = $mKondisi[1];
        }
        if (empty($history->jenis_kelamin) && preg_match('/JK:\s*(Laki-laki|Perempuan)/i', $rawMasalah, $mJk)) {
            $this->jenis_kelamin = $mJk[1];
        }

        if (str_contains($rawMasalah, '|')) {
            $parts = explode('|', $rawMasalah);
            $rawMasalah = trim(end($parts));
        } elseif (str_starts_with($rawMasalah, 'Kondisi:')) {
            $rawMasalah = 'Tidak Ada';
        }

        $masalahOptions = array_diff(
            \App\Enums\MaternalComplicationEnum::values('intranatal'),
            ['Tidak Ada', 'Lainnya']
        );

        if (!empty($history->masalah_persalinan_lainnya)) {
            $this->masalah = 'Lainnya';
            $this->masalah_lainnya = $history->masalah_persalinan_lainnya;
        } elseif (empty($rawMasalah) || strcasecmp($rawMasalah, 'tidak ada') === 0 || strcasecmp($rawMasalah, 'normal') === 0) {
            $this->masalah = 'Tidak Ada';
            $this->masalah_lainnya = '';
        } elseif (in_array($rawMasalah, $masalahOptions)) {
            $this->masalah = $rawMasalah;
            $this->masalah_lainnya = '';
        } elseif (str_starts_with($rawMasalah, 'Lainnya:')) {
            $this->masalah = 'Lainnya';
            $this->masalah_lainnya = trim(substr($rawMasalah, 8));
        } else {
            $this->masalah = 'Lainnya';
            $this->masalah_lainnya = $rawMasalah;
        }
    }

    public function save(): void
    {
        $rules = [
            'status'             => 'required|in:Lahir,Abortus,Meninggal',
            'tanggal_persalinan' => 'nullable|date',
            'tempat_persalinan'  => 'required|string|max:100',
            'penolong'           => 'required|string|max:100',
        ];

        $messages = [
            'status.required'            => 'Status kehamilan/persalinan wajib dipilih.',
            'status.in'                  => 'Pilihan status kehamilan tidak valid.',
            'tempat_persalinan.required' => 'Tempat bersalin/kejadian wajib dipilih.',
            'penolong.required'          => 'Penolong persalinan/medis wajib dipilih.',
        ];

        if ($this->status === 'Lahir') {
            $rules['cara_melahirkan'] = 'required|string|max:50';
            $rules['kondisi_bayi']    = 'required|string|max:50';
            $rules['jenis_kelamin']   = 'required|string|max:20';
            $rules['bb_bayi']         = 'nullable|numeric|min:0.5|max:10.0';
            $rules['masalah']         = 'required|string|max:100';
            $rules['masalah_lainnya'] = 'required_if:masalah,Lainnya|nullable|string|max:255';

            $messages['cara_melahirkan.required'] = 'Jenis persalinan wajib dipilih.';
            $messages['kondisi_bayi.required']    = 'Kondisi bayi wajib dipilih.';
            $messages['jenis_kelamin.required']   = 'Jenis kelamin bayi wajib dipilih.';
            $messages['bb_bayi.numeric']          = 'Berat badan bayi harus berupa angka numerik.';
            $messages['bb_bayi.min']              = 'Berat badan bayi minimal 0.5 kg.';
            $messages['bb_bayi.max']              = 'Berat badan bayi maksimal 10.0 kg.';
            $messages['masalah_lainnya.required_if'] = 'Sebutkan masalah persalinan lainnya jika memilih opsi Lainnya.';
        } elseif ($this->status === 'Abortus') {
            $rules['masalah'] = 'nullable|string|max:255';
        } else {
            $rules['masalah'] = 'required|string|max:1000';
            $messages['masalah.required'] = 'Keterangan penyebab kematian pasien wajib diisi.';
        }

        $this->validate($rules, $messages);

        $masalahTersimpan = $this->masalah;
        $masalahLainnyaTersimpan = null;
        if ($this->status === 'Lahir') {
            if ($masalahTersimpan === 'Lainnya') {
                $masalahLainnyaTersimpan = !empty($this->masalah_lainnya) ? trim($this->masalah_lainnya) : null;
            } elseif ($masalahTersimpan === 'Tidak Ada') {
                $masalahTersimpan = null;
            }
        }

        $caraMelahirkan = match ($this->status) {
            'Abortus' => 'Abortus',
            'Meninggal' => 'Meninggal',
            default => $this->cara_melahirkan,
        };

        $data = [
            'status'                     => $this->status,
            'tanggal_persalinan'         => $this->tanggal_persalinan,
            'cara_melahirkan'            => $caraMelahirkan,
            'tempat_persalinan'          => $this->tempat_persalinan,
            'kondisi_bayi'               => $this->status === 'Lahir' ? $this->kondisi_bayi : null,
            'jenis_kelamin'              => $this->status === 'Lahir' ? $this->jenis_kelamin : null,
            'bb_bayi'                    => $this->status === 'Lahir' && $this->bb_bayi ? (float) $this->bb_bayi : null,
            'penolong'                   => $this->penolong,
            'masalah_persalinan'         => $this->status === 'Lahir' ? $masalahTersimpan : ($this->status === 'Abortus' ? ($masalahTersimpan ?: 'Abortus (Keguguran)') : 'Pasien Meninggal: ' . $masalahTersimpan),
            'masalah_persalinan_lainnya' => $masalahLainnyaTersimpan,
        ];

        if ($this->isEditing && $this->editing_id) {
            $history = PregnancyHistory::findOrFail($this->editing_id);
            $history->update($data);

            $this->toast()->success('Riwayat persalinan berhasil diperbarui')->send();
        } else {
            $data['patient_id'] = $this->patientId;
            $data['created_by'] = Auth::id();
            PregnancyHistory::create($data);

            $this->toast()->success('Riwayat persalinan berhasil ditambahkan')->send();
        }

        $this->resetForm();
        $this->loadHistories();
    }

    /**
     * Memeriksa apakah user saat ini berhak menghapus riwayat persalinan.
     * Hanya penginput atau koordinator yang berhak.
     */
    public function canDeleteHistory(PregnancyHistory $history): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if ($user->hasRole('koordinator-bidan')) {
            return true;
        }

        return $history->created_by !== null && (int) $history->created_by === (int) $user->id;
    }

    /**
     * Menghapus riwayat persalinan dengan otorisasi.
     */
    public function deleteHistory(int $id): void
    {
        $history = PregnancyHistory::where('id', $id)->where('patient_id', $this->patientId)->first();

        if (!$history) {
            $this->toast()->error('Data riwayat persalinan tidak ditemukan.')->send();
            return;
        }

        if (!$this->canDeleteHistory($history)) {
            $this->toast()->error('Anda tidak memiliki hak akses untuk menghapus riwayat ini. Hanya penginput atau koordinator yang dapat menghapusnya.')->send();
            return;
        }

        $history->delete();
        $this->toast()->success('Riwayat persalinan berhasil dihapus')->send();
        $this->loadHistories();
    }
};
?>

<div>
    <x-modal wire="showModal" title="Riwayat Kehamilan & Persalinan Lalu" size="6xl" z-index="z-50">
        @if ($patient)
            <div class="space-y-5">
                <!-- Info Pasien -->
                <div class="flex items-center justify-between rounded-2xl bg-emerald-50/70 p-4 border border-emerald-200">
                    <div>
                        <h4 class="text-base font-bold text-slate-900">{{ $patient->full_name }}</h4>
                        <div class="text-xs text-slate-600 mt-0.5 flex flex-wrap gap-x-2">
                            <span>No. RM: <strong class="text-emerald-800">{{ $patient->medical_record_number_label }}</strong></span>
                            <span>&bull;</span>
                            <span>NIK: {{ $patient->nik ?? '-' }}</span>
                            <span>&bull;</span>
                            <span>Suami: {{ $patient->family_name ?? '-' }}</span>
                        </div>
                    </div>
                    <span class="rounded-xl bg-white px-3 py-1.5 text-xs font-bold text-emerald-800 border border-emerald-200 shadow-xs">
                        {{ $patient->pregnancyHistories->count() }} Riwayat Tercatat
                    </span>
                </div>

                <!-- Form Input / Edit Riwayat Persalinan -->
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs">
                    <div class="flex items-center justify-between mb-3 border-b border-slate-100 pb-2">
                        <h5 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                            @if ($isEditing)
                                <x-ti-edit class="size-4 text-emerald-600" /> Ubah Data Riwayat Persalinan
                            @else
                                <x-ti-circle-plus class="size-4 text-emerald-600" /> Tambah Riwayat Persalinan Lalu
                            @endif
                        </h5>
                        @if ($isEditing)
                            <x-button color="gray" flat xs wire:click="cancelEdit">
                                Batal Ubah
                            </x-button>
                        @endif
                    </div>

                    <form wire:submit="save" class="space-y-3.5">
                        <!-- Baris 1: Status, Tanggal, Cara Bersalin, Tempat -->
                        <div class="grid grid-cols-1 {{ $status === 'Lahir' ? 'sm:grid-cols-4' : 'sm:grid-cols-3' }} gap-3">
                            <x-select.styled
                                wire:model.live="status"
                                label="Status Riwayat *"
                                :options="[
                                    ['label' => 'Lahir (Persalinan)', 'value' => 'Lahir'],
                                    ['label' => 'Abortus (Keguguran)', 'value' => 'Abortus'],
                                    ['label' => 'Pasien Meninggal', 'value' => 'Meninggal'],
                                ]"
                                select="label:label|value:value"
                            />

                            <x-date
                                wire:model="tanggal_persalinan"
                                :label="$status === 'Lahir' ? 'Tanggal Persalinan' : ($status === 'Abortus' ? 'Tanggal Abortus' : 'Tanggal Pasien Meninggal')"
                                format="YYYY-MM-DD"
                                placeholder="Pilih tanggal"
                            />

                            @if ($status === 'Lahir')
                                <x-select.styled
                                    wire:model="cara_melahirkan"
                                    label="Jenis Persalinan *"
                                    :options="\App\Enums\PartusTypeEnum::choices()"
                                    select="label:label|value:value"
                                />
                            @endif

                            <x-select.styled
                                wire:model="tempat_persalinan"
                                :label="$status === 'Lahir' ? 'Tempat Bersalin *' : 'Tempat Penanganan / Kejadian *'"
                                :options="\App\Enums\DeliveryFacilityEnum::choices()"
                                select="label:label|value:value"
                            />
                        </div>

                        <!-- Baris 2: Kondisi Lahir, Jenis Kelamin, BB Bayi (Hanya jika Lahir) -->
                        @if ($status === 'Lahir')
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <x-select.styled
                                    wire:model="kondisi_bayi"
                                    label="Kondisi Lahir Bayi *"
                                    :options="\App\Enums\InfantConditionEnum::choices()"
                                    select="label:label|value:value"
                                />

                                <x-select.styled
                                    wire:model="jenis_kelamin"
                                    label="Jenis Kelamin Bayi *"
                                    :options="\App\Enums\GenderEnum::choices()"
                                    select="label:label|value:value"
                                />

                                <x-input
                                    type="number"
                                    step="0.01"
                                    wire:model="bb_bayi"
                                    label="Berat Badan Bayi (kg)"
                                    placeholder="Contoh: 3.20"
                                    suffix="kg"
                                />
                            </div>
                        @endif

                        <!-- Baris 3: Penolong & Masalah / Keterangan -->
                        @if ($status === 'Lahir')
                            <div class="grid grid-cols-1 {{ $masalah === 'Lainnya' ? 'sm:grid-cols-3' : 'sm:grid-cols-2' }} gap-3">
                                <x-select.styled
                                    wire:model="penolong"
                                    label="Penolong Bersalin *"
                                    :options="\App\Enums\BirthAttendantEnum::choices()"
                                    select="label:label|value:value"
                                />

                                <x-select.styled
                                    wire:model.live="masalah"
                                    label="Masalah / Komplikasi Persalinan"
                                    :options="\App\Enums\MaternalComplicationEnum::choices('intranatal')"
                                    select="label:label|value:value"
                                />

                                @if ($masalah === 'Lainnya')
                                    <x-input
                                        wire:model="masalah_lainnya"
                                        label="Sebutkan Masalah Lainnya *"
                                        placeholder="Contoh: KPD > 12 jam, Atonia uteri, dll..."
                                    />
                                @endif
                            </div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <x-select.styled
                                    wire:model="penolong"
                                    :label="$status === 'Abortus' ? 'Penolong / Tenaga Medis *' : 'Tenaga Medis yang Menangani *'"
                                    :options="\App\Enums\BirthAttendantEnum::choices()"
                                    select="label:label|value:value"
                                />

                                <x-textarea
                                    wire:model="masalah"
                                    :label="$status === 'Abortus' ? 'Keterangan Abortus' : 'Keterangan Penyebab Kematian *'"
                                    rows="2"
                                    :placeholder="$status === 'Abortus' ? 'Catatan abortus...' : 'Penyebab kematian...'"
                                />
                            </div>
                        @endif

                        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                            @if ($isEditing)
                                <x-button color="gray" flat sm wire:click="cancelEdit">Batal</x-button>
                                <x-button submit color="emerald" sm loading="save">
                                    <x-ti-check class="size-4 mr-1 inline" /> Update Riwayat
                                </x-button>
                            @else
                                <x-button submit color="emerald" sm loading="save">
                                    <x-ti-plus class="size-4 mr-1 inline" /> Simpan Riwayat
                                </x-button>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Tabel Riwayat Persalinan yang Sudah Ada -->
                <div>
                    <h5 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Daftar Riwayat Persalinan Sebelumnya:
                    </h5>

                    <div class="overflow-x-auto rounded-2xl border border-slate-200">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                    <th class="p-3">No</th>
                                    <th class="p-3">Tgl Kejadian</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Cara Bersalin</th>
                                    <th class="p-3">Tempat & Penolong</th>
                                    <th class="p-3">Bayi (Kondisi & JK)</th>
                                    <th class="p-3">BB Bayi</th>
                                    <th class="p-3">Masalah / Komplikasi</th>
                                    <th class="p-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($patient->pregnancyHistories as $idx => $hist)
                                    @php
                                        $histStatus = $hist->status ?? ($hist->cara_melahirkan === 'Abortus' ? 'Abortus' : ($hist->cara_melahirkan === 'Meninggal' ? 'Meninggal' : 'Lahir'));
                                    @endphp
                                    <tr class="hover:bg-slate-50/60 {{ $editing_id === $hist->id ? 'bg-emerald-50/40' : '' }}">
                                        <td class="p-3 font-bold text-slate-800">{{ $idx + 1 }}</td>
                                        <td class="p-3">{{ $hist->tanggal_persalinan ? $hist->tanggal_persalinan->format('d/m/Y') : '-' }}</td>
                                        <td class="p-3">
                                            @if ($histStatus === 'Abortus')
                                                <x-badge text="Abortus" color="amber" light round />
                                            @elseif ($histStatus === 'Meninggal')
                                                <x-badge text="Meninggal" color="rose" light round />
                                            @else
                                                <x-badge text="Lahir" color="emerald" light round />
                                            @endif
                                        </td>
                                        <td class="p-3 font-semibold text-slate-800">
                                            @if ($histStatus === 'Lahir')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold border {{ \App\Enums\PartusTypeEnum::badgeClasses($hist->cara_melahirkan) }}">
                                                    {{ $hist->cara_melahirkan }}
                                                </span>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            <div class="font-medium text-slate-800">{{ $hist->tempat_persalinan }}</div>
                                            <div class="text-xs text-slate-500">{{ $hist->penolong }}</div>
                                        </td>
                                        <td class="p-3">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                @php
                                                    $isHidup = strtolower($hist->kondisi_bayi ?? '') !== 'lahir mati' && !str_contains(strtolower($hist->masalah_persalinan ?? ''), 'mati');
                                                    $isFemale = str_contains(strtolower($hist->jenis_kelamin ?? ''), 'perempuan') || str_contains(strtolower($hist->masalah_persalinan ?? ''), 'jk: perempuan');
                                                @endphp
                                                @if ($histStatus === 'Lahir')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $isHidup ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                                        {{ $hist->kondisi_bayi ?: ($isHidup ? 'Lahir Hidup' : 'Lahir Mati') }}
                                                    </span>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $isFemale ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-sky-50 text-sky-700 border border-sky-200' }}">
                                                        {{ $hist->jenis_kelamin ?: ($isFemale ? 'Perempuan' : 'Laki-laki') }}
                                                    </span>
                                                @else
                                                    <span class="text-slate-400">-</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="p-3 font-bold text-emerald-700">{{ $hist->bb_bayi ? $hist->bb_bayi . ' kg' : '-' }}</td>
                                        <td class="p-3">
                                            @php
                                                $cleanMasalah = $hist->masalah_persalinan;
                                                if (str_contains($cleanMasalah, '|')) {
                                                    $parts = explode('|', $cleanMasalah);
                                                    $cleanMasalah = trim(end($parts));
                                                } elseif (str_starts_with($cleanMasalah, 'Kondisi:')) {
                                                    $cleanMasalah = null;
                                                }
                                            @endphp
                                            @php
                                                $displayMasalah = $cleanMasalah;
                                                if ($displayMasalah === 'Lainnya' && $hist->masalah_persalinan_lainnya) {
                                                    $displayMasalah = 'Lainnya: ' . $hist->masalah_persalinan_lainnya;
                                                }
                                            @endphp
                                            @if ($displayMasalah && strcasecmp($displayMasalah, 'tidak ada') !== 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                    {{ $displayMasalah }}
                                                </span>
                                            @else
                                                <span class="text-slate-400">Normal (Tanpa Komplikasi)</span>
                                            @endif
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                @if ($this->canDeleteHistory($hist))
                                                    <x-button.circle color="emerald" sm flat wire:click="editHistory({{ $hist->id }})" title="Ubah Riwayat">
                                                        <x-ti-edit class="size-4" />
                                                    </x-button.circle>
                                                    @if (!$hist->pregnancy_id)
                                                        <x-button.circle color="red" sm flat wire:click="deleteHistory({{ $hist->id }})" wire:confirm="Hapus data riwayat persalinan ini?" title="Hapus Riwayat">
                                                            <x-ti-trash class="size-4" />
                                                        </x-button.circle>
                                                    @endif
                                                @else
                                                    <span class="text-xs text-slate-400">{{ $hist->creator?->name }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="p-6 text-center text-slate-400">
                                            Belum ada catatan riwayat persalinan sebelumnya (Kehamilan Pertama / Primigravida).
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-slate-100">
                    <x-button color="gray" flat wire:click="$set('showModal', false)">Tutup</x-button>
                </div>
            </div>
        @endif
    </x-modal>
</div>