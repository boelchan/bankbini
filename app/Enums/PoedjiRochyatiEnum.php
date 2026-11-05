<?php

namespace App\Enums;

/**
 * Enum Faktor Risiko Poedji Rochyati untuk Skrining Kehamilan.
 */
enum PoedjiRochyatiEnum: string
{
    // I. Ada Potensi Bahaya (APB) - Skor +4 (Kecuali No. 10 SC Skor +8)
    case TERLALU_MUDA_HAMIL_1 = 'terlalu_muda_hamil_1';
    case TERLALU_TUA_HAMIL_1 = 'terlalu_tua_hamil_1';
    case LAMBAT_HAMIL_1 = 'lambat_hamil_1';
    case TERLALU_LAMA_HAMIL_LAGI = 'terlalu_lama_hamil_lagi';
    case TERLALU_CEPAT_HAMIL_LAGI = 'terlalu_cepat_hamil_lagi';
    case ANAK_BANYAK = 'anak_banyak';
    case TERLALU_TUA_UMUR = 'terlalu_tua_umur';
    case TERLALU_PENDEK = 'terlalu_pendek';
    case PERNAH_GAGAL_KEHAMILAN = 'pernah_gagal_kehamilan';
    case PERNAH_LAHIR_VAKUM_TANG = 'pernah_lahir_vakum_tang';
    case URI_DIROGOH = 'uri_dirogoh';
    case PERNAH_INFUS_TRANSFUSI = 'pernah_infus_transfusi';
    case PERNAH_OPERASI_SESAR = 'pernah_operasi_sesar';

    // II. Ada Bahaya (AB) - Skor +4 (Kecuali No. 17 & 18 Kelainan Letak Skor +8)
    case PENYAKIT_KURANG_DARAH = 'penyakit_kurang_darah';
    case PENYAKIT_MALARIA = 'penyakit_malaria';
    case PENYAKIT_TBC = 'penyakit_tbc';
    case PENYAKIT_JANTUNG = 'penyakit_jantung';
    case PENYAKIT_DIABETES = 'penyakit_diabetes';
    case PENYAKIT_PMS = 'penyakit_pms';
    case BENGKAK_TENSI_TINGGI = 'bengkak_tensi_tinggi';
    case HAMIL_KEMBAR = 'hamil_kembar';
    case HYDRAMNION = 'hydramnion';
    case BAYI_MATI_KANDUNGAN = 'bayi_mati_kandungan';
    case KEHAMILAN_LEBIH_BULAN = 'kehamilan_lebih_bulan';
    case LETAK_SUNGSANG = 'letak_sungsang';
    case LETAK_LINTANG = 'letak_lintang';

    // III. Ada Gawat Darurat (AGD) - Skor +8
    case PERDARAHAN_HAMIL_INI = 'perdarahan_hamil_ini';
    case PREEKLAMSIA_KEJANG = 'preeklamsia_kejang';

