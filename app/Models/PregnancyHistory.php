<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PregnancyHistory extends Model
{
    protected $fillable = [
        'patient_id',
        'pregnancy_id',
        'status',
        'tanggal_persalinan',
        'cara_melahirkan',
        'tempat_persalinan',
        'kondisi_bayi',
        'jenis_kelamin',
        'bb_bayi',
        'penolong',
        'masalah_persalinan',
        'masalah_persalinan_lainnya',
        'keterangan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_persalinan' => 'date',
            'bb_bayi' => 'decimal:2',
            'pregnancy_id' => 'integer',
        ];
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
}
