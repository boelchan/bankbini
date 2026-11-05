<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;

#[Table(name: 'users', key: 'id')]
#[Fillable([
    'name',
    'email',
    'password',
    'nik',
    'ihs_number',
    'status',
    'jabatan',
    'email_verified_at',
    'village_code',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'village_code' => 'integer',
        ];
    }

    public function village()
    {
        return $this->belongsTo(IndonesiaRegion::class, 'village_code', 'code');
    }

    /**
     * Memutus seluruh sesi login aktif & token "ingat saya" milik pengguna ini.
     * Dipanggil saat administrator mereset password atau menonaktifkan akun.
     */
    public function invalidateSessions(): void
    {
        if (config('session.driver') === 'database') {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', $this->getKey())
                ->delete();
        }

        $this->forceFill(['remember_token' => \Illuminate\Support\Str::random(60)])->saveQuietly();
    }

    /**
     * Memeriksa apakah user memiliki peran Koordinator Bidan atau Administrator.
     */
    public function isKoordinatorOrAdmin(): bool
    {
        return $this->hasRole(['administrator', 'koordinator-bidan']);
    }

    /**
     * Memeriksa apakah user memiliki peran Koordinator Bidan.
     */
    public function isKoordinator(): bool
    {
        return $this->hasRole('koordinator-bidan');
    }

    /**
     * Memeriksa apakah user memiliki peran Administrator.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('administrator');
    }

    /**
     * Memeriksa apakah user memiliki peran Bidan.
     */
    public function isBidan(): bool
    {
        return $this->hasRole('bidan');
    }
}
