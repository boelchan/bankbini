<?php

declare(strict_types=1);

namespace App\Enums;

enum WalletColorEnum: string
{
    case ROSE = 'rose';
    case PINK = 'pink';
    case AMBER = 'amber';
    case YELLOW = 'yellow';
    case EMERALD = 'emerald';
    case LIME = 'lime';
    case SKY = 'sky';
    case CYAN = 'cyan';
    case VIOLET = 'violet';
    case FUCHSIA = 'fuchsia';
    case TEAL = 'teal';
    case ORANGE = 'orange';

    /**
     * Mengambil nama warna kantong yang ceria, manis, dan ramah anak (permen & buah ceria).
     */
    public function label(): string
    {
        return match ($this) {
            self::ROSE => 'Permen Stroberi (Rose Ceria)',
            self::PINK => 'Permen Karet (Bubblegum Pink)',
            self::AMBER => 'Madu Hangat (Karamel Manis)',
            self::YELLOW => 'Permen Lemon (Kuning Ceria)',
            self::EMERALD => 'Melon Manis (Mint Ceria)',
            self::LIME => 'Apel Hijau (Lime Segar)',
            self::SKY => 'Awan Biru (Cotton Candy)',
            self::CYAN => 'Permen Mint (Cyan Segar)',
            self::VIOLET => 'Permen Anggur (Ungu Lilac)',
            self::FUCHSIA => 'Permen Beri (Fuchsia Ceria)',
            self::TEAL => 'Es Lilin Toska (Teal Ceria)',
            self::ORANGE => 'Jeruk Manis (Orange Ceria)',
        };
    }

    /**
     * Mengambil kelas permukaan kartu kantong ceria dengan warna pastel cerah dan sudut membulat.
     */
    public function cardClasses(): string
    {
        return match ($this) {
            self::ROSE => 'rounded-2xl bg-rose-100/95 border-2 border-rose-300 text-rose-950 shadow-2xs hover:border-rose-400 transition-all',
            self::PINK => 'rounded-2xl bg-pink-100/95 border-2 border-pink-300 text-pink-950 shadow-2xs hover:border-pink-400 transition-all',
            self::AMBER => 'rounded-2xl bg-amber-100/95 border-2 border-amber-300 text-amber-950 shadow-2xs hover:border-amber-400 transition-all',
            self::YELLOW => 'rounded-2xl bg-yellow-100/95 border-2 border-yellow-300 text-yellow-950 shadow-2xs hover:border-yellow-400 transition-all',
            self::EMERALD => 'rounded-2xl bg-emerald-100/95 border-2 border-emerald-300 text-emerald-950 shadow-2xs hover:border-emerald-400 transition-all',
            self::LIME => 'rounded-2xl bg-lime-100/95 border-2 border-lime-300 text-lime-950 shadow-2xs hover:border-lime-400 transition-all',
            self::SKY => 'rounded-2xl bg-sky-100/95 border-2 border-sky-300 text-sky-950 shadow-2xs hover:border-sky-400 transition-all',
            self::CYAN => 'rounded-2xl bg-cyan-100/95 border-2 border-cyan-300 text-cyan-950 shadow-2xs hover:border-cyan-400 transition-all',
            self::VIOLET => 'rounded-2xl bg-violet-100/95 border-2 border-violet-300 text-violet-950 shadow-2xs hover:border-violet-400 transition-all',
            self::FUCHSIA => 'rounded-2xl bg-fuchsia-100/95 border-2 border-fuchsia-300 text-fuchsia-950 shadow-2xs hover:border-fuchsia-400 transition-all',
            self::TEAL => 'rounded-2xl bg-teal-100/95 border-2 border-teal-300 text-teal-950 shadow-2xs hover:border-teal-400 transition-all',
            self::ORANGE => 'rounded-2xl bg-orange-100/95 border-2 border-orange-300 text-orange-950 shadow-2xs hover:border-orange-400 transition-all',
        };
    }

    /**
     * Mengambil kelas kotak saldo dan daftar transaksi di dalam kartu kantong.
     */
    public function innerBoxClasses(): string
    {
        return match ($this) {
            self::ROSE => 'border-rose-200/90 bg-white/95',
            self::PINK => 'border-pink-200/90 bg-white/95',
            self::AMBER => 'border-amber-200/90 bg-white/95',
            self::YELLOW => 'border-yellow-200/90 bg-white/95',
            self::EMERALD => 'border-emerald-200/90 bg-white/95',
            self::LIME => 'border-lime-200/90 bg-white/95',
            self::SKY => 'border-sky-200/90 bg-white/95',
            self::CYAN => 'border-cyan-200/90 bg-white/95',
            self::VIOLET => 'border-violet-200/90 bg-white/95',
            self::FUCHSIA => 'border-fuchsia-200/90 bg-white/95',
            self::TEAL => 'border-teal-200/90 bg-white/95',
            self::ORANGE => 'border-orange-200/90 bg-white/95',
        };
    }

