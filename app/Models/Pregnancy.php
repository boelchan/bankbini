<?php

namespace App\Models;

use App\Enums\PoedjiRochyatiEnum;
use App\Models\Patient;
use App\Models\PregnancyHistory;
use App\Models\Encounter;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Pregnancy extends Model
{
    protected $fillable = [
        'uuid',
        'patient_id',
        'created_by',
        'updated_by',
        'gravida',
        'partus',
        'abortus',
        'hpht',
        'hpl',
        'hpl_usg',
        'status',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'hpht' => 'date',
            'hpl' => 'date',
            'hpl_usg' => 'date',
            'gravida' => 'integer',
            'partus' => 'integer',
            'abortus' => 'integer',
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

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Bidan/pengguna yang mencatat dan mengampu kehamilan ini pertama kali.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Bidan pengampu kehamilan.
     */
    public function pengampu(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Alias backward-compatible untuk creator (Bidan pengampu).
     */
    public function midwife(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Memeriksa apakah user tertentu merupakan pengampu kehamilan ini.
     */
    public function isPengampu(?User $user): bool
    {
        if (!$user || !$this->created_by) {
            return false;
        }

        return (int) $this->created_by === (int) $user->id;
    }

    /**
     * Memeriksa apakah user tertentu (atau user login) berhak mengedit atau menghapus data kehamilan ini.
     * Hanya Koordinator Bidan atau Bidan Pengampu (creator) yang berhak (tanpa administrator).
     */
    public function canEdit(?User $user = null): bool
    {
        $user ??= Auth::user();
        if (!$user) {
            return false;
        }

        if ($user->hasRole('koordinator-bidan')) {
            return true;
        }

        return $this->isPengampu($user);
    }

    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class)->latest('encounter_date');
    }

    public function latestEncounter()
    {
        return $this->hasOne(Encounter::class)->latestOfMany('encounter_date');
    }

    public function postnatalEncounters(): HasMany
    {
        return $this->hasMany(PostnatalEncounter::class)->orderBy('encounter_date', 'desc');
    }

    public function latestPostnatalEncounter()
    {
        return $this->hasOne(PostnatalEncounter::class)->latestOfMany('encounter_date');
    }

    public function neonatalEncounters(): HasMany
    {
        return $this->hasMany(NeonatalEncounter::class)
            ->where(function ($query) {
                $query->where('kn_type', '!=', 'KOMPLIKASI')->orWhereNull('kn_type');
            })
            ->orderBy('encounter_date', 'desc');
    }

    public function latestNeonatalEncounter()
    {
        return $this->hasOne(NeonatalEncounter::class)
            ->where(function ($query) {
                $query->where('kn_type', '!=', 'KOMPLIKASI')->orWhereNull('kn_type');
            })
            ->latestOfMany('encounter_date');
    }

    public function neonatalComplication()
    {
        return $this->hasOne(NeonatalEncounter::class)->where('kn_type', 'KOMPLIKASI');
    }

    public function pregnancyHistories(): HasMany
    {
        return $this->hasMany(PregnancyHistory::class);
    }

    public function pregnancyHistory(): HasOne
    {
        return $this->hasOne(PregnancyHistory::class)->latestOfMany();
    }

    /**
     * Seluruh bidan pemeriksa yang pernah melakukan kontrol pada kehamilan ini.
     *
     * @return Collection<int, User>
     */
    public function getAttendingMidwivesAttribute(): Collection
    {
        return $this->encounters
            ->map(fn($e) => $e->creator)
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Daftar hasil lab abnormal dari kontrol terakhir.
     */
    public function getLatestAbnormalLabResultsAttribute(): array
    {
        return $this->latestEncounter?->getAbnormalLabResults() ?? [];
    }

    public function getGpaLabelAttribute(): string
    {
        return "G{$this->gravida} P{$this->partus} A{$this->abortus}";
    }

    public function getUsiaKehamilanSaatIniAttribute(): string
    {
        if (!$this->hpht) return '-';
        $hpht = Carbon::parse($this->hpht)->startOfDay();
        $today = today();
        if ($today->lt($hpht)) {
            return '0 Mgg 0 Hr';
        }
        $diffDays = (int) $hpht->diffInDays($today);
        $weeks = intdiv($diffDays, 7);
        $days = $diffDays % 7;
        return "{$weeks} Mgg {$days} Hr";
    }

    /**
     * Menghitung umur/usia ibu saat tanggal HPHT (dalam tahun, bulan, hari).
     */
    public function getUsiaSaatHphtAttribute(): string
    {
        if (!$this->hpht || !$this->patient?->birth_date) {
            return '-';
        }

        $birthDate = Carbon::parse($this->patient->birth_date)->startOfDay();
        $hpht = Carbon::parse($this->hpht)->startOfDay();

        if ($hpht->lt($birthDate)) {
            return '-';
        }

        $diff = $birthDate->diff($hpht);
        return $diff->y . ' th ' . $diff->m . ' bln ' . $diff->d . ' hr';
    }

    public function getMingguKeAttribute(): ?int
    {
        if (!$this->hpht) return null;
        $hpht = Carbon::parse($this->hpht)->startOfDay();
        $today = today();
        if ($today->lt($hpht)) {
            return 0;
        }
        $diffDays = (int) $hpht->diffInDays($today);
        return (int) intdiv($diffDays, 7);
    }

    public function getHariSisaAttribute(): ?int
    {
        if (!$this->hpht) return null;
        $hpht = Carbon::parse($this->hpht)->startOfDay();
        $today = today();
        if ($today->lt($hpht)) {
            return 0;
        }
        $diffDays = (int) $hpht->diffInDays($today);
        return (int) ($diffDays % 7);
    }

    public function getTrimesterAttribute(): int
    {
        $weeks = $this->minggu_ke ?? 0;
        if ($weeks <= 11) return 1;
        if ($weeks <= 24) return 2;
        return 3;
    }

    public function getLatestSkorPujiRochyatiAttribute(): int
    {
        return $this->latestEncounter?->skor_puji_rochyati ?? 2;
    }

    public function getKategoriRisikoAttribute(): string
    {
        $skor = $this->latest_skor_puji_rochyati;
        if ($skor <= 5) return 'krr';
        if ($skor <= 10) return 'krt';
        return 'krst';
    }

    public function getIsKrrAttribute(): bool
    {
        return $this->latest_skor_puji_rochyati <= 5;
    }

    public function getIsKrtAttribute(): bool
    {
        $skor = $this->latest_skor_puji_rochyati;
        return $skor >= 6 && $skor <= 10;
    }

    public function getIsKrstAttribute(): bool
    {
        return $this->latest_skor_puji_rochyati > 12;
    }

    public function getIsRisikoTinggiAttribute(): bool
    {
        return $this->latest_skor_puji_rochyati >= 6;
    }

    public function getKategoriPujiRochyatiLabelAttribute(): string
    {
        return PoedjiRochyatiEnum::getCategory($this->latest_skor_puji_rochyati);
    }

    public function getSisaHariHplAttribute(): ?int
    {
        if (!$this->hpl) return null;
        $hpl = Carbon::parse($this->hpl)->startOfDay();
        return (int) today()->diffInDays($hpl, false);
    }

    /**
     * Memeriksa apakah kehamilan selesai dengan bayi lahir hidup.
     */
    public function getIsBayiLahirHidupAttribute(): bool
    {
        if ($this->status !== 'selesai') {
            return false;
        }

        $latestHistory = $this->patient?->pregnancyHistories->first();
        if (!$latestHistory) {
            return true;
        }

        if (isset($latestHistory->kondisi_bayi) && $latestHistory->kondisi_bayi === 'Lahir Mati') {
            return false;
        }

        if ($latestHistory->masalah_persalinan && str_contains($latestHistory->masalah_persalinan, 'Lahir Mati')) {
            return false;
        }

        return true;
    }

    /**
     * Rekapitulasi capaian indikator pelayanan KIA (K1 Murni/Akses, K4, K5, K6) beserta bulannya.
     *
     * @return array<string, array{achieved: bool, label: string, bulan: ?string, detail: string, color: string, type?: ?string}>
     */
    public function getKiaMilestonesAttribute(): array
    {
        $encounters = $this->encounters;

        $k1Enc = null;
        $k4Enc = null;
        $k5Enc = null;
        $k6Enc = null;

        if ($encounters && $encounters->isNotEmpty()) {
            $sorted = $encounters->sortBy([
                ['encounter_date', 'asc'],
                ['id', 'asc'],
            ]);

            foreach ($sorted as $enc) {
                if ($k1Enc === null && $enc->kunjungan_ke === 1) {
                    $k1Enc = $enc;
                }
                if ($k4Enc === null && $enc->k_stage === 'K4') {
                    $k4Enc = $enc;
                }
                if ($k5Enc === null && $enc->k_stage === 'K5') {
                    $k5Enc = $enc;
                }
                if ($k6Enc === null && $enc->k_stage === 'K6') {
                    $k6Enc = $enc;
                }
            }
        }

        $milestones = [];

        // K1
        if ($k1Enc) {
            $isMurni = $k1Enc->is_k1_murni;
            $bulan = $k1Enc->encounter_date ? $k1Enc->encounter_date->translatedFormat('F Y') : '-';
            $milestones['k1'] = [
                'achieved' => true,
                'type'     => $isMurni ? 'murni' : 'akses',
                'label'    => $isMurni ? 'K1 Murni' : 'K1 Akses',
                'bulan'    => $bulan,
                'detail'   => ($isMurni ? 'K1 Murni' : 'K1 Akses') . ' (Bulan ' . $bulan . ')',
                'color'    => $isMurni ? 'emerald' : 'amber',
            ];
        } else {
            $milestones['k1'] = [
                'achieved' => false,
                'type'     => null,
                'label'    => 'K1',
                'bulan'    => null,
                'detail'   => 'Belum ada kunjungan',
                'color'    => 'slate',
            ];
        }

        // K4
        if ($k4Enc) {
            $bulan = $k4Enc->encounter_date ? $k4Enc->encounter_date->translatedFormat('F Y') : '-';
            $milestones['k4'] = [
                'achieved' => true,
                'label'    => 'K4',
                'bulan'    => $bulan,
                'detail'   => 'K4 (Bulan ' . $bulan . ')',
                'color'    => 'purple',
            ];
        } else {
            $milestones['k4'] = [
                'achieved' => false,
                'label'    => 'K4',
                'bulan'    => null,
                'detail'   => 'Belum terpenuhi',
                'color'    => 'slate',
            ];
        }

        // K5
        if ($k5Enc) {
            $bulan = $k5Enc->encounter_date ? $k5Enc->encounter_date->translatedFormat('F Y') : '-';
            $milestones['k5'] = [
                'achieved' => true,
                'label'    => 'K5',
                'bulan'    => $bulan,
                'detail'   => 'K5 (Bulan ' . $bulan . ')',
                'color'    => 'sky',
            ];
        } else {
            $milestones['k5'] = [
                'achieved' => false,
                'label'    => 'K5',
                'bulan'    => null,
                'detail'   => 'Belum terpenuhi',
                'color'    => 'slate',
            ];
        }

        // K6
        if ($k6Enc) {
            $bulan = $k6Enc->encounter_date ? $k6Enc->encounter_date->translatedFormat('F Y') : '-';
            $milestones['k6'] = [
                'achieved' => true,
                'label'    => 'K6',
                'bulan'    => $bulan,
                'detail'   => 'K6 (Bulan ' . $bulan . ')',
                'color'    => 'teal',
            ];
        } else {
            $milestones['k6'] = [
                'achieved' => false,
                'label'    => 'K6',
                'bulan'    => null,
                'detail'   => 'Belum terpenuhi',
                'color'    => 'slate',
            ];
        }

        return $milestones;
    }
}
