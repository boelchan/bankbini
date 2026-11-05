<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Reset cache permission Spatie agar tidak terjadi bentrok cache permission
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->call([
            IndonesiaRegionSeeder::class
        ]);
        
        $permissions = [
            'user-list',
            'user-tambah',
            'user-edit',
            'user-hapus',
            'pasien-list',
            'pasien-tambah',
            'pasien-edit',
            'pasien-hapus',
            'posting-pamflet',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);
        $adminRole->syncPermissions([
            'user-list',
            'user-tambah',
            'user-edit',
            'user-hapus',
        ]);

        $dokterRole = Role::firstOrCreate(['name' => 'dokter', 'guard_name' => 'web']);
        $dokterRole->syncPermissions([
            'pasien-list',
            'pasien-tambah',
            'pasien-edit',
            'pasien-hapus',
            'posting-pamflet',
        ]);

        // Role Koordinator Bidan: memiliki semua hak akses operasional kecuali manajemen user (milik administrator)
        $koordinatorRole = Role::firstOrCreate(['name' => 'koordinator-bidan', 'guard_name' => 'web']);
        $koordinatorRole->syncPermissions([
            'pasien-list',
            'pasien-tambah',
            'pasien-edit',
            'pasien-hapus',
            'posting-pamflet',
        ]);

        $bidanRole = Role::firstOrCreate(['name' => 'bidan', 'guard_name' => 'web']);
        $bidanRole->syncPermissions([
            'pasien-list',
            'pasien-tambah',
            'pasien-edit',
            'pasien-hapus',
        ]);

        // Dummy Administrator
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@app.com'],
            [
                'name'     => 'Administrator',
                'password' => bcrypt('123'),
                'jabatan'  => 'Administrator Sistem',
                'status'   => 'active',
            ]
        );
        $adminUser->syncRoles(['administrator']);

        // Dummy Koordinator Bidan
        $koordinatorUser = User::updateOrCreate(
            ['email' => 'koordinator@app.com'],
            [
                'name'     => 'Koordinator Bidan',
                'password' => bcrypt('123'),
                'jabatan'  => 'Koordinator Bidan Puskesmas',
                'status'   => 'active',
            ]
        );
        $koordinatorUser->syncRoles(['koordinator-bidan']);

        // Dummy Dokter Puskesmas
        $dokterUser = User::updateOrCreate(
            ['email' => 'dokter@app.com'],
            [
                'name'     => 'dr. Ahmad',
                'password' => bcrypt('123'),
                'jabatan'  => 'Dokter Puskesmas',
                'status'   => 'active',
            ]
        );
        $dokterUser->syncRoles(['dokter']);

        // Dummy Bidan (Desa Prenduan)
        $bidanUser = User::updateOrCreate(
            ['email' => 'bidan@app.com'],
            [
                'name'         => 'Bidan Siti Rahayu, S.Tr.Keb',
                'password'     => bcrypt('123'),
                'jabatan'      => 'Bidan Desa Prenduan',
                'village_code' => 3529112012,
                'status'       => 'active',
            ]
        );
        $bidanUser->syncRoles(['bidan']);

        // Dummy Bidan 2 (Desa Karduluk) - email bidan2@app.com
        $bidanUserB = User::updateOrCreate(
            ['email' => 'bidan2@app.com'],
            [
                'name'         => 'Bidan Nurul Komariyah, Amd.Keb',
                'password'     => bcrypt('123'),
                'jabatan'      => 'Bidan Desa Karduluk',
                'village_code' => 3529112014,
                'status'       => 'active',
            ]
        );
        $bidanUserB->syncRoles(['bidan']);

        // Akun alias bidanb@app.com untuk kompatibilitas riwayat
        $bidanUserBAlias = User::updateOrCreate(
            ['email' => 'bidanb@app.com'],
            [
                'name'         => 'Bidan Nurul Komariyah, Amd.Keb',
                'password'     => bcrypt('123'),
                'jabatan'      => 'Bidan Desa Karduluk',
                'village_code' => 3529112014,
                'status'       => 'active',
            ]
        );
        $bidanUserBAlias->syncRoles(['bidan']);

        // Dummy Bidan 3 (Desa Prenduan Daya)
        $bidanUserC = User::updateOrCreate(
            ['email' => 'bidanc@app.com'],
            [
                'name'         => 'Bidan Fatimah, Amd.Keb',
                'password'     => bcrypt('123'),
                'jabatan'      => 'Bidan Desa Prenduan Daya',
                'village_code' => 3529112011,
                'status'       => 'active',
            ]
        );
        $bidanUserC->syncRoles(['bidan']);

        // Jalankan Seeder Pasien dan Kunjungan Antenatal
        $this->call([
            PatientSeeder::class,
            AntenatalEncounterSeeder::class,
        ]);
    }
}

