<?php

use App\Models\Pregnancy;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new 
#[Title('Dasbor')]
class extends Component
{
    /**
     * Konfigurasi posisi koordinat statis 14 desa Kecamatan Pragaan.
     */
    public const array VILLAGE_COORDINATES = [
        'Aeng Panas' => [
            'code' => 3529112013,
            'lat'  => -7.101728,
            'lng'  => 113.696822,
        ],
        'Jaddung' => [
            'code' => 3529112009,
            'lat'  => -7.091757,
            'lng'  => 113.650320,
        ],
        'Kaduara Timur' => [
            'code' => 3529112001,
            'lat'  => -7.115723,
            'lng'  => 113.610082,
        ],
        'Karduluk' => [
            'code' => 3529112014,
            'lat'  => -7.085086,
            'lng'  => 113.715838,
        ],
        'Larangan Perreng' => [
            'code' => 3529112005,
            'lat'  => -7.072161,
            'lng'  => 113.620175,
        ],
        'Pakamban Daya' => [
            'code' => 3529112007,
            'lat'  => -7.081775,
            'lng'  => 113.641727,
        ],
        'Pakamban Laok' => [
            'code' => 3529112008,
            'lat'  => -7.117911,
            'lng'  => 113.645057,
        ],
        'Pragaan Daya' => [
            'code' => 3529112011,
            'lat'  => -7.088677,
            'lng'  => 113.663928,
        ],
        'Pragaan Laok' => [
            'code' => 3529112010,
            'lat'  => -7.110569,
            'lng'  => 113.659813,
        ],
        'Prenduan' => [
            'code' => 3529112012,
            'lat'  => -7.100107,
            'lng'  => 113.675828,
        ],
        'Rombasan' => [
            'code' => 3529112003,
            'lat'  => -7.108655,
            'lng'  => 113.619296,
        ],
        'Sendang' => [
            'code' => 3529112002,
            'lat'  => -7.121252,
            'lng'  => 113.627874,
        ],
        'Sentol Daya' => [
            'code' => 3529112006,
            'lat'  => -7.089727,
            'lng'  => 113.631549,
        ],
        'Sentol Laok' => [
            'code' => 3529112004,
            'lat'  => -7.103048,
            'lng'  => 113.632642,
        ],
    ];

    /**
     * Filter tingkat risiko pada peta (krr, krt, krst).
     */
    public string $filter_risiko = '';

    /**
     * Filter usia trimester pada peta (1, 2, 3).
     */
    public string $filter_tm = '';

    /**
     * Filter khusus ibu hamil siaga H-3 HPL (mendekati taksiran persalinan).
     */
    public bool $filter_h_min_3 = false;

    /**
     * Seluruh data kehamilan aktif di wilayah kerja Puskesmas Pragaan.
     *
     * @return Collection<int, Pregnancy>
     */
    #[Computed]
    public function allActivePregnancies(): Collection
    {
        return Pregnancy::with([
            'patient.village',
            'latestEncounter.creator',
            'creator',
        ])
        ->where('status', 'aktif')
        ->get();
    }

    /**
     * Ringkasan statistik kehamilan aktif (Risiko KRR/KRT/KRST, Trimester TM1/TM2/TM3, Lab Abnormal, Siaga H-3).
     *
     * @return array<string, int>
     */
    #[Computed]
    public function pregnancyStats(): array
    {
        $activePregnancies = $this->allActivePregnancies;

        $totalActive = $activePregnancies->count();
        $krrCount = 0;
        $krtCount = 0;
        $krstCount = 0;
        $tm1Count = 0;
        $tm2Count = 0;
        $tm3Count = 0;
        $abnormalLabCount = 0;
        $h3Count = 0;

        foreach ($activePregnancies as $preg) {
            $skor = $preg->latest_skor_puji_rochyati ?? 2;
            if ($skor <= 5) {
                $krrCount++;
            } elseif ($skor <= 10) {
                $krtCount++;
            } else {
                $krstCount++;
            }

            $tm = $preg->trimester;
            if ($tm === 1) {
                $tm1Count++;
            } elseif ($tm === 2) {
                $tm2Count++;
            } else {
                $tm3Count++;
            }

            if (!empty($preg->latest_abnormal_lab_results)) {
                $abnormalLabCount++;
            }

            $sisa = $preg->sisa_hari_hpl;
            if ($sisa !== null && $sisa <= 3 && $sisa >= -7) {
                $h3Count++;
            }
        }

        return [
            'total_active'                => $totalActive,
            'krr_count'                   => $krrCount,
            'krt_count'                   => $krtCount,
            'krst_count'                  => $krstCount,
            'tm1_count'                   => $tm1Count,
            'tm2_count'                   => $tm2Count,
            'tm3_count'                   => $tm3Count,
            'abnormal_lab_patients_count' => $abnormalLabCount,
            'h3_count'                    => $h3Count,
        ];
    }

