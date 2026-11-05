<?php

namespace App\Enums;

enum NationalityEnum: string
{
    case WNI = 'WNI';
    case WNA = 'WNA';

    public function label(): string
    {
        return $this->value;
    }

    public static function choices(): array
    {
        return array_map(fn ($enum) => ['label' => $enum->value, 'value' => $enum->value], self::cases());
    }
}
