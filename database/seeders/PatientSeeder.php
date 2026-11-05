<?php

namespace Database\Seeders;

use App\Enums\EducationEnum;
use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\NationalityEnum;
use App\Enums\ReligionEnum;
use App\Models\Encounter;
use App\Models\IndonesiaRegion;
use App\Models\NeonatalEncounter;
use App\Models\Patient;
use App\Models\PostnatalEncounter;
use App\Models\Pregnancy;
use App\Models\PregnancyHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PatientSeeder extends Seeder
{
    /**
     * Menjalankan seeder master data pasien untuk wilayah Kecamatan Pragaan.
     * Tidak menyertakan transaksi kehamilan/kunjungan (tabel transaksi tetap bersih).
     */
    public function run(): void
    {
        // Bersihkan data transaksi dan pasien untuk memastikan database siap digunakan secara bersih
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        NeonatalEncounter::truncate();
        PostnatalEncounter::truncate();
        Encounter::truncate();
        PregnancyHistory::truncate();
        Pregnancy::truncate();
        Patient::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Ambil data user pembuat (Bidan atau User pertama), semai BidanSeeder jika belum ada user
        $creator = User::role('bidan')->first() ?? User::first();
        if (!$creator) {
            $this->call(BidanSeeder::class);
            $creator = User::role('bidan')->first() ?? User::first();
        }
        $creatorId = $creator?->id ?? 1;

        // Ambil desa-desa di Kecamatan Pragaan (parent 352911), semai IndonesiaRegionSeeder jika belum ada
        $villages = IndonesiaRegion::where('parent', 352911)
            ->where('type', 'desa')
            ->orderBy('code', 'asc')
            ->get()
            ->keyBy('code');

        if ($villages->isEmpty()) {
            $this->call(IndonesiaRegionSeeder::class);
            $villages = IndonesiaRegion::where('parent', 352911)
                ->where('type', 'desa')
                ->orderBy('code', 'asc')
                ->get()
                ->keyBy('code');
        }

        // 20 Master Data Pasien Tetap yang tersebar di 14 Desa Kecamatan Pragaan
        $patients = [
            [
                'medical_record_number' => 260001,
                'nik'                   => '3529114101980001',
                'full_name'             => 'Siti Aminah',
                'birth_date'            => '1998-01-14',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::SMA,
                'occupation'            => 'Ibu Rumah Tangga',
                'village_code'          => 3529112012, // Prenduan
                'address'               => 'Dusun Somber RT 01 RW 01, Desa Prenduan',
            ],
            [
                'medical_record_number' => 260002,
                'nik'                   => '3529114202950002',
                'full_name'             => 'Nurul Hidayati',
                'birth_date'            => '1995-02-18',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::S1,
                'occupation'            => 'Guru Honorer',
                'village_code'          => 3529112014, // Karduluk
                'address'               => 'Dusun Morleke RT 02 RW 01, Desa Karduluk',
            ],
            [
                'medical_record_number' => 260003,
                'nik'                   => '3529114303010003',
                'full_name'             => 'Fatimah Azzahra',
                'birth_date'            => '2001-03-22',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::SMA,
                'occupation'            => 'Ibu Rumah Tangga',
                'village_code'          => 3529112010, // Pragaan Laok
                'address'               => 'Dusun Gempal RT 01 RW 02, Desa Pragaan Laok',
            ],
            [
                'medical_record_number' => 260004,
                'nik'                   => '3529114404990004',
                'full_name'             => 'Dewi Sartika',
                'birth_date'            => '1999-04-10',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::D3,
                'occupation'            => 'Wiraswasta',
                'village_code'          => 3529112011, // Pragaan Daya
                'address'               => 'Dusun Nong Pote RT 03 RW 01, Desa Pragaan Daya',
            ],
            [
                'medical_record_number' => 260005,
                'nik'                   => '3529114505030005',
                'full_name'             => 'Anisah Rahmawati',
                'birth_date'            => '2003-05-02',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::SMA,
                'occupation'            => 'Ibu Rumah Tangga',
                'village_code'          => 3529112013, // Aeng Panas
                'address'               => 'Dusun Pesisir RT 02 RW 02, Desa Aeng Panas',
            ],
            [
                'medical_record_number' => 260006,
                'nik'                   => '3529114606900006',
                'full_name'             => 'Sri Wahyuni',
                'birth_date'            => '1990-06-15',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::SMP,
                'occupation'            => 'Petani / Pekebun',
                'village_code'          => 3529112007, // Pakamban Daya
                'address'               => 'Dusun Gunung RT 01 RW 01, Desa Pakamban Daya',
            ],
            [
                'medical_record_number' => 260007,
                'nik'                   => '3529114707040007',
                'full_name'             => 'Holifah',
                'birth_date'            => '2004-07-21',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::SMA,
                'occupation'            => 'Ibu Rumah Tangga',
                'village_code'          => 3529112009, // Jaddung
                'address'               => 'Dusun Karang RT 02 RW 01, Desa Jaddung',
            ],
            [
                'medical_record_number' => 260008,
                'nik'                   => '3529114808970008',
                'full_name'             => 'Lilik Marlina',
                'birth_date'            => '1997-08-30',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::SMA,
                'occupation'            => 'Pedagang',
                'village_code'          => 3529112002, // Sendang
                'address'               => 'Dusun Sumber RT 01 RW 02, Desa Sendang',
            ],
            [
                'medical_record_number' => 260009,
                'nik'                   => '3529114909020009',
                'full_name'             => 'Halimatus Sakdiyah',
                'birth_date'            => '2002-09-12',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::SMP,
                'occupation'            => 'Ibu Rumah Tangga',
                'village_code'          => 3529112003, // Rombasan
                'address'               => 'Dusun Somber RT 02 RW 01, Desa Rombasan',
            ],
            [
                'medical_record_number' => 260010,
                'nik'                   => '3529115010990010',
                'full_name'             => 'Wardah Khumairah',
                'birth_date'            => '1999-10-05',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::S1,
                'occupation'            => 'Perangkat Desa',
                'village_code'          => 3529112005, // Larangan Perreng
                'address'               => 'Dusun Tenggina RT 01 RW 01, Desa Larangan Perreng',
            ],
            [
                'medical_record_number' => 260011,
                'nik'                   => '3529115111960011',
                'full_name'             => 'Khadijah Safitri',
                'birth_date'            => '1996-11-20',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::SMA,
                'occupation'            => 'Ibu Rumah Tangga',
                'village_code'          => 3529112006, // Sentol Daya
                'address'               => 'Dusun Ares RT 03 RW 02, Desa Sentol Daya',
            ],
            [
                'medical_record_number' => 260012,
                'nik'                   => '3529115212940012',
                'full_name'             => 'Sumarni Ningsih',
                'birth_date'            => '1994-12-14',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::SMP,
                'occupation'            => 'Petani / Pekebun',
                'village_code'          => 3529112008, // Pakamban Laok
                'address'               => 'Dusun Talon RT 02 RW 01, Desa Pakamban Laok',
            ],
            [
                'medical_record_number' => 260013,
                'nik'                   => '3529115301010013',
                'full_name'             => 'Ainur Rohmah',
                'birth_date'            => '2001-01-25',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::SMA,
                'occupation'            => 'Wiraswasta',
                'village_code'          => 3529112001, // Kaduara Timur
                'address'               => 'Dusun Bates RT 01 RW 01, Desa Kaduara Timur',
            ],
            [
                'medical_record_number' => 260014,
                'nik'                   => '3529115402920014',
                'full_name'             => 'Rohaniyah',
                'birth_date'            => '1992-02-11',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::SMA,
                'occupation'            => 'Ibu Rumah Tangga',
                'village_code'          => 3529112004, // Sentol Laok
                'address'               => 'Dusun Morleke RT 01 RW 02, Desa Sentol Laok',
            ],
            [
                'medical_record_number' => 260015,
                'nik'                   => '3529115503000015',
                'full_name'             => 'Musdalifah',
                'birth_date'            => '2000-03-08',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::SMA,
                'occupation'            => 'Pedagang',
                'village_code'          => 3529112011, // Pragaan Daya
                'address'               => 'Dusun Nong Pote RT 01 RW 02, Desa Pragaan Daya',
            ],
            [
                'medical_record_number' => 260016,
                'nik'                   => '3529115604970016',
                'full_name'             => 'Uswatun Hasanah',
                'birth_date'            => '1997-04-29',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::D3,
                'occupation'            => 'Tenaga Kesehatan',
                'village_code'          => 3529112010, // Pragaan Laok
                'address'               => 'Dusun Pesisir RT 02 RW 01, Desa Pragaan Laok',
            ],
            [
                'medical_record_number' => 260017,
                'nik'                   => '3529115705990017',
                'full_name'             => 'Fauziyah Nurul',
                'birth_date'            => '1999-05-17',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::SMA,
                'occupation'            => 'Ibu Rumah Tangga',
                'village_code'          => 3529112003, // Rombasan
                'address'               => 'Dusun Somber RT 01 RW 01, Desa Rombasan',
            ],
            [
                'medical_record_number' => 260018,
                'nik'                   => '3529115806020018',
                'full_name'             => 'Mariyam Kusuma',
                'birth_date'            => '2002-06-09',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::SMA,
                'occupation'            => 'Wiraswasta',
                'village_code'          => 3529112012, // Prenduan
                'address'               => 'Dusun Karang RT 03 RW 01, Desa Prenduan',
            ],
            [
                'medical_record_number' => 260019,
                'nik'                   => '3529115907930019',
                'full_name'             => 'Indah Wahyuni',
                'birth_date'            => '1993-07-27',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::S1,
                'occupation'            => 'Guru',
                'village_code'          => 3529112014, // Karduluk
                'address'               => 'Dusun Somber RT 02 RW 02, Desa Karduluk',
            ],
            [
                'medical_record_number' => 260020,
                'nik'                   => '3529116008000020',
                'full_name'             => 'Fitriana Zahra',
                'birth_date'            => '2000-08-16',
                'birth_place'           => 'Sumenep',
                'education'             => EducationEnum::SMA,
                'occupation'            => 'Ibu Rumah Tangga',
                'village_code'          => 3529112013, // Aeng Panas
                'address'               => 'Dusun Somber RT 01 RW 01, Desa Aeng Panas',
            ],
        ];

        foreach ($patients as $item) {
            Patient::create([
                'uuid'                  => (string) Str::uuid(),
                'medical_record_number' => $item['medical_record_number'],
                'nik'                   => $item['nik'],
                'full_name'             => $item['full_name'],
                'gender'                => GenderEnum::FEMALE,
                'birth_date'            => $item['birth_date'],
                'birth_place'           => $item['birth_place'],
                'religion'              => ReligionEnum::ISLAM,
                'marital_status'        => MaritalStatusEnum::KAWIN,
                'education'             => $item['education'],
                'occupation'            => $item['occupation'],
                'phone'                 => sprintf('08123456%04d', $item['medical_record_number'] % 10000),
                'mobile_phone'          => sprintf('08123456%04d', $item['medical_record_number'] % 10000),
                'province_code'         => 35,
                'regency_code'          => 3529,
                'district_code'         => 352911,
                'village_code'          => $item['village_code'],
                'address'               => $item['address'],
                'nationality'           => NationalityEnum::WNI,
                'is_active'             => true,
                'created_by'            => $creatorId,
            ]);
        }

        $this->command->info('Master data pasien (' . count($patients) . ' pasien) berhasil dibuat tanpa transaksi!');
    }
}
