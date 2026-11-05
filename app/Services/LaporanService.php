<?php

namespace App\Services;

use App\Models\IndonesiaRegion;
use App\Models\Patient;
use Illuminate\Support\Collection;

class LaporanService
{
    /**
     * Mengambil daftar desa wilayah Puskesmas Pragaan (kode kecamatan: 352911).
     *
     * @return Collection<int, object{code: int, name: string}>
     */
    public static function getVillages(): Collection
    {
        // Hanya mengambil desa wilayah kerja Puskesmas Pragaan (kode kecamatan: 352911)
        return IndonesiaRegion::where('parent', 352911)
            ->where('type', 'desa')
            ->orderBy('code', 'asc')
            ->get(['code', 'name']);
    }

    /**
     * Pilihan bulan untuk filter laporan.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public static function getMonthOptions(): array
    {
        return [
            ['value' => 1, 'label' => 'Januari'],
            ['value' => 2, 'label' => 'Februari'],
            ['value' => 3, 'label' => 'Maret'],
            ['value' => 4, 'label' => 'April'],
            ['value' => 5, 'label' => 'Mei'],
            ['value' => 6, 'label' => 'Juni'],
            ['value' => 7, 'label' => 'Juli'],
            ['value' => 8, 'label' => 'Agustus'],
            ['value' => 9, 'label' => 'September'],
            ['value' => 10, 'label' => 'Oktober'],
            ['value' => 11, 'label' => 'November'],
            ['value' => 12, 'label' => 'Desember'],
        ];
    }

    /**
     * Pilihan tahun untuk filter laporan.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public static function getYearOptions(): array
    {
        $current = (int) date('Y');
        $years = [];
        for ($y = $current - 3; $y <= $current + 2; $y++) {
            $years[] = ['value' => $y, 'label' => (string) $y];
        }
        return $years;
    }
}
