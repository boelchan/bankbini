<?php

namespace App\Enums;

enum ReligionEnum: string
{
    case ISLAM = 'Islam';
    case KRISTEN = 'Kristen';
    case KATOLIK = 'Katolik';
    case HINDU = 'Hindu';
    case BUDDHA = 'Buddha';
    case KHONGHUCU = 'Khonghucu';
    case LAINNYA = 'Lainnya';

    public function label(): string
    {
        return $this->value;
    }

    public static function choices(): array
    {
        return array_map(fn($enum) => ['label' => $enum->value, 'value' => $enum->value], self::cases());
    }
}
