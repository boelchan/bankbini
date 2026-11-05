<?php

use App\Models\Encounter;
use App\Models\PostnatalEncounter;
use App\Models\Pregnancy;
use App\Models\PregnancyHistory;
use App\Services\LaporanService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Laporan LB3 Maternal')]
class extends Component {
    public int $bulan;
    public int $tahun;

    public function mount(): void
    {
        $this->bulan = (int) date('n');
        $this->tahun = (int) date('Y');
    }

    /**
     * Memuat ulang data rekapitulasi LB3 Maternal.
     */
    public function refresh(): void
    {
        unset($this->rows, $this->totals);
    }

    #[Computed]
    public function monthOptions(): array
    {
        return LaporanService::getMonthOptions();
    }

    #[Computed]
    public function yearOptions(): array
    {
        return LaporanService::getYearOptions();
    }

    #[Computed]
    public function selectedMonthLabel(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        return ($months[$this->bulan] ?? '') . ' ' . $this->tahun;
    }

    #[Computed]
    public function rows(): array
    {
        $villages = LaporanService::getVillages();

        // 1. Data Kunjungan ANC pada bulan & tahun ini
        $encounters = Encounter::with(['pregnancy.patient', 'pregnancy.encounters', 'creator.roles'])
            ->whereMonth('encounter_date', $this->bulan)
            ->whereYear('encounter_date', $this->tahun)
            ->get();

        // 2. Data Persalinan & Resolusi pada bulan & tahun ini (dari PregnancyHistory)
        $persalinans = PregnancyHistory::with('patient')
            ->whereMonth('tanggal_persalinan', $this->bulan)
            ->whereYear('tanggal_persalinan', $this->tahun)
            ->get();

        // 3. Data Nifas pada bulan & tahun ini
        $postnatals = PostnatalEncounter::with(['pregnancy.patient'])
            ->whereMonth('encounter_date', $this->bulan)
            ->whereYear('encounter_date', $this->tahun)
            ->get();

        $data = [];
        foreach ($villages as $v) {
            $data[$v->code] = [
                'village_code' => $v->code,
                'village_name' => $v->name,
                // Capaian ANC
                'k1_akses' => 0,
                'k4' => 0,
                // 4 Terlalu (4T)
                't_muda' => 0,      // <= 16 th
                't_tua' => 0,       // >= 35 th
                't_cepat' => 0,     // <= 2 th
                't_banyak' => 0,    // anak >= 4
                // Lab HB K1
                'hb_k1_periksa' => 0,
                'hb_k1_anemia_ringan' => 0, // 8 - 11 g/dL
                'hb_k1_anemia_berat' => 0,  // < 8 g/dL
                // Lab HB K4
                'hb_k4_anemia_ringan' => 0,
                'hb_k4_anemia_berat' => 0,
                // Pemberian FE (5 Indikator Sesuai Spesifikasi MD)
                'fe1' => 0,
                'fe2' => 0,
                'fe3' => 0,
                'fe_kurang_90' => 0,
                'fe_lebih_90' => 0,
                // KEK (LILA K1)
                'kek_periksa' => 0,
                'kek_lila_kurang' => 0, // LILA < 23.5 cm
                // Lab Terpadu
                'protein_urin_periksa' => 0,
                'protein_urin_positif' => 0,
                'gd_periksa' => 0,
                'gd_tinggi' => 0, // > 140 mg/dL
                'hiv_periksa' => 0,
                'hiv_positif' => 0,
                'hbsag_periksa' => 0,
                'hbsag_positif' => 0,
                'sipilis_periksa' => 0,
                'sipilis_positif' => 0,
                // Kasus Maternal (15 Kategori Sesuai Dokumen Spesifikasi MD)
                'hiperemesis' => 0,
                'abortus' => 0,
                'preeklamsia' => 0,
                'perdarahan_kehamilan' => 0,
                'perdarahan_persalinan' => 0,
                'perdarahan_nifas' => 0,
                'partus_lama' => 0,
                'infeksi' => 0,
                'tb' => 0,
                'malaria' => 0,
                'jantung' => 0,
                'dm' => 0,
                'covid19' => 0,
                'obesitas' => 0,
                'lainnya' => 0,
                // Jumlah Komplikasi Per Tahapan (Tanpa 'Lainnya')
                'komplikasi_kehamilan' => 0,
                'komplikasi_persalinan' => 0,
                'komplikasi_nifas' => 0,
                // Jenis Persalinan (5 Kategori Sesuai MD)
                'partus_normal' => 0,
                'partus_sc' => 0,
                'partus_drip' => 0,
                'partus_curatage' => 0,
                'partus_plasenta_manual' => 0,
                // Penolong Persalinan
                'partus_dukun' => 0,
                // Tempat Persalinan (7 Fasilitas Sesuai MD)
                'tempat_polindes' => 0,
                'tempat_rs' => 0,
                'tempat_puskesmas_poned' => 0,
                'tempat_puskesmas_non_poned' => 0,
                'tempat_klinik' => 0,
                'tempat_pmb' => 0,
                'tempat_rumah' => 0,
            ];
        }

        // Tracking deduplikasi per kasus per pasien di bulan ini
        $countedAncKasus = [];
        $countedPersalinanKasus = [];
        $countedNifasKasus = [];
        $countedFe2 = [];
        $countedK4 = [];
        $countedAbortusPatients = [];
        $countedKompKehamilan = [];
        $countedKompPersalinan = [];
        $countedKompNifas = [];

        // 1. Olah Data Kunjungan ANC
        foreach ($encounters as $enc) {
            $vCode = $enc->pregnancy?->patient?->village_code;
            if (!$vCode || !isset($data[$vCode])) {
                continue;
            }

            $minggu = $enc->usia_kehamilan_minggu;
            $kunjunganKe = $enc->kunjungan_ke;
            $isK1 = ($kunjunganKe === 1);
            $isK4 = (bool) $enc->is_k4;

            if ($isK1) {
                $data[$vCode]['k1_akses']++;
                $data[$vCode]['fe1']++;

                // 4T murni dari checklist faktor risiko Poedji Rochyati yang dipilih (tanpa rekomendasi/asumsi nilai)
                $frList = is_array($enc->faktor_risiko_json) ? $enc->faktor_risiko_json : [];

                // Terlalu Muda (<= 16 tahun)
                if (in_array('terlalu_muda_hamil_1', $frList)) {
                    $data[$vCode]['t_muda']++;
                }
                // Terlalu Tua (>= 35 tahun)
                if (in_array('terlalu_tua_hamil_1', $frList) || in_array('terlalu_tua_umur', $frList)) {
                    $data[$vCode]['t_tua']++;
                }
                // Terlalu Cepat (jarak kehamilan <= 2 tahun)
                if (in_array('terlalu_cepat_hamil_lagi', $frList)) {
                    $data[$vCode]['t_cepat']++;
                }
                // Terlalu Banyak (anak >= 4)
                if (in_array('anak_banyak', $frList)) {
                    $data[$vCode]['t_banyak']++;
                }

                // HB K1
                if ($enc->lab_hb !== null && (float) $enc->lab_hb > 0) {
                    $data[$vCode]['hb_k1_periksa']++;
                    $hb = (float) $enc->lab_hb;
                    if ($hb < 8.0) {
                        $data[$vCode]['hb_k1_anemia_berat']++;
                    } elseif ($hb < 11.0) {
                        $data[$vCode]['hb_k1_anemia_ringan']++;
                    }
                }

                // LILA K1 & KEK (< 23.5 cm)
                if ($enc->lila !== null && (float) $enc->lila > 0) {
                    $data[$vCode]['kek_periksa']++;
                    if ((float) $enc->lila < 23.5) {
                        $data[$vCode]['kek_lila_kurang']++;
                    }
                }
            }

            // Kunjungan Pertama di TM2 (12-24 mgg): FE-2 (menggunakan accessor is_first_tm2)
            if ($enc->is_first_tm2) {
                $pregId = $enc->pregnancy_id;
                if (!isset($countedFe2[$vCode][$pregId])) {
                    $countedFe2[$vCode][$pregId] = true;
                    $data[$vCode]['fe2']++;
                }
            }

            // Kunjungan K4 (25-28 mgg)
            if ($isK4) {
                $pregId = $enc->pregnancy_id;
                if (!isset($countedK4[$vCode][$pregId])) {
                    $countedK4[$vCode][$pregId] = true;
                    $data[$vCode]['k4']++;
                    $data[$vCode]['fe3']++;
                }

                if ($enc->lab_hb !== null && (float) $enc->lab_hb > 0) {
                    $hb = (float) $enc->lab_hb;
                    if ($hb < 8.0) {
                        $data[$vCode]['hb_k4_anemia_berat']++;
                    } elseif ($hb < 11.0) {
                        $data[$vCode]['hb_k4_anemia_ringan']++;
                    }
                }
            }

            // Pemeriksaan Laboratorium Terpadu
            if ($enc->lab_protein_urine) {
                $data[$vCode]['protein_urin_periksa']++;
                if (!str_contains(strtolower($enc->lab_protein_urine), 'negatif')) {
                    $data[$vCode]['protein_urin_positif']++;
                }
            }
            if ($enc->lab_gda !== null && (int) $enc->lab_gda > 0) {
                $data[$vCode]['gd_periksa']++;
                if ((int) $enc->lab_gda > 140) {
                    $data[$vCode]['gd_tinggi']++;
                }
            }
            if ($enc->lab_hiv && $enc->lab_hiv !== 'Belum Diperiksa') {
                $data[$vCode]['hiv_periksa']++;
                if (!str_contains(strtolower($enc->lab_hiv), 'non') && !str_contains(strtolower($enc->lab_hiv), 'negatif')) {
                    $data[$vCode]['hiv_positif']++;
                }
            }
            if ($enc->lab_hbsag && $enc->lab_hbsag !== 'Belum Diperiksa') {
                $data[$vCode]['hbsag_periksa']++;
                if (!str_contains(strtolower($enc->lab_hbsag), 'non') && !str_contains(strtolower($enc->lab_hbsag), 'negatif')) {
                    $data[$vCode]['hbsag_positif']++;
                }
            }
            if ($enc->lab_syphilis && $enc->lab_syphilis !== 'Belum Diperiksa') {
                $data[$vCode]['sipilis_periksa']++;
                if (!str_contains(strtolower($enc->lab_syphilis), 'non') && !str_contains(strtolower($enc->lab_syphilis), 'negatif')) {
                    $data[$vCode]['sipilis_positif']++;
                }
            }

            // Kasus Maternal Saat Kehamilan
            $mk = strtolower($enc->masalah_kehamilan ?? '');
            if ($mk === 'lainnya' && !empty($enc->masalah_kehamilan_lainnya)) {
                $mk .= ' ' . strtolower($enc->masalah_kehamilan_lainnya);
            }

            if (!empty($mk) && !str_contains($mk, 'tidak ada')) {
                $patId = $enc->pregnancy?->patient_id ?? $enc->pregnancy_id ?? $enc->id;

                if ((str_contains($mk, 'hiper') || str_contains($mk, 'emisis')) && !isset($countedAncKasus[$vCode][$patId]['hiperemesis'])) {
                    $countedAncKasus[$vCode][$patId]['hiperemesis'] = true;
                    $data[$vCode]['hiperemesis']++;
                }
                if ((str_contains($mk, 'preeklamsi') || str_contains($mk, 'eklamsi')) && !isset($countedAncKasus[$vCode][$patId]['preeklamsia'])) {
                    $countedAncKasus[$vCode][$patId]['preeklamsia'] = true;
                    $data[$vCode]['preeklamsia']++;
                }
                if (str_contains($mk, 'darah') && !isset($countedAncKasus[$vCode][$patId]['perdarahan_kehamilan'])) {
                    $countedAncKasus[$vCode][$patId]['perdarahan_kehamilan'] = true;
                    $data[$vCode]['perdarahan_kehamilan']++;
                }
                if (str_contains($mk, 'infeksi') && !isset($countedAncKasus[$vCode][$patId]['infeksi'])) {
                    $countedAncKasus[$vCode][$patId]['infeksi'] = true;
                    $data[$vCode]['infeksi']++;
                }
                if (str_contains($mk, 'tb') && !isset($countedAncKasus[$vCode][$patId]['tb'])) {
                    $countedAncKasus[$vCode][$patId]['tb'] = true;
                    $data[$vCode]['tb']++;
                }
                if (str_contains($mk, 'malaria') && !isset($countedAncKasus[$vCode][$patId]['malaria'])) {
                    $countedAncKasus[$vCode][$patId]['malaria'] = true;
                    $data[$vCode]['malaria']++;
                }
                if (str_contains($mk, 'jantung') && !isset($countedAncKasus[$vCode][$patId]['jantung'])) {
                    $countedAncKasus[$vCode][$patId]['jantung'] = true;
                    $data[$vCode]['jantung']++;
                }
                if ((str_contains($mk, 'dm') || str_contains($mk, 'diabetes')) && !isset($countedAncKasus[$vCode][$patId]['dm'])) {
                    $countedAncKasus[$vCode][$patId]['dm'] = true;
                    $data[$vCode]['dm']++;
                }
                if (str_contains($mk, 'covid') && !isset($countedAncKasus[$vCode][$patId]['covid19'])) {
                    $countedAncKasus[$vCode][$patId]['covid19'] = true;
                    $data[$vCode]['covid19']++;
                }
                if (str_contains($mk, 'obesitas') && !isset($countedAncKasus[$vCode][$patId]['obesitas'])) {
                    $countedAncKasus[$vCode][$patId]['obesitas'] = true;
                    $data[$vCode]['obesitas']++;
                }

                $matchedAnc = (str_contains($mk, 'hiper') || str_contains($mk, 'emisis'))
                    || (str_contains($mk, 'preeklamsi') || str_contains($mk, 'eklamsi'))
                    || str_contains($mk, 'darah')
                    || str_contains($mk, 'infeksi')
                    || str_contains($mk, 'tb')
                    || str_contains($mk, 'malaria')
                    || str_contains($mk, 'jantung')
                    || (str_contains($mk, 'dm') || str_contains($mk, 'diabetes'))
                    || str_contains($mk, 'covid')
                    || str_contains($mk, 'obesitas');

                if (!$matchedAnc && !isset($countedAncKasus[$vCode][$patId]['lainnya'])) {
                    $countedAncKasus[$vCode][$patId]['lainnya'] = true;
                    $data[$vCode]['lainnya']++;
                }

                // Jumlah komplikasi dalam kehamilan (yang 'Lainnya' tidak dihitung)
                $rawMk = trim((string) ($enc->masalah_kehamilan ?? ''));
                if (!empty($rawMk) && strcasecmp($rawMk, 'tidak ada') !== 0 && strcasecmp($rawMk, 'lainnya') !== 0) {
                    $ancKey = $patId . '_' . strtolower($rawMk);
                    if (!isset($countedKompKehamilan[$vCode][$ancKey])) {
                        $countedKompKehamilan[$vCode][$ancKey] = true;
                        $data[$vCode]['komplikasi_kehamilan']++;
                    }
                }
            }
        }

        // 2. Olah Data Persalinan & Kasus Abortus (dari PregnancyHistory pada bulan & tahun ini)
        foreach ($persalinans as $p) {
            $vCode = $p->patient?->village_code;
            if (!$vCode || !isset($data[$vCode])) {
                continue;
            }

            $pStatus = strtolower(trim($p->status ?? ''));
            $cara = strtolower(trim($p->cara_melahirkan ?? ''));
            $isAbortus = ($pStatus === 'abortus');
            $isMeninggal = ($pStatus === 'meninggal');

            // Kasus Abortus: diambil dari bumil yang selesai dengan status abortus pada bulan dan tahun ini
            if ($isAbortus) {
                $patId = $p->patient_id ?? $p->id;
                if (!isset($countedAbortusPatients[$vCode][$patId])) {
                    $countedAbortusPatients[$vCode][$patId] = true;
                    $data[$vCode]['abortus']++;
                    $data[$vCode]['komplikasi_persalinan']++;
                }
                continue; // Cegah abortus masuk ke persalinan normal/sc/tempat persalinan
            }

            if ($isMeninggal) {
                continue;
            }

            // Jenis Persalinan sesuai PartusTypeEnum & dokumen MD (jika tidak terisi/kosong tetap 0, tanpa asumsi)
            if (str_contains($cara, 'sc') || str_contains($cara, 'caesar')) {
                $data[$vCode]['partus_sc']++;
            } elseif (str_contains($cara, 'drip') || str_contains($cara, 'induksi')) {
                $data[$vCode]['partus_drip']++;
            } elseif (str_contains($cara, 'curatage') || str_contains($cara, 'kuret')) {
                $data[$vCode]['partus_curatage']++;
            } elseif (str_contains($cara, 'manual') || str_contains($cara, 'plasenta manual')) {
                $data[$vCode]['partus_plasenta_manual']++;
            } elseif (str_contains($cara, 'normal') || str_contains($cara, 'spontan')) {
                $data[$vCode]['partus_normal']++;
            }

            // Penolong Persalinan
            $penolong = strtolower($p->penolong ?? '');
            if (str_contains($penolong, 'dukun')) {
                $data[$vCode]['partus_dukun']++;
            }

            // Tempat Persalinan (7 Fasilitas Sesuai MD & DeliveryFacilityEnum)
            $tempat = strtolower($p->tempat_persalinan ?? '');
            if (str_contains($tempat, 'polindes') || str_contains($tempat, 'poskesdes')) {
                $data[$vCode]['tempat_polindes']++;
            } elseif (str_contains($tempat, 'rs') || str_contains($tempat, 'rumah sakit')) {
                $data[$vCode]['tempat_rs']++;
            } elseif (str_contains($tempat, 'non poned') || str_contains($tempat, 'non-poned') || str_contains($tempat, 'non_poned')) {
                $data[$vCode]['tempat_puskesmas_non_poned']++;
            } elseif (str_contains($tempat, 'poned') || str_contains($tempat, 'puskesmas')) {
                $data[$vCode]['tempat_puskesmas_poned']++;
            } elseif (str_contains($tempat, 'klinik')) {
                $data[$vCode]['tempat_klinik']++;
            } elseif (str_contains($tempat, 'pmb') || str_contains($tempat, 'bidan') || str_contains($tempat, 'bpm')) {
                $data[$vCode]['tempat_pmb']++;
            } elseif (str_contains($tempat, 'rumah')) {
                $data[$vCode]['tempat_rumah']++;
            }

            // Masalah Persalinan (Kasus Maternal & Komplikasi Persalinan)
            $mp = strtolower($p->masalah_persalinan ?? '');
            if ($mp === 'lainnya' && !empty($p->masalah_persalinan_lainnya)) {
                $mp .= ' ' . strtolower($p->masalah_persalinan_lainnya);
            } elseif (str_contains($mp, '|')) {
                $parts = explode('|', $mp);
                $mp = strtolower(trim(end($parts)));
            } elseif (str_starts_with($mp, 'kondisi:')) {
                $mp = ''; // Bukan komplikasi, hanya keterangan kondisi bayi normal
            }

            if (!empty($mp) && !str_contains($mp, 'tidak ada')) {
                $patId = $p->patient_id ?? $p->id;

                if ((str_contains($mp, 'preeklamsi') || str_contains($mp, 'eklamsi')) && !isset($countedPersalinanKasus[$vCode][$patId]['preeklamsia'])) {
                    $countedPersalinanKasus[$vCode][$patId]['preeklamsia'] = true;
                    $data[$vCode]['preeklamsia']++;
                }
                if (str_contains($mp, 'darah') && !isset($countedPersalinanKasus[$vCode][$patId]['perdarahan_persalinan'])) {
                    $countedPersalinanKasus[$vCode][$patId]['perdarahan_persalinan'] = true;
                    $data[$vCode]['perdarahan_persalinan']++;
                }
                if ((str_contains($mp, 'partus lama') || str_contains($mp, 'lama') || str_contains($mp, 'prolong') || str_contains($mp, 'macet')) && !isset($countedPersalinanKasus[$vCode][$patId]['partus_lama'])) {
                    $countedPersalinanKasus[$vCode][$patId]['partus_lama'] = true;
                    $data[$vCode]['partus_lama']++;
                }
                if (str_contains($mp, 'infeksi') && !isset($countedPersalinanKasus[$vCode][$patId]['infeksi'])) {
                    $countedPersalinanKasus[$vCode][$patId]['infeksi'] = true;
                    $data[$vCode]['infeksi']++;
                }
                if (str_contains($mp, 'tb') && !isset($countedPersalinanKasus[$vCode][$patId]['tb'])) {
                    $countedPersalinanKasus[$vCode][$patId]['tb'] = true;
                    $data[$vCode]['tb']++;
                }
                if (str_contains($mp, 'malaria') && !isset($countedPersalinanKasus[$vCode][$patId]['malaria'])) {
                    $countedPersalinanKasus[$vCode][$patId]['malaria'] = true;
                    $data[$vCode]['malaria']++;
                }
                if (str_contains($mp, 'jantung') && !isset($countedPersalinanKasus[$vCode][$patId]['jantung'])) {
                    $countedPersalinanKasus[$vCode][$patId]['jantung'] = true;
                    $data[$vCode]['jantung']++;
                }
                if ((str_contains($mp, 'dm') || str_contains($mp, 'diabetes')) && !isset($countedPersalinanKasus[$vCode][$patId]['dm'])) {
                    $countedPersalinanKasus[$vCode][$patId]['dm'] = true;
                    $data[$vCode]['dm']++;
                }
                if (str_contains($mp, 'covid') && !isset($countedPersalinanKasus[$vCode][$patId]['covid19'])) {
                    $countedPersalinanKasus[$vCode][$patId]['covid19'] = true;
                    $data[$vCode]['covid19']++;
                }
                if (str_contains($mp, 'obesitas') && !isset($countedPersalinanKasus[$vCode][$patId]['obesitas'])) {
                    $countedPersalinanKasus[$vCode][$patId]['obesitas'] = true;
                    $data[$vCode]['obesitas']++;
                }

                $matchedPersalinan = (str_contains($mp, 'preeklamsi') || str_contains($mp, 'eklamsi'))
                    || str_contains($mp, 'darah')
                    || (str_contains($mp, 'partus lama') || str_contains($mp, 'lama') || str_contains($mp, 'prolong') || str_contains($mp, 'macet'))
                    || str_contains($mp, 'infeksi')
                    || str_contains($mp, 'tb')
                    || str_contains($mp, 'malaria')
                    || str_contains($mp, 'jantung')
                    || (str_contains($mp, 'dm') || str_contains($mp, 'diabetes'))
                    || str_contains($mp, 'covid')
                    || str_contains($mp, 'obesitas');

                if (!$matchedPersalinan && !isset($countedPersalinanKasus[$vCode][$patId]['lainnya'])) {
                    $countedPersalinanKasus[$vCode][$patId]['lainnya'] = true;
                    $data[$vCode]['lainnya']++;
                }

                // Jumlah komplikasi dalam persalinan (yang 'Lainnya' tidak dihitung)
                $rawMp = trim((string) ($p->masalah_persalinan ?? ''));
                if (!empty($rawMp) && strcasecmp($rawMp, 'tidak ada') !== 0 && strcasecmp($rawMp, 'lainnya') !== 0) {
                    $partusKey = $patId . '_' . strtolower($rawMp);
                    if (!isset($countedKompPersalinan[$vCode][$partusKey])) {
                        $countedKompPersalinan[$vCode][$partusKey] = true;
                        $data[$vCode]['komplikasi_persalinan']++;
                    }
                }
            }
        }

        // 3. Olah Data Kunjungan Nifas
        foreach ($postnatals as $post) {
            $vCode = $post->pregnancy?->patient?->village_code;
            if (!$vCode || !isset($data[$vCode])) {
                continue;
            }

            // Masalah Nifas (Kasus Maternal)
            $mn = strtolower($post->masalah_nifas ?? '');
            if ($mn === 'lainnya' && !empty($post->masalah_nifas_lainnya)) {
                $mn .= ' ' . strtolower($post->masalah_nifas_lainnya);
            }

            if (!empty($mn) && !str_contains($mn, 'tidak ada')) {
                $patId = $post->pregnancy?->patient_id ?? $post->pregnancy_id ?? $post->id;

                if ((str_contains($mn, 'preeklamsi') || str_contains($mn, 'eklamsi')) && !isset($countedNifasKasus[$vCode][$patId]['preeklamsia'])) {
                    $countedNifasKasus[$vCode][$patId]['preeklamsia'] = true;
                    $data[$vCode]['preeklamsia']++;
                }
                if (str_contains($mn, 'darah') && !isset($countedNifasKasus[$vCode][$patId]['perdarahan_nifas'])) {
                    $countedNifasKasus[$vCode][$patId]['perdarahan_nifas'] = true;
                    $data[$vCode]['perdarahan_nifas']++;
                }
                if (str_contains($mn, 'infeksi') && !isset($countedNifasKasus[$vCode][$patId]['infeksi'])) {
                    $countedNifasKasus[$vCode][$patId]['infeksi'] = true;
                    $data[$vCode]['infeksi']++;
                }
                if (str_contains($mn, 'tb') && !isset($countedNifasKasus[$vCode][$patId]['tb'])) {
                    $countedNifasKasus[$vCode][$patId]['tb'] = true;
                    $data[$vCode]['tb']++;
                }
                if (str_contains($mn, 'malaria') && !isset($countedNifasKasus[$vCode][$patId]['malaria'])) {
                    $countedNifasKasus[$vCode][$patId]['malaria'] = true;
                    $data[$vCode]['malaria']++;
                }
                if (str_contains($mn, 'jantung') && !isset($countedNifasKasus[$vCode][$patId]['jantung'])) {
                    $countedNifasKasus[$vCode][$patId]['jantung'] = true;
                    $data[$vCode]['jantung']++;
                }
                if ((str_contains($mn, 'dm') || str_contains($mn, 'diabetes')) && !isset($countedNifasKasus[$vCode][$patId]['dm'])) {
                    $countedNifasKasus[$vCode][$patId]['dm'] = true;
                    $data[$vCode]['dm']++;
                }
                if (str_contains($mn, 'covid') && !isset($countedNifasKasus[$vCode][$patId]['covid19'])) {
                    $countedNifasKasus[$vCode][$patId]['covid19'] = true;
                    $data[$vCode]['covid19']++;
                }
                if (str_contains($mn, 'obesitas') && !isset($countedNifasKasus[$vCode][$patId]['obesitas'])) {
                    $countedNifasKasus[$vCode][$patId]['obesitas'] = true;
                    $data[$vCode]['obesitas']++;
                }

                $matchedNifas = (str_contains($mn, 'preeklamsi') || str_contains($mn, 'eklamsi'))
                    || str_contains($mn, 'darah')
                    || str_contains($mn, 'infeksi')
                    || str_contains($mn, 'tb')
                    || str_contains($mn, 'malaria')
                    || str_contains($mn, 'jantung')
                    || (str_contains($mn, 'dm') || str_contains($mn, 'diabetes'))
                    || str_contains($mn, 'covid')
                    || str_contains($mn, 'obesitas');

                if (!$matchedNifas && !isset($countedNifasKasus[$vCode][$patId]['lainnya'])) {
                    $countedNifasKasus[$vCode][$patId]['lainnya'] = true;
                    $data[$vCode]['lainnya']++;
                }

                // Jumlah komplikasi dalam nifas (yang 'Lainnya' tidak dihitung)
                $rawMn = trim((string) ($post->masalah_nifas ?? ''));
                if (!empty($rawMn) && strcasecmp($rawMn, 'tidak ada') !== 0 && strcasecmp($rawMn, 'lainnya') !== 0) {
                    $nifasKey = $patId . '_' . strtolower($rawMn);
                    if (!isset($countedKompNifas[$vCode][$nifasKey])) {
                        $countedKompNifas[$vCode][$nifasKey] = true;
                        $data[$vCode]['komplikasi_nifas']++;
                    }
                }
            }
        }

        // Sinkronisasi nilai turunan FE sesuai spesifikasi dokumen MD: FE < 90 (= FE-2) dan FE > 90 (= FE-3)
        foreach ($villages as $v) {
            $data[$v->code]['fe_kurang_90'] = $data[$v->code]['fe2'];
            $data[$v->code]['fe_lebih_90'] = $data[$v->code]['fe3'];
        }

        return array_values($data);
    }

