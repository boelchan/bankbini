<?php

namespace App\Enums;

enum TrimesterEnum: string
{
    case TM1 = '1';
    case TM2 = '2';
    case TM3 = '3';

    public function label(): string
    {
        return match ($this) {
            self::TM1 => 'Trimester 1 (TM 1)',
            self::TM2 => 'Trimester 2 (TM 2)',
            self::TM3 => 'Trimester 3 (TM 3)',
        };
    }

    public function detailLabel(): string
    {
        return match ($this) {
            self::TM1 => 'TM 1 (≤ 11 Minggu)',
            self::TM2 => 'TM 2 (12 - 24 Minggu)',
            self::TM3 => 'TM 3 (≥ 25 Minggu)',
        };
    }

    /**
     * Opsi pilihan trimester untuk dropdown filter TallStackUI.
     *
     * @return array<int, array<string, string>>
     */
    public static function choices(): array
    {
        return [
            ['label' => 'Semua Trimester', 'value' => 'semua'],
            ['label' => self::TM1->detailLabel(), 'value' => self::TM1->value],
            ['label' => self::TM2->detailLabel(), 'value' => self::TM2->value],
            ['label' => self::TM3->detailLabel(), 'value' => self::TM3->value],
        ];
    }
}

