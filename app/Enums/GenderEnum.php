<?php

namespace App\Enums;

enum GenderEnum: string
{
    case MALE = "L";
    case FEMALE = "P";

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Laki-laki',
            self::FEMALE => 'Perempuan',
        };
    }

    public static function choices(): array
    {
        return [
            ['label' => self::MALE->label(), 'value' => self::MALE->value],
            ['label' => self::FEMALE->label(), 'value' => self::FEMALE->value],
        ];
    }

}
