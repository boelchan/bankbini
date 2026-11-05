<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

#[Table(name: 'users', key: 'id')]
#[Fillable([
    'name',
    'email',
    'password',
    'phone',
    'status',
    'jabatan',
    'email_verified_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Konfigurasi konversi tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi ke daftar kantong keuangan milik pengguna.
     *
     * @return HasMany<Wallet, $this>
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'user_id');
    }

    /**
     * Relasi ke seluruh transaksi milik pengguna.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    /**
     * Relasi ke daftar transfer antar kantong milik pengguna.
     *
     * @return HasMany<Transfer, $this>
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'user_id');
    }

    /**
     * Relasi ke kategori transaksi kustom milik pengguna.
     *
     * @return HasMany<TransactionCategory, $this>
     */
    public function transactionCategories(): HasMany
    {
        return $this->hasMany(TransactionCategory::class, 'user_id');
    }

    /**
     * Relasi ke catatan log aktivitas kantong.
     *
     * @return HasMany<WalletActivityLog, $this>
     */
    public function walletActivityLogs(): HasMany
    {
        return $this->hasMany(WalletActivityLog::class, 'user_id');
    }

    /**
     * Memutus seluruh sesi login aktif dan token ingat saya milik pengguna ini.
     */
    public function invalidateSessions(): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $this->getKey())
                ->delete();
        }

        $this->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
    }

    /**
     * Memeriksa apakah pengguna memiliki peran Administrator.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('administrator');
    }
}
