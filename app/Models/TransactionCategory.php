<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CategoryTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'transaction_categories', key: 'id')]
#[Fillable([
    'user_id',
    'name',
    'type',
    'icon',
    'color',
    'is_default',
])]
class TransactionCategory extends Model
{
    /**
     * Konfigurasi konversi tipe data atribut kategori.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CategoryTypeEnum::class,
            'is_default' => 'boolean',
        ];
    }

    /**
     * Relasi ke pengguna pemilik kategori kustom.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke transaksi yang menggunakan kategori ini.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }

    /**
     * Scope kategori yang tersedia untuk pengguna tertentu (kategori bawaan + kustom pengguna).
     *
     * @param Builder<TransactionCategory> $query
     * @return Builder<TransactionCategory>
     */
    public function scopeAvailableFor(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId): void {
            $q->whereNull('user_id')->orWhere('user_id', $userId);
        });
    }
}
