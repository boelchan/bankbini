<?php

namespace App\Enums;

enum NeonatalPjbEnum: string
{
    case NORMAL = 'normal';
    case TIDAK_NORMAL = 'tidak_normal';

    public function label(): string
    {
        return match ($this) {
            self::NORMAL => 'Normal',
            self::TIDAK_NORMAL => 'Tidak Normal',
        };
    }

    /**
     * Opsi pilihan skrining PJB untuk TallStackUI select.
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
