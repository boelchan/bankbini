<?php

namespace App\Enums;

enum EducationEnum: string
{
    case SD = 'SD';
    case SMP = 'SMP';
    case SMA = 'SMA';
    case D1 = 'D1';
    case D2 = 'D2';
    case D3 = 'D3';
    case S1 = 'S1';
    case S2 = 'S2';
    case S3 = 'S3';
    case TIDAK_SEKOLAH = 'Tidak Sekolah';

    public function label(): string
    {
        return $this->value;
    }

    public static function choices(): array
    {
        return array_map(fn($enum) => ['label' => $enum->value, 'value' => $enum->value], self::cases());
    }
}