    /**
     * Menghasilkan 1 marker terpadu per desa di titik pusat (centroid) desa.
     * Marker ini dinamis menampilkan chip angka untuk (KRR, KRT, KRST, H-3)
     * hanya jika nilainya > 0, serta memuat 1 popup komprehensif lengkap dengan daftar pasien.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function villageMarkers(): array
    {
        $allPregnancies = $this->allActivePregnancies;
        $markers = [];

        foreach (self::VILLAGE_COORDINATES as $villageName => $coord) {
            $vPregs = $allPregnancies->where('patient.village_code', $coord['code']);

            // Filter Trimester jika dipilih
            if (!empty($this->filter_tm)) {
                $vPregs = $vPregs->filter(fn($p) => (string) $p->trimester === (string) $this->filter_tm);
            }

            // Kategori KRR (Skor <= 5)
            $krrList = $vPregs->filter(fn($p) => ($p->latest_skor_puji_rochyati ?? 2) <= 5);

            // Kategori KRT (Skor 6 - 10)
            $krtList = $vPregs->filter(function ($p) {
                $skor = $p->latest_skor_puji_rochyati ?? 2;
                return $skor >= 6 && $skor <= 10;
            });

            // Kategori KRST (Skor > 10)
            $krstList = $vPregs->filter(fn($p) => ($p->latest_skor_puji_rochyati ?? 2) > 10);

            // Kategori Siaga H-3 HPL (Sisa hari <= 3 dan >= -7)
            $h3List = $vPregs->filter(function ($p) {
                $sisa = $p->sisa_hari_hpl;
                return $sisa !== null && $sisa <= 3 && $sisa >= -7;
            });

            // Status keterlihatan chip berdasarkan filter
            $showKrr = (empty($this->filter_risiko) || $this->filter_risiko === 'krr') && !$this->filter_h_min_3;
            $showKrt = (empty($this->filter_risiko) || $this->filter_risiko === 'krt') && !$this->filter_h_min_3;
            $showKrst = (empty($this->filter_risiko) || $this->filter_risiko === 'krst') && !$this->filter_h_min_3;
            $showH3 = empty($this->filter_risiko) || $this->filter_h_min_3;

            $visibleCount = 0;
            if ($showKrr) $visibleCount += $krrList->count();
            if ($showKrt) $visibleCount += $krtList->count();
            if ($showKrst) $visibleCount += $krstList->count();
            if ($this->filter_h_min_3) {
                $visibleCount = $h3List->count();
            }

            // Jika desa tidak memiliki kasus aktif sesuai filter saat ini, sembunyikan marker desa ini
            if ($visibleCount === 0) {
                continue;
            }

            $formatPatient = function ($p) {
                $sisaHari = $p->sisa_hari_hpl;
                $isH3 = $sisaHari !== null && $sisaHari <= 3 && $sisaHari >= -7;
                $sisaLabel = match (true) {
                    $sisaHari > 0      => "H-{$sisaHari} ({$sisaHari} hr)",
                    $sisaHari === 0    => 'Hari Ini (HPL)',
                    $sisaHari !== null => 'Lewat ' . abs($sisaHari) . ' hr',
                    default            => '-',
                };

                return [
                    'id'             => $p->id,
                    'name'           => $p->patient?->full_name ?? 'Tanpa Nama',
                    'mrn'            => $p->patient?->medical_record_number_label ?? '-',
                    'nik'            => $p->patient?->nik ?? '-',
                    'phone'          => $p->patient?->mobile_phone ?? '-',
                    'hpl'            => $p->hpl?->format('d/m/Y') ?? '-',
                    'sisa_hari'      => $sisaHari,
                    'sisa_label'     => $sisaLabel,
                    'is_h3'          => $isH3,
                    'skor'           => $p->latest_skor_puji_rochyati ?? 2,
                    'trimester'      => $p->trimester ?? '-',
                    'minggu_ke'      => $p->minggu_ke ?? '-',
                    'bidan_pengampu' => $p->creator?->name ?? 'Belum Ditentukan',
                ];
            };

            // Warna dominan atau border aksen penanda
            $hasKrst = $krstList->count() > 0;
            $hasH3 = $h3List->count() > 0;
            $hasKrt = $krtList->count() > 0;

            $primaryColor = match (true) {
                $hasH3   => '#8b5cf6',
                $hasKrst => '#e11d48',
                $hasKrt  => '#d97706',
                default  => '#059669',
            };

            $markers[] = [
                'code'          => $coord['code'],
                'name'          => $villageName,
                'lat'           => (float) $coord['lat'],
                'lng'           => (float) $coord['lng'],
                'primary_color' => $primaryColor,
                'total_bumil'   => $vPregs->count(),
                'krr_count'     => $krrList->count(),
                'krt_count'     => $krtList->count(),
                'krst_count'    => $krstList->count(),
                'h3_count'      => $h3List->count(),
                'has_krst'      => $hasKrst,
                'has_h3'        => $hasH3,
                'show_krr'      => $showKrr && $krrList->count() > 0,
                'show_krt'      => $showKrt && $krtList->count() > 0,
                'show_krst'     => $showKrst && $krstList->count() > 0,
                'show_h3'       => $showH3 && $h3List->count() > 0,
                'krr_patients'  => $krrList->map($formatPatient)->values()->all(),
                'krt_patients'  => $krtList->map($formatPatient)->values()->all(),
                'krst_patients' => $krstList->map($formatPatient)->values()->all(),
                'h3_patients'   => $h3List->map($formatPatient)->values()->all(),
            ];
        }

        return $markers;
    }

    /**
     * Total seluruh ibu hamil aktif sesuai filter usia kehamilan.
     */
    #[Computed]
    public function totalBumil(): int
    {
        return $this->allActivePregnancies
            ->when(!empty($this->filter_tm), fn($c) => $c->filter(fn($p) => (string) $p->trimester === (string) $this->filter_tm))
            ->count();
    }

