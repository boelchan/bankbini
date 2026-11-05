<?php

namespace Database\Seeders;

use App\Enums\PoedjiRochyatiEnum;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AntenatalEncounterSeeder extends Seeder
{
    /**
     * Menjalankan seeder untuk 20 data kehamilan aktif dan 20 kunjungan antenatal (ANC).
     * Mencakup variasi klinis lengkap:
     * - Trimester 1, 2, dan 3
     * - Kategori Risiko KRR (10), KRT (6), dan KRST (4)
     * - 3 Pasien Siaga H-3 HPL (H-2, H-1, Lewat 1 hari)
     * - Parameter tanda vital, lab abnormal (anemia, proteinuria, gda), dan skrining jiwa
     * - Bidan pengampu terdistribusi antara Bidan 1 dan Bidan 2
     */
    public function run(): void
    {
        // 1. Pastikan data Bidan tersedia
        $bidan1 = User::where('email', 'bidan@app.com')->first();
        $bidan2 = User::where('email', 'bidan2@app.com')->first() ?? User::where('email', 'bidanb@app.com')->first();

        if (!$bidan1 || !$bidan2) {
            $this->call(BidanSeeder::class);
            $bidan1 = User::where('email', 'bidan@app.com')->first();
            $bidan2 = User::where('email', 'bidan2@app.com')->first() ?? User::where('email', 'bidanb@app.com')->first();
        }

        $bidan1Id = $bidan1?->id ?? 1;
        $bidan2Id = $bidan2?->id ?? $bidan1Id;

        // 2. Bersihkan data kunjungan dan kehamilan lama agar seeder bersifat idempoten
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Encounter::truncate();
        Pregnancy::truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 3. Pastikan 20 data Pasien tersedia
        $patients = Patient::orderBy('id', 'asc')->take(20)->get();
        if ($patients->count() < 20) {
            $this->call(PatientSeeder::class);
            $patients = Patient::orderBy('id', 'asc')->take(20)->get();
        }

        // 3. Konfigurasi 20 kasus klinis yang realistis dan variatif
        $cases = [
            // --- KELOMPOK I: 3 PASIEN SIAGA H-3 HPL (TRIMESTER 3 AKHIR) ---
            [
                'index'          => 0,
                'hpl'            => today()->addDays(2), // H-2 HPL (2 hari lagi)
                'weeks_gestation'=> 39,
                'gravida'        => 1,
                'partus'         => 0,
                'abortus'        => 0,
                'skor_pr'        => 2,
                'factors'        => [],
                'bidan_id'       => $bidan1Id,
                'bb'             => 63.5,
                'tb'             => 155.0,
                'lila'           => 26.0,
                'sys'            => 115,
                'dia'            => 75,
                'tfu'            => 31.0,
                'djj'            => 142,
                'hb'             => 12.4,
                'protein_urine'  => 'Negatif',
                'gda'            => 98,
                'skor_jiwa'      => 4,
                'is_usg'         => true,
                'masalah'        => 'Persiapan persalinan normal, donor darah & transportasi desa siaga',
            ],
            [
                'index'          => 1,
                'hpl'            => today()->addDays(1), // H-1 HPL (1 hari lagi / besok)
                'weeks_gestation'=> 39,
                'gravida'        => 2,
                'partus'         => 1,
                'abortus'        => 0,
                'skor_pr'        => 6, // KRT
                'factors'        => [PoedjiRochyatiEnum::TERLALU_CEPAT_HAMIL_LAGI->value],
                'bidan_id'       => $bidan2Id,
                'bb'             => 67.0,
                'tb'             => 152.0,
                'lila'           => 25.5,
                'sys'            => 125,
                'dia'            => 80,
                'tfu'            => 32.0,
                'djj'            => 146,
                'hb'             => 11.2, // Anemia ringan
                'protein_urine'  => 'Negatif',
                'gda'            => 105,
                'skor_jiwa'      => 6,
                'is_usg'         => true,
                'masalah'        => 'Mendekati HPL, pemantauan his dan tanda inpartu',
            ],
            [
                'index'          => 2,
                'hpl'            => today()->subDays(1), // Lewat 1 hari HPL (Postdate awal)
                'weeks_gestation'=> 40,
                'gravida'        => 3,
                'partus'         => 2,
                'abortus'        => 0,
                'skor_pr'        => 10, // KRT
                'factors'        => [
                    PoedjiRochyatiEnum::KEHAMILAN_LEBIH_BULAN->value,
                    PoedjiRochyatiEnum::ANAK_BANYAK->value,
                ],
                'bidan_id'       => $bidan2Id,
                'bb'             => 70.5,
                'tb'             => 156.0,
                'lila'           => 27.0,
                'sys'            => 130,
                'dia'            => 85,
                'tfu'            => 33.0,
                'djj'            => 140,
                'hb'             => 12.0,
                'protein_urine'  => 'Negatif',
                'gda'            => 110,
                'skor_jiwa'      => 8,
                'is_usg'         => true,
                'masalah'        => 'Lewat HPL 1 hari, rencana rujukan jika belum inpartu dalam 48 jam',
            ],

            // --- KELOMPOK II: 4 PASIEN TRIMESTER 3 REGULER (28 - 36 MINGGU) ---
            [
                'index'          => 3,
                'hpl'            => today()->addWeeks(8), // ~32 minggu
                'weeks_gestation'=> 32,
                'gravida'        => 2,
                'partus'         => 0,
                'abortus'        => 1,
                'skor_pr'        => 6, // KRT
                'factors'        => [PoedjiRochyatiEnum::PERNAH_GAGAL_KEHAMILAN->value],
                'bidan_id'       => $bidan1Id,
                'bb'             => 59.0,
                'tb'             => 154.0,
                'lila'           => 24.5,
                'sys'            => 120,
                'dia'            => 80,
                'tfu'            => 28.0,
                'djj'            => 144,
                'hb'             => 12.2,
                'protein_urine'  => 'Negatif',
                'gda'            => 92,
                'skor_jiwa'      => 5,
                'is_usg'         => true,
                'masalah'        => 'Riwayat abortus sebelumnya, evaluasi ketuban & plasenta',
            ],
            [
                'index'          => 4,
                'hpl'            => today()->addWeeks(5), // ~35 minggu
                'weeks_gestation'=> 35,
                'gravida'        => 1,
                'partus'         => 0,
                'abortus'        => 0,
                'skor_pr'        => 18, // KRST
                'factors'        => [
                    PoedjiRochyatiEnum::PREEKLAMSIA_KEJANG->value, // +8
                    PoedjiRochyatiEnum::BENGKAK_TENSI_TINGGI->value, // +4
                    PoedjiRochyatiEnum::TERLALU_MUDA_HAMIL_1->value, // +4
                ],
                'bidan_id'       => $bidan2Id,
                'bb'             => 66.0,
                'tb'             => 149.0,
                'lila'           => 23.0,
                'sys'            => 145,
                'dia'            => 95,
                'tfu'            => 30.0,
                'djj'            => 148,
                'hb'             => 11.5,
                'protein_urine'  => '+2', // Abnormal!
                'gda'            => 115,
                'skor_jiwa'      => 11, // Gejala kecemasan sedang
                'is_usg'         => true,
                'masalah'        => 'Preeklamsia sedang-berat, edema tungkai +2, konsultasi dokter SpOG',
            ],
            [
                'index'          => 5,
                'hpl'            => today()->addWeeks(11), // ~29 minggu
                'weeks_gestation'=> 29,
                'gravida'        => 4,
                'partus'         => 3,
                'abortus'        => 0,
                'skor_pr'        => 2, // KRR
                'factors'        => [],
                'bidan_id'       => $bidan1Id,
                'bb'             => 62.0,
                'tb'             => 157.0,
                'lila'           => 26.5,
                'sys'            => 110,
                'dia'            => 70,
                'tfu'            => 26.0,
                'djj'            => 138,
                'hb'             => 12.6,
                'protein_urine'  => 'Negatif',
                'gda'            => 90,
                'skor_jiwa'      => 3,
                'is_usg'         => true,
                'masalah'        => 'Kondisi ibu dan janin normal, edukasi tanda bahaya TM3',
            ],
            [
                'index'          => 6,
                'hpl'            => today()->addWeeks(6), // ~34 minggu
                'weeks_gestation'=> 34,
                'gravida'        => 1,
                'partus'         => 0,
                'abortus'        => 0,
                'skor_pr'        => 10, // KRT
                'factors'        => [PoedjiRochyatiEnum::LETAK_SUNGSANG->value], // +8
                'bidan_id'       => $bidan2Id,
                'bb'             => 58.5,
                'tb'             => 153.0,
                'lila'           => 24.0,
                'sys'            => 118,
                'dia'            => 78,
                'tfu'            => 29.0,
                'djj'            => 145,
                'hb'             => 12.1,
                'protein_urine'  => 'Negatif',
                'gda'            => 94,
                'skor_jiwa'      => 7,
                'is_usg'         => true,
                'masalah'        => 'Letak sungsang pada 34 minggu, edukasi knee-chest position',
            ],

            // --- KELOMPOK III: 7 PASIEN TRIMESTER 2 (13 - 24 MINGGU) ---
            [
                'index'          => 7,
                'hpl'            => today()->addWeeks(20), // ~20 minggu
                'weeks_gestation'=> 20,
                'gravida'        => 2,
                'partus'         => 1,
                'abortus'        => 0,
                'skor_pr'        => 2, // KRR
                'factors'        => [],
                'bidan_id'       => $bidan1Id,
                'bb'             => 55.0,
                'tb'             => 158.0,
                'lila'           => 25.0,
                'sys'            => 115,
                'dia'            => 75,
                'tfu'            => 19.0,
                'djj'            => 140,
                'hb'             => 12.5,
                'protein_urine'  => 'Negatif',
                'gda'            => 95,
                'skor_jiwa'      => 2,
                'is_usg'         => true,
                'masalah'        => 'Pemeriksaan rutin TM2, gerakan janin aktif',
            ],
            [
                'index'          => 8,
                'hpl'            => today()->addWeeks(16), // ~24 minggu
                'weeks_gestation'=> 24,
                'gravida'        => 1,
                'partus'         => 0,
                'abortus'        => 0,
                'skor_pr'        => 6, // KRT
                'factors'        => [PoedjiRochyatiEnum::PENYAKIT_KURANG_DARAH->value], // +4
                'bidan_id'       => $bidan2Id,
                'bb'             => 51.0,
                'tb'             => 150.0,
                'lila'           => 22.5, // KEK!
                'sys'            => 105,
                'dia'            => 65,
                'tfu'            => 22.0,
                'djj'            => 142,
                'hb'             => 9.8, // Anemia sedang!
                'protein_urine'  => 'Negatif',
                'gda'            => 88,
                'skor_jiwa'      => 6,
                'is_usg'         => true,
                'masalah'        => 'Anemia defisiensi besi & LILA < 23.5 cm (KEK), suplementasi Fe ganda & PMT',
            ],
            [
                'index'          => 9,
                'hpl'            => today()->addWeeks(24), // ~16 minggu
                'weeks_gestation'=> 16,
                'gravida'        => 1,
                'partus'         => 0,
                'abortus'        => 0,
                'skor_pr'        => 2, // KRR
                'factors'        => [],
                'bidan_id'       => $bidan1Id,
                'bb'             => 52.0,
                'tb'             => 156.0,
                'lila'           => 24.5,
                'sys'            => 110,
                'dia'            => 70,
                'tfu'            => 15.0,
                'djj'            => 144,
                'hb'             => 12.8,
                'protein_urine'  => 'Negatif',
                'gda'            => 90,
                'skor_jiwa'      => 3,
                'is_usg'         => false,
                'masalah'        => 'Kontrol ANC K2, keluhan mual mulai mereda',
            ],
            [
                'index'          => 10,
                'hpl'            => today()->addWeeks(18), // ~22 minggu
                'weeks_gestation'=> 22,
                'gravida'        => 3,
                'partus'         => 1,
                'abortus'        => 1,
                'skor_pr'        => 14, // KRST
                'factors'        => [
                    PoedjiRochyatiEnum::PERNAH_OPERASI_SESAR->value, // +8
                    PoedjiRochyatiEnum::PERNAH_GAGAL_KEHAMILAN->value, // +4
                ],
                'bidan_id'       => $bidan2Id,
                'bb'             => 60.0,
                'tb'             => 155.0,
                'lila'           => 26.0,
                'sys'            => 120,
                'dia'            => 80,
                'tfu'            => 20.0,
                'djj'            => 140,
                'hb'             => 12.0,
                'protein_urine'  => 'Negatif',
                'gda'            => 102,
                'skor_jiwa'      => 7,
                'is_usg'         => true,
                'masalah'        => 'Riwayat SC 3 tahun lalu dan kuretase, rencana persalinan SC elektif',
            ],
            [
                'index'          => 11,
                'hpl'            => today()->addWeeks(22), // ~18 minggu
                'weeks_gestation'=> 18,
                'gravida'        => 2,
                'partus'         => 1,
                'abortus'        => 0,
                'skor_pr'        => 2, // KRR
                'factors'        => [],
                'bidan_id'       => $bidan1Id,
                'bb'             => 54.5,
                'tb'             => 153.0,
                'lila'           => 25.0,
                'sys'            => 115,
                'dia'            => 75,
                'tfu'            => 17.0,
                'djj'            => 138,
                'hb'             => 12.4,
                'protein_urine'  => 'Negatif',
                'gda'            => 96,
                'skor_jiwa'      => 4,
                'is_usg'         => false,
                'masalah'        => 'Kontrol ANC K2, anjuran konsumsi tablet tambah darah rutin',
            ],
            [
                'index'          => 12,
                'hpl'            => today()->addWeeks(17), // ~23 minggu
                'weeks_gestation'=> 23,
                'gravida'        => 1,
                'partus'         => 0,
                'abortus'        => 0,
                'skor_pr'        => 2, // KRR
                'factors'        => [],
                'bidan_id'       => $bidan2Id,
                'bb'             => 57.0,
                'tb'             => 160.0,
                'lila'           => 25.5,
                'sys'            => 118,
                'dia'            => 76,
                'tfu'            => 21.0,
                'djj'            => 144,
                'hb'             => 13.0,
                'protein_urine'  => 'Negatif',
                'gda'            => 100,
                'skor_jiwa'      => 3,
                'is_usg'         => true,
                'masalah'        => 'Kondisi janin baik, USG anatomi trimester kedua normal',
            ],
            [
                'index'          => 13,
                'hpl'            => today()->addWeeks(25), // ~15 minggu
                'weeks_gestation'=> 15,
                'gravida'        => 3,
                'partus'         => 2,
                'abortus'        => 0,
                'skor_pr'        => 6, // KRT
                'factors'        => [PoedjiRochyatiEnum::TERLALU_PENDEK->value], // +4
                'bidan_id'       => $bidan1Id,
                'bb'             => 48.0,
                'tb'             => 144.0, // Pendek <= 145 cm
                'lila'           => 23.8,
                'sys'            => 110,
                'dia'            => 70,
                'tfu'            => 14.0,
                'djj'            => 142,
                'hb'             => 12.3,
                'protein_urine'  => 'Negatif',
                'gda'            => 92,
                'skor_jiwa'      => 4,
                'is_usg'         => false,
                'masalah'        => 'Tinggi badan ibu 144 cm (panggul sempit relatif), observasi ketat jalan lahir',
            ],

            // --- KELOMPOK IV: 6 PASIEN TRIMESTER 1 (6 - 11 MINGGU) ---
            [
                'index'          => 14,
                'hpl'            => today()->addWeeks(30), // ~10 minggu
                'weeks_gestation'=> 10,
                'gravida'        => 1,
                'partus'         => 0,
                'abortus'        => 0,
                'skor_pr'        => 2, // KRR
                'factors'        => [],
                'bidan_id'       => $bidan2Id,
                'bb'             => 49.0,
                'tb'             => 155.0,
                'lila'           => 24.0,
                'sys'            => 110,
                'dia'            => 70,
                'tfu'            => 12.0,
                'djj'            => 148,
                'hb'             => 12.7,
                'protein_urine'  => 'Negatif',
                'gda'            => 94,
                'skor_jiwa'      => 5,
                'is_usg'         => true,
                'masalah'        => 'Kunjungan K1 murni, edukasi asam folat dan pencegahan mual muntah',
            ],
            [
                'index'          => 15,
                'hpl'            => today()->addWeeks(32), // ~8 minggu
                'weeks_gestation'=> 8,
                'gravida'        => 2,
                'partus'         => 1,
                'abortus'        => 0,
                'skor_pr'        => 2, // KRR
                'factors'        => [],
                'bidan_id'       => $bidan1Id,
                'bb'             => 56.0,
                'tb'             => 156.0,
                'lila'           => 25.0,
                'sys'            => 112,
                'dia'            => 72,
                'tfu'            => 10.0,
                'djj'            => 150,
                'hb'             => 13.1,
                'protein_urine'  => 'Negatif',
                'gda'            => 90,
                'skor_jiwa'      => 3,
                'is_usg'         => true,
                'masalah'        => 'Kantong gestasi intrauterin (+), detak jantung janin positif',
            ],
            [
                'index'          => 16,
                'hpl'            => today()->addWeeks(29), // ~11 minggu
                'weeks_gestation'=> 11,
                'gravida'        => 1,
                'partus'         => 0,
                'abortus'        => 0,
                'skor_pr'        => 6, // KRT
                'factors'        => [PoedjiRochyatiEnum::TERLALU_MUDA_HAMIL_1->value], // +4
                'bidan_id'       => $bidan2Id,
                'bb'             => 46.5,
                'tb'             => 150.0,
                'lila'           => 23.0,
                'sys'            => 108,
                'dia'            => 68,
                'tfu'            => 13.0,
                'djj'            => 146,
                'hb'             => 11.4,
                'protein_urine'  => 'Negatif',
                'gda'            => 89,
                'skor_jiwa'      => 6,
                'is_usg'         => false,
                'masalah'        => 'Primigravida muda (usia 16 tahun), konseling gizi dan pendampingan keluarga',
            ],
            [
                'index'          => 17,
                'hpl'            => today()->addWeeks(33), // ~7 minggu
                'weeks_gestation'=> 7,
                'gravida'        => 1,
                'partus'         => 0,
                'abortus'        => 0,
                'skor_pr'        => 2, // KRR
                'factors'        => [],
                'bidan_id'       => $bidan1Id,
                'bb'             => 50.0,
                'tb'             => 157.0,
                'lila'           => 24.5,
                'sys'            => 115,
                'dia'            => 75,
                'tfu'            => 8.0,
                'djj'            => 145,
                'hb'             => 12.9,
                'protein_urine'  => 'Negatif',
                'gda'            => 95,
                'skor_jiwa'      => 4,
                'is_usg'         => false,
                'masalah'        => 'K1 awal, keluhan morning sickness ringan, diberikan vitamin B6',
            ],
            [
                'index'          => 18,
                'hpl'            => today()->addWeeks(31), // ~9 minggu
                'weeks_gestation'=> 9,
                'gravida'        => 3,
                'partus'         => 1,
                'abortus'        => 1,
                'skor_pr'        => 12, // KRST
                'factors'        => [
                    PoedjiRochyatiEnum::PERNAH_OPERASI_SESAR->value, // +8
                    PoedjiRochyatiEnum::PENYAKIT_KURANG_DARAH->value, // +4
                ],
                'bidan_id'       => $bidan2Id,
                'bb'             => 53.0,
                'tb'             => 152.0,
                'lila'           => 23.5,
                'sys'            => 115,
                'dia'            => 75,
                'tfu'            => 11.0,
                'djj'            => 148,
                'hb'             => 10.4, // Anemia
                'protein_urine'  => 'Negatif',
                'gda'            => 98,
                'skor_jiwa'      => 6,
                'is_usg'         => true,
                'masalah'        => 'Riwayat SC sebelumnya disertai anemia ringan, edukasi jarak kelahiran',
            ],
            [
                'index'          => 19,
                'hpl'            => today()->addWeeks(34), // ~6 minggu
                'weeks_gestation'=> 6,
                'gravida'        => 1,
                'partus'         => 0,
                'abortus'        => 0,
                'skor_pr'        => 2, // KRR
                'factors'        => [],
                'bidan_id'       => $bidan1Id,
                'bb'             => 47.0,
                'tb'             => 154.0,
                'lila'           => 24.0,
                'sys'            => 110,
                'dia'            => 70,
                'tfu'            => 7.0,
                'djj'            => 142,
                'hb'             => 12.5,
                'protein_urine'  => 'Negatif',
                'gda'            => 92,
                'skor_jiwa'      => 3,
                'is_usg'         => false,
                'masalah'        => 'Konfirmasi kehamilan via tes pack positif, edukasi tanda awal kehamilan',
            ],
        ];

        $totalEncountersCreated = 0;

        foreach ($cases as $case) {
            $patient = $patients->get($case['index']);
            if (!$patient) {
                continue;
            }

            // Hitung HPHT berbasis HPL (HPL - 280 hari)
            $hpl = Carbon::parse($case['hpl'])->startOfDay();
            $hpht = $hpl->copy()->subDays(280);

            // 1. Buat atau perbarui catatan Kehamilan aktif (Pregnancy)
            $pregnancy = Pregnancy::updateOrCreate(
                [
                    'patient_id' => $patient->id,
                    'status'     => 'aktif',
                ],
                [
                    'uuid'       => (string) Str::uuid(),
                    'created_by' => $case['bidan_id'],
                    'updated_by' => $case['bidan_id'],
                    'gravida'    => $case['gravida'],
                    'partus'     => $case['partus'],
                    'abortus'    => $case['abortus'],
                    'hpht'       => $hpht->format('Y-m-d'),
                    'hpl'        => $hpl->format('Y-m-d'),
                    'hpl_usg'    => $case['is_usg'] ? $hpl->format('Y-m-d') : null,
                    'catatan'    => $case['masalah'],
                ]
            );

            // Hitung IMT & MAP otomatis
            $imt = Encounter::calculateImt($case['bb'], $case['tb']);
            $map = Encounter::calculateMap($case['sys'], $case['dia']);

            // Tanggal kunjungan & rencana kontrol berikutnya
            $encounterDate = today()->subDays(rand(1, 10));
            // Jika H-3 HPL, kontrol berikutnya adalah 3 hari ke depan / saat bersalin
            $nextDate = ($case['hpl']->diffInDays(today(), false) <= 3)
                ? today()->addDays(3)
                : today()->addDays(rand(14, 28));

            // 2. Buat Kunjungan Antenatal (Encounter)
            Encounter::create([
                'uuid'                      => (string) Str::uuid(),
                'patient_id'                => $patient->id,
                'pregnancy_id'              => $pregnancy->id,
                'encounter_number'          => sprintf('ANC-%s-%04d', date('ym'), $case['index'] + 1),
                'encounter_date'            => $encounterDate->format('Y-m-d'),
                'next_encounter_date'       => $nextDate->format('Y-m-d'),
                'bb'                        => $case['bb'],
                'tb'                        => $case['tb'],
                'imt'                       => $imt,
                'lila'                      => $case['lila'],
                'td_sistolik'               => $case['sys'],
                'td_diastolik'              => $case['dia'],
                'map'                       => $map,
                'tfu'                       => $case['tfu'],
                'djj'                       => $case['djj'],
                'skor_puji_rochyati'        => $case['skor_pr'],
                'faktor_risiko_json'        => $case['factors'],
                'skor_skrining_jiwa'        => $case['skor_jiwa'],
                'lab_hb'                    => $case['hb'],
                'lab_hiv'                   => 'Non Reaktif',
                'lab_syphilis'              => 'Non Reaktif',
                'lab_hbsag'                 => 'Non Reaktif',
                'lab_protein_urine'         => $case['protein_urine'],
                'lab_gda'                   => $case['gda'],
                'lab_reduksi_urine'         => 'Negatif',
                'is_usg'                    => $case['is_usg'],
                'masalah_kehamilan'         => $case['masalah'],
                'masalah_kehamilan_lainnya' => null,
                'created_by'                => $case['bidan_id'],
                'updated_by'                => $case['bidan_id'],
            ]);

            $totalEncountersCreated++;
        }

        $this->command->info("Berhasil membuat {$totalEncountersCreated} kunjungan antenatal (ANC) dan kehamilan aktif lengkap dengan variasi KRR/KRT/KRST dan Siaga H-3 HPL!");
    }
}
