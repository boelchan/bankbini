<?php

use App\Enums\PoedjiRochyatiEnum;
use App\Models\Encounter;
use App\Models\Pregnancy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
     * Modal Form Pemeriksaan ANC State
     */
    public bool $showPemeriksaanModal = false;
    public ?int $editingEncounterId = null;
    public ?string $editingEncounterNumber = null;
    public ?string $pem_encounter_date = null;
    public ?string $pem_next_encounter_date = null;

    // Nilai Vital & Pemeriksaan Fisik
    public ?string $pem_lila = null;
    public ?string $pem_bb = null;
    public ?string $pem_tb = null;
    public ?string $pem_imt = null;
    public ?int $pem_td_sistolik = null;
    public ?int $pem_td_diastolik = null;
    public ?string $pem_map = null;
    public ?string $pem_tfu = null;
    public ?int $pem_djj = null;

    // Skor Puji Rochyati
    public int $pem_skor_puji_rochyati = 2;
    public string $pem_kategori_puji_rochyati = 'Kehamilan Risiko Rendah';
    public array $pem_faktor_risiko_selected = [];

    // Skrining Jiwa
    public ?int $pem_skor_skrining_jiwa = null;
    public string $pem_kategori_skrining_jiwa = '-';

    // Hasil Laboratorium
    public ?string $pem_lab_hb = null;
    public string $pem_lab_hiv = 'Belum Diperiksa';
    public string $pem_lab_syphilis = 'Belum Diperiksa';
    public string $pem_lab_hbsag = 'Belum Diperiksa';
    public string $pem_lab_protein_urine = 'Negatif';
    public ?int $pem_lab_gda = null;
    public string $pem_lab_reduksi_urine = 'Negatif';

    // USG & Masalah Kehamilan
    public int $pem_is_usg = 0;
    public string $pem_masalah_kehamilan = 'Tidak Ada';
    public string $pem_masalah_kehamilan_lainnya = '';

    /**
     * Listener saat data di-refresh.
     */
    #[On('refresh-tabs')]
    public function refreshTabs(): void
    {
        unset($this->selectedPregnancy);
    }

    /**
     * Mengambil model data kehamilan beserta relasi pemeriksaannya.
     */
    #[Computed]
    public function selectedPregnancy(): ?Pregnancy
    {
        return Pregnancy::with([
            'patient.village',
            'patient.pregnancyHistories',
            'creator',
            'encounters.creator',
            'encounters.pregnancy',
        ])->find($this->pregnancyId);
    }

    /**
     * Realtime kalkulasi IMT & MAP saat berat badan berubah.
     */
    public function updatedPemBb(): void
    {
        $this->calculatePemVitalStats();
    }

    /**
     * Realtime kalkulasi IMT & MAP serta faktor risiko TB <= 145 cm.
     */
    public function updatedPemTb(): void
    {
        $this->calculatePemVitalStats();
        $tb = floatval($this->pem_tb);
        if ($tb > 0 && $tb <= 145) {
            if (!in_array('terlalu_pendek', $this->pem_faktor_risiko_selected)) {
                $this->pem_faktor_risiko_selected[] = 'terlalu_pendek';
                $this->updatedPemFaktorRisikoSelected();
            }
        } elseif ($tb > 145 && in_array('terlalu_pendek', $this->pem_faktor_risiko_selected)) {
            $this->pem_faktor_risiko_selected = array_values(array_diff($this->pem_faktor_risiko_selected, ['terlalu_pendek', 'tb_pendek']));
            $this->updatedPemFaktorRisikoSelected();
        }
    }

    public function updatedPemTdSistolik(): void
    {
        $this->calculatePemVitalStats();
    }

    public function updatedPemTdDiastolik(): void
    {
        $this->calculatePemVitalStats();
    }

    public function calculatePemVitalStats(): void
    {
        $bb = floatval($this->pem_bb);
        $tb = floatval($this->pem_tb);
        if ($bb > 0 && $tb > 0) {
            $tbM = $tb / 100;
            $this->pem_imt = (string) round($bb / ($tbM * $tbM), 2);
        }

        $sys = intval($this->pem_td_sistolik);
        $dia = intval($this->pem_td_diastolik);
        if ($sys > 0 && $dia > 0) {
            $this->pem_map = (string) round(($sys + (2 * $dia)) / 3, 2);
        }
    }

    public function updatedPemLabHb(): void
    {
        $hb = floatval($this->pem_lab_hb);
        if ($hb > 0 && $hb < 10) {
            if (!in_array('penyakit_kurang_darah', $this->pem_faktor_risiko_selected)) {
                $this->pem_faktor_risiko_selected[] = 'penyakit_kurang_darah';
                $this->updatedPemFaktorRisikoSelected();
            }
        } elseif ($hb >= 10 && in_array('penyakit_kurang_darah', $this->pem_faktor_risiko_selected)) {
            $this->pem_faktor_risiko_selected = array_values(array_diff($this->pem_faktor_risiko_selected, ['penyakit_kurang_darah', 'anemia']));
            $this->updatedPemFaktorRisikoSelected();
        }
    }

    public function updatedPemFaktorRisikoSelected(): void
    {
        $this->pem_skor_puji_rochyati = PoedjiRochyatiEnum::calculateScore($this->pem_faktor_risiko_selected);
        $this->pem_kategori_puji_rochyati = PoedjiRochyatiEnum::getCategory($this->pem_skor_puji_rochyati);
    }

    public function updatedPemSkorSkriningJiwa(): void
    {
        $skor = $this->pem_skor_skrining_jiwa !== null && $this->pem_skor_skrining_jiwa !== '' ? intval($this->pem_skor_skrining_jiwa) : null;
        $this->pem_kategori_skrining_jiwa = Encounter::calculateKategoriJiwa($skor);
    }

    /**
     * Buka modal Tambah Pemeriksaan ANC.
     */
    public function openPemeriksaanModal(): void
    {
        $pregnancy = $this->selectedPregnancy;
        if (!$pregnancy || $pregnancy->status !== 'aktif') {
            $this->toast()->warning('Tidak dapat menambah pemeriksaan untuk kehamilan yang sudah selesai atau tidak aktif.')->send();
            return;
        }

        $this->editingEncounterId = null;
        $this->editingEncounterNumber = null;
        $this->pem_encounter_date = date('Y-m-d');
        $this->pem_next_encounter_date = null;

        $this->pem_lila = null;
        $this->pem_bb = null;
        $this->pem_tb = null;
        $this->pem_imt = null;
        $this->pem_td_sistolik = null;
        $this->pem_td_diastolik = null;
        $this->pem_map = null;
        $this->pem_tfu = null;
        $this->pem_djj = null;
        $this->pem_faktor_risiko_selected = [];
        $this->pem_skor_puji_rochyati = 2;
        $this->pem_kategori_puji_rochyati = 'Kehamilan Risiko Rendah';
        $this->pem_skor_skrining_jiwa = null;
        $this->pem_kategori_skrining_jiwa = '-';

        $this->pem_lab_hb = null;
        $this->pem_lab_hiv = 'Belum Diperiksa';
        $this->pem_lab_syphilis = 'Belum Diperiksa';
        $this->pem_lab_hbsag = 'Belum Diperiksa';
        $this->pem_lab_protein_urine = 'Negatif';
        $this->pem_lab_gda = null;
        $this->pem_lab_reduksi_urine = 'Negatif';
        $this->pem_is_usg = 0;
        $this->pem_masalah_kehamilan = 'Tidak Ada';
        $this->pem_masalah_kehamilan_lainnya = '';

        // Preload TB dari rekam kunjungan sebelumnya jika ada
        $existingTb = Encounter::where('patient_id', $pregnancy->patient_id)
            ->whereNotNull('tb')
            ->where('tb', '>', 0)
            ->orderByDesc('encounter_date')
            ->orderByDesc('id')
            ->value('tb');

        $this->pem_tb = $existingTb ? (string) (floatval($existingTb) == intval($existingTb) ? intval($existingTb) : floatval($existingTb)) : '155';

        // Preload faktor risiko
        $prevEncounter = Encounter::where('pregnancy_id', $pregnancy->id)
            ->whereNotNull('faktor_risiko_json')
            ->orderByDesc('encounter_date')
            ->orderByDesc('id')
            ->first();

        if ($prevEncounter && !empty($prevEncounter->faktor_risiko_json)) {
            $this->pem_faktor_risiko_selected = $prevEncounter->faktor_risiko_json;
        }

        if ($pregnancy->partus >= 4 && !in_array(PoedjiRochyatiEnum::ANAK_BANYAK->value, $this->pem_faktor_risiko_selected)) {
            $this->pem_faktor_risiko_selected[] = PoedjiRochyatiEnum::ANAK_BANYAK->value;
        }
        if ($pregnancy->abortus >= 1 && !in_array(PoedjiRochyatiEnum::PERNAH_GAGAL_KEHAMILAN->value, $this->pem_faktor_risiko_selected)) {
            $this->pem_faktor_risiko_selected[] = PoedjiRochyatiEnum::PERNAH_GAGAL_KEHAMILAN->value;
        }
        if ($this->pem_tb && floatval($this->pem_tb) <= 145 && !in_array(PoedjiRochyatiEnum::TERLALU_PENDEK->value, $this->pem_faktor_risiko_selected)) {
            $this->pem_faktor_risiko_selected[] = PoedjiRochyatiEnum::TERLALU_PENDEK->value;
        }
        if ($pregnancy->patient) {
            $hasSc = $pregnancy->patient->pregnancyHistories->contains(function ($h) {
                return str_contains(strtoupper($h->cara_melahirkan), 'SC') || str_contains(strtoupper($h->cara_melahirkan), 'SESAR');
            });
            if ($hasSc && !in_array(PoedjiRochyatiEnum::PERNAH_OPERASI_SESAR->value, $this->pem_faktor_risiko_selected)) {
                $this->pem_faktor_risiko_selected[] = PoedjiRochyatiEnum::PERNAH_OPERASI_SESAR->value;
            }

            $hasVakum = $pregnancy->patient->pregnancyHistories->contains(function ($h) {
                return str_contains(strtoupper($h->cara_melahirkan), 'VAKUM') || str_contains(strtoupper($h->cara_melahirkan), 'TANG');
            });
            if ($hasVakum && !in_array(PoedjiRochyatiEnum::PERNAH_LAHIR_VAKUM_TANG->value, $this->pem_faktor_risiko_selected)) {
                $this->pem_faktor_risiko_selected[] = PoedjiRochyatiEnum::PERNAH_LAHIR_VAKUM_TANG->value;
            }
        }

        $this->calculatePemVitalStats();
        $this->updatedPemFaktorRisikoSelected();
        $this->showPemeriksaanModal = true;
    }

    /**
     * Buka modal Ubah Pemeriksaan ANC.
     */
    public function openEditPemeriksaanModal(int $encounterId): void
    {
        try {
            $encounter = Encounter::findOrFail($encounterId);

            if (!$encounter->canManage()) {
                $this->toast()->error('Anda tidak memiliki hak akses untuk mengedit pemeriksaan kontrol ini.')->send();
                return;
            }

            $this->editingEncounterId = $encounter->id;
            $this->editingEncounterNumber = $encounter->encounter_number;

            $this->pem_encounter_date = $encounter->encounter_date ? $encounter->encounter_date->format('Y-m-d') : date('Y-m-d');
            $this->pem_next_encounter_date = $encounter->next_encounter_date ? $encounter->next_encounter_date->format('Y-m-d') : null;

            $this->pem_lila = $encounter->lila !== null ? (string) (floatval($encounter->lila) == intval($encounter->lila) ? intval($encounter->lila) : floatval($encounter->lila)) : null;
            $this->pem_bb = $encounter->bb !== null ? (string) (floatval($encounter->bb) == intval($encounter->bb) ? intval($encounter->bb) : floatval($encounter->bb)) : null;
            $this->pem_tb = $encounter->tb !== null ? (string) (floatval($encounter->tb) == intval($encounter->tb) ? intval($encounter->tb) : floatval($encounter->tb)) : null;
            $this->pem_imt = $encounter->imt !== null ? (string) $encounter->imt : null;
            $this->pem_td_sistolik = $encounter->td_sistolik;
            $this->pem_td_diastolik = $encounter->td_diastolik;
            $this->pem_map = $encounter->map !== null ? (string) $encounter->map : null;
            $this->pem_tfu = $encounter->tfu !== null ? (string) (floatval($encounter->tfu) == intval($encounter->tfu) ? intval($encounter->tfu) : floatval($encounter->tfu)) : null;
            $this->pem_djj = $encounter->djj;

            $this->pem_faktor_risiko_selected = is_array($encounter->faktor_risiko_json) ? $encounter->faktor_risiko_json : [];
            $this->pem_skor_puji_rochyati = $encounter->skor_puji_rochyati ?: 2;
            $this->pem_kategori_puji_rochyati = $encounter->kategori_puji_rochyati ?: 'Kehamilan Risiko Rendah';

            $this->pem_skor_skrining_jiwa = $encounter->skor_skrining_jiwa;
            $this->pem_kategori_skrining_jiwa = $encounter->kategori_skrining_jiwa ?: '-';

            $this->pem_lab_hb = $encounter->lab_hb !== null ? (string) $encounter->lab_hb : null;
            $this->pem_lab_hiv = $encounter->lab_hiv ?: 'Belum Diperiksa';
            $this->pem_lab_syphilis = $encounter->lab_syphilis ?: 'Belum Diperiksa';
            $this->pem_lab_hbsag = $encounter->lab_hbsag ?: 'Belum Diperiksa';
            $this->pem_lab_protein_urine = $encounter->lab_protein_urine ?: 'Negatif';
            $this->pem_lab_gda = $encounter->lab_gda;
            $this->pem_lab_reduksi_urine = $encounter->lab_reduksi_urine ?: 'Negatif';
            $this->pem_is_usg = $encounter->is_usg ? 1 : 0;
            $this->pem_masalah_kehamilan = $encounter->masalah_kehamilan ?: 'Tidak Ada';
            $this->pem_masalah_kehamilan_lainnya = $encounter->masalah_kehamilan_lainnya ?: '';

            $this->calculatePemVitalStats();
            $this->showPemeriksaanModal = true;
        } catch (\Throwable $e) {
            Log::error('Error in openEditPemeriksaanModal: ' . $e->getMessage(), ['id' => $encounterId]);
            $this->toast()->error('Data pemeriksaan kontrol tidak ditemukan.')->send();
        }
    }

    /**
     * Simpan pemeriksaan kontrol ANC (Baru atau Ubah).
     */
    public function savePemeriksaan(): void
    {
        $this->validate([
            'pem_encounter_date'      => 'required|date',
            'pem_next_encounter_date' => 'nullable|date',
            'pem_lila'                => 'required|numeric|min:10|max:80',
            'pem_bb'                 => 'required|numeric|min:20|max:250',
            'pem_tb'                 => 'required|numeric|min:50|max:250',
            'pem_td_sistolik'        => 'required|integer|min:40|max:300',
            'pem_td_diastolik'       => 'required|integer|min:30|max:200',
            'pem_tfu'                => 'required|numeric|min:0|max:60',
            'pem_djj'                => 'required|integer|min:0|max:240',
            'pem_skor_skrining_jiwa' => 'nullable|integer|min:1|max:20',
            'pem_lab_hb'             => 'nullable|numeric|min:1|max:30',
            'pem_lab_gda'            => 'nullable|integer|min:10|max:1000',
            'pem_lab_protein_urine'  => 'nullable|string|max:50',
            'pem_lab_reduksi_urine'  => 'nullable|string|max:50',
            'pem_lab_hiv'            => 'nullable|string|max:50',
            'pem_lab_syphilis'       => 'nullable|string|max:50',
            'pem_lab_hbsag'          => 'nullable|string|max:50',
        ], [
            'pem_encounter_date.required'     => 'Tanggal pemeriksaan wajib diisi.',
            'pem_encounter_date.date'         => 'Format tanggal pemeriksaan tidak valid.',
            'pem_next_encounter_date.date'    => 'Format tanggal kontrol selanjutnya tidak valid.',
            'pem_lila.required'               => 'LiLA wajib diisi.',
            'pem_lila.min'                    => 'LiLA minimal 10 cm.',
            'pem_lila.max'                    => 'LiLA maksimal 80 cm.',
            'pem_bb.required'                 => 'Berat badan (BB) wajib diisi.',
            'pem_bb.min'                      => 'Berat badan minimal 20 kg.',
            'pem_tb.required'                 => 'Tinggi badan (TB) wajib diisi.',
            'pem_tb.min'                      => 'Tinggi badan minimal 50 cm.',
            'pem_td_sistolik.required'        => 'Tekanan darah sistolik wajib diisi.',
            'pem_td_diastolik.required'       => 'Tekanan darah diastolik wajib diisi.',
            'pem_tfu.required'                => 'Tinggi Fundus Uteri (TFU) wajib diisi.',
            'pem_djj.required'                => 'Denyut Jantung Janin (DJJ) wajib diisi.',
            'pem_skor_skrining_jiwa.integer'  => 'Skor skrining jiwa harus berupa bilangan bulat.',
            'pem_skor_skrining_jiwa.min'      => 'Skor skrining jiwa minimal 1.',
            'pem_skor_skrining_jiwa.max'      => 'Skor skrining jiwa maksimal 20.',
        ]);

        $this->calculatePemVitalStats();
        $preg = $this->selectedPregnancy;

        if (!$preg) {
            $this->toast()->error('Data kehamilan tidak valid atau belum dipilih!')->send();
            return;
        }

        $data = [
            'encounter_date'            => $this->pem_encounter_date,
            'next_encounter_date'       => $this->pem_next_encounter_date ?: null,
            'lila'                      => (float) $this->pem_lila,
            'bb'                        => (float) $this->pem_bb,
            'tb'                        => (float) $this->pem_tb,
            'imt'                       => $this->pem_imt ? (float) $this->pem_imt : null,
            'td_sistolik'               => $this->pem_td_sistolik,
            'td_diastolik'              => $this->pem_td_diastolik,
            'map'                       => $this->pem_map ? (float) $this->pem_map : null,
            'tfu'                       => (float) $this->pem_tfu,
            'djj'                       => $this->pem_djj,
            'skor_puji_rochyati'        => $this->pem_skor_puji_rochyati,
            'faktor_risiko_json'        => $this->pem_faktor_risiko_selected,
            'skor_skrining_jiwa'        => ($this->pem_skor_skrining_jiwa !== null && $this->pem_skor_skrining_jiwa !== '') ? (int) $this->pem_skor_skrining_jiwa : null,
            'lab_hb'                    => $this->pem_lab_hb ? (float) $this->pem_lab_hb : null,
            'lab_hiv'                   => $this->pem_lab_hiv,
            'lab_syphilis'              => $this->pem_lab_syphilis,
            'lab_hbsag'                 => $this->pem_lab_hbsag,
            'lab_protein_urine'         => $this->pem_lab_protein_urine,
            'lab_gda'                   => $this->pem_lab_gda,
            'lab_reduksi_urine'         => $this->pem_lab_reduksi_urine,
            'is_usg'                    => $this->pem_is_usg,
            'masalah_kehamilan'         => $this->pem_masalah_kehamilan !== 'Tidak Ada' ? $this->pem_masalah_kehamilan : null,
            'masalah_kehamilan_lainnya' => $this->pem_masalah_kehamilan === 'Lainnya' ? $this->pem_masalah_kehamilan_lainnya : null,
        ];

        try {
            DB::transaction(function () use ($preg, $data) {
                if ($this->editingEncounterId) {
                    $encounter = Encounter::findOrFail($this->editingEncounterId);

                    if ($encounter->pregnancy_id !== $preg->id) {
                        throw new \DomainException('Kunjungan tidak sesuai dengan data kehamilan yang sedang diperiksa.');
                    }

                    if (!$encounter->canManage()) {
                        throw new \DomainException('Anda tidak memiliki hak akses untuk mengedit pemeriksaan kontrol ini.');
                    }

                    $data['updated_by'] = Auth::id();
                    unset($data['created_by']);
                    $encounter->update($data);
                } else {
                    if ($preg->status !== 'aktif') {
                        throw new \DomainException('Tidak dapat menambah pemeriksaan untuk kehamilan yang sudah tidak aktif.');
                    }

                    $data['patient_id'] = $preg->patient_id;
                    $data['pregnancy_id'] = $preg->id;
                    $data['created_by'] = Auth::id();
                    Encounter::create($data);
                }
            });

            $isEdit = (bool) $this->editingEncounterId;
            $this->showPemeriksaanModal = false;
            $this->editingEncounterId = null;
            $this->editingEncounterNumber = null;

            unset($this->selectedPregnancy);
            $this->dispatch('pregnancy-updated');

            if ($isEdit) {
                $this->toast()->success('Data pemeriksaan kontrol berhasil diperbarui!')->send();
            } else {
                $this->toast()->success('Pemeriksaan berhasil dicatat! Hasil langsung diperbarui di Kantong Persalinan.')->send();
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error in savePemeriksaan: ' . $e->getMessage());
            $this->toast()->error('Terjadi kesalahan saat menyimpan pemeriksaan: ' . $e->getMessage())->send();
        }
    }

    /**
     * Menghapus data transaksi pemeriksaan kontrol ANC.
     */
    public function deleteEncounter(int $encounterId): void
    {
        try {
            $encounter = Encounter::findOrFail($encounterId);

            if (!$encounter->canManage()) {
                $this->toast()->error('Anda tidak memiliki hak akses untuk menghapus kunjungan ini.')->send();
                return;
            }

            DB::transaction(function () use ($encounter) {
                $encounter->delete();
            });

            unset($this->selectedPregnancy);
            $this->dispatch('pregnancy-updated');
            $this->toast()->success('Data kunjungan kontrol berhasil dihapus.')->send();
        } catch (\Throwable $e) {
            Log::error('Error in deleteEncounter: ' . $e->getMessage(), ['id' => $encounterId]);
            $this->toast()->error('Terjadi kesalahan saat menghapus data kunjungan: ' . $e->getMessage())->send();
        }
    }
};