    /**
     * Dapatkan label resmi faktor risiko Poedji Rochyati sesuai panduan klinis.
     */
    public function label(): string
    {
        return match ($this) {
            self::TERLALU_MUDA_HAMIL_1     => '1. Terlalu muda hamil pertama ≤ 16 Tahun',
            self::TERLALU_TUA_HAMIL_1      => '2.a. Terlalu tua hamil pertama ≥ 35 Tahun',
            self::LAMBAT_HAMIL_1           => '2.b. Terlalu lambat hamil I setelah kawin ≥ 4 Tahun',
            self::TERLALU_LAMA_HAMIL_LAGI  => '3. Terlalu lama hamil lagi ≥ 10 Tahun',
            self::TERLALU_CEPAT_HAMIL_LAGI => '4. Terlalu cepat hamil lagi ≤ 2 Tahun',
            self::ANAK_BANYAK              => '5. Terlalu banyak anak, 4 atau lebih',
            self::TERLALU_TUA_UMUR         => '6. Terlalu tua umur ≥ 35 Tahun',
            self::TERLALU_PENDEK           => '7. Terlalu pendek ≤ 145 cm',
            self::PERNAH_GAGAL_KEHAMILAN   => '8. Pernah gagal kehamilan (keguguran)',
            self::PERNAH_LAHIR_VAKUM_TANG  => '9.a. Melahirkan tarikan tang / vakum',
            self::URI_DIROGOH              => '9.b. Melahirkan dengan uri dirogoh',
            self::PERNAH_INFUS_TRANSFUSI   => '9.c. Melahirkan diberi infus / transfusi',
            self::PERNAH_OPERASI_SESAR     => '10. Pernah operasi sesar* [+8]',

            self::PENYAKIT_KURANG_DARAH    => '11.a. Penyakit Kurang Darah / Anemia',
            self::PENYAKIT_MALARIA         => '11.b. Penyakit Malaria',
            self::PENYAKIT_TBC             => '11.c. Penyakit TBC Paru',
            self::PENYAKIT_JANTUNG         => '11.d. Payah Jantung',
            self::PENYAKIT_DIABETES        => '11.e. Kencing Manis (Diabetes)',
            self::PENYAKIT_PMS             => '11.f. Penyakit Menular Seksual (PMS)',
            self::BENGKAK_TENSI_TINGGI     => '12. Bengkak muka/tungkai & tekanan darah tinggi',
            self::HAMIL_KEMBAR             => '13. Hamil kembar',
            self::HYDRAMNION               => '14. Hydramnion (kembar air)',
            self::BAYI_MATI_KANDUNGAN      => '15. Bayi mati dalam kandungan (IUFD)',
            self::KEHAMILAN_LEBIH_BULAN    => '16. Kehamilan lebih bulan (Serotinus)',
            self::LETAK_SUNGSANG           => '17. Letak sungsang* [+8]',
            self::LETAK_LINTANG            => '18. Letak lintang* [+8]',

            self::PERDARAHAN_HAMIL_INI     => '19. Perdarahan dalam kehamilan ini* [+8]',
            self::PREEKLAMSIA_KEJANG       => '20. Preeklamsia / kejang-kejang* [+8]',
        };
    }

    /**
     * Dapatkan label faktor risiko tanpa nomor urut (untuk badge / tampilan ringkas).
     */
    public function labelAlias(): string
    {
        return match ($this) {
            self::TERLALU_MUDA_HAMIL_1     => 'Terlalu muda hamil pertama ≤ 16 Tahun',
            self::TERLALU_TUA_HAMIL_1      => 'Terlalu tua hamil pertama ≥ 35 Tahun',
            self::LAMBAT_HAMIL_1           => 'Terlalu lambat hamil I setelah kawin ≥ 4 Tahun',
            self::TERLALU_LAMA_HAMIL_LAGI  => 'Terlalu lama hamil lagi ≥ 10 Tahun',
            self::TERLALU_CEPAT_HAMIL_LAGI => 'Terlalu cepat hamil lagi ≤ 2 Tahun',
            self::ANAK_BANYAK              => 'Terlalu banyak anak, 4 atau lebih',
            self::TERLALU_TUA_UMUR         => 'Terlalu tua umur ≥ 35 Tahun',
            self::TERLALU_PENDEK           => 'Terlalu pendek ≤ 145 cm',
            self::PERNAH_GAGAL_KEHAMILAN   => 'Pernah gagal kehamilan (keguguran)',
            self::PERNAH_LAHIR_VAKUM_TANG  => 'Melahirkan tarikan tang / vakum',
            self::URI_DIROGOH              => 'Melahirkan dengan uri dirogoh',
            self::PERNAH_INFUS_TRANSFUSI   => 'Melahirkan diberi infus / transfusi',
            self::PERNAH_OPERASI_SESAR     => 'Pernah operasi sesar',

            self::PENYAKIT_KURANG_DARAH    => 'Penyakit Kurang Darah / Anemia',
            self::PENYAKIT_MALARIA         => 'Penyakit Malaria',
            self::PENYAKIT_TBC             => 'Penyakit TBC Paru',
            self::PENYAKIT_JANTUNG         => 'Payah Jantung',
            self::PENYAKIT_DIABETES        => 'Kencing Manis (Diabetes)',
            self::PENYAKIT_PMS             => 'Penyakit Menular Seksual (PMS)',
            self::BENGKAK_TENSI_TINGGI     => 'Bengkak muka/tungkai & tekanan darah tinggi',
            self::HAMIL_KEMBAR             => 'Hamil kembar',
            self::HYDRAMNION               => 'Hydramnion (kembar air)',
            self::BAYI_MATI_KANDUNGAN      => 'Bayi mati dalam kandungan (IUFD)',
            self::KEHAMILAN_LEBIH_BULAN    => 'Kehamilan lebih bulan (Serotinus)',
            self::LETAK_SUNGSANG           => 'Letak sungsang',
            self::LETAK_LINTANG            => 'Letak lintang',

            self::PERDARAHAN_HAMIL_INI     => 'Perdarahan dalam kehamilan ini',
            self::PREEKLAMSIA_KEJANG       => 'Preeklamsia / kejang-kejang',
        };
    }

