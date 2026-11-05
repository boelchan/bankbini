<?php

use App\Models\NeonatalEncounter;
use App\Models\PregnancyHistory;
use App\Services\LaporanService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Laporan PWS Bayi')]
class extends Component {
    public int $bulan;
    public int $tahun;

    public function mount(): void
    {
        $this->bulan = (int) date('n');
        $this->tahun = (int) date('Y');
    }

    /**
     * Memuat ulang data rekapitulasi PWS Bayi.
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

        // 1. Data Kelahiran pada bulan dan tahun ini
        $persalinans = PregnancyHistory::with('patient')
            ->whereMonth('tanggal_persalinan', $this->bulan)
            ->whereYear('tanggal_persalinan', $this->tahun)
            ->get();

        // 2. Data Kunjungan Neonatal KN1, KN2, KN3 pada bulan dan tahun ini
        $neonatals = NeonatalEncounter::with(['pregnancy.neonatalEncounters', 'pregnancy.patient', 'patient'])
            ->whereMonth('encounter_date', $this->bulan)
            ->whereYear('encounter_date', $this->tahun)
            ->get();

        $data = [];
        foreach ($villages as $v) {
            $data[$v->code] = [
                'village_code' => $v->code,
                'village_name' => $v->name,
                'lahir_hidup' => 0,
                'lahir_mati' => 0,
                'kn1' => 0,
                'kn2' => 0,
                'kn3' => 0,
                'kn_lengkap' => 0,
            ];
        }

        // Hitung Lahir Hidup & Lahir Mati dari Riwayat Persalinan
        foreach ($persalinans as $p) {
            $vCode = $p->patient?->village_code;
            if (!$vCode || !isset($data[$vCode])) {
                continue;
            }

            $pStatus = strtolower($p->status ?? '');
            $cara = strtolower($p->cara_melahirkan ?? '');
            if ($pStatus === 'abortus' || $cara === 'abortus' || $pStatus === 'meninggal' || $cara === 'meninggal') {
                continue; // Abortus bukan kelahiran neonatus (lahir hidup / lahir mati)
            }

            $masalah = strtolower($p->masalah_persalinan ?? '');
            $kondisi = strtolower($p->kondisi_bayi ?? '');
            $isMati = str_contains($kondisi, 'mati') || str_contains($masalah, 'mati') || str_contains($masalah, 'iud') || str_contains($masalah, 'stillbirth');
            if ($isMati) {
                $data[$vCode]['lahir_mati']++;
            } else {
                $data[$vCode]['lahir_hidup']++;
            }
        }

        // Hitung Kunjungan KN1, KN2, KN3 dan Verifikasi KN Lengkap per Neonatus
        $countedKn = [];
        $countedKnLengkap = [];

        foreach ($neonatals as $neo) {
            $vCode = $neo->pregnancy?->patient?->village_code ?? $neo->patient?->village_code;
            if (!$vCode || !isset($data[$vCode])) {
                continue;
            }

            $pregnancyId = $neo->pregnancy_id ?: ('pat_' . $neo->patient_id);
            $knType = strtoupper(trim((string) ($neo->kn_type ?? '')));

            // Normalisasi jenis KN berdasarkan hari_ke jika belum terisi eksplisit
            if ($knType !== 'KN1' && $knType !== 'KN2' && $knType !== 'KN3') {
                if ($neo->hari_ke !== null) {
                    if ($neo->hari_ke <= 2) {
                        $knType = 'KN1';
                    } elseif ($neo->hari_ke <= 7) {
                        $knType = 'KN2';
                    } elseif ($neo->hari_ke <= 28) {
                        $knType = 'KN3';
                    }
                }
            }

            // Hitung KN per jenis tanpa duplikasi kunjungan ganda per bayi di bulan yang sama
            if (!isset($countedKn[$vCode][$pregnancyId][$knType])) {
                $countedKn[$vCode][$pregnancyId][$knType] = true;

                if ($knType === 'KN1') {
                    $data[$vCode]['kn1']++;
                } elseif ($knType === 'KN2') {
                    $data[$vCode]['kn2']++;
                } elseif ($knType === 'KN3') {
                    $data[$vCode]['kn3']++;
                }
            }

            // Verifikasi KN Lengkap:
            // Neonatus yang telah mendapatkan pelayanan KN1 (0-2 hari), KN2 (3-7 hari), dan KN3 (8-28 hari)
            if (!isset($countedKnLengkap[$vCode][$pregnancyId])) {
                $allEncounters = $neo->pregnancy?->neonatalEncounters;
                if ($allEncounters && $allEncounters->isNotEmpty()) {
                    $hasKn1 = $allEncounters->contains(fn($e) => strtoupper((string) $e->kn_type) === 'KN1' || ($e->hari_ke !== null && $e->hari_ke <= 2));
                    $hasKn2 = $allEncounters->contains(fn($e) => strtoupper((string) $e->kn_type) === 'KN2' || ($e->hari_ke !== null && $e->hari_ke >= 3 && $e->hari_ke <= 7));
                    $hasKn3 = $allEncounters->contains(fn($e) => strtoupper((string) $e->kn_type) === 'KN3' || ($e->hari_ke !== null && $e->hari_ke >= 8 && $e->hari_ke <= 28));

                    if ($hasKn1 && $hasKn2 && $hasKn3) {
                        $countedKnLengkap[$vCode][$pregnancyId] = true;
                        $data[$vCode]['kn_lengkap']++;
                    }
                }
            }
        }

        // Fallback KN Lengkap jika data relasi pregnancy kosong pada arsip lama
        foreach ($data as $code => $d) {
            if ($data[$code]['kn_lengkap'] === 0 && min($d['kn1'], $d['kn2'], $d['kn3']) > 0) {
                $data[$code]['kn_lengkap'] = min($d['kn1'], $d['kn2'], $d['kn3']);
            }
        }

        return array_values($data);
    }

    #[Computed]
    public function totals(): array
    {
        $rows = $this->rows;
        $keys = ['lahir_hidup', 'lahir_mati', 'kn1', 'kn2', 'kn3', 'kn_lengkap'];

        $totals = [];
        foreach ($keys as $k) {
            $totals[$k] = array_sum(array_column($rows, $k));
        }

        return $totals;
    }

    /**
     * Export data rekapitulasi PWS Bayi ke Excel menggunakan spatie/simple-excel.
     */
    public function exportExcel()
    {
        $fileName = 'PWS_BAYI_' . str_replace(' ', '_', $this->selectedMonthLabel) . '.xlsx';
        $writer = \Spatie\SimpleExcel\SimpleExcelWriter::streamDownload($fileName);

        $no = 1;
        foreach ($this->rows as $r) {
            $writer->addRow([
                'No' => $no++,
                'Desa / Kelurahan' => $r['village_name'],
                'Kelahiran Bayi (Lahir Hidup)' => $r['lahir_hidup'],
                'Kelahiran Bayi (Lahir Mati)' => $r['lahir_mati'],
                'Kunjungan Neonatal (KN1 0-2 Hari)' => $r['kn1'],
                'Kunjungan Neonatal (KN2 3-7 Hari)' => $r['kn2'],
                'Kunjungan Neonatal (KN3 8-28 Hari)' => $r['kn3'],
                'KN Lengkap' => $r['kn_lengkap'],
            ]);
        }

        // Baris Total
        $writer->addRow([
            'No' => '',
            'Desa / Kelurahan' => 'TOTAL PUSKESMAS',
            'Kelahiran Bayi (Lahir Hidup)' => $this->totals['lahir_hidup'] ?? 0,
            'Kelahiran Bayi (Lahir Mati)' => $this->totals['lahir_mati'] ?? 0,
            'Kunjungan Neonatal (KN1 0-2 Hari)' => $this->totals['kn1'] ?? 0,
            'Kunjungan Neonatal (KN2 3-7 Hari)' => $this->totals['kn2'] ?? 0,
            'Kunjungan Neonatal (KN3 8-28 Hari)' => $this->totals['kn3'] ?? 0,
            'KN Lengkap' => $this->totals['kn_lengkap'] ?? 0,
        ]);

        return response()->streamDownload(function () use ($writer) {
            $writer->close();
        }, $fileName);
    }
};