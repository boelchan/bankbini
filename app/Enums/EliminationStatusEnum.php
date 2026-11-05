<?php

namespace App\Enums;

enum EliminationStatusEnum: string
{
    case YA = 'ya';
    case TIDAK = 'tidak';

    public function label(): string
    {
        return match ($this) {
            self::YA => 'Ya (Lancar / Normal)',
            self::TIDAK => 'Tidak (Belum / Sulit / Sakit)',
        };
    }

    /**
     * Opsi pilihan BAB/BAK untuk TallStackUI select.
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
