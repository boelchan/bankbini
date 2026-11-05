<?php

namespace App\Enums;

enum RolesEnum: string
{
    case ADMINISTRATOR = 'administrator';
    case DOKTER_SPOG = 'dokter-spog';
    case DOKTER_UMUM = 'dokter-umum';
    case KOORDINATOR_BIDAN = 'koordinator-bidan';
    case BIDAN = 'bidan';

    public function label(): string
    {
        return match ($this) {
            self::ADMINISTRATOR => 'Administrator',
            self::DOKTER_SPOG => 'Dokter SPOG',
            self::DOKTER_UMUM => 'Dokter Umum',
            self::KOORDINATOR_BIDAN => 'Koordinator Bidan',
            self::BIDAN => 'Bidan',
        };
    }

    public static function choices(): array
    {
        return [
            self::ADMINISTRATOR->value => self::ADMINISTRATOR->label(),
            self::DOKTER_SPOG->value => self::DOKTER_SPOG->label(),
            self::DOKTER_UMUM->value => self::DOKTER_UMUM->label(),
            self::KOORDINATOR_BIDAN->value => self::KOORDINATOR_BIDAN->label(),
            self::BIDAN->value => self::BIDAN->label(),
        ];
    }
}