    /**
     * Dapatkan skor angka dari faktor risiko.
     */
    public function score(): int
    {
        return match ($this) {
            self::PERNAH_OPERASI_SESAR,
            self::LETAK_SUNGSANG,
            self::LETAK_LINTANG,
            self::PERDARAHAN_HAMIL_INI,
            self::PREEKLAMSIA_KEJANG => 8,
            default                  => 4,
        };
    }

    /**
     * Dapatkan nama kelompok risiko faktor ini.
     */
    public function group(): string
    {
        return match ($this) {
            self::TERLALU_MUDA_HAMIL_1,
            self::TERLALU_TUA_HAMIL_1,
            self::LAMBAT_HAMIL_1,
            self::TERLALU_LAMA_HAMIL_LAGI,
            self::TERLALU_CEPAT_HAMIL_LAGI,
            self::ANAK_BANYAK,
            self::TERLALU_TUA_UMUR,
            self::TERLALU_PENDEK,
            self::PERNAH_GAGAL_KEHAMILAN,
            self::PERNAH_LAHIR_VAKUM_TANG,
            self::URI_DIROGOH,
            self::PERNAH_INFUS_TRANSFUSI,
            self::PERNAH_OPERASI_SESAR     => 'Ada Potensi Bahaya (APB)',

            self::PENYAKIT_KURANG_DARAH,
            self::PENYAKIT_MALARIA,
            self::PENYAKIT_TBC,
            self::PENYAKIT_JANTUNG,
            self::PENYAKIT_DIABETES,
            self::PENYAKIT_PMS,
            self::BENGKAK_TENSI_TINGGI,
            self::HAMIL_KEMBAR,
            self::HYDRAMNION,
            self::BAYI_MATI_KANDUNGAN,
            self::KEHAMILAN_LEBIH_BULAN,
            self::LETAK_SUNGSANG,
            self::LETAK_LINTANG            => 'Ada Bahaya (AB)',

            self::PERDARAHAN_HAMIL_INI,
            self::PREEKLAMSIA_KEJANG       => 'Ada Gawat Darurat (AGD)',
        };
    }

    /**
     * Dapatkan nomor romawi kelompok risiko.
     */
    public function groupRoman(): string
    {
        return match ($this) {
            self::TERLALU_MUDA_HAMIL_1,
            self::TERLALU_TUA_HAMIL_1,
            self::LAMBAT_HAMIL_1,
            self::TERLALU_LAMA_HAMIL_LAGI,
            self::TERLALU_CEPAT_HAMIL_LAGI,
            self::ANAK_BANYAK,
            self::TERLALU_TUA_UMUR,
            self::TERLALU_PENDEK,
            self::PERNAH_GAGAL_KEHAMILAN,
            self::PERNAH_LAHIR_VAKUM_TANG,
            self::URI_DIROGOH,
            self::PERNAH_INFUS_TRANSFUSI,
            self::PERNAH_OPERASI_SESAR     => 'I',

            self::PENYAKIT_KURANG_DARAH,
            self::PENYAKIT_MALARIA,
            self::PENYAKIT_TBC,
            self::PENYAKIT_JANTUNG,
            self::PENYAKIT_DIABETES,
            self::PENYAKIT_PMS,
            self::BENGKAK_TENSI_TINGGI,
            self::HAMIL_KEMBAR,
            self::HYDRAMNION,
            self::BAYI_MATI_KANDUNGAN,
            self::KEHAMILAN_LEBIH_BULAN,
            self::LETAK_SUNGSANG,
            self::LETAK_LINTANG            => 'II',

            self::PERDARAHAN_HAMIL_INI,
            self::PREEKLAMSIA_KEJANG       => 'III',
        };
    }

