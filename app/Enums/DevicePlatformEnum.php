<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Platform perangkat yang dapat menyimpan token FCM.
 */
enum DevicePlatformEnum: string
{
    case ANDROID = 'android';
    case IOS = 'ios';
    case WEB = 'web';

    /**
     * Mengambil label platform dalam Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::ANDROID => 'HP Android',
            self::IOS => 'HP iPhone',
            self::WEB => 'Browser Desktop',
        };
    }

    /**
     * Mengambil daftar nilai string dari seluruh platform.
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