    #[Computed]
    public function totals(): array
    {
        $rows = $this->rows;
        if (empty($rows)) {
            return [];
        }

        $keys = array_keys($rows[0]);
        $totals = [];
        foreach ($keys as $k) {
            if ($k === 'village_code' || $k === 'village_name') continue;
            $totals[$k] = array_sum(array_column($rows, $k));
        }

        return $totals;
    }

    /**
     * Export data rekapitulasi LB3 Maternal ke Excel menggunakan spatie/simple-excel.
     */
    public function exportExcel()
    {
        $fileName = 'LB3_MATERNAL_' . str_replace(' ', '_', $this->selectedMonthLabel) . '.xlsx';
        $writer = \Spatie\SimpleExcel\SimpleExcelWriter::streamDownload($fileName);

        $no = 1;
        foreach ($this->rows as $r) {
            $writer->addRow([
                'No' => $no++,
                'Desa / Kelurahan' => $r['village_name'],
                'Capaian ANC (K1 Akses)' => $r['k1_akses'],
                'Capaian ANC (K4)' => $r['k4'],
                '4T (<=16 Th)' => $r['t_muda'],
                '4T (>=35 Th)' => $r['t_tua'],
                '4T (<=2 Th)' => $r['t_cepat'],
                '4T (>3 Anak)' => $r['t_banyak'],
                'Hb K1 (Periksa)' => $r['hb_k1_periksa'],
                'Hb K1 (8-11)' => $r['hb_k1_anemia_ringan'],
                'Hb K1 (<8)' => $r['hb_k1_anemia_berat'],
                'Hb K4 (8-11)' => $r['hb_k4_anemia_ringan'],
                'Hb K4 (<8)' => $r['hb_k4_anemia_berat'],
                'Pemberian Fe (Fe-1)' => $r['fe1'],
                'Pemberian Fe (Fe-2)' => $r['fe2'],
                'Pemberian Fe (Fe-3)' => $r['fe3'],
                'Pemberian Fe (Fe < 90)' => $r['fe_kurang_90'],
                'Pemberian Fe (Fe > 90)' => $r['fe_lebih_90'],
                'KEK LILA (Periksa)' => $r['kek_periksa'],
                'KEK LILA (<23.5)' => $r['kek_lila_kurang'],
                'Protein Urin (Periksa)' => $r['protein_urin_periksa'],
                'Protein Urin (+)' => $r['protein_urin_positif'],
                'Gula Darah (Periksa)' => $r['gd_periksa'],
                'Gula Darah (>140)' => $r['gd_tinggi'],
                'HIV (Periksa)' => $r['hiv_periksa'],
                'HIV (+)' => $r['hiv_positif'],
                'HBsAg (Periksa)' => $r['hbsag_periksa'],
                'HBsAg (+)' => $r['hbsag_positif'],
                'Sifilis (Periksa)' => $r['sipilis_periksa'],
                'Sifilis (+)' => $r['sipilis_positif'],
                'Kasus Maternal (Hiper.)' => $r['hiperemesis'] ?? 0,
                'Kasus Maternal (Abortus)' => $r['abortus'] ?? 0,
                'Kasus Maternal (PE/Ekl.)' => $r['preeklamsia'] ?? 0,
                'Kasus Maternal (Perd. Hamil)' => $r['perdarahan_kehamilan'] ?? 0,
                'Kasus Maternal (Perd. Salin)' => $r['perdarahan_persalinan'] ?? 0,
                'Kasus Maternal (Perd. Nifas)' => $r['perdarahan_nifas'] ?? 0,
                'Kasus Maternal (Partus Lama)' => $r['partus_lama'] ?? 0,
                'Kasus Maternal (Infeksi)' => $r['infeksi'] ?? 0,
                'Kasus Maternal (TB)' => $r['tb'] ?? 0,
                'Kasus Maternal (Malaria)' => $r['malaria'] ?? 0,
                'Kasus Maternal (Jantung)' => $r['jantung'] ?? 0,
                'Kasus Maternal (DM)' => $r['dm'] ?? 0,
                'Kasus Maternal (Covid-19)' => $r['covid19'] ?? 0,
                'Kasus Maternal (Obesitas)' => $r['obesitas'] ?? 0,
                'Kasus Maternal (Lainnya)' => $r['lainnya'] ?? 0,
                'Jml Komplikasi (Hamil)' => $r['komplikasi_kehamilan'] ?? 0,
                'Jml Komplikasi (Persalinan)' => $r['komplikasi_persalinan'] ?? 0,
                'Jml Komplikasi (Nifas)' => $r['komplikasi_nifas'] ?? 0,
                'Jenis Persalinan (Normal)' => $r['partus_normal'],
                'Jenis Persalinan (SC)' => $r['partus_sc'],
                'Jenis Persalinan (Tindakan Drip)' => $r['partus_drip'] ?? 0,
                'Jenis Persalinan (Tindakan Curatage)' => $r['partus_curatage'] ?? 0,
                'Jenis Persalinan (Tindakan Manual)' => $r['partus_plasenta_manual'] ?? 0,
                'Jenis Persalinan (Dukun)' => $r['partus_dukun'] ?? 0,
                'Tempat Persalinan (Polindes)' => $r['tempat_polindes'],
                'Tempat Persalinan (RS)' => $r['tempat_rs'],
                'Tempat Persalinan (Pusk. PONED)' => $r['tempat_puskesmas_poned'],
                'Tempat Persalinan (Pusk. Non PONED)' => $r['tempat_puskesmas_non_poned'],
                'Tempat Persalinan (Klinik)' => $r['tempat_klinik'] ?? 0,
                'Tempat Persalinan (PMB)' => $r['tempat_pmb'],
                'Tempat Persalinan (Rumah)' => $r['tempat_rumah'],
            ]);
        }

        // Baris Total
        $totalRow = [
            'No' => '',
            'Desa / Kelurahan' => 'TOTAL PUSKESMAS',
            'Capaian ANC (K1 Akses)' => $this->totals['k1_akses'] ?? 0,
            'Capaian ANC (K4)' => $this->totals['k4'] ?? 0,
            '4T (<=16 Th)' => $this->totals['t_muda'] ?? 0,
            '4T (>=35 Th)' => $this->totals['t_tua'] ?? 0,
            '4T (<=2 Th)' => $this->totals['t_cepat'] ?? 0,
            '4T (>3 Anak)' => $this->totals['t_banyak'] ?? 0,
            'Hb K1 (Periksa)' => $this->totals['hb_k1_periksa'] ?? 0,
            'Hb K1 (8-11)' => $this->totals['hb_k1_anemia_ringan'] ?? 0,
            'Hb K1 (<8)' => $this->totals['hb_k1_anemia_berat'] ?? 0,
            'Hb K4 (8-11)' => $this->totals['hb_k4_anemia_ringan'] ?? 0,
            'Hb K4 (<8)' => $this->totals['hb_k4_anemia_berat'] ?? 0,
            'Pemberian Fe (Fe-1)' => $this->totals['fe1'] ?? 0,
            'Pemberian Fe (Fe-2)' => $this->totals['fe2'] ?? 0,
            'Pemberian Fe (Fe-3)' => $this->totals['fe3'] ?? 0,
            'Pemberian Fe (Fe < 90)' => $this->totals['fe_kurang_90'] ?? 0,
            'Pemberian Fe (Fe > 90)' => $this->totals['fe_lebih_90'] ?? 0,
            'KEK LILA (Periksa)' => $this->totals['kek_periksa'] ?? 0,
            'KEK LILA (<23.5)' => $this->totals['kek_lila_kurang'] ?? 0,
            'Protein Urin (Periksa)' => $this->totals['protein_urin_periksa'] ?? 0,
            'Protein Urin (+)' => $this->totals['protein_urin_positif'] ?? 0,
            'Gula Darah (Periksa)' => $this->totals['gd_periksa'] ?? 0,
            'Gula Darah (>140)' => $this->totals['gd_tinggi'] ?? 0,
            'HIV (Periksa)' => $this->totals['hiv_periksa'] ?? 0,
            'HIV (+)' => $this->totals['hiv_positif'] ?? 0,
            'HBsAg (Periksa)' => $this->totals['hbsag_periksa'] ?? 0,
            'HBsAg (+)' => $this->totals['hbsag_positif'] ?? 0,
            'Sifilis (Periksa)' => $this->totals['sipilis_periksa'] ?? 0,
            'Sifilis (+)' => $this->totals['sipilis_positif'] ?? 0,
            'Kasus Maternal (Hiper.)' => $this->totals['hiperemesis'] ?? 0,
            'Kasus Maternal (Abortus)' => $this->totals['abortus'] ?? 0,
            'Kasus Maternal (PE/Ekl.)' => $this->totals['preeklamsia'] ?? 0,
            'Kasus Maternal (Perd. Hamil)' => $this->totals['perdarahan_kehamilan'] ?? 0,
            'Kasus Maternal (Perd. Salin)' => $this->totals['perdarahan_persalinan'] ?? 0,
            'Kasus Maternal (Perd. Nifas)' => $this->totals['perdarahan_nifas'] ?? 0,
            'Kasus Maternal (Partus Lama)' => $this->totals['partus_lama'] ?? 0,
            'Kasus Maternal (Infeksi)' => $this->totals['infeksi'] ?? 0,
            'Kasus Maternal (TB)' => $this->totals['tb'] ?? 0,
            'Kasus Maternal (Malaria)' => $this->totals['malaria'] ?? 0,
            'Kasus Maternal (Jantung)' => $this->totals['jantung'] ?? 0,
            'Kasus Maternal (DM)' => $this->totals['dm'] ?? 0,
            'Kasus Maternal (Covid-19)' => $this->totals['covid19'] ?? 0,
            'Kasus Maternal (Obesitas)' => $this->totals['obesitas'] ?? 0,
            'Kasus Maternal (Lainnya)' => $this->totals['lainnya'] ?? 0,
            'Jml Komplikasi (Hamil)' => $this->totals['komplikasi_kehamilan'] ?? 0,
            'Jml Komplikasi (Persalinan)' => $this->totals['komplikasi_persalinan'] ?? 0,
            'Jml Komplikasi (Nifas)' => $this->totals['komplikasi_nifas'] ?? 0,
            'Jenis Persalinan (Normal)' => $this->totals['partus_normal'] ?? 0,
            'Jenis Persalinan (SC)' => $this->totals['partus_sc'] ?? 0,
            'Jenis Persalinan (Tindakan Drip)' => $this->totals['partus_drip'] ?? 0,
            'Jenis Persalinan (Tindakan Curatage)' => $this->totals['partus_curatage'] ?? 0,
            'Jenis Persalinan (Tindakan Manual)' => $this->totals['partus_plasenta_manual'] ?? 0,
            'Jenis Persalinan (Dukun)' => $this->totals['partus_dukun'] ?? 0,
            'Tempat Persalinan (Polindes)' => $this->totals['tempat_polindes'] ?? 0,
            'Tempat Persalinan (RS)' => $this->totals['tempat_rs'] ?? 0,
            'Tempat Persalinan (Pusk. PONED)' => $this->totals['tempat_puskesmas_poned'] ?? 0,
            'Tempat Persalinan (Pusk. Non PONED)' => $this->totals['tempat_puskesmas_non_poned'] ?? 0,
            'Tempat Persalinan (Klinik)' => $this->totals['tempat_klinik'] ?? 0,
            'Tempat Persalinan (PMB)' => $this->totals['tempat_pmb'] ?? 0,
            'Tempat Persalinan (Rumah)' => $this->totals['tempat_rumah'] ?? 0,
        ];
        $writer->addRow($totalRow);

        return response()->streamDownload(function () use ($writer) {
            $writer->close();
        }, $fileName);
    }
};
