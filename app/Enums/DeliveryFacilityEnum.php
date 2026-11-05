<?php

namespace App\Enums;

enum DeliveryFacilityEnum: string
{
    case POLINDES = 'Polindes';
    case RS = 'RS';
    case PUSKESMAS_PONED = 'Puskesmas Poned';
    case PUSKESMAS_NON_PONED = 'Puskesmas Non Poned';
    case KLINIK = 'Klinik';
    case PMB = 'PMB';
    case RUMAH = 'Rumah';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * Opsi fasilitas / tempat persalinan untuk TallStackUI select.
     */
    public static function choices(): array
    {
        return array_map(
            fn(self $enum) => ['label' => $enum->value, 'value' => $enum->value],
            self::cases()
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
