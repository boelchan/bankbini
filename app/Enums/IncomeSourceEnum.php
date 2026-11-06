<?php

declare(strict_types=1);

namespace App\Enums;

enum IncomeSourceEnum: string
{
    case DIRECT = 'direct';
    case FROM_POCKET = 'from_pocket';

    /**
     * Mengambil label sumber pemasukan uang dalam Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::DIRECT => 'Pemasukan Baru',
            self::FROM_POCKET => 'Ambil dari Kantong Lain',
        };
    }

    /**
     * Mengambil daftar nilai enum.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Mengambil daftar pilihan untuk komponen select / radio TallStackUI.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public static function choices(): array
    {
        return array_map(
            fn (self $case): array => [
                'label' => $case->label(),
                'value' => $case->value,
            ],
            self::cases()
        );
    }
}

