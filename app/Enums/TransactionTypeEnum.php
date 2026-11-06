<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case INCOME = 'income';
    case EXPENSE = 'expense';
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';

    /**
     * Mengambil label jenis transaksi dalam Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::INCOME => 'Pemasukan',
            self::EXPENSE => 'Pengeluaran',
            self::TRANSFER_IN => 'Terima dari Kantong',
            self::TRANSFER_OUT => 'Pindah ke Kantong',
        };
    }

    /**
     * Mengambil nama ikon Tabler yang relevan untuk jenis transaksi.
     */
    public function icon(): string
    {
        return match ($this) {
            self::INCOME => 'arrow-down-left',
            self::EXPENSE => 'arrow-up-right',
            self::TRANSFER_IN => 'arrows-exchange',
            self::TRANSFER_OUT => 'arrows-exchange',
        };
    }

    /**
     * Mengambil kelas badge semantik untuk tampilan tabel dan kartu.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::INCOME => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            self::EXPENSE => 'bg-rose-50 text-rose-800 border-rose-200',
            self::TRANSFER_IN => 'bg-sky-50 text-sky-800 border-sky-200',
            self::TRANSFER_OUT => 'bg-amber-50 text-amber-800 border-amber-200',
        };
    }

    /**
     * Mengambil kelas warna teks nominal sesuai arah aliran dana.
     */
    public function amountClass(): string
    {
        return match ($this) {
            self::INCOME, self::TRANSFER_IN => 'text-emerald-700',
            self::EXPENSE, self::TRANSFER_OUT => 'text-rose-700',
        };
    }

    /**
     * Mengambil simbol tanda tambah atau kurang untuk nominal transaksi.
     */
    public function sign(): string
    {
        return match ($this) {
            self::INCOME, self::TRANSFER_IN => '+',
            self::EXPENSE, self::TRANSFER_OUT => '-',
        };
    }

    /**
     * Mengambil warna semantik TallStackUI untuk komponen badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::INCOME => 'emerald',
            self::EXPENSE => 'rose',
            self::TRANSFER_IN => 'sky',
            self::TRANSFER_OUT => 'amber',
        };
    }

    /**
     * Memeriksa apakah transaksi menambah saldo kantong.
     */
    public function isCredit(): bool
    {
        return in_array($this, [self::INCOME, self::TRANSFER_IN], true);
    }

    /**
     * Alias pemeriksaan apakah transaksi bernilai positif terhadap saldo kantong.
     */
    public function isPositive(): bool
    {
        return $this->isCredit();
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
