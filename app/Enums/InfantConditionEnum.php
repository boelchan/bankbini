<?php

namespace App\Enums;

enum InfantConditionEnum: string
{
    case LAHIR_HIDUP = 'Lahir Hidup';
    case LAHIR_MATI = 'Lahir Mati';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * Opsi pilihan kondisi lahir bayi untuk TallStackUI select.
     *
     * @return array<int, array<string, string>>
     */
    public static function choices(): array
    {
        return array_map(
            fn(self $enum) => ['label' => $enum->value, 'value' => $enum->value],
            self::cases()
        );
    }

    /**
     * Warna semantik Tailwind.
     */
    public function color(): string
    {
        return match ($this) {
            self::LAHIR_HIDUP => 'emerald',
            self::LAHIR_MATI => 'rose',
        };
    }
}
