<?php

namespace App\Enums;

enum PartusTypeEnum: string
{
    case NORMAL = 'Normal';
    case SC = 'SC';
    case TINDAKAN_DRIP = 'Tindakan Drip';
    case TINDAKAN_CURATAGE = 'Tindakan Curatage';
    case TINDAKAN_PLASENTA_MANUAL = 'Tindakan Plasenta manual';
    case TINDAKAN_VAKUM = 'Tindakan Vakum';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * Opsi pilihan jenis persalinan (partus) untuk TallStackUI select.
     */
    public static function choices(): array
    {
        return array_map(
            fn(self $enum) => ['label' => $enum->value, 'value' => $enum->value],
            self::cases()
        );
    }

    /**
     * Warna semantik Tailwind sesuai jenis persalinan.
     */
    public function color(): string
    {
        return match ($this) {
            self::NORMAL => 'emerald',
            self::SC => 'amber',
            self::TINDAKAN_DRIP => 'sky',
            self::TINDAKAN_CURATAGE => 'indigo',
            self::TINDAKAN_PLASENTA_MANUAL => 'purple',
            self::TINDAKAN_VAKUM => 'orange',
        };
    }

    /**
     * Class CSS badge semantik Tailwind untuk tabel/tampilan.
     */
    public static function badgeClasses(?string $value): string
    {
        $enum = self::tryFrom((string) $value);
        if (!$enum) {
            return 'bg-slate-100 text-slate-700 border-slate-200';
        }

        return match ($enum) {
            self::NORMAL => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::SC => 'bg-amber-100 text-amber-800 border-amber-200',
            self::TINDAKAN_DRIP => 'bg-sky-100 text-sky-800 border-sky-200',
            self::TINDAKAN_CURATAGE => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            self::TINDAKAN_PLASENTA_MANUAL => 'bg-purple-100 text-purple-800 border-purple-200',
            self::TINDAKAN_VAKUM => 'bg-orange-100 text-orange-800 border-orange-200',
        };
    }

    /**
     * Array seluruh nilai string.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
