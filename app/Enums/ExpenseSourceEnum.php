<?php

declare(strict_types=1);

namespace App\Enums;

enum ExpenseSourceEnum: string
{
    case DIRECT = 'direct';
    case TO_POCKET = 'to_pocket';

    /**
     * Mengambil label jenis pengeluaran dalam Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::DIRECT => 'Pengeluaran Baru',
            self::TO_POCKET => 'Pindah ke Kantong Lain',
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
