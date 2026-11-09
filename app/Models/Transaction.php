<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'transactions', key: 'id')]
#[Fillable([
    'transaction_code',
    'idempotency_key',
    'user_id',
    'wallet_id',
    'wallet_name_snapshot',
    'counterpart_wallet_id',
    'counterpart_wallet_name_snapshot',
    'transfer_reference',
    'type',
    'category_id',
    'category_name_snapshot',
    'amount',
    'balance_before',
    'balance_after',
    'description',
    'notes',
    'transaction_date',
])]
class Transaction extends Model
{
    /**
     * Konfigurasi konversi tipe data atribut transaksi.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionTypeEnum::class,
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'transaction_date' => 'datetime',
        ];
    }

    /**
     * Relasi ke pengguna pemilik transaksi.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke kantong terkait (termasuk jika kantong telah dihapus secara soft-delete).
     *
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id')->withTrashed();
    }

    /**
     * Relasi ke kantong lawan pada transaksi transfer (termasuk jika kantong telah dihapus).
     *
     * @return BelongsTo<Wallet, $this>
     */
    public function counterpartWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'counterpart_wallet_id')->withTrashed();
    }

    /**
     * Relasi ke kategori pemasukan atau pengeluaran.
     *
     * @return BelongsTo<TransactionCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    /**
     * Relasi ke induk pencatatan transfer antar kantong.
     *
     * @return BelongsTo<Transfer, $this>
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class, 'transfer_reference', 'transfer_code');
    }

    /**
     * Mengambil nama kantong dari snapshot historis atau relasi aktif.
     */
    public function displayWalletName(): string
    {
        return $this->wallet_name_snapshot ?: ($this->wallet?->name ?? 'Kantong');
    }

    /**
     * Alias pengambilan nama kantong (termasuk jika kantong telah dihapus).
     */
    public function resolvedWalletName(): string
    {
        return $this->displayWalletName();
    }

    /**
     * Mengambil nama kantong lawan dari snapshot atau relasi.
     */
    public function resolvedCounterpartWalletName(): string
    {
        return $this->counterpart_wallet_name_snapshot ?: ($this->counterpartWallet?->name ?? 'Kantong');
    }

    /**
     * Memformat nominal transaksi beserta tanda + atau -.
     */
    public function formattedSignedAmount(): string
    {
        return $this->type->sign() . number_format((float) $this->amount, 0, ',', '.');
    }

    /**
     * Memformat saldo sebelum transaksi.
     */
    public function formattedBalanceBefore(): string
    {
        return number_format((float) $this->balance_before, 0, ',', '.');
    }

    /**
     * Memformat saldo sesudah transaksi.
     */
    public function formattedBalanceAfter(): string
    {
        return number_format((float) $this->balance_after, 0, ',', '.');
    }

    /**
     * Mengambil nama kategori dari snapshot historis atau relasi kategori.
     */
    public function displayCategoryName(): string
    {
        if (in_array($this->type, [TransactionTypeEnum::TRANSFER_IN, TransactionTypeEnum::TRANSFER_OUT], true)) {
            return 'Pindah Antar Kantong';
        }

        return $this->category_name_snapshot ?: ($this->category?->name ?? 'Umum');
    }

    /**
     * Mengambil deskripsi transaksi dengan format label Pindah ke / Terima dari yang konsisten.
     */
    public function displayDescription(): string
    {
        $desc = trim((string) $this->description);

        if ($this->type === TransactionTypeEnum::TRANSFER_OUT) {
            if (str_starts_with($desc, 'Transfer keluar ke ')) {
                return 'Pindah ke ' . substr($desc, strlen('Transfer keluar ke '));
            }
            if (str_starts_with($desc, 'Pindah ke kantong ')) {
                return 'Pindah ke ' . substr($desc, strlen('Pindah ke kantong '));
            }
        }

        if ($this->type === TransactionTypeEnum::TRANSFER_IN) {
            if (str_starts_with($desc, 'Transfer masuk dari ')) {
                return 'Terima dari ' . substr($desc, strlen('Transfer masuk dari '));
            }
        }

        if ($desc === '' || preg_match('/^(Pemasukan ke|Pengeluaran dari) /u', $desc) === 1) {
            return $this->type->label();
        }

        return $desc;
    }
}
