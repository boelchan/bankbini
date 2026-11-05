<?php

namespace App\Enums;

enum CongenitalHypothyroidResultEnum: string
{
    case NEGATIF = 'negatif';
    case POSITIF = 'positif';
    case TIDAK_DIPERIKSA = 'tidak_diperiksa';

    public function label(): string
    {
        return match ($this) {
            self::NEGATIF => 'Negatif (-) / Normal',
            self::POSITIF => 'Positif (+) HK',
            self::TIDAK_DIPERIKSA => 'Belum / Tidak Diperiksa',
        };
    }

    /**
     * Opsi pilihan hasil skrining hipotiroid kongenital untuk TallStackUI select.
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
