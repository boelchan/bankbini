<?php

use App\Enums\AsphyxiaLevelEnum;
use App\Enums\CongenitalHypothyroidResultEnum;
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
     * Modal Form Kasus / Komplikasi Neonatal (Cukup 1 kali)
     */
    public bool $showKomplikasiModal = false;
    public ?int $editingKomplikasiId = null;
    public ?string $komplikasi_encounter_date = null;
    public ?string $komplikasi_trauma_lahir = null;
    public ?string $komplikasi_asfiksia = null;
    public ?string $komplikasi_bblr = null;
    public ?string $komplikasi_infeksi = null;
    public ?string $komplikasi_tn = null;
    public ?string $komplikasi_kelainan_bawaan = null;
    public ?string $komplikasi_hk_positif = null;
    public ?string $komplikasi_covid19 = null;
    public ?string $komplikasi_masalah_lainnya = null;
    public ?string $komplikasi_catatan = null;

    /**
     * Listener saat data komplikasi neonatal di-refresh.
     */
    #[On('refresh-tabs')]
    public function refreshTabs(): void
    {
        unset($this->selectedPregnancy);
    }

    /**
     * Mengambil model data kehamilan beserta data komplikasi neonatal.
     */
    #[Computed]
    public function selectedPregnancy(): ?Pregnancy
    {
        return Pregnancy::with([
            'patient.pregnancyHistories',
            'creator',
            'neonatalComplication.creator',
        ])->find($this->pregnancyId);
    }

    /**
     * Buka modal Pencatatan Kasus / Komplikasi Neonatal (Cukup 1 kali).
     */
    public function openKomplikasiModal(): void
    {
        try {
            $pregnancy = $this->selectedPregnancy;
            if (!$pregnancy) {
                $this->toast()->error('Data kehamilan tidak ditemukan.')->send();
                return;
            }

            $history = PregnancyHistory::where('patient_id', $pregnancy->patient_id)
                ->orderByDesc('tanggal_persalinan')
                ->first();

            $existing = $pregnancy->neonatalComplication;
            if ($existing) {
                if (!$existing->canManage()) {
                    $this->toast()->error('Anda tidak memiliki hak akses untuk mengubah data komplikasi neonatal ini.')->send();
                    return;
                }

                $this->editingKomplikasiId        = $existing->id;
                $this->komplikasi_encounter_date  = $existing->encounter_date ? $existing->encounter_date->format('Y-m-d') : date('Y-m-d');
                $this->komplikasi_trauma_lahir    = $existing->trauma_lahir ?: YesNoEnum::TIDAK->value;
                $this->komplikasi_asfiksia        = $existing->asfiksia ?: AsphyxiaLevelEnum::NORMAL->value;
                $this->komplikasi_bblr            = $existing->bblr ?: YesNoEnum::TIDAK->value;
                $this->komplikasi_infeksi         = $existing->infeksi ?: YesNoEnum::TIDAK->value;
                $this->komplikasi_tn              = $existing->tn ?: YesNoEnum::TIDAK->value;
                $this->komplikasi_kelainan_bawaan = $existing->kelainan_bawaan ?: YesNoEnum::TIDAK->value;
                $this->komplikasi_hk_positif      = $existing->hk_positif ?: CongenitalHypothyroidResultEnum::NEGATIF->value;
                $this->komplikasi_covid19         = $existing->covid19 ?: YesNoEnum::TIDAK->value;
                $this->komplikasi_masalah_lainnya = $existing->masalah_lainnya ?: '';
                $this->komplikasi_catatan         = $existing->catatan ?: '';
            } else {
                $this->editingKomplikasiId        = null;
                $this->komplikasi_encounter_date  = date('Y-m-d');
                $this->komplikasi_trauma_lahir    = YesNoEnum::TIDAK->value;
                $this->komplikasi_asfiksia        = AsphyxiaLevelEnum::NORMAL->value;
                $this->komplikasi_bblr            = ($history?->bb_bayi && (float) $history->bb_bayi < 2.5) ? YesNoEnum::YA->value : YesNoEnum::TIDAK->value;
                $this->komplikasi_infeksi         = YesNoEnum::TIDAK->value;
                $this->komplikasi_tn              = YesNoEnum::TIDAK->value;
                $this->komplikasi_kelainan_bawaan = YesNoEnum::TIDAK->value;
                $this->komplikasi_hk_positif      = CongenitalHypothyroidResultEnum::NEGATIF->value;
                $this->komplikasi_covid19         = YesNoEnum::TIDAK->value;
                $this->komplikasi_masalah_lainnya = '';
                $this->komplikasi_catatan         = '';
            }

            $this->showKomplikasiModal = true;
        } catch (\Throwable $e) {
            Log::error('Error in openKomplikasiModal: ' . $e->getMessage(), ['id' => $this->pregnancyId]);
            $this->toast()->error('Data kehamilan tidak ditemukan: ' . $e->getMessage())->send();
        }
    }

    /**
     * Simpan Kasus / Komplikasi Neonatal (Cukup 1 kali).
     */
    public function saveKomplikasi(): void
    {
        $rules = [
            'komplikasi_encounter_date'  => ['required', 'date'],
            'komplikasi_trauma_lahir'    => ['required', Rule::enum(YesNoEnum::class)],
            'komplikasi_asfiksia'        => ['required', Rule::enum(AsphyxiaLevelEnum::class)],
            'komplikasi_bblr'            => ['required', Rule::enum(YesNoEnum::class)],
            'komplikasi_infeksi'         => ['required', Rule::enum(YesNoEnum::class)],
            'komplikasi_tn'              => ['required', Rule::enum(YesNoEnum::class)],
            'komplikasi_kelainan_bawaan' => ['required', Rule::enum(YesNoEnum::class)],
            'komplikasi_hk_positif'      => ['required', Rule::enum(CongenitalHypothyroidResultEnum::class)],
            'komplikasi_covid19'         => ['required', Rule::enum(YesNoEnum::class)],
            'komplikasi_masalah_lainnya' => ['nullable', 'string', 'max:255'],
            'komplikasi_catatan'         => ['nullable', 'string', 'max:1000'],
        ];

        $messages = [
            'komplikasi_encounter_date.required' => 'Tanggal pemeriksaan / skrining wajib diisi.',
        ];

        $this->validate($rules, $messages);

        $pregnancy = $this->selectedPregnancy;
        if (!$pregnancy) {
            $this->toast()->error('Data kehamilan tidak valid.')->send();
            return;
        }

        try {
            $data = [
                'patient_id'      => $pregnancy->patient_id,
                'pregnancy_id'    => $pregnancy->id,
                'encounter_date'  => $this->komplikasi_encounter_date,
                'kn_type'         => 'KOMPLIKASI',
                'trauma_lahir'    => $this->komplikasi_trauma_lahir,
                'asfiksia'        => $this->komplikasi_asfiksia,
                'bblr'            => $this->komplikasi_bblr,
                'infeksi'         => $this->komplikasi_infeksi,
                'tn'              => $this->komplikasi_tn,
                'kelainan_bawaan' => $this->komplikasi_kelainan_bawaan,
                'hk_positif'      => $this->komplikasi_hk_positif,
                'covid19'         => $this->komplikasi_covid19,
                'masalah_lainnya' => $this->komplikasi_masalah_lainnya ?: null,
                'catatan'         => $this->komplikasi_catatan ?: null,
            ];

            if ($this->editingKomplikasiId) {
                $komplikasi = NeonatalEncounter::findOrFail($this->editingKomplikasiId);
                if (!$komplikasi->canManage()) {
                    throw new \DomainException('Anda tidak memiliki hak akses untuk mengubah data komplikasi neonatal ini.');
                }
                $data['updated_by'] = Auth::id();
                $komplikasi->update($data);
                $this->toast()->success('Data kasus/komplikasi neonatal berhasil diperbarui!')->send();
            } else {
                $data['created_by'] = Auth::id();
                NeonatalEncounter::create($data);
                $this->toast()->success('Data kasus/komplikasi neonatal berhasil disimpan!')->send();
            }

            $this->showKomplikasiModal = false;

            unset($this->selectedPregnancy);
            $this->dispatch('pregnancy-updated');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error in saveKomplikasi: ' . $e->getMessage());
            $this->toast()->error('Terjadi kesalahan saat menyimpan data komplikasi neonatal: ' . $e->getMessage())->send();
        }
    }

    /**
     * Hapus Data Kasus / Komplikasi Neonatal.
     */
    public function deleteKomplikasi(int $komplikasiId): void
    {
        try {
            $komplikasi = NeonatalEncounter::findOrFail($komplikasiId);
            if (!$komplikasi->canManage()) {
                $this->toast()->error('Anda tidak memiliki hak akses untuk menghapus data ini.')->send();
                return;
            }

            $komplikasi->delete();
            unset($this->selectedPregnancy);
            $this->dispatch('pregnancy-updated');
            $this->toast()->success('Data komplikasi neonatal berhasil dihapus.')->send();
        } catch (\Throwable $e) {
            Log::error('Error in deleteKomplikasi: ' . $e->getMessage());
            $this->toast()->error('Gagal menghapus: ' . $e->getMessage())->send();
        }
    }
};
