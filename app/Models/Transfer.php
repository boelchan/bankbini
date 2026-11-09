<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table(name: 'transfers', key: 'id')]
#[Fillable([
    'transfer_code',
    'idempotency_key',
    'user_id',
    'source_wallet_id',
    'source_wallet_name',
    'destination_wallet_id',
    'destination_wallet_name',
    'amount',
    'description',
    'transaction_date',
])]
class Transfer extends Model
{
    /**
     * Konfigurasi konversi tipe data atribut transfer.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'datetime',
        ];
    }

    /**
     * Relasi ke pengguna pelaku transfer.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke kantong pengirim (termasuk kantong yang sudah dihapus).
     *
     * @return BelongsTo<Wallet, $this>
     */
    public function sourceWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'source_wallet_id')->withTrashed();
    }

    /**
     * Relasi ke kantong penerima (termasuk kantong yang sudah dihapus).
     *
     * @return BelongsTo<Wallet, $this>
     */
    public function destinationWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'destination_wallet_id')->withTrashed();
    }

    /**
     * Relasi ke sepasang transaksi masuk dan keluar dari transfer ini.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'transfer_reference', 'transfer_code');
    }

    /**
     * Relasi ke transaksi keluar (kantong pengirim) dari transfer ini.
     *
     * @return HasOne<Transaction, $this>
     */
    public function outTransaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'transfer_reference', 'transfer_code')
            ->where('type', TransactionTypeEnum::TRANSFER_OUT->value);
    }

    /**
     * Relasi ke transaksi masuk (kantong penerima) dari transfer ini.
     *
     * @return HasOne<Transaction, $this>
     */
    public function inTransaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'transfer_reference', 'transfer_code')
            ->where('type', TransactionTypeEnum::TRANSFER_IN->value);
    }

    /**
     * Memformat nominal transfer.
     */
    public function formattedAmount(): string
    {
        return number_format((float) $this->amount, 0, ',', '.');
    }

    /**
     * Accessor snapshot nama kantong pengirim.
     */
    public function getSourceWalletNameSnapshotAttribute(): ?string
    {
        return $this->source_wallet_name;
    }

    /**
     * Accessor snapshot nama kantong penerima.
     */
    public function getDestinationWalletNameSnapshotAttribute(): ?string
    {
        return $this->destination_wallet_name;
    }
}
