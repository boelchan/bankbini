<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'wallet_activity_logs', key: 'id')]
#[Fillable([
    'wallet_id',
    'wallet_name_snapshot',
    'user_id',
    'activity',
    'description',
])]
class WalletActivityLog extends Model
{
    /**
     * Relasi ke kantong yang dicatat aktivitasnya (termasuk yang sudah dihapus).
     *
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id')->withTrashed();
    }

    /**
     * Relasi ke pengguna yang melakukan aktivitas.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