    /**
     * Total kehamilan risiko rendah (KRR).
     */
    #[Computed]
    public function totalKrr(): int
    {
        return $this->allActivePregnancies
            ->when(!empty($this->filter_tm), fn($c) => $c->filter(fn($p) => (string) $p->trimester === (string) $this->filter_tm))
            ->filter(fn($p) => ($p->latest_skor_puji_rochyati ?? 2) <= 5)
            ->count();
    }

    /**
     * Total kehamilan risiko tinggi (KRT).
     */
    #[Computed]
    public function totalKrt(): int
    {
        return $this->allActivePregnancies
            ->when(!empty($this->filter_tm), fn($c) => $c->filter(fn($p) => (string) $p->trimester === (string) $this->filter_tm))
            ->filter(fn($p) => ($p->latest_skor_puji_rochyati ?? 2) >= 6 && ($p->latest_skor_puji_rochyati ?? 2) <= 10)
            ->count();
    }

    /**
     * Total kehamilan risiko sangat tinggi (KRST).
     */
    #[Computed]
    public function totalKrst(): int
    {
        return $this->allActivePregnancies
            ->when(!empty($this->filter_tm), fn($c) => $c->filter(fn($p) => (string) $p->trimester === (string) $this->filter_tm))
            ->filter(fn($p) => ($p->latest_skor_puji_rochyati ?? 2) > 10)
            ->count();
    }

    /**
     * Total ibu hamil siaga H-3 HPL (mendekati taksiran persalinan).
     */
    #[Computed]
    public function totalHMin3(): int
    {
        return $this->allActivePregnancies
            ->when(!empty($this->filter_tm), fn($c) => $c->filter(fn($p) => (string) $p->trimester === (string) $this->filter_tm))
            ->filter(fn($p) => $p->sisa_hari_hpl !== null && $p->sisa_hari_hpl <= 3 && $p->sisa_hari_hpl >= -7)
            ->count();
    }

