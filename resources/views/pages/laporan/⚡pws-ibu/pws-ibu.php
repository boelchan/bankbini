<?php

use App\Enums\PoedjiRochyatiEnum;
use App\Models\Encounter;
use App\Models\PostnatalEncounter;
use App\Models\Pregnancy;
use App\Models\PregnancyHistory;
use App\Services\LaporanService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Laporan PWS Ibu')]
class extends Component {
    public int $bulan;
    public int $tahun;

    public function mount(): void
    {
        $this->bulan = (int) date('n');
        $this->tahun = (int) date('Y');
    }

    /**
     * Memuat ulang data rekapitulasi PWS Ibu.
     */
    public function refresh(): void
    {
        unset($this->rows);
    }

    /**
     * Pilihan bulan untuk filter.
     */
    #[Computed]
    public function monthOptions(): array
    {
        return LaporanService::getMonthOptions();
    }

    /**
     * Pilihan tahun untuk filter.
     */
    #[Computed]
    public function yearOptions(): array
    {
        return LaporanService::getYearOptions();
    }

    /**
     * Label bulan terpilih dalam Bahasa Indonesia.
     */
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

    /**
     * Data rekapitulasi PWS Ibu per desa.
     */
    #[Computed]
    public function rows(): array
    {
        $villages = LaporanService::getVillages();

        // 1. Kunjungan ANC pada bulan dan tahun ini
        $encounters = Encounter::with(['pregnancy.patient', 'pregnancy.encounters', 'creator.roles'])
            ->whereMonth('encounter_date', $this->bulan)
            ->whereYear('encounter_date', $this->tahun)
            ->get();

        // 2. Kunjungan Nifas pada bulan dan tahun ini
        $postnatals = PostnatalEncounter::with(['pregnancy.patient'])
            ->whereMonth('encounter_date', $this->bulan)
            ->whereYear('encounter_date', $this->tahun)
            ->get();

        // 3. Persalinan yang terjadi pada bulan dan tahun ini
        $persalinans = PregnancyHistory::with('patient')
            ->whereMonth('tanggal_persalinan', $this->bulan)
            ->whereYear('tanggal_persalinan', $this->tahun)
            ->get();

        // Array inisialisasi per desa
        $data = [];
        foreach ($villages as $v) {
            $data[$v->code] = [
                'village_code' => $v->code,
                'village_name' => $v->name,
                'k1_akses' => 0,
                'k1_murni' => 0,
                'k1_dokter' => 0,
                'k1_usg' => 0,
                'k4' => 0,
                'k5' => 0,
                'k5_dokter' => 0,
                'k5_usg' => 0,
                'k6' => 0,
                'k7' => 0,
                'k8' => 0,
                'drtm' => 0,
                'drtn' => 0,
                'komplikasi' => 0,
                'linakes' => 0,
                'linakes_faskes' => 0,
                'polindes' => 0,
                'kf1' => 0,
                'kf2' => 0,
                'kf3' => 0,
                'kf4' => 0,
            ];
        }

        // Proses Kunjungan ANC
        $countedK4 = [];
        $countedK5 = [];
        $countedK6 = [];
        $countedK7 = [];
        $countedK8 = [];
        $countedAncKomplikasi = [];
        $countedPersalinanKomplikasi = [];
        $countedNifasKomplikasi = [];

        foreach ($encounters as $enc) {
            $vCode = $enc->pregnancy?->patient?->village_code;
            if (!$vCode || !isset($data[$vCode])) {
                continue;
            }

            $pregId = $enc->pregnancy_id;
            $minggu = $enc->usia_kehamilan_minggu;
            $kunjunganKe = $enc->kunjungan_ke;
            $isK1 = ($kunjunganKe === 1);
            $isDokter = ($enc->creator && $enc->creator->hasRole('dokter')) || (bool) $enc->is_usg;
            $isUsg = (bool) $enc->is_usg;
            $kStage = $enc->k_stage;

            // Kunjungan pertama (K1)
            if ($isK1) {
                $data[$vCode]['k1_akses']++;
                if ($minggu !== null && $minggu <= 11) {
                    $data[$vCode]['k1_murni']++;
                }
                // Nilai disamakan dengan akses sesuai spesifikasi dokumen PWS KIA
                $data[$vCode]['k1_dokter'] = $data[$vCode]['k1_akses'];
                if ($isUsg) {
                    $data[$vCode]['k1_usg']++;
                }
            } elseif ($kStage === 'K4') {
                // Kelompok K4: bumil yg kontrol di TM1 minimal 1x, TM2 minimal 1x, TM3 (usia 25-40) minimal 2x
                if (!isset($countedK4[$vCode][$pregId])) {
                    $countedK4[$vCode][$pregId] = true;
                    $data[$vCode]['k4']++;
                }
            } elseif ($kStage === 'K5') {
                // Kelompok K5 = K4 + 1x + USG + Lab + mengisi skor_skrining_jiwa
                if (!isset($countedK5[$vCode][$pregId])) {
                    $countedK5[$vCode][$pregId] = true;
                    $data[$vCode]['k5']++;
                    // Sesuai spesifikasi: dokter & usg disamakan dengan jumlah k5
                    $data[$vCode]['k5_dokter'] = $data[$vCode]['k5'];
                    $data[$vCode]['k5_usg'] = $data[$vCode]['k5'];
                }
            } elseif ($kStage === 'K6') {
                // Kelompok K6 = K5 + 1x
                if (!isset($countedK6[$vCode][$pregId])) {
                    $countedK6[$vCode][$pregId] = true;
                    $data[$vCode]['k6']++;
                }
            } elseif ($kStage === 'K7') {
                // Kelompok K7 = K6 + 1x
                if (!isset($countedK7[$vCode][$pregId])) {
                    $countedK7[$vCode][$pregId] = true;
                    $data[$vCode]['k7']++;
                }
            } elseif ($kStage === 'K8') {
                // Kelompok K8 = K7 + 1x dan seterusnya
                if (!isset($countedK8[$vCode][$pregId])) {
                    $countedK8[$vCode][$pregId] = true;
                    $data[$vCode]['k8']++;
                }
            }

            // DRTM & DRTN: Jumlah kunjungan pertama kali pada bumil dengan risiko tinggi (KRT) dan risiko sangat tinggi (KRST)
            // Diambil dari syarat kunjungan pertama kali ($isK1) dengan Skor Poedji Rochyati >= 6.
            // Nilai DRTN disamakan dengan DRTM sesuai spesifikasi PWS KIA.
            $skor = (int) ($enc->skor_puji_rochyati ?? 0);
            if ($isK1 && $skor >= 6) {
                $data[$vCode]['drtm']++;
                $data[$vCode]['drtn']++;
            }

            // Komplikasi yang terjadi saat pemeriksaan hamil (ANC) di bulan ini
            // Dihitung per kasus kunjungan: jika masalah sama dihitung 1, jika beda kasus dihitung berbeda
            $hasAncKomplikasi = !empty($enc->masalah_kehamilan) && !str_contains(strtolower($enc->masalah_kehamilan), 'tidak ada');
            if ($hasAncKomplikasi) {
                $kasusAnc = strtolower(trim($enc->masalah_kehamilan));
                $patId = $enc->pregnancy?->patient_id ?? $enc->pregnancy_id ?? $enc->id;
                $ancKey = $patId . '_' . $kasusAnc;

                if (!isset($countedAncKomplikasi[$vCode][$ancKey])) {
                    $countedAncKomplikasi[$vCode][$ancKey] = true;
                    $data[$vCode]['komplikasi']++;
                }
            }
        }

        // Proses Persalinan (Linakes & Faskes)
        foreach ($persalinans as $p) {
            $vCode = $p->patient?->village_code;
            if (!$vCode || !isset($data[$vCode])) {
                continue;
            }

            $pStatus = strtolower($p->status ?? '');
            $isAbortus = $pStatus === 'abortus';
            $isMeninggal = $pStatus === 'meninggal';

            if (!$isAbortus && !$isMeninggal) {
                $penolong = strtolower($p->penolong ?? '');
                $tempat = strtolower($p->tempat_persalinan ?? '');
                $isNakes = !empty($penolong) && !str_contains($penolong, 'dukun');

                // Linakes: penolong nakes kecuali dukun
                if ($isNakes) {
                    $data[$vCode]['linakes']++;

                    // Di Faskes: semua kecuali polindes dan rumah
                    if (!empty($tempat) && !str_contains($tempat, 'rumah') && !str_contains($tempat, 'polindes') && !str_contains($tempat, 'poskesdes')) {
                        $data[$vCode]['linakes_faskes']++;
                    }
                }

                // Persalinan di Polindes / Poskesdes: hanya di polindes
                if (str_contains($tempat, 'polindes') || str_contains($tempat, 'poskesdes')) {
                    $data[$vCode]['polindes']++;
                }
            }

            // Komplikasi saat persalinan atau selesai dengan cara abortus di bulan ini (pasien meninggal tidak dihitung)

            $hasMasalahPersalinan = false;
            $kasusPersalinan = '';

            if ($isAbortus) {
                $hasMasalahPersalinan = true;
                $kasusPersalinan = 'abortus';
            } elseif (!$isMeninggal && !empty($p->masalah_persalinan) && !str_contains(strtolower($p->masalah_persalinan), 'tidak ada')) {
                $hasMasalahPersalinan = true;
                $kasusPersalinan = strtolower(trim($p->masalah_persalinan));
            }

            if ($hasMasalahPersalinan) {
                $patId = $p->patient_id ?? $p->id;
                $partusKey = $patId . '_' . $kasusPersalinan;

                if (!isset($countedPersalinanKomplikasi[$vCode][$partusKey])) {
                    $countedPersalinanKomplikasi[$vCode][$partusKey] = true;
                    $data[$vCode]['komplikasi']++;
                }
            }
        }

        // Proses Kunjungan Nifas (KF1 - KF4)
        foreach ($postnatals as $post) {
            $vCode = $post->pregnancy?->patient?->village_code;
            if (!$vCode || !isset($data[$vCode])) {
                continue;
            }

            $kfType = $post->kf_type;

            if ($kfType === 'KF1') {
                $data[$vCode]['kf1']++;
            } elseif ($kfType === 'KF2') {
                $data[$vCode]['kf2']++;
            } elseif ($kfType === 'KF3') {
                $data[$vCode]['kf3']++;
            } elseif ($kfType === 'KF4') {
                $data[$vCode]['kf4']++;
            }

            // Komplikasi yang terjadi saat kunjungan nifas di bulan ini
            // Dihitung per kasus kunjungan: jika masalah sama dihitung 1, jika beda kasus dihitung berbeda
            $hasNifasKomplikasi = !empty($post->masalah_nifas) && !str_contains(strtolower($post->masalah_nifas), 'tidak ada');
            if ($hasNifasKomplikasi) {
                $kasusNifas = strtolower(trim($post->masalah_nifas));
                $patId = $post->pregnancy?->patient_id ?? $post->pregnancy_id ?? $post->id;
                $nifasKey = $patId . '_' . $kasusNifas;

                if (!isset($countedNifasKomplikasi[$vCode][$nifasKey])) {
                    $countedNifasKomplikasi[$vCode][$nifasKey] = true;
                    $data[$vCode]['komplikasi']++;
                }
            }
        }

        return array_values($data);
    }

