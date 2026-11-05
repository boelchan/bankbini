<?php

namespace App\Models;

use App\Enums\GenderEnum;
use App\Enums\ReligionEnum;
use App\Enums\EducationEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\NationalityEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Table(name: 'patients', key: 'id')]
#[Fillable([
    'uuid',
    'medical_record_number',
    'nik',
    'ihs_number',
    'passport_kitas',
    'full_name',
    'family_name',
    'birth_date',
    'birth_place',
    'gender',
    'phone',
    'mobile_phone',
    'email',
    'address',
    'province_code',
    'regency_code',
    'district_code',
    'village_code',
    'postal_code',
    'nationality',
    'religion',
    'education',
    'occupation',
    'marital_status',
    'insurance_number',
    'insurance_name',
    'emergency_contact_name',
    'emergency_contact_relation',
    'emergency_contact_phone',
    'is_active',
    'created_by',
    'updated_by',
])]
class Patient extends Model
{

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => GenderEnum::class,
            'religion' => ReligionEnum::class,
            'education' => EducationEnum::class,
            'marital_status' => MaritalStatusEnum::class,
            'nationality' => NationalityEnum::class,
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Menghitung umur pasien saat ini dalam format tahun, bulan, dan hari.
     */
    public function getUmurSekarangAttribute(): string
    {
        if (!$this->birth_date) {
            return '-';
        }

        $birthDate = \Carbon\Carbon::parse($this->birth_date)->startOfDay();
        $today = now()->startOfDay();

        if ($today->lt($birthDate)) {
            return '-';
        }

        $diff = $birthDate->diff($today);
        return $diff->y . ' th ' . $diff->m . ' bln ' . $diff->d . ' hr';
    }

    public function province()
    {
        return $this->belongsTo(IndonesiaRegion::class, 'province_code', 'code');
    }

    public function regency()
    {
        return $this->belongsTo(IndonesiaRegion::class, 'regency_code', 'code');
    }

    public function district()
    {
        return $this->belongsTo(IndonesiaRegion::class, 'district_code', 'code');
    }

    public function village()
    {
        return $this->belongsTo(IndonesiaRegion::class, 'village_code', 'code');
    }

    public function encounters()
    {
        return $this->hasMany(Encounter::class);
    }

    public function pregnancyHistories()
    {
        return $this->hasMany(PregnancyHistory::class)->orderBy('tanggal_persalinan', 'desc');
    }

    public function pregnancies()
    {
        return $this->hasMany(Pregnancy::class)->latest();
    }

    public function activePregnancy()
    {
        return $this->hasOne(Pregnancy::class)->where('status', 'aktif')->latestOfMany();
    }

    public function postnatalEncounters()
    {
        return $this->hasMany(PostnatalEncounter::class)->orderBy('encounter_date', 'asc');
    }

    public function neonatalEncounters()
    {
        return $this->hasMany(NeonatalEncounter::class)->orderBy('encounter_date', 'asc');
    }
}
