<?php

declare(strict_types=1);

namespace App\Enums;

enum WalletStatusEnum: string
{
    case ACTIVE = 'active';
    case FROZEN = 'frozen';
    case DELETED = 'deleted';

    /**
     * Mengambil label status kantong dalam Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Aktif',
            self::FROZEN => 'Dibekukan',
            self::DELETED => 'Dihapus',
        };
    }

    /**
     * Mengambil kelas badge Tailwind dengan kontras tinggi sesuai WCAG AA.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            self::FROZEN => 'bg-amber-50 text-amber-800 border-amber-200',
            self::DELETED => 'bg-rose-50 text-rose-800 border-rose-200',
        };
    }

    /**
     * Mengambil nama ikon Tabler untuk status kantong.
     */
    public function icon(): string
    {
        return match ($this) {
            self::ACTIVE => 'circle-check',
            self::FROZEN => 'snowflake',
            self::DELETED => 'trash',
        };
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
     * Mengambil warna semantik TallStackUI untuk komponen badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'emerald',
            self::FROZEN => 'amber',
            self::DELETED => 'rose',
        };
    }

    /**
     * Mengambil daftar pilihan untuk komponen select TallStackUI (Aktif & Dibekukan).
     *
     * @return array<int, array{label: string, value: string}>
     */
    public static function choices(): array
    {
        return [
            ['label' => self::ACTIVE->label(), 'value' => self::ACTIVE->value],
            ['label' => self::FROZEN->label(), 'value' => self::FROZEN->value],
        ];
    }

    /**
     * Mengambil opsi kantong aktif dan dibekukan untuk dropdown filter.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public static function activeAndFrozenChoices(): array
    {
        return self::choices();
    }

    /**
     * Mengambil seluruh pilihan termasuk status dihapus untuk filter riwayat.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public static function allChoices(): array
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