    /**
     * Total kolom keseluruhan (summary baris terakhir).
     */
    #[Computed]
    public function totals(): array
    {
        $rows = $this->rows;
        $keys = [
            'k1_akses', 'k1_murni', 'k1_dokter', 'k1_usg',
            'k4', 'k5', 'k5_dokter', 'k5_usg', 'k6', 'k7', 'k8',
            'drtm', 'drtn', 'komplikasi', 'linakes', 'linakes_faskes',
            'polindes', 'kf1', 'kf2', 'kf3', 'kf4'
        ];

        $totals = [];
        foreach ($keys as $k) {
            $totals[$k] = array_sum(array_column($rows, $k));
        }

        return $totals;
    }

    /**
     * Export data rekapitulasi PWS Ibu ke Excel menggunakan spatie/simple-excel.
     */
    public function exportExcel()
    {
        $fileName = 'PWS_IBU_' . str_replace(' ', '_', $this->selectedMonthLabel) . '.xlsx';
        $writer = \Spatie\SimpleExcel\SimpleExcelWriter::streamDownload($fileName);

        $no = 1;
        foreach ($this->rows as $r) {
            $writer->addRow([
                'No' => $no++,
                'Desa / Kelurahan' => $r['village_name'],
                'Kelompok K1 (Akses)' => $r['k1_akses'],
                'Kelompok K1 (Murni)' => $r['k1_murni'],
                'Kelompok K1 (Dokter)' => $r['k1_dokter'],
                'Kelompok K1 (USG)' => $r['k1_usg'],
                'K4' => $r['k4'],
                'Kelompok K5 (Jml K5)' => $r['k5'],
                'Kelompok K5 (Dokter)' => $r['k5_dokter'],
                'Kelompok K5 (USG)' => $r['k5_usg'],
                'K6' => $r['k6'],
                'K7' => $r['k7'],
                'K8' => $r['k8'],
                'Deteksi Risiko (DRTM)' => $r['drtm'],
                'Deteksi Risiko (DRTN)' => $r['drtn'],
                'Komp. Kebidanan' => $r['komplikasi'],
                'Persalinan (Linakes)' => $r['linakes'],
                'Persalinan (Di Faskes)' => $r['linakes_faskes'],
                'Persalinan (Polindes)' => $r['polindes'],
                'Pelayanan Nifas (KF1)' => $r['kf1'],
                'Pelayanan Nifas (KF2)' => $r['kf2'],
                'Pelayanan Nifas (KF3)' => $r['kf3'],
                'Pelayanan Nifas (KF4)' => $r['kf4'],
            ]);
        }

        // Baris Total
        $writer->addRow([
            'No' => '',
            'Desa / Kelurahan' => 'TOTAL PUSKESMAS',
            'Kelompok K1 (Akses)' => $this->totals['k1_akses'],
            'Kelompok K1 (Murni)' => $this->totals['k1_murni'],
            'Kelompok K1 (Dokter)' => $this->totals['k1_dokter'],
            'Kelompok K1 (USG)' => $this->totals['k1_usg'],
            'K4' => $this->totals['k4'],
            'Kelompok K5 (Jml K5)' => $this->totals['k5'],
            'Kelompok K5 (Dokter)' => $this->totals['k5_dokter'],
            'Kelompok K5 (USG)' => $this->totals['k5_usg'],
            'K6' => $this->totals['k6'],
            'K7' => $this->totals['k7'],
            'K8' => $this->totals['k8'],
            'Deteksi Risiko (DRTM)' => $this->totals['drtm'],
            'Deteksi Risiko (DRTN)' => $this->totals['drtn'],
            'Komp. Kebidanan' => $this->totals['komplikasi'],
            'Persalinan (Linakes)' => $this->totals['linakes'],
            'Persalinan (Di Faskes)' => $this->totals['linakes_faskes'],
            'Persalinan (Polindes)' => $this->totals['polindes'],
            'Pelayanan Nifas (KF1)' => $this->totals['kf1'],
            'Pelayanan Nifas (KF2)' => $this->totals['kf2'],
            'Pelayanan Nifas (KF3)' => $this->totals['kf3'],
            'Pelayanan Nifas (KF4)' => $this->totals['kf4'],
        ]);

        return response()->streamDownload(function () use ($writer) {
            $writer->close();
        }, $fileName);
    }
};