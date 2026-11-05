<?php

namespace App\Enums;

enum NeonatalSkinColorEnum: string
{
    case KEMERAHAN = 'kemerahan';
    case IKTERUS = 'ikterus';
    case SIANOSIS = 'sianosis';
    case PUCAT = 'pucat';

    public function label(): string
    {
        return match ($this) {
            self::KEMERAHAN => 'Kemerahan (Normal)',
            self::IKTERUS => 'Ikterus / Kuning',
            self::SIANOSIS => 'Sianosis / Kebiruan',
            self::PUCAT => 'Pucat',
        };
    }

    /**
     * Opsi pilihan warna kulit bayi untuk TallStackUI select.
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
