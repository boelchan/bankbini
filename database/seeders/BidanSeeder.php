<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class BidanSeeder extends Seeder
{
    /**
     * Menjalankan seeder master akun Bidan Puskesmas Pragaan.
     */
    public function run(): void
    {
        // Pastikan role bidan tersedia
        Role::firstOrCreate(['name' => 'bidan', 'guard_name' => 'web']);

        // 1. Bidan 1 (Desa Prenduan)
        $bidan1 = User::updateOrCreate(
            ['email' => 'bidan@app.com'],
            [
                'name'         => 'Bidan Siti Rahayu, S.Tr.Keb',
                'password'     => bcrypt('123'),
                'jabatan'      => 'Bidan Desa Prenduan',
                'village_code' => 3529112012,
                'status'       => 'active',
            ]
        );
        $bidan1->syncRoles(['bidan']);

        // 2. Bidan 2 (Desa Karduluk) - email bidan2@app.com & bidanb@app.com
        $bidan2 = User::updateOrCreate(
            ['email' => 'bidan2@app.com'],
            [
                'name'         => 'Bidan Nurul Komariyah, Amd.Keb',
                'password'     => bcrypt('123'),
                'jabatan'      => 'Bidan Desa Karduluk',
                'village_code' => 3529112014,
                'status'       => 'active',
            ]
        );
        $bidan2->syncRoles(['bidan']);

        // Akun alias bidanb@app.com untuk kompatibilitas riwayat
        $bidan2Alias = User::updateOrCreate(
            ['email' => 'bidanb@app.com'],
            [
                'name'         => 'Bidan Nurul Komariyah, Amd.Keb',
                'password'     => bcrypt('123'),
                'jabatan'      => 'Bidan Desa Karduluk',
                'village_code' => 3529112014,
                'status'       => 'active',
            ]
        );
        $bidan2Alias->syncRoles(['bidan']);

        // 3. Bidan 3 (Desa Pragaan Daya)
        $bidan3 = User::updateOrCreate(
            ['email' => 'bidanc@app.com'],
            [
                'name'         => 'Bidan Fatimah, Amd.Keb',
                'password'     => bcrypt('123'),
                'jabatan'      => 'Bidan Desa Pragaan Daya',
                'village_code' => 3529112011,
                'status'       => 'active',
            ]
        );
        $bidan3->syncRoles(['bidan']);

        $this->command->info('Akun Bidan 1 (bidan@app.com) dan Bidan 2 (bidan2@app.com / bidanb@app.com) berhasil disemai!');
    }
}