    /**
     * Menyusun teks pengumuman suara (Text-to-Speech Bahasa Indonesia) mengenai jumlah kasus KRST dan Siaga H-3 HPL.
     */
    #[Computed]
    public function voiceAnnouncementText(): string
    {
        $stats = $this->pregnancyStats;
        $krstCount = $stats['krst_count'];
        $h3Count = $stats['h3_count'];

        $krstPhrase = $krstCount > 0
            ? "terdapat {$krstCount} ibu hamil kategori K R S T atau risiko sangat tinggi"
            : 'tidak terdapat ibu hamil kategori K R S T atau risiko sangat tinggi';

        $h3Phrase = $h3Count > 0
            ? "terdapat {$h3Count} ibu hamil siaga H minus 3 menjelang hari perkiraan lahir"
            : 'tidak terdapat ibu hamil siaga H minus 3 menjelang hari perkiraan lahir';

        $closing = ($krstCount > 0 || $h3Count > 0)
            ? ' Mohon menjadi perhatian dan pemantauan prioritas.'
            : ' Seluruh pantauan prioritas saat ini nihil.';

        return "Informasi pantauan Kantong Kehamilan Puskesmas Pragaan. Saat ini {$krstPhrase}, serta {$h3Phrase}.{$closing}";
    }

    /**
     * Seluruh daftar pasien kehamilan aktif yang berada dalam status siaga H-3 HPL
     * untuk pemantauan mendalam persalinan dekat.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function hMin3PatientsList(): array
    {
        $h3Pregnancies = $this->allActivePregnancies
            ->filter(fn($p) => $p->sisa_hari_hpl !== null && $p->sisa_hari_hpl <= 3 && $p->sisa_hari_hpl >= -7);

        $list = [];
        foreach ($h3Pregnancies as $preg) {
            $sisaHari = $preg->sisa_hari_hpl;
            $sisaLabel = match (true) {
                $sisaHari > 0   => "H-{$sisaHari} ({$sisaHari} hr lagi)",
                $sisaHari === 0 => 'Hari Ini (HPL)',
                default         => 'Lewat ' . abs($sisaHari) . ' hr',
            };

            $skor = $preg->latest_skor_puji_rochyati ?? 2;
            $riskCode = $skor > 10 ? 'KRST' : ($skor >= 6 ? 'KRT' : 'KRR');
            $riskBadgeClass = match ($riskCode) {
                'KRST'  => 'text-rose-700 bg-rose-50 border border-rose-200',
                'KRT'   => 'text-amber-700 bg-amber-50 border border-amber-200',
                default => 'text-emerald-700 bg-emerald-50 border border-emerald-200',
            };

            $list[] = [
                'pregnancy_id'     => $preg->id,
                'patient_name'     => $preg->patient?->full_name ?? 'Tanpa Nama',
                'mrn'              => $preg->patient?->medical_record_number_label ?? '-',
                'nik'              => $preg->patient?->nik ?? '-',
                'phone'            => $preg->patient?->mobile_phone ?? '-',
                'village_name'     => $preg->patient?->village?->name ?? 'Pragaan',
                'hpl'              => $preg->hpl?->format('d/m/Y') ?? '-',
                'sisa_hari'        => $sisaHari,
                'sisa_label'       => $sisaLabel,
                'skor'             => $skor,
                'risk_code'        => $riskCode,
                'risk_badge_class' => $riskBadgeClass,
                'bidan_pengampu'   => $preg->creator?->name ?? 'Belum Ditentukan',
            ];
        }

        // Urutkan berdasarkan sisa hari HPL terkecil (paling mendesak di atas)
        usort($list, fn($a, $b) => $a['sisa_hari'] <=> $b['sisa_hari']);

        return $list;
    }

    /**
     * Rekapitulasi kehamilan aktif berdasarkan alamat desa/kelurahan.
     *
     * @return array<string, array<string, mixed>>
     */
    #[Computed]
    public function pregnancyVillageSummary(): array
    {
        $activePregnancies = $this->allActivePregnancies;

        $villageGroups = [];

        foreach ($activePregnancies as $preg) {
            $villageName = $preg->patient?->village?->name ?? 'Belum Terdata';
            $villageCode = $preg->patient?->village_code ?? '';

            if (!isset($villageGroups[$villageName])) {
                $villageGroups[$villageName] = [
                    'village_name'       => $villageName,
                    'village_code'       => $villageCode,
                    'total'              => 0,
                    'krr'                => 0,
                    'krt'                => 0,
                    'krst'               => 0,
                    'tm1'                => 0,
                    'tm2'                => 0,
                    'tm3'                => 0,
                    'h3_count'           => 0,
                    'abnormal_lab_count' => 0,
                ];
            }

            $villageGroups[$villageName]['total']++;

            $skor = $preg->latest_skor_puji_rochyati ?? 2;
            if ($skor <= 5) {
                $villageGroups[$villageName]['krr']++;
            } elseif ($skor <= 10) {
                $villageGroups[$villageName]['krt']++;
            } else {
                $villageGroups[$villageName]['krst']++;
            }

            $tm = $preg->trimester;
            if ($tm >= 1 && $tm <= 3) {
                $villageGroups[$villageName]['tm' . $tm]++;
            }

            $sisa = $preg->sisa_hari_hpl;
            if ($sisa !== null && $sisa <= 3 && $sisa >= -7) {
                $villageGroups[$villageName]['h3_count']++;
            }

            if (!empty($preg->latest_abnormal_lab_results)) {
                $villageGroups[$villageName]['abnormal_lab_count']++;
            }
        }

        uasort($villageGroups, fn($a, $b) => $b['total'] <=> $a['total'] ?: strcmp($a['village_name'], $b['village_name']));

        return $villageGroups;
    }

