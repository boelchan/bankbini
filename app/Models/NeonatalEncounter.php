<?php

namespace App\Models;

use App\Models\PregnancyHistory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class NeonatalEncounter extends Model
{
    protected $fillable = [
        'uuid',
        'patient_id',
        'pregnancy_id',
        'encounter_date',
        'kn_type',
        'bb',
        'tb',
        'rr',
        'suhu',
        'warna_kulit',
        'tali_pusat',
        'salep_mata',
        'vit_k',
        'imd',
        'hb0',
        'shk',
        'hbig',
        'pjb',
        'trauma_lahir',
        'asfiksia',
        'bblr',
        'infeksi',
        'tn',
        'kelainan_bawaan',
        'hk_positif',
        'covid19',
        'masalah_lainnya',
        'catatan',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'encounter_date' => 'date',
            'bb'             => 'decimal:2',
            'tb'             => 'decimal:1',
            'rr'             => 'integer',
            'suhu'           => 'decimal:1',
            'created_by'     => 'integer',
            'updated_by'     => 'integer',
        ];
    }

    public static function boot(): void
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
     * Mengambil nilai hari_ke jika ada, atau menghitung otomatis dari tanggal persalinan ke tanggal kunjungan (Hari lahir = Hari ke-1).
     */
    protected function hariKe(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value !== null) {
                    return (int) $value;
                }

                if (!empty($this->encounter_date)) {
                    $tanggalPersalinan = null;
                    if ($this->pregnancy_id) {
                        $tanggalPersalinan = PregnancyHistory::where('pregnancy_id', $this->pregnancy_id)->value('tanggal_persalinan');
                    }
                    if (!$tanggalPersalinan && $this->patient_id) {
                        $tanggalPersalinan = PregnancyHistory::where('patient_id', $this->patient_id)
                            ->latest('tanggal_persalinan')
                            ->value('tanggal_persalinan');
                    }

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
     * Accessor untuk atribut kn_ke.
     * Mengembalikan nomor urut Kunjungan Neonatal (1 untuk KN1, 2 untuk KN2, 3 untuk KN3) berdasarkan hari_ke.
     */
    protected function knKe(): Attribute
    {
        return Attribute::make(
            get: function () {
                $raw = strtoupper(trim((string) ($this->attributes['kn_type'] ?? '')));
                if ($raw === 'KOMPLIKASI') {
                    return null;
                }

                $hariKe = $this->hari_ke;
                if ($hariKe === null) {
                    return null;
                }

                if ($hariKe <= 2) {
                    return 1;
                } elseif ($hariKe <= 7) {
                    return 2;
                } elseif ($hariKe <= 28) {
                    return 3;
                }

                return null;
            }
        );
    }

    /**
     * Accessor untuk atribut kn_type.
     * Di tabel database bernilai 'KN' atau 'KOMPLIKASI' sebagai pembeda.
     * Mengembalikan 'KOMPLIKASI' untuk kasus komplikasi, atau 'KN1', 'KN2', 'KN3' berdasarkan hari_ke.
     */
    protected function knType(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $trimmed = strtoupper(trim((string) $value));
                if ($trimmed === 'KOMPLIKASI') {
                    return 'KOMPLIKASI';
                }

                if (in_array($trimmed, ['KN1', 'KN2', 'KN3'])) {
                    return $trimmed;
                }

                $hariKe = $this->hari_ke;
                if ($hariKe !== null) {
                    if ($hariKe <= 2) {
                        return 'KN1';
                    } elseif ($hariKe <= 7) {
                        return 'KN2';
                    } elseif ($hariKe <= 28) {
                        return 'KN3';
                    } else {
                        return '> KN3';
                    }
                }

                return $value ?: 'KN';
            }
        );
    }

    /**
     * Menghitung klasifikasi Kunjungan Neonatal (KN1, KN2, KN3) berdasarkan selisih hari setelah kelahiran.
     * Sesuai standar PWS KIA & Neonatus Kemenkes RI (Hari lahir = Hari ke-1):
     * - KN1 : Hari ke-1 s/d 2 (6 - 48 jam)
     * - KN2 : Hari ke-3 s/d 7
     * - KN3 : Hari ke-8 s/d 28
     *
     * @return array{hari_ke: int, kn_type: string, label: string}
     */
    public static function calculateKn(string|Carbon $tanggalPersalinan, string|Carbon $tanggalKunjungan): array
    {
        $persalinan = Carbon::parse($tanggalPersalinan)->startOfDay();
        $kunjungan = Carbon::parse($tanggalKunjungan)->startOfDay();
        $hariKe = max(1, (int) $persalinan->diffInDays($kunjungan) + 1);

        if ($hariKe <= 2) {
            $knType = 'KN1';
            $label = 'KN1 (Hari ke-1 - 2)';
        } elseif ($hariKe <= 7) {
            $knType = 'KN2';
            $label = 'KN2 (3 - 7 hari)';
        } elseif ($hariKe <= 28) {
            $knType = 'KN3';
            $label = 'KN3 (8 - 28 hari)';
        } else {
            $knType = '> KN3';
            $label = 'Lewat Masa Neonatal (> 28 hari)';
        }

        return [
            'hari_ke' => $hariKe,
            'kn_type' => $knType,
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
     * Relasi ke kehamilan terkait.
     */
    public function pregnancy(): BelongsTo
    {
        return $this->belongsTo(Pregnancy::class);
    }

    /**
     * Bidan / petugas pemeriksa yang mencatat kunjungan neonatal ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Memeriksa apakah user tertentu (atau user login) berhak mengelola (ubah / hapus) data kunjungan neonatal ini.
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
