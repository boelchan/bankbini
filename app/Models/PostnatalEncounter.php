<?php

namespace App\Models;

use App\Models\PregnancyHistory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PostnatalEncounter extends Model
{
    protected $fillable = [
        'uuid',
        'patient_id',
        'pregnancy_id',
        'encounter_date',
        'td_sistolik',
        'td_diastolik',
        'suhu',
        'nadi',
        'tfu',
        'lochea',
        'jalan_lahir',
        'asi',
        'bab',
        'bak',
        'masalah_nifas',
        'masalah_nifas_lainnya',
        'catatan',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'encounter_date' => 'date',
            'td_sistolik' => 'integer',
            'td_diastolik' => 'integer',
            'suhu' => 'decimal:1',
            'nadi' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Accessor untuk atribut hari_ke.
     * Mengambil nilai hari_ke dari kolom database jika ada, atau menghitung otomatis dari tanggal_persalinan.
     */
    protected function hariKe(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value !== null) {
                    return (int) $value;
                }

                if (!empty($this->encounter_date)) {
                    $tanggalPersalinan = PregnancyHistory::where('patient_id', $this->patient_id)
                        ->latest('tanggal_persalinan')
                        ->value('tanggal_persalinan');

                    if ($tanggalPersalinan) {
                        $persalinan = Carbon::parse($tanggalPersalinan)->startOfDay();
                        $kunjungan = Carbon::parse($this->encounter_date)->startOfDay();

                        return max(1, (int) $persalinan->diffInDays($kunjungan) + 1);
                    }
                }

                return null;
            }
        );
    }

    /**
     * Accessor untuk atribut kf_type.
     * Memastikan nilai kf_type terformat standar (KF1, KF2, KF3, KF4) atau ditentukan otomatis dari hari_ke.
     */
    protected function kfType(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $trimmed = strtoupper(trim((string) $value));
                if (in_array($trimmed, ['KF1', 'KF2', 'KF3', 'KF4'])) {
                    return $trimmed;
                }

                $hariKe = $this->hari_ke;
                if ($hariKe !== null) {
                    if ($hariKe <= 2) {
                        return 'KF1';
                    } elseif ($hariKe <= 7) {
                        return 'KF2';
                    } elseif ($hariKe <= 28) {
                        return 'KF3';
                    } elseif ($hariKe <= 42) {
                        return 'KF4';
                    } else {
                        return '> KF4';
                    }
                }

                return $value;
            }
        );
    }

    /**
     * Menentukan klasifikasi Kunjungan Nifas (KF1, KF2, KF3, KF4) berdasarkan selisih hari.
     * Sesuai standar PWS KIA Kemenkes RI (Hari lahir = Hari ke-1):
     * - KF1 : Hari ke-1 s/d 2 (6 jam s/d hari ke-2)
     * - KF2 : Hari ke-3 s/d 7
     * - KF3 : Hari ke-8 s/d 28
     * - KF4 : Hari ke-29 s/d 42
     */
    public static function calculateKf(string|Carbon $tanggalPersalinan, string|Carbon $tanggalKunjungan): array
    {
        $persalinan = Carbon::parse($tanggalPersalinan)->startOfDay();
        $kunjungan = Carbon::parse($tanggalKunjungan)->startOfDay();
        $hariKe = max(1, (int) $persalinan->diffInDays($kunjungan) + 1);

        if ($hariKe <= 2) {
            $kfType = 'KF1';
            $label = 'KF1 (Hari ke-1 - 2)';
        } elseif ($hariKe <= 7) {
            $kfType = 'KF2';
            $label = 'KF2 (3 - 7 hari)';
        } elseif ($hariKe <= 28) {
            $kfType = 'KF3';
            $label = 'KF3 (8 - 28 hari)';
        } elseif ($hariKe <= 42) {
            $kfType = 'KF4';
            $label = 'KF4 (29 - 42 hari)';
        } else {
            $kfType = '> KF4';
            $label = 'Lewat Masa Nifas (> 42 hari)';
        }

        return [
            'hari_ke' => $hariKe,
            'kf_type' => $kfType,
            'label'   => $label,
        ];
    }

    /**
     * Relasi ke data pasien ibu.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Relasi ke kehamilan terkait (yang telah diselesaikan / melahirkan).
     */
    public function pregnancy(): BelongsTo
    {
        return $this->belongsTo(Pregnancy::class);
    }

    /**
     * Bidan / petugas pemeriksa yang mencatat kunjungan nifas ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Memeriksa apakah user tertentu (atau user login) berhak mengelola (ubah / hapus) data kunjungan nifas ini.
     * Hak akses: Koordinator Bidan, Pembuat Kunjungan (created_by), atau Bidan Pengampu Kehamilan (isPengampu).
     */
    public function canManage(?User $user = null): bool
    {
        $user ??= Auth::user();
        if (!$user) {
            return false;
        }

        return $user->hasRole(['administrator', 'koordinator-bidan'])
            || ((int) $this->created_by === (int) $user->id)
            || (bool) $this->pregnancy?->isPengampu($user);
    }
}
