<?php

namespace App\Enums;

enum LactationConditionEnum: string
{
    case LANCAR = 'lancar';
    case CUKUP = 'cukup';
    case BELUM_LANCAR = 'belum_lancar';
    case TIDAK_KELUAR = 'tidak_keluar';
    case PUTING_LECET = 'puting_lecet';

    public function label(): string
    {
        return match ($this) {
            self::LANCAR => 'Lancar',
            self::CUKUP => 'Cukup / Sedang',
            self::BELUM_LANCAR => 'Belum Lancar / Sedikit',
            self::TIDAK_KELUAR => 'Tidak Keluar / Kering',
            self::PUTING_LECET => 'Puting Lecet / Nyeri Menyusui',
        };
    }

    /**
     * Opsi pilihan kondisi produksi & pengeluaran ASI untuk TallStackUI select.
     *
     * @return array<int, array<string, string>>
     */
    public static function choices(): array
    {
        return array_map(
            fn(self $enum) => ['label' => $enum->label(), 'value' => $enum->value],
            self::cases()
        );
    }

    /**
     * Seluruh nilai string enum.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
