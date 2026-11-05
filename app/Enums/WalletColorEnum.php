<?php

declare(strict_types=1);

namespace App\Enums;

enum WalletColorEnum: string
{
    case ROSE = 'rose';
    case PINK = 'pink';
    case EMERALD = 'emerald';
    case SKY = 'sky';
    case AMBER = 'amber';
    case VIOLET = 'violet';
    case TEAL = 'teal';
    case ORANGE = 'orange';

    /**
     * Mengambil nama warna kantong yang ramah dan manis dalam Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::ROSE => 'Pink Berry (Rose)',
            self::PINK => 'Sakura Manis (Pink)',
            self::EMERALD => 'Mint Segar (Emerald)',
            self::SKY => 'Biru Awan (Sky)',
            self::AMBER => 'Madu Hangat (Amber)',
            self::VIOLET => 'Ungu Lilac (Violet)',
            self::TEAL => 'Toska Kalem (Teal)',
            self::ORANGE => 'Persik Ceria (Peach)',
        };
    }

    /**
     * Mengambil kelas permukaan kartu pastel dengan border jelas.
     */
    public function cardClasses(): string
    {
        return match ($this) {
            self::ROSE => 'bg-rose-50/90 border-rose-200 hover:border-rose-300',
            self::PINK => 'bg-pink-50/90 border-pink-200 hover:border-pink-300',
            self::EMERALD => 'bg-emerald-50/90 border-emerald-200 hover:border-emerald-300',
            self::SKY => 'bg-sky-50/90 border-sky-200 hover:border-sky-300',
            self::AMBER => 'bg-amber-50/90 border-amber-200 hover:border-amber-300',
            self::VIOLET => 'bg-violet-50/90 border-violet-200 hover:border-violet-300',
            self::TEAL => 'bg-teal-50/90 border-teal-200 hover:border-teal-300',
            self::ORANGE => 'bg-orange-50/90 border-orange-200 hover:border-orange-300',
        };
    }

    /**
     * Mengambil kelas wadah ikon pada kartu kantong.
     */
    public function iconBoxClasses(): string
    {
        return match ($this) {
            self::ROSE => 'bg-rose-200/80 text-rose-900 border border-rose-300',
            self::PINK => 'bg-pink-200/80 text-pink-900 border border-pink-300',
            self::EMERALD => 'bg-emerald-200/80 text-emerald-900 border border-emerald-300',
            self::SKY => 'bg-sky-200/80 text-sky-900 border border-sky-300',
            self::AMBER => 'bg-amber-200/80 text-amber-900 border border-amber-300',
            self::VIOLET => 'bg-violet-200/80 text-violet-900 border border-violet-300',
            self::TEAL => 'bg-teal-200/80 text-teal-900 border border-teal-300',
            self::ORANGE => 'bg-orange-200/80 text-orange-900 border border-orange-300',
        };
    }

    /**
     * Alias kelas badge ikon pada kartu kantong.
     */
    public function iconBadgeClasses(): string
    {
        return $this->iconBoxClasses();
    }

    /**
     * Mengambil kelas warna teks utama kantong dengan kontras WCAG AA (>= 7:1).
     */
    public function accentTextClasses(): string
    {
        return match ($this) {
            self::ROSE => 'text-rose-950',
            self::PINK => 'text-pink-950',
            self::EMERALD => 'text-emerald-950',
            self::SKY => 'text-sky-950',
            self::AMBER => 'text-amber-950',
            self::VIOLET => 'text-violet-950',
            self::TEAL => 'text-teal-950',
            self::ORANGE => 'text-orange-950',
        };
    }

    /**
     * Mengambil kelas warna titik swatch untuk pemilih warna.
     */
    public function swatchClasses(): string
    {
        return match ($this) {
            self::ROSE => 'bg-rose-400 border-rose-500',
            self::PINK => 'bg-pink-400 border-pink-500',
            self::EMERALD => 'bg-emerald-400 border-emerald-500',
            self::SKY => 'bg-sky-400 border-sky-500',
            self::AMBER => 'bg-amber-400 border-amber-500',
            self::VIOLET => 'bg-violet-400 border-violet-500',
            self::TEAL => 'bg-teal-400 border-teal-500',
            self::ORANGE => 'bg-orange-400 border-orange-500',
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