    /**
     * Daftar pasien kehamilan aktif yang memiliki hasil laboratorium abnormal.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function abnormalLabPatients(): array
    {
        $activePregnancies = $this->allActivePregnancies;

        $result = [];

        foreach ($activePregnancies as $preg) {
            $abnormalLabs = $preg->latest_abnormal_lab_results;
            if (!empty($abnormalLabs)) {
                $bidanPengampu = $preg->creator?->name ?? 'Belum Ditentukan';

                $result[] = [
                    'pregnancy_id'          => $preg->id,
                    'patient_name'          => $preg->patient?->full_name ?? '-',
                    'mrn'                   => $preg->patient?->medical_record_number_label ?? '-',
                    'village_name'          => $preg->patient?->village?->name ?? 'Belum Terdata',
                    'usia_kehamilan'        => $preg->usia_kehamilan_saat_ini,
                    'trimester'             => $preg->trimester,
                    'skor_puji_rochyati'    => $preg->latest_skor_puji_rochyati,
                    'kategori_label'        => $preg->kategori_puji_rochyati_label,
                    'abnormal_labs'         => $abnormalLabs,
                    'bidan_pengampu'        => $bidanPengampu,
                    'latest_midwife_name'   => $preg->latestEncounter?->creator?->name ?? 'Belum Ditentukan',
                    'latest_encounter_date' => $preg->latestEncounter?->encounter_date?->format('d/m/Y') ?? '-',
                ];
            }
        }

        return $result;
    }

    /**
     * Memilih filter risiko (toggle aktif/non-aktif).
     */
    public function setFilterRisiko(string $risiko): void
    {
        if ($this->filter_risiko === $risiko) {
            $this->filter_risiko = '';
        } else {
            $this->filter_risiko = $risiko;
            $this->filter_h_min_3 = false;
        }
    }

    /**
     * Toggle filter siaga H-3 HPL.
     */
    public function toggleFilterHMin3(): void
    {
        $this->filter_h_min_3 = !$this->filter_h_min_3;
        if ($this->filter_h_min_3) {
            $this->filter_risiko = '';
        }
    }

    /**
     * Reset seluruh filter peta.
     */
    public function resetFilter(): void
    {
        $this->filter_risiko = '';
        $this->filter_tm = '';
        $this->filter_h_min_3 = false;
    }
};