    /**
     * Mengambil kelas wadah ikon stiker permen manis pada kartu kantong.
     */
    public function iconBoxClasses(): string
    {
        return match ($this) {
            self::ROSE => 'bg-white text-rose-600 border border-rose-300 shadow-2xs',
            self::PINK => 'bg-white text-pink-600 border border-pink-300 shadow-2xs',
            self::AMBER => 'bg-white text-amber-700 border border-amber-300 shadow-2xs',
            self::YELLOW => 'bg-white text-yellow-700 border border-yellow-300 shadow-2xs',
            self::EMERALD => 'bg-white text-emerald-600 border border-emerald-300 shadow-2xs',
            self::LIME => 'bg-white text-lime-700 border border-lime-300 shadow-2xs',
            self::SKY => 'bg-white text-sky-600 border border-sky-300 shadow-2xs',
            self::CYAN => 'bg-white text-cyan-600 border border-cyan-300 shadow-2xs',
            self::VIOLET => 'bg-white text-violet-600 border border-violet-300 shadow-2xs',
            self::FUCHSIA => 'bg-white text-fuchsia-600 border border-fuchsia-300 shadow-2xs',
            self::TEAL => 'bg-white text-teal-600 border border-teal-300 shadow-2xs',
            self::ORANGE => 'bg-white text-orange-600 border border-orange-300 shadow-2xs',
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
     * Mengambil kelas tombol expand/collapse transaksi yang senada dengan warna kantong.
     */
    public function expandButtonClasses(): string
    {
        return match ($this) {
            self::ROSE => 'bg-rose-50 text-rose-700 hover:bg-rose-100/80',
            self::PINK => 'bg-pink-50 text-pink-700 hover:bg-pink-100/80',
            self::AMBER => 'bg-amber-50 text-amber-800 hover:bg-amber-100/80',
            self::YELLOW => 'bg-yellow-50 text-yellow-800 hover:bg-yellow-100/80',
            self::EMERALD => 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100/80',
            self::LIME => 'bg-lime-50 text-lime-800 hover:bg-lime-100/80',
            self::SKY => 'bg-sky-50 text-sky-700 hover:bg-sky-100/80',
            self::CYAN => 'bg-cyan-50 text-cyan-700 hover:bg-cyan-100/80',
            self::VIOLET => 'bg-violet-50 text-violet-700 hover:bg-violet-100/80',
            self::FUCHSIA => 'bg-fuchsia-50 text-fuchsia-700 hover:bg-fuchsia-100/80',
            self::TEAL => 'bg-teal-50 text-teal-700 hover:bg-teal-100/80',
            self::ORANGE => 'bg-orange-50 text-orange-700 hover:bg-orange-100/80',
        };
    }

    /**
     * Mengambil kelas warna teks utama kantong dengan kontras WCAG AA (>= 7:1).
     */
    public function accentTextClasses(): string
    {
        return match ($this) {
            self::ROSE => 'text-rose-950',
            self::PINK => 'text-pink-950',
            self::AMBER => 'text-amber-950',
            self::YELLOW => 'text-yellow-950',
            self::EMERALD => 'text-emerald-950',
            self::LIME => 'text-lime-950',
            self::SKY => 'text-sky-950',
            self::CYAN => 'text-cyan-950',
            self::VIOLET => 'text-violet-950',
            self::FUCHSIA => 'text-fuchsia-950',
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
            self::AMBER => 'bg-amber-400 border-amber-500',
            self::YELLOW => 'bg-yellow-400 border-yellow-500',
            self::EMERALD => 'bg-emerald-400 border-emerald-500',
            self::LIME => 'bg-lime-400 border-lime-500',
            self::SKY => 'bg-sky-400 border-sky-500',
            self::CYAN => 'bg-cyan-400 border-cyan-500',
            self::VIOLET => 'bg-violet-400 border-violet-500',
            self::FUCHSIA => 'bg-fuchsia-400 border-fuchsia-500',
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
