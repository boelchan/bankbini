<?php

use App\Enums\EliminationStatusEnum;
use App\Enums\LactationConditionEnum;
use App\Enums\LocheaTypeEnum;
use App\Enums\MaternalComplicationEnum;
use App\Enums\PerineumConditionEnum;
use App\Enums\PostnatalTfuEnum;
use App\Models\PostnatalEncounter;
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
     * Modal Form Pencatatan Kunjungan Nifas
     */
    public bool $showNifasModal = false;
    public ?int $editingNifasId = null;
    public ?string $nifas_encounter_date = null;
    public ?string $nifas_kf_type = null;
    public ?int $nifas_hari_ke = null;
    public ?int $nifas_td_sistolik = null;
    public ?int $nifas_td_diastolik = null;
    public ?string $nifas_suhu = null;
    public ?int $nifas_nadi = null;
    public ?string $nifas_tfu = null;
    public ?string $nifas_lochea = null;
    public ?string $nifas_jalan_lahir = null;
    public ?string $nifas_asi = null;
    public ?string $nifas_bab = null;
    public ?string $nifas_bak = null;
    public ?string $nifas_masalah = null;
    public ?string $nifas_masalah_lainnya = null;
    public ?string $nifas_catatan = null;

    /**
     * Listener saat data nifas di-refresh.
     */
    #[On('refresh-tabs')]
    public function refreshTabs(): void
    {
        unset($this->selectedPregnancy);
    }

    /**
     * Mengambil model data kehamilan beserta rekam kunjungan nifas.
     */
    #[Computed]
    public function selectedPregnancy(): ?Pregnancy
    {
        return Pregnancy::with([
            'patient.pregnancyHistories',
            'creator',
            'postnatalEncounters.creator',
        ])->find($this->pregnancyId);
    }

    /**
     * Buka modal Pencatatan Kunjungan Nifas (KF1 - KF4)
     */
    public function openNifasModal(?int $nifasId = null): void
    {
        try {
            $pregnancy = $this->selectedPregnancy;
            if (!$pregnancy) {
                $this->toast()->error('Data kehamilan tidak ditemukan.')->send();
                return;
            }

            $this->editingNifasId = $nifasId;

            $history = PregnancyHistory::where('patient_id', $pregnancy->patient_id)
                ->orderByDesc('tanggal_persalinan')
                ->first();
            $tanggalPersalinan = $history?->tanggal_persalinan
                ? $history->tanggal_persalinan->format('Y-m-d')
                : ($pregnancy->updated_at ? $pregnancy->updated_at->format('Y-m-d') : date('Y-m-d'));

            if ($nifasId) {
                $nifas = PostnatalEncounter::findOrFail($nifasId);
                if (!$nifas->canManage()) {
                    $this->toast()->error('Anda tidak memiliki hak akses untuk mengedit kunjungan nifas ini.')->send();
                    return;
                }

                $this->nifas_encounter_date  = $nifas->encounter_date ? $nifas->encounter_date->format('Y-m-d') : date('Y-m-d');
                $this->nifas_kf_type         = $nifas->kf_type;
                $this->nifas_hari_ke         = $nifas->hari_ke;
                $this->nifas_td_sistolik     = $nifas->td_sistolik;
                $this->nifas_td_diastolik    = $nifas->td_diastolik;
                $this->nifas_suhu            = $nifas->suhu !== null ? (string) $nifas->suhu : null;
                $this->nifas_nadi            = $nifas->nadi;
                $this->nifas_tfu             = $nifas->tfu;
                $this->nifas_lochea          = $nifas->lochea;
                $this->nifas_jalan_lahir     = $nifas->jalan_lahir;
                $this->nifas_asi             = $nifas->asi;
                $this->nifas_bab             = $nifas->bab;
                $this->nifas_bak             = $nifas->bak;
                $this->nifas_masalah         = $nifas->masalah_nifas;
                $this->nifas_masalah_lainnya = $nifas->masalah_nifas_lainnya;
                $this->nifas_catatan         = $nifas->catatan;
            } else {
                $this->nifas_encounter_date  = date('Y-m-d');
                $kfCalc                      = PostnatalEncounter::calculateKf($tanggalPersalinan, $this->nifas_encounter_date);
                $this->nifas_kf_type         = $kfCalc['kf_type'];
                $this->nifas_hari_ke         = $kfCalc['hari_ke'];
                $this->nifas_td_sistolik     = null;
                $this->nifas_td_diastolik    = null;
                $this->nifas_suhu            = null;
                $this->nifas_nadi            = null;
                $this->nifas_tfu             = null;
                $this->nifas_lochea          = null;
                $this->nifas_jalan_lahir     = null;
                $this->nifas_asi             = null;
                $this->nifas_bab             = null;
                $this->nifas_bak             = null;
                $this->nifas_masalah         = null;
                $this->nifas_masalah_lainnya = null;
                $this->nifas_catatan         = null;
            }

            $this->showNifasModal = true;
        } catch (\Throwable $e) {
            Log::error('Error in openNifasModal: ' . $e->getMessage(), ['id' => $this->pregnancyId]);
            $this->toast()->error('Data kehamilan tidak ditemukan: ' . $e->getMessage())->send();
        }
    }

    /**
     * Hitung ulang klasifikasi KF saat tanggal kunjungan nifas berubah.
     */
    public function updatedNifasEncounterDate(): void
    {
        $pregnancy = $this->selectedPregnancy;
        if ($pregnancy && $this->nifas_encounter_date) {
            $history = PregnancyHistory::where('patient_id', $pregnancy->patient_id)
                ->orderByDesc('tanggal_persalinan')
                ->first();
            $tanggalPersalinan = $history?->tanggal_persalinan
                ? $history->tanggal_persalinan->format('Y-m-d')
                : ($pregnancy->updated_at ? $pregnancy->updated_at->format('Y-m-d') : date('Y-m-d'));

            $kfCalc = PostnatalEncounter::calculateKf($tanggalPersalinan, $this->nifas_encounter_date);
            $this->nifas_kf_type = $kfCalc['kf_type'];
            $this->nifas_hari_ke = $kfCalc['hari_ke'];
        }
    }

    /**
     * Simpan Kunjungan Nifas ke database.
     */
    public function saveNifas(): void
    {
        $rules = [
            'nifas_encounter_date'  => ['required', 'date'],
            'nifas_td_sistolik'     => ['nullable', 'integer', 'min:60', 'max:250'],
            'nifas_td_diastolik'    => ['nullable', 'integer', 'min:40', 'max:150'],
            'nifas_suhu'            => ['nullable', 'numeric', 'min:34', 'max:42'],
            'nifas_nadi'            => ['nullable', 'integer', 'min:40', 'max:180'],
            'nifas_tfu'             => ['nullable', Rule::enum(PostnatalTfuEnum::class)],
            'nifas_lochea'          => ['nullable', Rule::enum(LocheaTypeEnum::class)],
            'nifas_jalan_lahir'     => ['nullable', Rule::enum(PerineumConditionEnum::class)],
            'nifas_asi'             => ['nullable', Rule::enum(LactationConditionEnum::class)],
            'nifas_bab'             => ['nullable', Rule::enum(EliminationStatusEnum::class)],
            'nifas_bak'             => ['nullable', Rule::enum(EliminationStatusEnum::class)],
            'nifas_masalah'         => ['nullable', Rule::enum(MaternalComplicationEnum::class)],
            'nifas_masalah_lainnya' => ['required_if:nifas_masalah,Lainnya', 'nullable', 'string', 'max:255'],
            'nifas_catatan'         => ['nullable', 'string', 'max:1000'],
        ];

        $messages = [
            'nifas_encounter_date.required'     => 'Tanggal kunjungan nifas wajib diisi.',
            'nifas_td_sistolik.integer'         => 'Sistol harus berupa bilangan bulat.',
            'nifas_td_diastolik.integer'        => 'Diastol harus berupa bilangan bulat.',
            'nifas_suhu.numeric'                => 'Suhu tubuh harus berupa angka.',
            'nifas_nadi.integer'                => 'Nadi harus berupa bilangan bulat.',
            'nifas_masalah_lainnya.required_if' => 'Keterangan masalah lainnya wajib diisi jika memilih "Lainnya".',
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

            $kfCalc = PostnatalEncounter::calculateKf($tanggalPersalinan, $this->nifas_encounter_date);

            $data = [
                'patient_id'            => $pregnancy->patient_id,
                'pregnancy_id'          => $pregnancy->id,
                'encounter_date'        => $this->nifas_encounter_date,
                'td_sistolik'           => $this->nifas_td_sistolik,
                'td_diastolik'          => $this->nifas_td_diastolik,
                'suhu'                  => ($this->nifas_suhu !== null && $this->nifas_suhu !== '') ? (float) $this->nifas_suhu : null,
                'nadi'                  => $this->nifas_nadi,
                'tfu'                   => $this->nifas_tfu ?: null,
                'lochea'                => $this->nifas_lochea ?: null,
                'jalan_lahir'           => $this->nifas_jalan_lahir ?: null,
                'asi'                   => $this->nifas_asi ?: null,
                'bab'                   => $this->nifas_bab ?: EliminationStatusEnum::YA->value,
                'bak'                   => $this->nifas_bak ?: EliminationStatusEnum::YA->value,
                'masalah_nifas'         => ($this->nifas_masalah && $this->nifas_masalah !== MaternalComplicationEnum::TIDAK_ADA->value) ? $this->nifas_masalah : null,
                'masalah_nifas_lainnya' => $this->nifas_masalah === MaternalComplicationEnum::LAINNYA->value ? $this->nifas_masalah_lainnya : null,
                'catatan'               => $this->nifas_catatan ?: null,
            ];

            if ($this->editingNifasId) {
                $nifas = PostnatalEncounter::findOrFail($this->editingNifasId);
                if (!$nifas->canManage()) {
                    throw new \DomainException('Anda tidak memiliki hak akses untuk mengubah kunjungan nifas ini.');
                }
                $data['updated_by'] = Auth::id();
                $nifas->update($data);
                $this->toast()->success('Data kunjungan nifas berhasil diperbarui!')->send();
            } else {
                $data['created_by'] = Auth::id();
                PostnatalEncounter::create($data);
                $this->toast()->success('Kunjungan nifas (' . $kfCalc['kf_type'] . ') berhasil dicatat!')->send();
            }

            $this->showNifasModal = false;
            $this->editingNifasId = null;

            unset($this->selectedPregnancy);
            $this->dispatch('pregnancy-updated');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error in saveNifas: ' . $e->getMessage());
            $this->toast()->error('Terjadi kesalahan saat menyimpan kunjungan nifas: ' . $e->getMessage())->send();
        }
    }

    /**
     * Hapus Kunjungan Nifas.
     */
    public function deleteNifas(int $nifasId): void
    {
        try {
            $nifas = PostnatalEncounter::findOrFail($nifasId);
            if (!$nifas->canManage()) {
                $this->toast()->error('Anda tidak memiliki hak akses untuk menghapus kunjungan nifas ini.')->send();
                return;
            }

            $nifas->delete();
            unset($this->selectedPregnancy);
            $this->dispatch('pregnancy-updated');
            $this->toast()->success('Kunjungan nifas berhasil dihapus.')->send();
        } catch (\Throwable $e) {
            Log::error('Error in deleteNifas: ' . $e->getMessage());
            $this->toast()->error('Gagal menghapus kunjungan nifas: ' . $e->getMessage())->send();
        }
    }
};