    /**
     * Dapatkan warna indikator (amber untuk APB/AB, red untuk risti/AGD).
     */
    public function color(): string
    {
        return match ($this) {
            self::PERNAH_OPERASI_SESAR,
            self::LETAK_SUNGSANG,
            self::LETAK_LINTANG,
            self::PERDARAHAN_HAMIL_INI,
            self::PREEKLAMSIA_KEJANG => 'red',
            default                  => 'amber',
        };
    }

    /**
     * Dapatkan label human-readable dari string key (mendukung alias lama).
     */
    public static function getLabel(string $key): string
    {
        $case = self::tryFrom($key);
        if ($case) {
            return $case->label();
        }

        $aliases = self::aliases();
        if (isset($aliases[$key])) {
            $aliasCase = self::tryFrom($aliases[$key]);
            if ($aliasCase) {
                return $aliasCase->label();
            }
        }

        return str_replace('_', ' ', $key);
    }

    /**
     * Dapatkan label alias tanpa nomor urut dari string key (mendukung alias lama).
     */
    public static function getLabelAlias(string $key): string
    {
        $case = self::tryFrom($key);
        if ($case) {
            return $case->labelAlias();
        }

        $aliases = self::aliases();
        if (isset($aliases[$key])) {
            $aliasCase = self::tryFrom($aliases[$key]);
            if ($aliasCase) {
                return $aliasCase->labelAlias();
            }
        }

        return str_replace('_', ' ', $key);
    }

    /**
     * Dapatkan skor dari string key (mendukung alias lama).
     */
    public static function getScore(string $key): int
    {
        $case = self::tryFrom($key);
        if ($case) {
            return $case->score();
        }

        $aliases = self::aliases();
        if (isset($aliases[$key])) {
            $aliasCase = self::tryFrom($aliases[$key]);
            if ($aliasCase) {
                return $aliasCase->score();
            }
        }

        return 0;
    }

    /**
     * Hitung total skor Poedji Rochyati dari array faktor risiko yang dicentang.
     * Skor awal setiap ibu hamil adalah 2.
     *
     * @param array<string> $selectedFactors
     */
    public static function calculateScore(array $selectedFactors): int
    {
        $total = 2; // Skor Awal Ibu Hamil
        $counted = [];

        foreach ($selectedFactors as $key) {
            // Normalkan key jika berupa alias
            $normalizedKey = self::aliases()[$key] ?? $key;
            if (!in_array($normalizedKey, $counted, true)) {
                $total += self::getScore($normalizedKey);
                $counted[] = $normalizedKey;
            }
        }

        return $total;
    }

    /**
     * Dapatkan kategori risiko berdasarkan skor total.
     */
    public static function getCategory(int $score): string
    {
        if ($score <= 5) {
            return 'Kehamilan Risiko Rendah';
        }

        if ($score <= 10) {
            return 'Kehamilan Risiko Tinggi';
        }

        return 'Kehamilan Risiko Sangat Tinggi';
    }

    /**
     * Dapatkan kode singkatan kategori risiko (KRR, KRT, KRST).
     */
    public static function getCategoryCode(int $score): string
    {
        if ($score <= 5) {
            return 'KRR';
        }

        if ($score <= 10) {
            return 'KRT';
        }

        return 'KRST';
    }

