<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'wallet_reconciliations', key: 'id')]
#[Fillable([
    'wallet_id',
    'user_id',
    'label',
    'amount',
])]
class WalletReconciliation extends Model
{
    /**
     * Konfigurasi konversi tipe data atribut rekonsiliasi.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Relasi ke kantong yang direkonsiliasi.
     *
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    /**
     * Relasi ke anggota keluarga yang mencatat saldo manual ini.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Memformat nominal saldo manual tanpa simbol Rp.
     */
    public function formattedAmount(): string
    {
        return number_format((float) $this->amount, 0, ',', '.');
    }
}
