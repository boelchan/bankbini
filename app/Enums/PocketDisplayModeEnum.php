<?php

declare(strict_types=1);

namespace App\Enums;

enum PocketDisplayModeEnum: string
{
    case ALL = 'all';
    case MONTHLY = 'monthly';

    /**
     * Mengambil label jenis tampilan riwayat kantong dalam Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::ALL => 'Tampil Semua',
            self::MONTHLY => 'Per Bulan',
        };
    }

    /**
     * Mengambil nama ikon Tabler yang mewakili jenis tampilan riwayat kantong.
     */
    public function icon(): string
    {
        return match ($this) {
            self::ALL => 'list',
            self::MONTHLY => 'calendar-month',
        };
    }

    /**
     * Memeriksa apakah mode tampilan menampilkan seluruh riwayat transaksi.
     */
    public function isAll(): bool
    {
        return $this === self::ALL;
    }

    /**
     * Memeriksa apakah mode tampilan memfilter transaksi per bulan.
     */
    public function isMonthly(): bool
    {
        return $this === self::MONTHLY;
    }

    /**
     * Mengambil daftar seluruh nilai enum.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Mengambil daftar pilihan untuk komponen pilihan TallStackUI.
     *
     * @return array<int, array{label: string, value: string, icon: string}>
     */
    public static function choices(): array
    {
        return array_map(
            fn (self $case): array => [
                'label' => $case->label(),
                'value' => $case->value,
                'icon' => $case->icon(),
            ],
            self::cases()
        );
    }
}