    /**
     * Daftar seluruh label faktor risiko (termasuk alias untuk view/select).
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $map = [];
        foreach (self::cases() as $case) {
            $map[$case->value] = $case->label();
        }

        foreach (self::aliases() as $aliasKey => $targetKey) {
            if (!isset($map[$aliasKey])) {
                $map[$aliasKey] = self::getLabel($targetKey);
            }
        }

        return $map;
    }

    /**
     * Pemetaan alias lama untuk kompatibilitas data lama di database.
     *
     * @return array<string, string>
     */
    public static function aliases(): array
    {
        return [
            'bengkak_muka_tungkai_hipertensi' => self::BENGKAK_TENSI_TINGGI->value,
            'hamil_kembar_2_lebih'            => self::HAMIL_KEMBAR->value,
            'hamil_kembar_air'                => self::HYDRAMNION->value,
            'bayi_mati_dlm_kandungan'         => self::BAYI_MATI_KANDUNGAN->value,
            'perdarahan_sebelum_bayi_lahir'   => self::PERDARAHAN_HAMIL_INI->value,
            'preeklampsia_berat_kejang'       => self::PREEKLAMSIA_KEJANG->value,
            'hamil_muda_tua'                  => self::TERLALU_MUDA_HAMIL_1->value,
            'kawin_lama'                      => self::LAMBAT_HAMIL_1->value,
            'jarak_dekat'                     => self::TERLALU_CEPAT_HAMIL_LAGI->value,
            'jarak_jauh'                      => self::TERLALU_LAMA_HAMIL_LAGI->value,
            'tb_pendek'                       => self::TERLALU_PENDEK->value,
            'pernah_keguguran'                => self::PERNAH_GAGAL_KEHAMILAN->value,
            'pernah_sc_vakum'                 => self::PERNAH_OPERASI_SESAR->value,
            'anemia'                          => self::PENYAKIT_KURANG_DARAH->value,
            'preeklamsia'                     => self::PREEKLAMSIA_KEJANG->value,
            'kelainan_letak'                  => self::LETAK_SUNGSANG->value,
            'perdarahan'                      => self::PERDARAHAN_HAMIL_INI->value,
        ];
    }

    /**
     * Dapatkan daftar faktor risiko yang dikelompokkan berdasarkan kategori (I, II, III).
     * Sangat berguna untuk rendering form / checkbox grup di view atau komponen.
     *
     * @return array<string, array{
     *     title: string,
     *     subtitle: string,
     *     badge_bg: string,
     *     badge_text: string,
     *     badge_border: string,
     *     border_class: string,
     *     bg_class: string,
     *     title_class: string,
     *     count_class: string,
     *     cases: list<\App\Enums\PoedjiRochyatiEnum>
     * }>
     */
    public static function groupedCases(): array
    {
        $groups = [
            'I' => [
                'title'        => 'Ada Potensi Bahaya (APB)',
                'subtitle'     => 'Skor +4 (No. 10 Sesar +8)',
                'badge_bg'     => 'bg-slate-200',
                'badge_text'   => 'text-slate-700',
                'badge_border' => 'border-slate-300',
                'border_class' => 'border-slate-200',
                'bg_class'     => 'bg-slate-50/80',
                'title_class'  => 'text-slate-800',
                'count_class'  => 'text-slate-500',
                'cases'        => [],
            ],
            'II' => [
                'title'        => 'Ada Bahaya (AB)',
                'subtitle'     => 'Skor +4 (No. 17 & 18 Kelainan Letak +8)',
                'badge_bg'     => 'bg-amber-200',
                'badge_text'   => 'text-amber-900',
                'badge_border' => 'border-amber-300',
                'border_class' => 'border-amber-300/80',
                'bg_class'     => 'bg-amber-50/60',
                'title_class'  => 'text-amber-950',
                'count_class'  => 'text-amber-700',
                'cases'        => [],
            ],
            'III' => [
                'title'        => 'Ada Gawat Darurat (AGD)',
                'subtitle'     => 'Skor +8',
                'badge_bg'     => 'bg-rose-200',
                'badge_text'   => 'text-rose-900',
                'badge_border' => 'border-rose-300',
                'border_class' => 'border-rose-300/80',
                'bg_class'     => 'bg-rose-50/70',
                'title_class'  => 'text-rose-950',
                'count_class'  => 'text-rose-700',
                'cases'        => [],
            ],
        ];

        foreach (self::cases() as $case) {
            $groups[$case->groupRoman()]['cases'][] = $case;
        }

        return $groups;
    }

    /**
     * Dapatkan seluruh opsi untuk dropdown / select / data transfer.
     *
     * @return array<int, array{label: string, value: string, score: int, group: string, color: string}>
     */
    public static function options(): array
    {
        return array_map(fn(self $case) => [
            'label' => $case->label(),
            'value' => $case->value,
            'score' => $case->score(),
            'group' => $case->group(),
            'color' => $case->color(),
        ], self::cases());
    }
}

