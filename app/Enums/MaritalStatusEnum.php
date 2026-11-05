<?php

namespace App\Enums;

enum MaritalStatusEnum: string
{
    case BELUM_KAWIN = 'Belum Kawin';
    case KAWIN = 'Kawin';
    case CERAI_HIDUP = 'Cerai Hidup';
    case CERAI_MATI = 'Cerai Mati';

    public function label(): string
    {
        return $this->value;
    }

    public static function choices(): array
    {
        return array_map(fn ($enum) => ['label' => $enum->value, 'value' => $enum->value], self::cases());
    }
}
