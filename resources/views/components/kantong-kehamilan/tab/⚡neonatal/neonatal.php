<?php

use App\Enums\NeonatalKnTypeEnum;
use App\Enums\NeonatalPjbEnum;
use App\Enums\NeonatalSkinColorEnum;
use App\Enums\UmbilicalCordConditionEnum;
use App\Enums\YesNoEnum;
use App\Models\NeonatalEncounter;
use App\Models\Pregnancy;
use App\Models\PregnancyHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use Interactions;

    public int $pregnancyId;

    /**
     * Modal Form Pencatatan Kunjungan Neonatal (KN1 - KN3)
     */
    public bool $showNeonatalModal = false;
    public ?int $editingNeonatalId = null;
    public ?string $neonatal_encounter_date = null;
    public ?string $neonatal_kn_type = null;
    public ?int $neonatal_hari_ke = null;

    // Pemeriksaan Antropometri & Vital Bayi
    public ?string $neonatal_bb = null;
    public ?string $neonatal_tb = null;
    public ?int $neonatal_rr = null;
    public ?string $neonatal_suhu = null;
    public ?string $neonatal_warna_kulit = null;
    public ?string $neonatal_tali_pusat = null;

    // Skrining & Pelayanan Khusus KN1
    public ?string $neonatal_salep_mata = null;
    public ?string $neonatal_vit_k = null;
    public ?string $neonatal_imd = null;
    public ?string $neonatal_hb0 = null;
    public ?string $neonatal_shk = null;
    public ?string $neonatal_hbig = null;
    public ?string $neonatal_pjb = null;

    public ?string $neonatal_catatan = null;

    /**
     * Listener saat data neonatal di-refresh.
     */
    #[On('refresh-tabs')]
    public function refreshTabs(): void
    {
        unset($this->selectedPregnancy);
    }

    /**
     * Mengambil model data kehamilan beserta kunjungan neonatal.
     */
    #[Computed]
    public function selectedPregnancy(): ?Pregnancy
    {
        return Pregnancy::with([
            'patient.pregnancyHistories',
            'creator',
            'neonatalEncounters.creator',
        ])->find($this->pregnancyId);
    }

    /**
     * Buka modal Pencatatan Kunjungan Neonatal (KN1 - KN3).
     */
    public function openNeonatalModal(?int $neonatalId = null): void
    {
        try {
            $pregnancy = $this->selectedPregnancy;
            if (!$pregnancy) {
                $this->toast()->error('Data kehamilan tidak ditemukan.')->send();
                return;
            }

            $this->editingNeonatalId = $neonatalId;

            $history = PregnancyHistory::where('patient_id', $pregnancy->patient_id)
                ->orderByDesc('tanggal_persalinan')
                ->first();
            $tanggalPersalinan = $history?->tanggal_persalinan
                ? $history->tanggal_persalinan->format('Y-m-d')
                : ($pregnancy->updated_at ? $pregnancy->updated_at->format('Y-m-d') : date('Y-m-d'));

            if ($neonatalId) {
                $neonatal = NeonatalEncounter::findOrFail($neonatalId);
                if (!$neonatal->canManage()) {
                    $this->toast()->error('Anda tidak memiliki hak akses untuk mengedit kunjungan neonatal ini.')->send();
                    return;
                }

                $this->neonatal_encounter_date = $neonatal->encounter_date ? $neonatal->encounter_date->format('Y-m-d') : date('Y-m-d');
                $this->neonatal_kn_type        = $neonatal->kn_type;
                $this->neonatal_hari_ke        = $neonatal->hari_ke;
                $this->neonatal_bb             = $neonatal->bb !== null ? (string) $neonatal->bb : null;
                $this->neonatal_tb             = $neonatal->tb !== null ? (string) $neonatal->tb : null;
                $this->neonatal_rr             = $neonatal->rr;
                $this->neonatal_suhu           = $neonatal->suhu !== null ? (string) $neonatal->suhu : null;
                $this->neonatal_warna_kulit    = $neonatal->warna_kulit;
                $this->neonatal_tali_pusat     = $neonatal->tali_pusat;

                // KN1
                $this->neonatal_salep_mata     = $neonatal->salep_mata;
                $this->neonatal_vit_k          = $neonatal->vit_k;
                $this->neonatal_imd            = $neonatal->imd;
                $this->neonatal_hb0            = $neonatal->hb0;
                $this->neonatal_shk            = $neonatal->shk;
                $this->neonatal_hbig           = $neonatal->hbig;
                $this->neonatal_pjb            = $neonatal->pjb;

                $this->neonatal_catatan        = $neonatal->catatan;
            } else {
                $this->neonatal_encounter_date = date('Y-m-d');
                $knCalc                        = NeonatalEncounter::calculateKn($tanggalPersalinan, $this->neonatal_encounter_date);
                $this->neonatal_kn_type        = $knCalc['kn_type'];
                $this->neonatal_hari_ke        = $knCalc['hari_ke'];

                $this->neonatal_bb             = null;
                $this->neonatal_tb             = null;
                $this->neonatal_rr             = null;
                $this->neonatal_suhu           = null;
                $this->neonatal_warna_kulit    = null;
                $this->neonatal_tali_pusat     = null;

                // KN1
                $this->neonatal_salep_mata     = null;
                $this->neonatal_vit_k          = null;
                $this->neonatal_imd            = null;
                $this->neonatal_hb0            = null;
                $this->neonatal_shk            = null;
                $this->neonatal_hbig           = null;
                $this->neonatal_pjb            = null;

                $this->neonatal_catatan        = null;
            }

            $this->showNeonatalModal = true;
        } catch (\Throwable $e) {
            Log::error('Error in openNeonatalModal: ' . $e->getMessage(), ['id' => $this->pregnancyId]);
            $this->toast()->error('Data kehamilan tidak ditemukan: ' . $e->getMessage())->send();
        }
    }

    /**
     * Hitung ulang klasifikasi KN saat tanggal kunjungan neonatal berubah.
     */
    public function updatedNeonatalEncounterDate(): void
    {
        $pregnancy = $this->selectedPregnancy;
        if ($pregnancy && $this->neonatal_encounter_date) {
            $history = PregnancyHistory::where('patient_id', $pregnancy->patient_id)
                ->orderByDesc('tanggal_persalinan')
                ->first();
            $tanggalPersalinan = $history?->tanggal_persalinan
                ? $history->tanggal_persalinan->format('Y-m-d')
                : ($pregnancy->updated_at ? $pregnancy->updated_at->format('Y-m-d') : date('Y-m-d'));

            $knCalc = NeonatalEncounter::calculateKn($tanggalPersalinan, $this->neonatal_encounter_date);
            $this->neonatal_kn_type = $knCalc['kn_type'];
            $this->neonatal_hari_ke = $knCalc['hari_ke'];
        }
    }

    /**
     * Simpan Kunjungan Neonatal ke database.
     */
    public function saveNeonatal(): void
    {
        $rules = [
            'neonatal_encounter_date'  => ['required', 'date'],
            'neonatal_bb'              => ['nullable', 'numeric', 'min:0.5', 'max:10.0'],
            'neonatal_tb'              => ['nullable', 'numeric', 'min:20', 'max:80'],
            'neonatal_rr'              => ['nullable', 'integer', 'min:20', 'max:120'],
            'neonatal_suhu'            => ['nullable', 'numeric', 'min:34', 'max:42'],
            'neonatal_warna_kulit'     => ['nullable', Rule::enum(NeonatalSkinColorEnum::class)],
            'neonatal_tali_pusat'      => ['nullable', Rule::enum(UmbilicalCordConditionEnum::class)],
            'neonatal_salep_mata'      => ['nullable', Rule::enum(YesNoEnum::class)],
            'neonatal_vit_k'           => ['nullable', Rule::enum(YesNoEnum::class)],
            'neonatal_imd'             => ['nullable', Rule::enum(YesNoEnum::class)],
            'neonatal_hb0'             => ['nullable', Rule::enum(YesNoEnum::class)],
            'neonatal_shk'             => ['nullable', Rule::enum(YesNoEnum::class)],
            'neonatal_hbig'            => ['nullable', Rule::enum(YesNoEnum::class)],
            'neonatal_pjb'             => ['nullable', Rule::enum(NeonatalPjbEnum::class)],
            'neonatal_catatan'         => ['nullable', 'string', 'max:1000'],
        ];

        $messages = [
            'neonatal_encounter_date.required' => 'Tanggal kunjungan neonatal wajib diisi.',
            'neonatal_bb.numeric'              => 'Berat badan bayi harus berupa angka.',
            'neonatal_tb.numeric'              => 'Panjang badan bayi harus berupa angka.',
            'neonatal_rr.integer'              => 'Pernapasan harus berupa bilangan bulat.',
            'neonatal_suhu.numeric'            => 'Suhu tubuh bayi harus berupa angka.',
        ];

        $this->validate($rules, $messages);

        $pregnancy = $this->selectedPregnancy;
        if (!$pregnancy) {
            $this->toast()->error('Data kehamilan tidak valid.')->send();
            return;
        }

        try {
            $history = PregnancyHistory::where('patient_id', $pregnancy->patient_id)
                ->orderByDesc('tanggal_persalinan')
                ->first();
            $tanggalPersalinan = $history?->tanggal_persalinan
                ? $history->tanggal_persalinan->format('Y-m-d')
                : ($pregnancy->updated_at ? $pregnancy->updated_at->format('Y-m-d') : date('Y-m-d'));

            $knCalc = NeonatalEncounter::calculateKn($tanggalPersalinan, $this->neonatal_encounter_date);

            $data = [
                'patient_id'     => $pregnancy->patient_id,
                'pregnancy_id'   => $pregnancy->id,
                'encounter_date' => $this->neonatal_encounter_date,
                'kn_type'        => 'KN',
                'bb'             => $this->neonatal_bb,
                'tb'             => $this->neonatal_tb,
                'rr'             => $this->neonatal_rr,
                'suhu'           => $this->neonatal_suhu,
                'warna_kulit'    => $this->neonatal_warna_kulit,
                'tali_pusat'     => $this->neonatal_tali_pusat,
                'salep_mata'     => $this->neonatal_salep_mata,
                'vit_k'          => $this->neonatal_vit_k,
                'imd'            => $this->neonatal_imd,
                'hb0'            => $this->neonatal_hb0,
                'shk'            => $this->neonatal_shk,
                'hbig'           => $this->neonatal_hbig,
                'pjb'            => $this->neonatal_pjb,
                'catatan'        => $this->neonatal_catatan,
            ];

            if ($this->editingNeonatalId) {
                $neonatal = NeonatalEncounter::findOrFail($this->editingNeonatalId);
                if (!$neonatal->canManage()) {
                    throw new \DomainException('Anda tidak memiliki hak akses untuk mengubah kunjungan neonatal ini.');
                }
                $data['updated_by'] = Auth::id();
                $neonatal->update($data);
                $this->toast()->success('Data kunjungan neonatal berhasil diperbarui!')->send();
            } else {
                $data['created_by'] = Auth::id();
                NeonatalEncounter::create($data);
                $this->toast()->success('Kunjungan neonatal (' . ($this->neonatal_kn_type ?: $knCalc['kn_type']) . ') berhasil dicatat!')->send();
            }

            $this->showNeonatalModal = false;
            $this->editingNeonatalId = null;

            unset($this->selectedPregnancy);
            $this->dispatch('pregnancy-updated');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error in saveNeonatal: ' . $e->getMessage());
            $this->toast()->error('Terjadi kesalahan saat menyimpan kunjungan neonatal: ' . $e->getMessage())->send();
        }
    }

    /**
     * Hapus Kunjungan Neonatal.
     */
    public function deleteNeonatal(int $neonatalId): void
    {
        try {
            $neonatal = NeonatalEncounter::findOrFail($neonatalId);
            if (!$neonatal->canManage()) {
                $this->toast()->error('Anda tidak memiliki hak akses untuk menghapus kunjungan neonatal ini.')->send();
                return;
            }

            $neonatal->delete();
            unset($this->selectedPregnancy);
            $this->dispatch('pregnancy-updated');
            $this->toast()->success('Kunjungan neonatal berhasil dihapus.')->send();
        } catch (\Throwable $e) {
            Log::error('Error in deleteNeonatal: ' . $e->getMessage());
            $this->toast()->error('Gagal menghapus: ' . $e->getMessage())->send();
        }
    }
};
