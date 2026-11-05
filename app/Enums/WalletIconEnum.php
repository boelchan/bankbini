<?php

declare(strict_types=1);

namespace App\Enums;

enum WalletIconEnum: string
{
    case WALLET = 'wallet';
    case PIG_MONEY = 'pig-money';
    case SHOPPING_BAG = 'shopping-bag';
    case HEART = 'heart';
    case PLANE_TILT = 'plane-tilt';
    case HOME = 'home';
    case TOOLS_KITCHEN_2 = 'tools-kitchen-2';
    case SPARKLES = 'sparkles';
    case GIFT = 'gift';
    case FIRST_AID_KIT = 'first-aid-kit';
    case SCHOOL = 'school';
    case CAR = 'car';
    case RECEIPT = 'receipt';
    case COIN = 'coin';
    case BRIEFCASE = 'briefcase';
    case DEVICE_GAMEPAD_2 = 'device-gamepad-2';

    /**
     * Mengambil label nama ikon dalam Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::WALLET => 'Dompet Utama',
            self::PIG_MONEY => 'Celengan Tabungan',
            self::SHOPPING_BAG => 'Belanja & Fashion',
            self::HEART => 'Kasih Sayang & Istri',
            self::PLANE_TILT => 'Liburan & Wisata',
            self::HOME => 'Rumah Tangga',
            self::TOOLS_KITCHEN_2 => 'Dapur & Kuliner',
            self::SPARKLES => 'Skincare & Cantik',
            self::GIFT => 'Hadiah & Kejutan',
            self::FIRST_AID_KIT => 'Kesehatan & Darurat',
            self::SCHOOL => 'Pendidikan & Kursus',
            self::CAR => 'Transportasi & BBM',
            self::RECEIPT => 'Tagihan & Langganan',
            self::COIN => 'Investasi & Emas',
            self::BRIEFCASE => 'Gaji & Usaha',
            self::DEVICE_GAMEPAD_2 => 'Hiburan & Hobi',
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
     * Mengambil daftar pilihan untuk komponen select TallStackUI.
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
