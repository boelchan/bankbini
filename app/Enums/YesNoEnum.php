<?php

namespace App\Enums;

enum YesNoEnum: string
{
    case YA = 'ya';
    case TIDAK = 'tidak';

    public function label(): string
    {
        return match ($this) {
            self::YA => 'Ya',
            self::TIDAK => 'Tidak',
        };
    }

    /**
     * Opsi pilihan Ya / Tidak untuk TallStackUI select dengan label fleksibel.
     *
     * @return array<int, array<string, string>>
     */
    public static function choices(?string $yesLabel = 'Ya', ?string $noLabel = 'Tidak'): array
    {
        return [
            ['label' => $yesLabel ?? 'Ya', 'value' => self::YA->value],
            ['label' => $noLabel ?? 'Tidak', 'value' => self::TIDAK->value],
        ];
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
