<?php

namespace App\Models;

use App\Enums\PoedjiRochyatiEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Encounter extends Model
{
    protected $fillable = [
        'uuid',
        'patient_id',
        'pregnancy_id',
        'encounter_number',
        'encounter_date',
        'next_encounter_date',
        'lila',
        'bb',
        'tb',
        'imt',
        'td_sistolik',
        'td_diastolik',
        'map',
        'tfu',
        'djj',
        'skor_puji_rochyati',
        'faktor_risiko_json',
        'skor_skrining_jiwa',
        'lab_hb',
        'lab_hiv',
        'lab_syphilis',
        'lab_hbsag',
        'lab_protein_urine',
        'lab_gda',
        'lab_reduksi_urine',
        'is_usg',
        'masalah_kehamilan',
        'masalah_kehamilan_lainnya',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'encounter_date' => 'date',
            'next_encounter_date' => 'date',
            'is_usg' => 'boolean',
            'lila' => 'decimal:2',
            'bb' => 'decimal:2',
            'tb' => 'decimal:2',
            'imt' => 'decimal:2',
            'map' => 'decimal:2',
            'tfu' => 'decimal:2',
            'lab_hb' => 'decimal:1',
            'faktor_risiko_json' => 'array',
        ];
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->encounter_number)) {
                $model->encounter_number = static::generateEncounterNumber();
            }
        });
    }

    public static function generateEncounterNumber(): string
    {
        $prefix = 'ANC-' . date('ym');
        $latest = static::where('encounter_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (!$latest) {
            return $prefix . '-0001';
        }

        $num = (int) substr($latest->encounter_number, -4);
        return $prefix . '-' . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function pregnancy(): BelongsTo
    {
        return $this->belongsTo(Pregnancy::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Memeriksa apakah user tertentu (atau user login) berhak mengelola (ubah / hapus) data kunjungan pemeriksaan ini.
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

    // Helper calculate IMT
    public static function calculateImt(?float $bb, ?float $tb): ?float
    {
        if (!$bb || !$tb || $tb <= 0) return null;
        $tbMeter = $tb / 100;
        return round($bb / ($tbMeter * $tbMeter), 2);
    }

    // Helper calculate MAP
    public static function calculateMap(?int $sys, ?int $dia): ?float
    {
        if (!$sys || !$dia) return null;
        return round(($sys + (2 * $dia)) / 3, 2);
    }

    // Helper calculate Skrining Jiwa category
    public static function calculateKategoriJiwa(?int $skor): string
    {
        if ($skor === null) return '-';
        if ($skor <= 8) return 'Normal (1-8)';
        if ($skor <= 14) return 'Sedang (9-14)';
        return 'Depresi (>14)';
    }

    // Helper calculate Puji Rochyati category
    public static function calculateKategoriPujiRochyati(int $skor): string
    {
        return PoedjiRochyatiEnum::getCategory($skor);
    }

    /**
     * Kategori Skor Poedji Rochyati (Accessor dinamis berbasis skor_puji_rochyati).
     */
    public function getKategoriPujiRochyatiAttribute(): string
    {
        return PoedjiRochyatiEnum::getCategory((int) ($this->skor_puji_rochyati ?? 2));
    }

    /**
     * Kategori Skrining Jiwa (Accessor dinamis berbasis skor_skrining_jiwa).
     */
    public function getKategoriSkriningJiwaAttribute(): ?string
    {
        if ($this->skor_skrining_jiwa === null) {
            return null;
        }

        return static::calculateKategoriJiwa($this->skor_skrining_jiwa);
    }

    /**
     * Mendapatkan daftar hasil lab yang tidak normal / di luar nilai rujukan.
     * Rujukan normal:
     * - Hb: 12 - 16 g/dL
     * - GDA: 70 - 125 mg/dL
     * - Protein Urine: Negatif
     * - Reduksi Urine: Negatif
     * - HIV: Negatif / Non Reaktif
     * - Syphilis: Negatif / Non Reaktif
     * - HBsAg: Negatif / Non Reaktif
     *
     * @return array<int, array{param: string, label: string, value: string, status: string, normal_ref: string, alert_type: string}>
     */
    public function getAbnormalLabResults(): array
    {
        $abnormal = [];

        // 1. Hb: Nilai normal wanita hamil 12 - 16 g/dL
        if ($this->lab_hb !== null && (float) $this->lab_hb > 0) {
            $hb = (float) $this->lab_hb;
            if ($hb < 12.0) {
                $abnormal[] = [
                    'param' => 'Hb',
                    'label' => 'Hemoglobin',
                    'value' => number_format($hb, 1) . ' g/dL',
                    'status' => 'Rendah (Anemia)',
                    'normal_ref' => 'Normal: 12 - 16 g/dL',
                    'alert_type' => $hb < 10.0 ? 'danger' : 'warning',
                ];
            } elseif ($hb > 16.0) {
                $abnormal[] = [
                    'param' => 'Hb',
                    'label' => 'Hemoglobin',
                    'value' => number_format($hb, 1) . ' g/dL',
                    'status' => 'Tinggi',
                    'normal_ref' => 'Normal: 12 - 16 g/dL',
                    'alert_type' => 'warning',
                ];
            }
        }

        // 2. GDA: Nilai normal 70 - 125 mg/dL
        if ($this->lab_gda !== null && $this->lab_gda !== '') {
            $gda = (float) $this->lab_gda;
            if ($gda > 0) {
                if ($gda < 70) {
                    $abnormal[] = [
                        'param' => 'GDA',
                        'label' => 'Gula Darah Acak',
                        'value' => $this->lab_gda . ' mg/dL',
                        'status' => 'Rendah (<70)',
                        'normal_ref' => 'Normal: 70 - 125 mg/dL',
                        'alert_type' => 'warning',
                    ];
                } elseif ($gda > 125) {
                    $abnormal[] = [
                        'param' => 'GDA',
                        'label' => 'Gula Darah Acak',
                        'value' => $this->lab_gda . ' mg/dL',
                        'status' => 'Tinggi (>125)',
                        'normal_ref' => 'Normal: 70 - 125 mg/dL',
                        'alert_type' => 'danger',
                    ];
                }
            }
        }

        // 3. Protein Urine: Nilai normal Negatif
        if ($this->lab_protein_urine) {
            $val = strtolower(trim($this->lab_protein_urine));
            if ($val !== '' && $val !== 'belum diperiksa' && !str_contains($val, 'negatif')) {
                $abnormal[] = [
                    'param' => 'Protein Urine',
                    'label' => 'Protein Urine',
                    'value' => $this->lab_protein_urine,
                    'status' => 'Positif',
                    'normal_ref' => 'Normal: Negatif',
                    'alert_type' => 'danger',
                ];
            }
        }

        // 4. Reduksi Urine: Nilai normal Negatif
        if ($this->lab_reduksi_urine) {
            $val = strtolower(trim($this->lab_reduksi_urine));
            if ($val !== '' && $val !== 'belum diperiksa' && !str_contains($val, 'negatif')) {
                $abnormal[] = [
                    'param' => 'Reduksi Urine',
                    'label' => 'Reduksi Urine',
                    'value' => $this->lab_reduksi_urine,
                    'status' => 'Positif',
                    'normal_ref' => 'Normal: Negatif',
                    'alert_type' => 'warning',
                ];
            }
        }

        // 5. HIV: Nilai normal Negatif
        if ($this->lab_hiv) {
            $val = strtolower(trim($this->lab_hiv));
            if ($val !== '' && $val !== 'belum diperiksa' && !str_contains($val, 'non') && !str_contains($val, 'negatif')) {
                $displayVal = str_contains($val, 'reaktif') ? 'Positif' : $this->lab_hiv;
                $abnormal[] = [
                    'param' => 'HIV',
                    'label' => 'HIV',
                    'value' => $displayVal,
                    'status' => 'Positif',
                    'normal_ref' => 'Normal: Negatif',
                    'alert_type' => 'danger',
                ];
            }
        }

        // 6. Syphilis: Nilai normal Negatif
        if ($this->lab_syphilis) {
            $val = strtolower(trim($this->lab_syphilis));
            if ($val !== '' && $val !== 'belum diperiksa' && !str_contains($val, 'non') && !str_contains($val, 'negatif')) {
                $displayVal = str_contains($val, 'reaktif') ? 'Positif' : $this->lab_syphilis;
                $abnormal[] = [
                    'param' => 'Syphilis',
                    'label' => 'Syphilis (Sifilis)',
                    'value' => $displayVal,
                    'status' => 'Positif',
                    'normal_ref' => 'Normal: Negatif',
                    'alert_type' => 'danger',
                ];
            }
        }

        // 7. HBsAg: Nilai normal Negatif
        if ($this->lab_hbsag) {
            $val = strtolower(trim($this->lab_hbsag));
            if ($val !== '' && $val !== 'belum diperiksa' && !str_contains($val, 'non') && !str_contains($val, 'negatif')) {
                $displayVal = str_contains($val, 'reaktif') ? 'Positif' : $this->lab_hbsag;
                $abnormal[] = [
                    'param' => 'HBsAg',
                    'label' => 'HBsAg (Hepatitis B)',
                    'value' => $displayVal,
                    'status' => 'Positif',
                    'normal_ref' => 'Normal: Negatif',
                    'alert_type' => 'danger',
                ];
            }
        }

        return $abnormal;
    }

    /**
     * Urutan kunjungan ke berapa pada kehamilan ini (1 untuk K1, 2 untuk K2, dst).
     */
    public function getKunjunganKeAttribute(): int
    {
        if (!$this->pregnancy_id) {
            return 1;
        }

        if ($this->relationLoaded('pregnancy') && $this->pregnancy?->relationLoaded('encounters')) {
            $sorted = $this->pregnancy->encounters->sortBy([
                ['encounter_date', 'asc'],
                ['id', 'asc'],
            ])->values();

            $index = $sorted->search(fn($item) => $item->id === $this->id);
            return $index !== false ? $index + 1 : 1;
        }

        return (int) static::where('pregnancy_id', $this->pregnancy_id)
            ->where(function ($q) {
                $q->where('encounter_date', '<', $this->encounter_date)
                    ->orWhere(function ($q2) {
                        $q2->where('encounter_date', $this->encounter_date)
                            ->where('id', '<=', $this->id);
                    });
            })
            ->count();
    }

    /**
     * Tanggal HPHT kehamilan (diambil otomatis dari relasi Pregnancy).
     */
    public function getHphtAttribute(): ?Carbon
    {
        return $this->pregnancy?->hpht;
    }

    /**
     * Tanggal HPL kehamilan (diambil otomatis dari relasi Pregnancy).
     */
    public function getHplAttribute(): ?Carbon
    {
        return $this->pregnancy?->hpl;
    }

    /**
     * Usia kehamilan saat kunjungan kontrol ini (misal: "12 Minggu 3 Hari").
     * Dihitung secara otomatis & dinamis dari HPHT kehamilan dan tanggal kunjungan.
     */
    public function getUsiaKehamilanAttribute(): ?string
    {
        $hpht = $this->hpht;
        if (!$hpht || !$this->encounter_date) {
            return null;
        }

        $start = Carbon::parse($hpht)->startOfDay();
        $encDate = Carbon::parse($this->encounter_date)->startOfDay();
        if ($encDate->lt($start)) {
            return '0 Minggu 0 Hari';
        }

        $diffDays = (int) $start->diffInDays($encDate);
        $weeks = intdiv($diffDays, 7);
        $days = $diffDays % 7;

        return "{$weeks} Minggu {$days} Hari";
    }

    /**
     * Usia kehamilan dalam minggu saat kunjungan kontrol ini.
     * Digunakan untuk klasifikasi K1 Murni (<= 11 minggu) pada laporan PWS Ibu & LB3 Maternal.
     */
    public function getUsiaKehamilanMingguAttribute(): ?int
    {
        $hpht = $this->hpht;
        if ($hpht && $this->encounter_date) {
            $start = Carbon::parse($hpht)->startOfDay();
            $encDate = Carbon::parse($this->encounter_date)->startOfDay();
            if ($encDate->gte($start)) {
                return (int) $start->diffInWeeks($encDate);
            }
            return 0;
        }

        return null;
    }

    /**
     * Memeriksa apakah minimal salah satu parameter laboratorium telah diisi pada kunjungan ini.
     */
    public function hasFilledLab(): bool
    {
        if ($this->lab_hb !== null && (float) $this->lab_hb > 0) {
            return true;
        }

        if ($this->lab_gda !== null && trim((string) $this->lab_gda) !== '' && (float) $this->lab_gda > 0) {
            return true;
        }

        $textLabs = [
            $this->lab_protein_urine,
            $this->lab_reduksi_urine,
            $this->lab_hiv,
            $this->lab_syphilis,
            $this->lab_hbsag,
        ];

        foreach ($textLabs as $val) {
            if ($val !== null) {
                $clean = strtolower(trim((string) $val));
                if ($clean !== '' && $clean !== 'belum diperiksa' && $clean !== '-') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Menentukan tahapan capaian kunjungan berjenjang (K4, K5, K6, K7, K8) pada kunjungan ini:
     * - K4: Bumil kontrol di TM1 (0-11 mg) min 1x, TM2 (12-24 mg) min 1x, TM3 (25-40 mg) min 2x.
     * - K5: K4 + 1x kunjungan berikutnya yang disertai USG + Lab + mengisi skor_skrining_jiwa.
     * - K6: K5 + 1x kunjungan berikutnya.
     * - K7: K6 + 1x kunjungan berikutnya.
     * - K8: K7 + 1x kunjungan berikutnya dan seterusnya.
     */
    public function getKStageAttribute(): ?string
    {
        $pregnancy = $this->pregnancy;
        $hpht = $pregnancy?->hpht;
        $allEncounters = $pregnancy?->encounters;

        if (!$hpht || !$allEncounters || !$this->encounter_date) {
            return null;
        }

        $startHpht = Carbon::parse($hpht)->startOfDay();
        $targetDate = Carbon::parse($this->encounter_date)->startOfDay();

        $sortedEncounters = $allEncounters
            ->filter(function ($e) use ($targetDate) {
                if (!$e->encounter_date) {
                    return false;
                }
                $eDate = Carbon::parse($e->encounter_date)->startOfDay();
                return $eDate->lt($targetDate) || ($eDate->eq($targetDate) && $e->id <= $this->id);
            })
            ->sortBy([
                fn ($e) => Carbon::parse($e->encounter_date)->timestamp,
                fn ($e) => $e->id,
            ])
            ->values();

        $tm1Count = 0;
        $tm2Count = 0;
        $tm3Count = 0;
        $achievedStage = null;
        $thisEncounterStage = null;

        foreach ($sortedEncounters as $e) {
            $eDate = Carbon::parse($e->encounter_date)->startOfDay();
            $eWeek = $eDate->gte($startHpht) ? (int) $startHpht->diffInWeeks($eDate) : 0;

            if ($eWeek <= 11) {
                $tm1Count++;
            } elseif ($eWeek >= 12 && $eWeek <= 24) {
                $tm2Count++;
            } elseif ($eWeek >= 25 && $eWeek <= 40) {
                $tm3Count++;
            }

            $currentStage = null;

            if ($achievedStage === null) {
                if ($eWeek >= 25 && $eWeek <= 40 && $tm1Count >= 1 && $tm2Count >= 1 && $tm3Count >= 2) {
                    $currentStage = 'K4';
                    $achievedStage = 'K4';
                }
            } elseif ($achievedStage === 'K4') {
                if ((bool) $e->is_usg && $e->skor_skrining_jiwa !== null && $e->hasFilledLab()) {
                    $currentStage = 'K5';
                    $achievedStage = 'K5';
                }
            } elseif ($achievedStage === 'K5') {
                $currentStage = 'K6';
                $achievedStage = 'K6';
            } elseif ($achievedStage === 'K6') {
                $currentStage = 'K7';
                $achievedStage = 'K7';
            } elseif ($achievedStage === 'K7' || $achievedStage === 'K8') {
                $currentStage = 'K8';
                $achievedStage = 'K8';
            }

            if ((int) $e->id === (int) $this->id) {
                $thisEncounterStage = $currentStage;
            }
        }

        return $thisEncounterStage;
    }

    /**
     * Memeriksa apakah kunjungan ini merupakan kunjungan pertama (K1).
     */
    public function getIsK1Attribute(): bool
    {
        return $this->kunjungan_ke === 1;
    }

    /**
     * Memeriksa apakah kunjungan ini merupakan K1 Murni (kunjungan pertama pada usia kehamilan <= 11 minggu / TM1).
     */
    public function getIsK1MurniAttribute(): bool
    {
        if (!$this->is_k1) {
            return false;
        }

        $minggu = $this->usia_kehamilan_minggu;
        return $minggu !== null && $minggu <= 11;
    }

    /**
     * Memeriksa apakah kunjungan ini merupakan K1 Akses (kunjungan pertama pada usia kehamilan > 11 minggu / setelah TM1).
     */
    public function getIsK1AksesAttribute(): bool
    {
        if (!$this->is_k1) {
            return false;
        }

        $minggu = $this->usia_kehamilan_minggu;
        return $minggu === null || $minggu > 11;
    }

    /**
     * Informasi klasifikasi indikator pelayanan KIA dan bulan pencapaian pelayanan (K1 Murni, K1 Akses, K4, K5, K6).
     *
     * @return array{stage: string, bulan: string, detail: string, color: string, desc: string}
     */
    public function getKiaStatusBadgeAttribute(): array
    {
        $bulanStr = $this->encounter_date ? $this->encounter_date->translatedFormat('F Y') : '-';

        if ($this->is_k1_murni) {
            return [
                'stage'  => 'K1 Murni',
                'bulan'  => $bulanStr,
                'detail' => 'K1 Murni (Bulan ' . $bulanStr . ')',
                'color'  => 'emerald',
                'desc'   => 'K1 Murni (Kunjungan pertama saat usia kehamilan ≤ 11 minggu / TM1)',
            ];
        }

        if ($this->is_k1_akses) {
            return [
                'stage'  => 'K1 Akses',
                'bulan'  => $bulanStr,
                'detail' => 'K1 Akses (Bulan ' . $bulanStr . ')',
                'color'  => 'amber',
                'desc'   => 'K1 Akses (Kunjungan pertama saat usia kehamilan > 11 minggu / setelah TM1)',
            ];
        }

        if ($this->k_stage === 'K4') {
            return [
                'stage'  => 'K4',
                'bulan'  => $bulanStr,
                'detail' => 'K4 (Bulan ' . $bulanStr . ')',
                'color'  => 'purple',
                'desc'   => 'Capaian K4 (Standar kontrol TM1 ≥ 1x, TM2 ≥ 1x, TM3 ≥ 2x)',
            ];
        }

        if ($this->k_stage === 'K5') {
            return [
                'stage'  => 'K5',
                'bulan'  => $bulanStr,
                'detail' => 'K5 (Bulan ' . $bulanStr . ')',
                'color'  => 'sky',
                'desc'   => 'Capaian K5 (K4 + 1x disertai USG, Lab lengkap & Skrining Jiwa)',
            ];
        }

        if ($this->k_stage === 'K6') {
            return [
                'stage'  => 'K6',
                'bulan'  => $bulanStr,
                'detail' => 'K6 (Bulan ' . $bulanStr . ')',
                'color'  => 'teal',
                'desc'   => 'Capaian K6 (Standar 6x kunjungan Kemenkes RI)',
            ];
        }

        if (in_array($this->k_stage, ['K7', 'K8'])) {
            return [
                'stage'  => $this->k_stage,
                'bulan'  => $bulanStr,
                'detail' => $this->k_stage . ' (Bulan ' . $bulanStr . ')',
                'color'  => 'slate',
                'desc'   => 'Capaian ' . $this->k_stage . ' (Kunjungan antenatal lanjutan)',
            ];
        }

        $kKe = 'K' . $this->kunjungan_ke;
        return [
            'stage'  => $kKe,
            'bulan'  => $bulanStr,
            'detail' => $kKe . ' (Bulan ' . $bulanStr . ')',
            'color'  => 'slate',
            'desc'   => 'Kunjungan kontrol ke-' . $this->kunjungan_ke,
        ];
    }

    /**
     * Memeriksa apakah kunjungan ini memenuhi kriteria K4:
     * Bumil yang kontrol di TM1 minimal 1x, TM2 minimal 1x, dan TM3 (rentang 25-40 minggu) minimal 2x.
     */
    public function getIsK4Attribute(): bool
    {
        return $this->k_stage === 'K4';
    }

    /**
     * Memeriksa apakah kunjungan ini memenuhi kriteria K5:
     * K4 + 1x kunjungan dengan pemeriksaan USG, Lab, dan mengisi skor skrining jiwa.
     */
    public function getIsK5Attribute(): bool
    {
        return $this->k_stage === 'K5';
    }

    /**
     * Memeriksa apakah kunjungan ini memenuhi kriteria K6 (K5 + 1x).
     */
    public function getIsK6Attribute(): bool
    {
        return $this->k_stage === 'K6';
    }

    /**
     * Memeriksa apakah kunjungan ini memenuhi kriteria K7 (K6 + 1x).
     */
    public function getIsK7Attribute(): bool
    {
        return $this->k_stage === 'K7';
    }

    /**
     * Memeriksa apakah kunjungan ini memenuhi kriteria K8 (K7 + 1x dan seterusnya).
     */
    public function getIsK8Attribute(): bool
    {
        return $this->k_stage === 'K8';
    }

    /**
     * Memeriksa apakah kunjungan ini merupakan kunjungan pertama pada Trimester 2 (rentang 12-24 minggu).
     * Digunakan untuk indikator pemberian Fe-2 dan Fe < 90 pada laporan LB3 Maternal.
     */
    public function getIsFirstTm2Attribute(): bool
    {
        $minggu = $this->usia_kehamilan_minggu;
        if ($minggu === null || $minggu < 12 || $minggu > 24) {
            return false;
        }

        $pregnancy = $this->pregnancy;
        $hpht = $pregnancy?->hpht;
        $allEncounters = $pregnancy?->encounters;

        if (!$hpht || !$allEncounters || !$this->encounter_date) {
            return false;
        }

        $startHpht = Carbon::parse($hpht)->startOfDay();
        $currentDate = Carbon::parse($this->encounter_date)->startOfDay();

        $tm2Count = 0;

        foreach ($allEncounters as $e) {
            if (!$e->encounter_date) {
                continue;
            }

            $eDate = Carbon::parse($e->encounter_date)->startOfDay();
            if ($eDate->gt($currentDate) || ($eDate->eq($currentDate) && $e->id > $this->id)) {
                continue;
            }

            $eWeek = $eDate->gte($startHpht) ? (int) $startHpht->diffInWeeks($eDate) : 0;

            if ($eWeek >= 12 && $eWeek <= 24) {
                $tm2Count++;
            }
        }

        return $tm2Count === 1;
    }
}

