<?php

namespace App\Enums;

enum StatusEncounterEnum: string
{
    case REGISTERED = 'registered';
    case ARRIVED = 'arrived';
    case INPROGRESS = 'inprogress';
    case FINISHED = 'finished';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::REGISTERED => 'Baru',
            self::ARRIVED => 'Datang',
            self::INPROGRESS => 'Dalam Proses',
            self::FINISHED => 'Selesai',
            self::CANCELLED => 'Batal',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::REGISTERED => '<span class="badge badge-soft badge-info h-auto">' . self::REGISTERED->label() . '</span>',
            self::ARRIVED => '<span class="badge badge-soft badge-primary h-auto">' . self::ARRIVED->label() . '</span>',
            self::INPROGRESS => '<span class="badge badge-soft badge-warning h-auto">' . self::INPROGRESS->label() . '</span>',
            self::FINISHED => '<span class="badge badge-soft badge-success h-auto">' . self::FINISHED->label() . '</span>',
            self::CANCELLED => '<span class="badge badge-soft badge-error h-auto">' . self::CANCELLED->label() . '</span>',
        };
    }

    public static function choices(): array
    {
        return [
            [
                'value' => self::REGISTERED->value,
                'label' => self::REGISTERED->label(),
            ],
            [
                'value' => self::ARRIVED->value,
                'label' => self::ARRIVED->label(),
            ],
            [
                'value' => self::INPROGRESS->value,
                'label' => self::INPROGRESS->label(),
            ],
            [
                'value' => self::FINISHED->value,
                'label' => self::FINISHED->label(),
            ],
            [
                'value' => self::CANCELLED->value,
                'label' => self::CANCELLED->label(),
            ],
        ];
    }
}
