<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DevicePlatformEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'fcm_tokens', key: 'id')]
#[Fillable(['user_id', 'token', 'platform', 'label'])]
class FcmToken extends Model
{
    /**
     * Konfigurasi konversi tipe data atribut token.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => DevicePlatformEnum::class,
        ];
    }

    /**
     * Relasi ke pemilik perangkat.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
