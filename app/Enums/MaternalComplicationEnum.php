<?php

namespace App\Enums;

enum MaternalComplicationEnum: string
{
    case TIDAK_ADA = 'Tidak Ada';
    case HIPER_EMISIS = 'Hiper Emisis';
    case PREEKLAMSI = 'Preeklamsi/eklamsi';
    case PERDARAHAN_KEHAMILAN = 'Perdarahan Kehamilan';
    case PERDARAHAN_PERSALINAN = 'Perdarahan Persalinan';
    case PERDARAHAN_NIFAS = 'Perdarahan Nifas';
    case PARTUS_LAMA = 'Partus Lama';
    case INFEKSI = 'Infeksi';
    case TB = 'TB';
    case MALARIA = 'Malaria';
    case JANTUNG = 'Jantung';
    case DM = 'DM';
    case POSITIF_COVID19 = 'Positif Covid19';
    case OBESITAS = 'Obesitas';
    case LAINNYA = 'Lainnya';

    /**
     * Pemetaan kelompok komplikasi kebidanan:
     * - 'antenatal'
     * - 'intranatal'
     * - 'postnatal'
     *
     * @return array<string>
     */
    public function contexts(): array
    {
        return match ($this) {
            self::HIPER_EMISIS => ['antenatal'],
            self::PERDARAHAN_KEHAMILAN => ['antenatal'],

            self::PERDARAHAN_PERSALINAN => ['intranatal'],
            self::PARTUS_LAMA => ['intranatal'],

            self::PERDARAHAN_NIFAS => ['postnatal'],

            default => [
                'antenatal',
                'intranatal',
                'postnatal',
            ],
        };
    }

    /**
     * Memeriksa apakah komplikasi ini berlaku pada kelompok / fase tertentu:
     * 'antenatal', 'intranatal', atau 'postnatal'.
     */
    public function appliesTo(string $context): bool
    {
        return in_array(strtolower($context), $this->contexts(), true);
    }

    /**
     * Opsi pilihan komplikasi untuk TallStackUI select berdasarkan kelompok:
     * - 'antenatal'
     * - 'intranatal'
     * - 'postnatal'
     * Jika context bernilai null, maka seluruh kasus komplikasi dikembalikan.
     *
     * @return array<int, array<string, string>>
     */
    public static function choices(?string $context = null): array
    {
        $cases = self::cases();

        if ($context !== null) {
            $context = strtolower($context);
            $cases = array_filter($cases, fn(self $enum) => $enum->appliesTo($context));
        }

        return array_values(array_map(
            fn(self $enum) => [
                'label' => $enum->value,
                'value' => $enum->value,
            ],
            $cases
        ));
    }

    /**
     * Opsi pilihan komplikasi khusus masa Antenatal.
     *
     * @return array<int, array<string, string>>
     */
    public static function choicesForAntenatal(): array
    {
        return self::choices('antenatal');
    }

    /**
     * Opsi pilihan komplikasi khusus masa Intranatal.
     *
     * @return array<int, array<string, string>>
     */
    public static function choicesForIntranatal(): array
    {
        return self::choices('intranatal');
    }

    /**
     * Opsi pilihan komplikasi khusus masa Postnatal.
     *
     * @return array<int, array<string, string>>
     */
    public static function choicesForPostnatal(): array
    {
        return self::choices('postnatal');
    }

    /**
     * Daftar nilai string enum (opsional difilter berdasarkan kelompok 'antenatal', 'intranatal', 'postnatal').
     *
     * @return array<int, string>
     */
    public static function values(?string $context = null): array
    {
        $cases = self::cases();

        if ($context !== null) {
            $context = strtolower($context);
            $cases = array_filter($cases, fn(self $enum) => $enum->appliesTo($context));
        }

        return array_values(array_map(fn(self $enum) => $enum->value, $cases));
    }
}
