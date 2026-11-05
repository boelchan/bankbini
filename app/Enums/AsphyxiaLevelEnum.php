<?php

namespace App\Enums;

enum AsphyxiaLevelEnum: string
{
    case NORMAL = 'normal';
    case RINGAN = 'ringan';
    case SEDANG = 'sedang';
    case BERAT = 'berat';

    public function label(): string
    {
        return match ($this) {
            self::NORMAL => 'Normal / Tidak Asfiksia',
            self::RINGAN => 'Asfiksia Ringan',
            self::SEDANG => 'Asfiksia Sedang',
            self::BERAT => 'Asfiksia Berat',
        };
    }

    /**
     * Opsi pilihan tingkat asfiksia untuk TallStackUI select.
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
