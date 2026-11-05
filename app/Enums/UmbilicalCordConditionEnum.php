<?php

namespace App\Enums;

enum UmbilicalCordConditionEnum: string
{
    case BERSIH_KERING = 'bersih_kering';
    case BASAH = 'basah';
    case KEMERAHAN = 'kemerahan';
    case BERBAU = 'berbau';
    case SUDAH_LEPAS = 'sudah_lepas';

    public function label(): string
    {
        return match ($this) {
            self::BERSIH_KERING => 'Bersih & Kering (Normal)',
            self::BASAH => 'Basah / Lembap',
            self::KEMERAHAN => 'Kemerahan / Meradang',
            self::BERBAU => 'Berbau / Bernanah',
            self::SUDAH_LEPAS => 'Sudah Lepas / Puput',
        };
    }

    /**
     * Opsi pilihan kondisi tali pusat bayi untuk TallStackUI select.
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
