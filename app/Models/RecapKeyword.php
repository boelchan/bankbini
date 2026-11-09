<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'recap_keywords', key: 'id')]
#[Fillable([
    'user_id',
    'keyword',
    'budget_limit',
    'color',
])]
class RecapKeyword extends Model
{
    /**
     * Konfigurasi konversi tipe data atribut kata kunci.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget_limit' => 'decimal:2',
        ];
    }

    /**
     * Relasi ke pembuat kata kunci.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Format batas atas tanpa simbol Rp.
     */
    public function formattedLimit(): string
    {
        $limit = (float) $this->budget_limit;
        return $limit > 0 ? number_format($limit, 0, ',', '.') : 'Tanpa Batas';
    }
}
