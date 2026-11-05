<?php

namespace App\Enums;

enum PerineumConditionEnum: string
{
    case BAIK_NORMAL = 'baik_normal';
    case JAHITAN_UTUH = 'jahitan_utuh';
    case BENGKAK_EDEMA = 'bengkak_edema';
    case KEMERAHAN = 'kemerahan';
    case JAHITAN_TERBUKA = 'jahitan_terbuka';
    case INFEKSI = 'infeksi';

    public function label(): string
    {
        return match ($this) {
            self::BAIK_NORMAL => 'Baik/Normal',
            self::JAHITAN_UTUH => 'Jahitan Utuh',
            self::BENGKAK_EDEMA => 'Bengkak/Edema',
            self::KEMERAHAN => 'Kemerahan',
            self::JAHITAN_TERBUKA => 'Jahitan Terbuka',
            self::INFEKSI => 'Infeksi',
        };
    }

    /**
     * Opsi pilihan kondisi luka jalan lahir / perineum untuk TallStackUI select.
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
