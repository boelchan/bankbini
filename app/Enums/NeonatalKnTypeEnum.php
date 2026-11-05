<?php

namespace App\Enums;

enum NeonatalKnTypeEnum: string
{
    case KN1 = 'KN1';
    case KN2 = 'KN2';
    case KN3 = 'KN3';
    case LEWAT_KN3 = '> KN3';

    public function label(): string
    {
        return match ($this) {
            self::KN1 => 'KN1 (Usia 0 - 2 Hari / 6-48 Jam)',
            self::KN2 => 'KN2 (Usia 3 - 7 Hari)',
            self::KN3 => 'KN3 (Usia 8 - 28 Hari)',
            self::LEWAT_KN3 => '> KN3 (Lewat Masa Neonatal)',
        };
    }

    /**
     * Opsi pilihan jenis kunjungan neonatal untuk TallStackUI select.
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
