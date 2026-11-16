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
    case SHOPPING_CART = 'shopping-cart';
    case CAKE = 'cake';
    case COFFEE = 'coffee';
    case BABY_CARRIAGE = 'baby-carriage';
    case CAT = 'cat';
    case BASKET = 'basket';
    case GAS_STATION = 'gas-station';
    case DEVICE_MOBILE = 'device-mobile';
    case RIBBON = 'ribbon';
    case BOOK = 'book';
    case BEACH = 'beach';
    case MOUNTAIN = 'mountain';
    case TENT = 'tent';
    case FERRIS_WHEEL = 'ferris-wheel';
    case POPCORN = 'popcorn';
    case MUSIC = 'music';
    case GIRL = 'girl';
    case BOY = 'boy';
    case PARENTS = 'parents';
    case TOY = 'toy';
    case DOLL = 'doll';
    case TOY_TRAIN = 'toy-train';

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
            self::SHOPPING_CART => 'Belanja Bulanan',
            self::CAKE => 'Jajan & Kue',
            self::COFFEE => 'Kopi & Nongkrong',
            self::BABY_CARRIAGE => 'Anak & Bayi',
            self::CAT => 'Kucing & Peliharaan',
            self::BASKET => 'Belanja Dapur',
            self::GAS_STATION => 'Bensin & Perjalanan',
            self::DEVICE_MOBILE => 'Pulsa & Internet',
            self::RIBBON => 'Perawatan & Kecantikan',
            self::BOOK => 'Buku & Bacaan',
            self::BEACH => 'Pantai & Liburan',
            self::MOUNTAIN => 'Gunung & Alam',
            self::TENT => 'Kemah & Camping',
            self::FERRIS_WHEEL => 'Wahana & Hiburan',
            self::POPCORN => 'Nonton & Film',
            self::MUSIC => 'Musik & Konser',
            self::GIRL => 'Anak Perempuan',
            self::BOY => 'Anak Laki-laki',
            self::PARENTS => 'Orang Tua & Keluarga',
            self::TOY => 'Mainan & Boneka',
            self::DOLL => 'Boneka Anak Perempuan',
            self::TOY_TRAIN => 'Mainan Anak Laki-laki',
        };
    }

    /**
     * Mengambil simbol ikon yang tampil di kartu dan pilihan ikon.
     */
    public function emoji(): string
    {
        return match ($this) {
            self::WALLET => '👛',
            self::PIG_MONEY => '🐷',
            self::SHOPPING_BAG => '🛍️',
            self::HEART => '💖',
            self::PLANE_TILT => '✈️',
            self::HOME => '🏠',
            self::TOOLS_KITCHEN_2 => '🍳',
            self::SPARKLES => '✨',
            self::GIFT => '🎁',
            self::FIRST_AID_KIT => '🩹',
            self::SCHOOL => '🎓',
            self::CAR => '🚗',
            self::RECEIPT => '🧾',
            self::COIN => '🪙',
            self::BRIEFCASE => '💼',
            self::DEVICE_GAMEPAD_2 => '🎮',
            self::SHOPPING_CART => '🛒',
            self::CAKE => '🍰',
            self::COFFEE => '☕',
            self::BABY_CARRIAGE => '👶',
            self::CAT => '🐱',
            self::BASKET => '🧺',
            self::GAS_STATION => '⛽',
            self::DEVICE_MOBILE => '📱',
            self::RIBBON => '🎀',
            self::BOOK => '📚',
            self::BEACH => '🏖️',
            self::MOUNTAIN => '🏔️',
            self::TENT => '⛺',
            self::FERRIS_WHEEL => '🎡',
            self::POPCORN => '🍿',
            self::MUSIC => '🎶',
            self::GIRL => '👧',
            self::BOY => '👦',
            self::PARENTS => '👪',
            self::TOY => '🧸',
            self::DOLL => '🪆',
            self::TOY_TRAIN => '🚂',
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

    /**
     * Mengambil daftar pilihan ikon kantong beserta simbolnya.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public static function emojiChoices(): array
    {
        return array_map(
            fn (self $case): array => [
                'label' => $case->emoji() . '  ' . $case->label(),
                'value' => $case->value,
            ],
            self::cases()
        );
    }
}
