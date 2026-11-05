<?php

namespace App\Enums;

enum LocheaTypeEnum: string
{
    case RUBRA = 'Rubra';
    case SANGUINOLENTA = 'Sanguinolenta';
    case SEROSA = 'Serosa';
    case ALBA = 'Alba';
    case PURULENTA = 'Purulenta';

    public function label(): string
    {
        return match ($this) {
            self::RUBRA => 'Rubra (Merah segar, hari 1-3)',
            self::SANGUINOLENTA => 'Sanguinolenta (Merah kekuningan, hari 4-7)',
            self::SEROSA => 'Serosa (Kuning / kecokelatan, hari 8-14)',
            self::ALBA => 'Alba (Putih kekuningan / jernih, > 14 hari)',
            self::PURULENTA => 'Purulenta (Nanah / bau busuk - Infeksi)',
        };
    }

    /**
     * Opsi pilihan jenis lochea untuk TallStackUI select.
     *
     * @return array<int, array<string, string>>
     */
    public static function choices(): array
    {
        return array_map(
            fn(self $enum) => ['label' => $enum->label(), 'value' => $enum->value],
            self::cases()
        );
    }

    /**
     * Seluruh nilai string enum.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
