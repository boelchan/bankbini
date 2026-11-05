<?php

use App\Enums\BirthAttendantEnum;
use App\Enums\DeliveryFacilityEnum;
use App\Enums\InfantConditionEnum;
use App\Enums\PartusTypeEnum;
use App\Enums\RolesEnum;
use App\Models\Pregnancy;
use App\Models\PregnancyHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use Interactions;

    public bool $showSelesaiModal = false;
    public ?int $selesai_pregnancy_id = null;
    public string $selesai_status_akhir = 'selesai'; // selesai, abortus, meninggal
    public bool $is_edit = false;
    public bool $can_batal = false;

    // Form 1: Persalinan / Lahir
    public ?string $lahir_tanggal = null;
    public string $lahir_cara_melahirkan = '';
    public string $lahir_tempat = '';
    public string $lahir_kondisi_bayi = '';
    public string $lahir_jenis_kelamin = '';
    public ?string $lahir_bb_bayi = null;
    public string $lahir_penolong = '';
    public string $lahir_masalah = '';
    public string $lahir_masalah_lainnya = '';

    // Form 2: Abortus
    public ?string $abortus_tanggal = null;
    public string $abortus_tempat = '';
    public string $abortus_penolong = '';
    public string $abortus_keterangan = '';

    // Form 3: Pasien Meninggal
    public ?string $meninggal_tanggal = null;
    public string $meninggal_tempat = '';
    public string $meninggal_penolong = '';
    public string $meninggal_keterangan = '';

    public function updatedSelesaiStatusAkhir(): void
    {
        $this->resetValidation();
    }

    /**
     * Memeriksa apakah user saat ini berhak menyelesaikan / mengedit status kehamilan.
     * Menggunakan method canEdit() pada model Pregnancy.
     */
    public function canEditPregnancy(Pregnancy $pregnancy): bool
    {
        return $pregnancy->canEdit();
    }

    /**
     * Buka modal Selesai Persalinan / Abortus / Meninggal atau Edit dari event.
     */
    #[On('open-selesai-modal')]
    public function openModal(int $pregnancyId): void
    {
        try {
            $this->resetValidation();

            $pregnancy = Pregnancy::with([
                'patient',
                'postnatalEncounters',
                'neonatalEncounters',
                'pregnancyHistory',
                'pregnancyHistories',
            ])->findOrFail($pregnancyId);

            if (!$this->canEditPregnancy($pregnancy)) {
                $this->toast()->error('Anda tidak memiliki hak akses untuk mengubah status akhir kehamilan ini.')->send();
                return;
            }

            $this->selesai_pregnancy_id = $pregnancyId;
            $today = date('Y-m-d');
            $history = $pregnancy->pregnancyHistory ?? $pregnancy->pregnancyHistories()->latest()->first();

            // Inisialisasi ulang seluruh inputan formulir
            $this->lahir_tanggal = $today;
            $this->lahir_cara_melahirkan = '';
            $this->lahir_tempat = '';
            $this->lahir_kondisi_bayi = '';
            $this->lahir_jenis_kelamin = '';
            $this->lahir_bb_bayi = null;
            $this->lahir_penolong = '';
            $this->lahir_masalah = '';
            $this->lahir_masalah_lainnya = '';

            $this->abortus_tanggal = $today;
            $this->abortus_tempat = '';
            $this->abortus_penolong = '';
            $this->abortus_keterangan = '';

            $this->meninggal_tanggal = $today;
            $this->meninggal_tempat = '';
            $this->meninggal_penolong = '';
            $this->meninggal_keterangan = '';

            if ($pregnancy->status === 'aktif') {
                $this->is_edit = false;
                $this->can_batal = false;
                $this->selesai_status_akhir = 'selesai';
            } else {
                // Edit Mode untuk kehamilan yang sudah diselesaikan
                $this->is_edit = true;
                $this->selesai_status_akhir = $pregnancy->status;
                // Hanya bisa dibatalkan jika belum ada kunjungan nifas (PNC) maupun neonatal (KN)
                $this->can_batal = !$pregnancy->postnatalEncounters()->exists() && !$pregnancy->neonatalEncounters()->exists();

                if ($pregnancy->status === 'selesai') {
                    $this->lahir_tanggal = $history?->tanggal_persalinan?->format('Y-m-d') ?? $today;
                    $this->lahir_cara_melahirkan = $history?->cara_melahirkan ?? PartusTypeEnum::NORMAL->value;
                    $this->lahir_tempat = $history?->tempat_persalinan ?? DeliveryFacilityEnum::PUSKESMAS_PONED->value;
                    $this->lahir_kondisi_bayi = $history?->kondisi_bayi ?? InfantConditionEnum::LAHIR_HIDUP->value;

                    // Normalisasi jenis kelamin dari legacy integer / string
                    $jk = $history?->jenis_kelamin;
                    if ($jk === '1' || $jk === 1) {
                        $jk = 'Laki-laki';
                    } elseif ($jk === '2' || $jk === 2) {
                        $jk = 'Perempuan';
                    }
                    $this->lahir_jenis_kelamin = $jk ?: 'Laki-laki';

                    $this->lahir_bb_bayi = $history?->bb_bayi ? (string) $history->bb_bayi : null;
                    $this->lahir_penolong = $history?->penolong ?? 'Bidan';

                    // Normalisasi masalah persalinan dari format lama jika ada
                    $rawMasalah = $history?->masalah_persalinan;
                    if ($rawMasalah && str_contains($rawMasalah, '|')) {
                        $parts = explode('|', $rawMasalah);
                        $rawMasalah = trim(end($parts));
                    } elseif ($rawMasalah && str_starts_with($rawMasalah, 'Kondisi:')) {
                        $rawMasalah = 'Tidak Ada';
                    }

                    if ($rawMasalah && str_starts_with($rawMasalah, 'Lainnya: ')) {
                        $this->lahir_masalah_lainnya = substr($rawMasalah, 9);
                        $rawMasalah = 'Lainnya';
                    } else {
                        $this->lahir_masalah_lainnya = $history?->masalah_persalinan_lainnya ?? '';
                    }

                    $this->lahir_masalah = $rawMasalah ?: 'Tidak Ada';
                } elseif ($pregnancy->status === 'abortus') {
                    $this->abortus_tanggal = $history?->tanggal_persalinan?->format('Y-m-d') ?? $today;
                    $this->abortus_tempat = $history?->tempat_persalinan ?? '';
                    $this->abortus_penolong = $history?->penolong ?? '';
                    $this->abortus_keterangan = $history?->masalah_persalinan ?? '';
                } elseif ($pregnancy->status === 'meninggal') {
                    $this->meninggal_tanggal = $history?->tanggal_persalinan?->format('Y-m-d') ?? $today;
                    $this->meninggal_tempat = $history?->tempat_persalinan ?? '';
                    $this->meninggal_penolong = $history?->penolong ?? '';
                    $this->meninggal_keterangan = $history?->keterangan ?? '';
                }
            }

            $this->showSelesaiModal = true;
        } catch (\Throwable $e) {
            Log::error('Error in openSelesaiModal: ' . $e->getMessage(), ['id' => $pregnancyId]);
            $this->toast()->error('Data kehamilan tidak ditemukan.')->send();
        }
    }

    /**
     * Membuka dialog konfirmasi pembatalan persalinan.
     */
    public function promptBatalkanPersalinan(): void
    {
        try {
            $pregnancy = Pregnancy::with([
                'patient',
                'postnatalEncounters',
                'neonatalEncounters',
            ])->findOrFail($this->selesai_pregnancy_id);

            if (!$this->canEditPregnancy($pregnancy)) {
                $this->toast()->error('Anda tidak memiliki hak akses untuk membatalkan persalinan ini.')->send();
                return;
            }

            if ($pregnancy->postnatalEncounters()->exists() || $pregnancy->neonatalEncounters()->exists()) {
                $this->toast()->error('Tidak dapat membatalkan persalinan karena sudah terdapat data kunjungan nifas atau kunjungan neonatal.')->send();
                return;
            }

            $patientName = $pregnancy->patient?->full_name ?? 'Pasien';
            $statusLabel = match ($pregnancy->status) {
                'abortus'   => 'status kejadian abortus',
                'meninggal' => 'status kematian pasien',
                default     => 'status persalinan',
            };

            $this->dialog()
                ->warning(
                    'Konfirmasi Batalkan ' . ucfirst($pregnancy->status),
                    "Apakah Anda yakin ingin membatalkan {$statusLabel} Ibu {$patientName}? Status kehamilan akan kembali menjadi AKTIF dan riwayat resolusi kehamilan ini akan dihapus."
                )
                ->confirm('Ya, Batalkan', 'confirmBatalkanPersalinan', $pregnancy->id)
                ->cancel('Tutup')
                ->send();
        } catch (\Throwable $e) {
            Log::error('Error in promptBatalkanPersalinan: ' . $e->getMessage());
            $this->toast()->error('Terjadi kesalahan saat memproses pembatalan persalinan: ' . $e->getMessage())->send();
        }
    }

    /**
     * Eksekusi pembatalan persalinan (mengembalikan status kehamilan menjadi aktif).
     */
    public function confirmBatalkanPersalinan(?int $pregnancyId = null): void
    {
        try {
            $targetId = $pregnancyId ?: $this->selesai_pregnancy_id;
            $pregnancy = Pregnancy::with([
                'patient',
                'postnatalEncounters',
                'neonatalEncounters',
                'pregnancyHistories',
            ])->findOrFail($targetId);

            if (!$this->canEditPregnancy($pregnancy)) {
                $this->toast()->error('Anda tidak memiliki hak akses untuk membatalkan persalinan ini.')->send();
                return;
            }

            if ($pregnancy->postnatalEncounters()->exists() || $pregnancy->neonatalEncounters()->exists()) {
                $this->toast()->error('Tidak dapat membatalkan persalinan karena sudah ada inputan nifas atau neonatal.')->send();
                return;
            }

            // Bersihkan catatan penanda abortus/meninggal dari catatan kehamilan
            $cleanedCatatan = preg_replace('/(\r?\n)?\[(Abortus|Pasien Meninggal): [^\n]*\]/m', '', $pregnancy->catatan ?? '');

            DB::transaction(function () use ($pregnancy, $cleanedCatatan) {
                // Hapus riwayat persalinan terkait kehamilan ini
                $pregnancy->pregnancyHistories()->delete();

                // Jika sebelumnya status meninggal, aktifkan kembali pasien
                if ($pregnancy->status === 'meninggal') {
                    $pregnancy->patient?->update(['is_active' => true]);
                }

                // Kembalikan status kehamilan menjadi aktif
                $pregnancy->update([
                    'status'  => 'aktif',
                    'catatan' => trim($cleanedCatatan) ?: null,
                ]);
            });

            $this->showSelesaiModal = false;
            $this->dispatch('pregnancy-updated');
            $this->toast()->success('Status kehamilan berhasil dibatalkan dan kembali aktif.')->send();
        } catch (\Throwable $e) {
            Log::error('Error in confirmBatalkanPersalinan: ' . $e->getMessage());
            $this->toast()->error('Gagal membatalkan persalinan: ' . $e->getMessage())->send();
        }
    }

    /**
     * Simpan status akhir kehamilan (Selesai Melahirkan, Abortus, atau Pasien Meninggal).
     */
    public function simpanSelesaiKehamilan(): void
    {
        // Sanitasi koma desimal pada berat badan bayi sebelum validasi
        if ($this->lahir_bb_bayi !== null) {
            $this->lahir_bb_bayi = str_replace(',', '.', trim((string) $this->lahir_bb_bayi));
        }

        $pregnancy = Pregnancy::with(['patient', 'pregnancyHistory', 'pregnancyHistories'])->findOrFail($this->selesai_pregnancy_id);

        if (!$this->canEditPregnancy($pregnancy)) {
            $this->toast()->error('Anda tidak memiliki hak akses untuk mengubah status kehamilan ini.')->send();
            return;
        }

        // Kunci status akhir ke status eksisting saat mode edit
        if ($this->is_edit) {
            $this->selesai_status_akhir = $pregnancy->status;
        }

        $today = date('Y-m-d');
        $hphtDate = $pregnancy->hpht ? $pregnancy->hpht->format('Y-m-d') : null;

        $rules = [
            'selesai_pregnancy_id' => 'required|exists:pregnancies,id',
            'selesai_status_akhir' => 'required|in:selesai,abortus,meninggal',
        ];

        $messages = [
            'selesai_pregnancy_id.required' => 'ID kehamilan tidak valid.',
            'selesai_status_akhir.required' => 'Pilihan status akhir kehamilan wajib dipilih.',
            'selesai_status_akhir.in'       => 'Pilihan status akhir kehamilan tidak valid.',
        ];

        if ($this->selesai_status_akhir === 'selesai') {
            $rules['lahir_tanggal'] = 'required|date|before_or_equal:' . $today;
            if ($hphtDate) {
                $rules['lahir_tanggal'] .= '|after_or_equal:' . $hphtDate;
            }

            $rules += [
                'lahir_cara_melahirkan' => ['required', Rule::enum(PartusTypeEnum::class)],
                'lahir_tempat'          => ['required', Rule::enum(DeliveryFacilityEnum::class)],
                'lahir_kondisi_bayi'    => ['required', Rule::enum(InfantConditionEnum::class)],
                'lahir_jenis_kelamin'   => 'required',
                'lahir_bb_bayi'         => 'required|numeric|min:0.5|max:10.0',
                'lahir_penolong'        => ['required', Rule::enum(BirthAttendantEnum::class)],
                'lahir_masalah'         => 'nullable|string|max:100',
                'lahir_masalah_lainnya' => 'required_if:lahir_masalah,Lainnya|nullable|string|max:255',
            ];

            $messages += [
                'lahir_tanggal.required'                => 'Tanggal persalinan wajib diisi.',
                'lahir_tanggal.date'                    => 'Format tanggal persalinan tidak valid.',
                'lahir_tanggal.before_or_equal'         => 'Tanggal persalinan tidak boleh lebih dari hari ini.',
                'lahir_tanggal.after_or_equal'          => 'Tanggal persalinan tidak boleh sebelum tanggal HPHT (' . ($pregnancy->hpht?->format('d/m/Y') ?? '') . ').',
                'lahir_cara_melahirkan.required'        => 'Jenis persalinan wajib dipilih.',
                'lahir_tempat.required'                 => 'Tempat persalinan wajib dipilih.',
                'lahir_kondisi_bayi.required'           => 'Kondisi lahir bayi wajib dipilih.',
                'lahir_jenis_kelamin.required'          => 'Jenis kelamin bayi wajib dipilih.',
                'lahir_bb_bayi.required'                => 'Berat badan bayi wajib diisi.',
                'lahir_bb_bayi.numeric'                 => 'Berat badan bayi harus berupa angka numerik (contoh: 3.20).',
                'lahir_bb_bayi.min'                     => 'Berat badan bayi minimal 0.5 kg.',
                'lahir_bb_bayi.max'                     => 'Berat badan bayi maksimal 10.0 kg.',
                'lahir_penolong.required'               => 'Penolong persalinan wajib dipilih.',
                'lahir_masalah_lainnya.required_if'     => 'Keterangan masalah lainnya wajib diisi jika memilih opsi Lainnya.',
            ];
        } elseif ($this->selesai_status_akhir === 'abortus') {
            $rules['abortus_tanggal'] = 'required|date|before_or_equal:' . $today;
            if ($hphtDate) {
                $rules['abortus_tanggal'] .= '|after_or_equal:' . $hphtDate;
            }

            $rules += [
                'abortus_tempat'     => ['required', Rule::enum(DeliveryFacilityEnum::class)],
                'abortus_penolong'   => ['required', Rule::enum(BirthAttendantEnum::class)],
                'abortus_keterangan' => 'nullable|string|max:1000',
            ];

            $messages += [
                'abortus_tanggal.required'        => 'Tanggal kejadian abortus wajib diisi.',
                'abortus_tanggal.date'            => 'Format tanggal abortus tidak valid.',
                'abortus_tanggal.before_or_equal' => 'Tanggal abortus tidak boleh lebih dari hari ini.',
                'abortus_tanggal.after_or_equal'  => 'Tanggal abortus tidak boleh sebelum tanggal HPHT (' . ($pregnancy->hpht?->format('d/m/Y') ?? '') . ').',
                'abortus_tempat.required'         => 'Tempat penanganan wajib dipilih.',
                'abortus_penolong.required'       => 'Tenaga medis penolong/pemeriksa wajib dipilih.',
            ];
        } else {
            $rules['meninggal_tanggal'] = 'required|date|before_or_equal:' . $today;
            if ($hphtDate) {
                $rules['meninggal_tanggal'] .= '|after_or_equal:' . $hphtDate;
            }

            $rules += [
                'meninggal_tempat'     => ['required', Rule::enum(DeliveryFacilityEnum::class)],
                'meninggal_penolong'   => ['nullable', Rule::enum(BirthAttendantEnum::class)],
                'meninggal_keterangan' => 'max:1000',
            ];

            $messages += [
                'meninggal_tanggal.required'        => 'Tanggal kematian pasien wajib diisi.',
                'meninggal_tanggal.date'            => 'Format tanggal kematian tidak valid.',
                'meninggal_tanggal.before_or_equal' => 'Tanggal kematian tidak boleh lebih dari hari ini.',
                'meninggal_tanggal.after_or_equal'  => 'Tanggal kematian tidak boleh sebelum tanggal HPHT (' . ($pregnancy->hpht?->format('d/m/Y') ?? '') . ').',
                'meninggal_tempat.required'         => 'Tempat kejadian wajib dipilih.',
            ];
        }

        $this->validate($rules, $messages);

        try {
            DB::transaction(function () use ($pregnancy) {
                $history = $pregnancy->pregnancyHistory;

                if ($this->selesai_status_akhir === 'selesai') {
                    $masalahTersimpan = trim($this->lahir_masalah);
                    if ($masalahTersimpan === 'Tidak Ada' || $masalahTersimpan === '') {
                        $masalahTersimpan = null;
                    }
                    $masalahLainnyaTersimpan = $this->lahir_masalah === 'Lainnya' ? trim($this->lahir_masalah_lainnya) : null;

                    $pregnancy->update([
                        'status'  => 'selesai',
                    ]);

                    $historyData = [
                        'pregnancy_id'               => $pregnancy->id,
                        'patient_id'                 => $pregnancy->patient_id,
                        'status'                     => 'Lahir',
                        'tanggal_persalinan'         => $this->lahir_tanggal,
                        'cara_melahirkan'            => $this->lahir_cara_melahirkan,
                        'tempat_persalinan'          => $this->lahir_tempat,
                        'kondisi_bayi'               => $this->lahir_kondisi_bayi,
                        'jenis_kelamin'              => $this->lahir_jenis_kelamin,
                        'bb_bayi'                    => $this->lahir_bb_bayi ? (float) $this->lahir_bb_bayi : null,
                        'penolong'                   => $this->lahir_penolong,
                        'masalah_persalinan'         => $masalahTersimpan,
                        'masalah_persalinan_lainnya' => $masalahLainnyaTersimpan,
                        'created_by'                 => Auth::id(),
                    ];

                    if ($this->is_edit && $history) {
                        unset($historyData['created_by']);
                        $history->update($historyData);
                    } else {
                        PregnancyHistory::create($historyData);
                    }
                } elseif ($this->selesai_status_akhir === 'abortus') {

                    $pregnancy->update([
                        'status'  => 'abortus',
                    ]);

                    $historyData = [
                        'pregnancy_id'               => $pregnancy->id,
                        'patient_id'                 => $pregnancy->patient_id,
                        'status'                     => 'Abortus',
                        'tanggal_persalinan'         => $this->abortus_tanggal,
                        'tempat_persalinan'          => $this->abortus_tempat,
                        'penolong'                   => $this->abortus_penolong,
                        'keterangan'                 => trim($this->abortus_keterangan) ?? null,
                        'created_by'                 => Auth::id(),
                    ];

                    if ($this->is_edit && $history) {
                        unset($historyData['created_by']);
                        $history->update($historyData);
                    } else {
                        PregnancyHistory::create($historyData);
                    }
                } elseif ($this->selesai_status_akhir === 'meninggal') {

                    $pregnancy->update([
                        'status'  => 'meninggal',
                    ]);

                    // Nonaktifkan data pasien karena telah meninggal
                    $pregnancy->patient?->update([
                        'is_active' => false,
                    ]);

                    $historyData = [
                        'pregnancy_id'               => $pregnancy->id,
                        'patient_id'                 => $pregnancy->patient_id,
                        'status'                     => 'Meninggal',
                        'tanggal_persalinan'         => $this->meninggal_tanggal,
                        'tempat_persalinan'          => $this->meninggal_tempat,
                        'penolong'                   => $this->meninggal_penolong ?: null,
                        'keterangan'                 => trim($this->meninggal_keterangan) ?? null,
                        'created_by'                 => Auth::id(),
                    ];

                    if ($this->is_edit && $history) {
                        unset($historyData['created_by']);
                        $history->update($historyData);
                    } else {
                        PregnancyHistory::create($historyData);
                    }
                }
            });

            $this->showSelesaiModal = false;
            $this->dispatch('pregnancy-updated');

            if ($this->is_edit) {
                $this->toast()->success('Data persalinan / resolusi kehamilan berhasil diperbarui.')->send();
                return;
            }

            if ($this->selesai_status_akhir === 'selesai') {
                $this->dispatch('open-detail-modal', pregnancyId: $pregnancy->id, tab: 'nifas');
                $this->toast()->success('Kehamilan diselesaikan (Melahirkan). Silakan lanjutkan dengan mencatat Kunjungan Nifas pertama (KF1)!')->send();
                return;
            } elseif ($this->selesai_status_akhir === 'abortus') {
                $this->toast()->success('Status kehamilan berhasil diubah menjadi Abortus (Keguguran).')->send();
            } else {
                $this->toast()->success('Data kehamilan ditandai Pasien Meninggal. Pasien telah dinonaktifkan.')->send();
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error in simpanSelesaiKehamilan: ' . $e->getMessage());
            $this->toast()->error('Terjadi kesalahan saat memproses status kehamilan: ' . $e->getMessage())->send();
        }
    }
};
