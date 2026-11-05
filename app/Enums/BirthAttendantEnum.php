<?php

namespace App\Enums;

enum BirthAttendantEnum: string
{
    case DOKTER_SPOG = 'Dokter SpOG';
    case DOKTER_UMUM = 'Dokter Umum';
    case BIDAN = 'Bidan';
    case DUKUN = 'Dukun';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * Opsi penolong persalinan untuk TallStackUI select.
     */
    public static function choices(): array
    {
        $cases = [
            self::DOKTER_SPOG,
            self::DOKTER_UMUM,
            self::BIDAN,
            self::DUKUN,
        ];

        return array_map(
            fn(self $enum) => ['label' => $enum->value, 'value' => $enum->value],
            $cases
        );
    }

    /**
     * Array seluruh nilai string.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
