<?php

use App\Models\NeonatalEncounter;
use App\Models\PregnancyHistory;
use App\Services\LaporanService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Laporan LB3 Neonatal')]
class extends Component {
    public int $bulan;
    public int $tahun;

    public function mount(): void
    {
        $this->bulan = (int) date('n');
        $this->tahun = (int) date('Y');
    }

    /**
     * Memuat ulang data rekapitulasi LB3 Neonatal.
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

        // 1. Data Riwayat Persalinan / Lahir Hidup
        $persalinans = PregnancyHistory::with(['patient.pregnancies'])
            ->whereMonth('tanggal_persalinan', $this->bulan)
            ->whereYear('tanggal_persalinan', $this->tahun)
            ->get();

        // 2. Data Pelayanan Neonatal
        $neonatals = NeonatalEncounter::with([
            'pregnancy.pregnancyHistory',
            'pregnancy.patient',
            'patient',
        ])
            ->whereMonth('encounter_date', $this->bulan)
            ->whereYear('encounter_date', $this->tahun)
            ->get();

        $data = [];
        foreach ($villages as $v) {
            $data[$v->code] = [
                'village_code' => $v->code,
                'village_name' => $v->name,
                // LAHIR HIDUP
                'lahir_hidup_l' => 0,
                'lahir_hidup_p' => 0,
                'lahir_hidup_total' => 0,
                // LAHIR PREMATUR (< 37 mgg)
                'prematur_l' => 0,
                'prematur_p' => 0,
                'prematur_total' => 0,
                // SHK (saat KN1)
                'shk_l' => 0,
                'shk_p' => 0,
                'shk_total' => 0,
                // KASUS NEONATAL (Dikelompokkan per jenis kelamin L & P)
                'trauma_lahir_l' => 0,
                'trauma_lahir_p' => 0,
                'asfiksia_l' => 0,
                'asfiksia_p' => 0,
                'bblr_l' => 0,
                'bblr_p' => 0,
                'infeksi_l' => 0,
                'infeksi_p' => 0,
                'tn_l' => 0,
                'tn_p' => 0,
                'kelainan_bawaan_l' => 0,
                'kelainan_bawaan_p' => 0,
                'hk_positif_l' => 0,
                'hk_positif_p' => 0,
                'covid19_l' => 0,
                'covid19_p' => 0,
                'lainnya_l' => 0,
                'lainnya_p' => 0,
                'total_kasus_l' => 0,
                'total_kasus_p' => 0,
                'total_kasus' => 0,
            ];
        }

        // Kalkulasi Lahir Hidup & Lahir Prematur dari Persalinan
        foreach ($persalinans as $p) {
            $vCode = $p->patient?->village_code;
            if (!$vCode || !isset($data[$vCode])) {
                continue;
            }

            $pStatus = strtolower($p->status ?? '');
            $cara = strtolower($p->cara_melahirkan ?? '');
            if ($pStatus === 'abortus' || $cara === 'abortus' || $pStatus === 'meninggal' || $cara === 'meninggal') {
                continue; // Abortus / meninggal bukan persalinan / kelahiran neonatus hidup
            }

            $masalah = strtolower($p->masalah_persalinan ?? '');
            $kondisi = strtolower($p->kondisi_bayi ?? '');
            $isMati = str_contains($kondisi, 'mati');
            if ($isMati) {
                continue;
            }

            // Ambil jenis kelamin bayi riil dari data riwayat persalinan (L / P)
            $genderBayi = $p->jenis_kelamin;

            if ($genderBayi === 'L') {
                $data[$vCode]['lahir_hidup_l']++;
            } else {
                $data[$vCode]['lahir_hidup_p']++;
            }
            $data[$vCode]['lahir_hidup_total']++;

            // Cek usia kehamilan saat persalinan (standar kurang bulan WHO & Kemenkes: < 37 minggu)
            $pregnancy = $p->patient?->pregnancies?->sortByDesc('hpht')->first();
            $usiaKehamilan = 39;
            if ($pregnancy && $pregnancy->hpht && $p->tanggal_persalinan) {
                $hpht = Carbon::parse($pregnancy->hpht);
                $partus = Carbon::parse($p->tanggal_persalinan);
                $usiaKehamilan = (int) $hpht->diffInWeeks($partus);
            }

            if ($usiaKehamilan < 37) {
                if ($genderBayi === 'L') {
                    $data[$vCode]['prematur_l']++;
                } else {
                    $data[$vCode]['prematur_p']++;
                }
                $data[$vCode]['prematur_total']++;
            }
        }

        // Kalkulasi SHK & Kasus Neonatal (dihitung per neonatus per kasus pada bulan pelaporan)
        $countedShk = [];
        $countedCases = [];

        foreach ($neonatals as $neo) {
            $vCode = $neo->pregnancy?->patient?->village_code ?? $neo->patient?->village_code;
            if (!$vCode || !isset($data[$vCode])) {
                continue;
            }

            $pregnancyId = $neo->pregnancy_id ?: ('pat_' . $neo->patient_id);

            // Ambil riwayat persalinan langsung berdasarkan pregnancy_id
            $history = null;
            if ($neo->pregnancy_id) {
                $history = $neo->pregnancy?->pregnancyHistory
                    ?? PregnancyHistory::where('pregnancy_id', $neo->pregnancy_id)->first();
            }

            // Ambil jenis kelamin bayi riil dari riwayat persalinan (L / P)
            $genderBayi = strtoupper(trim((string) ($history?->jenis_kelamin ?? ''))) === 'P' ? 'P' : 'L';

            // Skrining Hipotiroid Kongenital (SHK)
            if (strtolower($neo->shk ?? '') === 'ya') {
                if (!isset($countedShk[$vCode][$pregnancyId])) {
                    $countedShk[$vCode][$pregnancyId] = true;
                    if ($genderBayi === 'L') {
                        $data[$vCode]['shk_l']++;
                    } else {
                        $data[$vCode]['shk_p']++;
                    }
                    $data[$vCode]['shk_total']++;
                }
            }

            // Kasus-kasus Neonatal
            $hasAnyCase = false;

            // 1. Trauma Lahir
            if (strtolower($neo->trauma_lahir ?? '') === 'ya') {
                if (!isset($countedCases[$vCode][$pregnancyId]['trauma_lahir'])) {
                    $countedCases[$vCode][$pregnancyId]['trauma_lahir'] = true;
                    if ($genderBayi === 'L') {
                        $data[$vCode]['trauma_lahir_l']++;
                    } else {
                        $data[$vCode]['trauma_lahir_p']++;
                    }
                }
                $hasAnyCase = true;
            }

            // 2. Asfiksia
            $asfiksia = strtolower($neo->asfiksia ?? 'normal');
            if (in_array($asfiksia, ['ringan', 'sedang', 'berat'])) {
                if (!isset($countedCases[$vCode][$pregnancyId]['asfiksia'])) {
                    $countedCases[$vCode][$pregnancyId]['asfiksia'] = true;
                    if ($genderBayi === 'L') {
                        $data[$vCode]['asfiksia_l']++;
                    } else {
                        $data[$vCode]['asfiksia_p']++;
                    }
                }
                $hasAnyCase = true;
            }

            // 3. BBLR (< 2500 gr)
            if (strtolower($neo->bblr ?? '') === 'ya' || ($neo->bb && $neo->bb < 2.5)) {
                if (!isset($countedCases[$vCode][$pregnancyId]['bblr'])) {
                    $countedCases[$vCode][$pregnancyId]['bblr'] = true;
                    if ($genderBayi === 'L') {
                        $data[$vCode]['bblr_l']++;
                    } else {
                        $data[$vCode]['bblr_p']++;
                    }
                }
                $hasAnyCase = true;
            }

            // 4. Infeksi
            if (strtolower($neo->infeksi ?? '') === 'ya') {
                if (!isset($countedCases[$vCode][$pregnancyId]['infeksi'])) {
                    $countedCases[$vCode][$pregnancyId]['infeksi'] = true;
                    if ($genderBayi === 'L') {
                        $data[$vCode]['infeksi_l']++;
                    } else {
                        $data[$vCode]['infeksi_p']++;
                    }
                }
                $hasAnyCase = true;
            }

            // 5. Tetanus Neonatorum (TN)
            if (strtolower($neo->tn ?? '') === 'ya') {
                if (!isset($countedCases[$vCode][$pregnancyId]['tn'])) {
                    $countedCases[$vCode][$pregnancyId]['tn'] = true;
                    if ($genderBayi === 'L') {
                        $data[$vCode]['tn_l']++;
                    } else {
                        $data[$vCode]['tn_p']++;
                    }
                }
                $hasAnyCase = true;
            }

            // 6. Kelainan Bawaan
            if (strtolower($neo->kelainan_bawaan ?? '') === 'ya') {
                if (!isset($countedCases[$vCode][$pregnancyId]['kelainan_bawaan'])) {
                    $countedCases[$vCode][$pregnancyId]['kelainan_bawaan'] = true;
                    if ($genderBayi === 'L') {
                        $data[$vCode]['kelainan_bawaan_l']++;
                    } else {
                        $data[$vCode]['kelainan_bawaan_p']++;
                    }
                }
                $hasAnyCase = true;
            }

            // 7. Hipotiroid Kongenital Positif (HK +)
            $hkPositif = strtolower($neo->hk_positif ?? '');
            if ($hkPositif === 'positif' || $hkPositif === 'ya' || $hkPositif === '+') {
                if (!isset($countedCases[$vCode][$pregnancyId]['hk_positif'])) {
                    $countedCases[$vCode][$pregnancyId]['hk_positif'] = true;
                    if ($genderBayi === 'L') {
                        $data[$vCode]['hk_positif_l']++;
                    } else {
                        $data[$vCode]['hk_positif_p']++;
                    }
                }
                $hasAnyCase = true;
            }

            // 8. Konfirmasi Covid-19
            if (strtolower($neo->covid19 ?? '') === 'ya') {
                if (!isset($countedCases[$vCode][$pregnancyId]['covid19'])) {
                    $countedCases[$vCode][$pregnancyId]['covid19'] = true;
                    if ($genderBayi === 'L') {
                        $data[$vCode]['covid19_l']++;
                    } else {
                        $data[$vCode]['covid19_p']++;
                    }
                }
                $hasAnyCase = true;
            }

            // 9. Kasus Lainnya
            if (!empty($neo->masalah_lainnya) && strtolower($neo->masalah_lainnya) !== 'tidak ada') {
                if (!isset($countedCases[$vCode][$pregnancyId]['lainnya'])) {
                    $countedCases[$vCode][$pregnancyId]['lainnya'] = true;
                    if ($genderBayi === 'L') {
                        $data[$vCode]['lainnya_l']++;
                    } else {
                        $data[$vCode]['lainnya_p']++;
                    }
                }
                $hasAnyCase = true;
            }

            // Total Kasus Unik Neonatus
            if ($hasAnyCase) {
                if (!isset($countedCases[$vCode][$pregnancyId]['total_kasus'])) {
                    $countedCases[$vCode][$pregnancyId]['total_kasus'] = true;
                    if ($genderBayi === 'L') {
                        $data[$vCode]['total_kasus_l']++;
                    } else {
                        $data[$vCode]['total_kasus_p']++;
                    }
                    $data[$vCode]['total_kasus']++;
                }
            }
        }

        return array_values($data);
    }

    #[Computed]
    public function totals(): array
    {
        $rows = $this->rows;
        $keys = [
            'lahir_hidup_l', 'lahir_hidup_p', 'lahir_hidup_total',
            'prematur_l', 'prematur_p', 'prematur_total',
            'shk_l', 'shk_p', 'shk_total',
            'trauma_lahir_l', 'trauma_lahir_p',
            'asfiksia_l', 'asfiksia_p',
            'bblr_l', 'bblr_p',
            'infeksi_l', 'infeksi_p',
            'tn_l', 'tn_p',
            'kelainan_bawaan_l', 'kelainan_bawaan_p',
            'hk_positif_l', 'hk_positif_p',
            'covid19_l', 'covid19_p',
            'lainnya_l', 'lainnya_p',
            'total_kasus_l', 'total_kasus_p', 'total_kasus',
        ];

        $totals = [];
        foreach ($keys as $k) {
            $totals[$k] = array_sum(array_column($rows, $k));
        }

        return $totals;
    }

    /**
     * Export data rekapitulasi LB3 Neonatal ke Excel menggunakan spatie/simple-excel.
     */
    public function exportExcel()
    {
        $fileName = 'LB3_NEONATAL_' . str_replace(' ', '_', $this->selectedMonthLabel) . '.xlsx';
        $writer = \Spatie\SimpleExcel\SimpleExcelWriter::streamDownload($fileName)
            ->noHeaderRow();

        // Baris Header 1 (Grup Kategori Utama)
        $writer->addRow([
            'No',
            'Desa / Kelurahan',
            'Lahir Hidup', '', '',
            'Lahir Bayi Prematur (<37 Mgg)', '', '',
            'Bayi di-SHK (KN1)', '', '',
            'Kasus Masalah Neonatal', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
        ]);

        // Baris Header 2 (Nama Sub-Kategori Kasus Neonatal)
        $writer->addRow([
            '',
            '',
            '', '', '',
            '', '', '',
            '', '', '',
            'Trauma', '',
            'Asfiksia', '',
            'BBLR', '',
            'Infeksi', '',
            'TN', '',
            'Bawaan', '',
            'HK (+)', '',
            'Covid', '',
            'Lainnya', '',
        ]);

        // Baris Header 3 (Disagregasi Gender L / P / Total)
        $writer->addRow([
            '',
            '',
            'L', 'P', 'Total',
            'L', 'P', 'Total',
            'L', 'P', 'Total',
            'L', 'P',
            'L', 'P',
            'L', 'P',
            'L', 'P',
            'L', 'P',
            'L', 'P',
            'L', 'P',
            'L', 'P',
            'L', 'P',
        ]);

        $no = 1;
        foreach ($this->rows as $r) {
            $writer->addRow([
                $no++,
                $r['village_name'],
                $r['lahir_hidup_l'],
                $r['lahir_hidup_p'],
                $r['lahir_hidup_total'],
                $r['prematur_l'],
                $r['prematur_p'],
                $r['prematur_total'],
                $r['shk_l'],
                $r['shk_p'],
                $r['shk_total'],
                $r['trauma_lahir_l'],
                $r['trauma_lahir_p'],
                $r['asfiksia_l'],
                $r['asfiksia_p'],
                $r['bblr_l'],
                $r['bblr_p'],
                $r['infeksi_l'],
                $r['infeksi_p'],
                $r['tn_l'],
                $r['tn_p'],
                $r['kelainan_bawaan_l'],
                $r['kelainan_bawaan_p'],
                $r['hk_positif_l'],
                $r['hk_positif_p'],
                $r['covid19_l'],
                $r['covid19_p'],
                $r['lainnya_l'],
                $r['lainnya_p'],
            ]);
        }

        // Baris Total Puskesmas
        $writer->addRow([
            '',
            'TOTAL PUSKESMAS',
            $this->totals['lahir_hidup_l'] ?? 0,
            $this->totals['lahir_hidup_p'] ?? 0,
            $this->totals['lahir_hidup_total'] ?? 0,
            $this->totals['prematur_l'] ?? 0,
            $this->totals['prematur_p'] ?? 0,
            $this->totals['prematur_total'] ?? 0,
            $this->totals['shk_l'] ?? 0,
            $this->totals['shk_p'] ?? 0,
            $this->totals['shk_total'] ?? 0,
            $this->totals['trauma_lahir_l'] ?? 0,
            $this->totals['trauma_lahir_p'] ?? 0,
            $this->totals['asfiksia_l'] ?? 0,
            $this->totals['asfiksia_p'] ?? 0,
            $this->totals['bblr_l'] ?? 0,
            $this->totals['bblr_p'] ?? 0,
            $this->totals['infeksi_l'] ?? 0,
            $this->totals['infeksi_p'] ?? 0,
            $this->totals['tn_l'] ?? 0,
            $this->totals['tn_p'] ?? 0,
            $this->totals['kelainan_bawaan_l'] ?? 0,
            $this->totals['kelainan_bawaan_p'] ?? 0,
            $this->totals['hk_positif_l'] ?? 0,
            $this->totals['hk_positif_p'] ?? 0,
            $this->totals['covid19_l'] ?? 0,
            $this->totals['covid19_p'] ?? 0,
            $this->totals['lainnya_l'] ?? 0,
            $this->totals['lainnya_p'] ?? 0,
        ]);

        return response()->streamDownload(function () use ($writer) {
            $writer->close();
        }, $fileName);
    }
};
