<?php

namespace App\Enums;

enum PostnatalTfuEnum: string
{
    case SETINGGI_PUSAT = 'setinggi_pusat';
    case SATU_JARI_BAWAH_PUSAT = 'satu_jari_di_bawah_pusat';
    case DUA_JARI_BAWAH_PUSAT = 'dua_jari_di_bawah_pusat';
    case PERTENGAHAN_PUSAT_SIMFISIS = 'pertengahan_pusat_simfisis';
    case TIDAK_TERABA = 'tidak_teraba';

    public function label(): string
    {
        return match ($this) {
            self::SETINGGI_PUSAT => 'Setinggi Pusat',
            self::SATU_JARI_BAWAH_PUSAT => '1 Jari di Bawah Pusat',
            self::DUA_JARI_BAWAH_PUSAT => '2 Jari di Bawah Pusat',
            self::PERTENGAHAN_PUSAT_SIMFISIS => 'Pertengahan Pusat - Simfisis',
            self::TIDAK_TERABA => 'Tidak Teraba di Atas Simfisis',
        };
    }

    /**
     * Opsi pilihan TFU masa nifas untuk TallStackUI select.
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
