<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WalletColorEnum;
use App\Enums\WalletIconEnum;
use App\Enums\WalletStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table(name: 'wallets', key: 'id')]
#[Fillable([
    'user_id',
    'name',
    'description',
    'icon',
    'color',
    'initial_balance',
    'balance',
    'is_primary',
    'status',
])]
class Wallet extends Model
{
    use SoftDeletes;

    /**
     * Konfigurasi konversi tipe data atribut kantong.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:2',
            'balance' => 'decimal:2',
            'is_primary' => 'boolean',
            'status' => WalletStatusEnum::class,
        ];
    }

    /**
     * Relasi ke pemilik kantong.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke seluruh transaksi pada kantong ini.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'wallet_id');
    }

    /**
     * Relasi ke catatan aktivitas pengelolaan kantong.
     *
     * @return HasMany<WalletActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(WalletActivityLog::class, 'wallet_id');
    }

    /**
     * Scope kantong yang masih tampil di daftar kantong (aktif dan dibekukan).
     *
     * @param Builder<Wallet> $query
     * @return Builder<Wallet>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereIn('status', [
            WalletStatusEnum::ACTIVE->value,
            WalletStatusEnum::FROZEN->value,
        ])->whereNull('deleted_at');
    }

    /**
     * Scope khusus kantong yang berstatus aktif dan siap bertransaksi.
     *
     * @param Builder<Wallet> $query
     * @return Builder<Wallet>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', WalletStatusEnum::ACTIVE->value)
            ->whereNull('deleted_at');
    }

    /**
     * Memeriksa apakah kantong dalam keadaan aktif untuk transaksi.
     */
    public function isActive(): bool
    {
        return $this->status === WalletStatusEnum::ACTIVE && $this->deleted_at === null;
    }

    /**
     * Memeriksa apakah kantong sedang dibekukan.
     */
    public function isFrozen(): bool
    {
        return $this->status === WalletStatusEnum::FROZEN && $this->deleted_at === null;
    }

    /**
     * Memeriksa apakah kantong sudah dihapus/ditutup.
     */
    public function isRemoved(): bool
    {
        return $this->status === WalletStatusEnum::DELETED || $this->deleted_at !== null;
    }

    /**
     * Mengambil objek enum warna kantong dengan fallback aman.
     */
    public function colorEnum(): WalletColorEnum
    {
        return WalletColorEnum::tryFrom((string) $this->color) ?? WalletColorEnum::ROSE;
    }

    /**
     * Mengambil objek enum ikon kantong dengan fallback aman.
     */
    public function iconEnum(): WalletIconEnum
    {
        return WalletIconEnum::tryFrom((string) $this->icon) ?? WalletIconEnum::WALLET;
    }

    /**
     * Memformat saldo kantong saat ini.
     */
    public function formattedBalance(): string
    {
        return number_format((float) $this->balance, 0, ',', '.');
    }

    /**
     * Memformat saldo awal kantong.
     */
    public function formattedInitialBalance(): string
    {
        return number_format((float) $this->initial_balance, 0, ',', '.');
    }
}
