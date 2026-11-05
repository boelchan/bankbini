<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class KantongKehamilanSeeder extends Seeder
{
    /**
     * Menjalankan seeder master pasien dan transaksi kantong kehamilan (ANC).
     */
    public function run(): void
    {
        $this->command->info('Menjalankan seeder pasien dan kunjungan antenatal kantong kehamilan...');
        $this->call([
            PatientSeeder::class,
            AntenatalEncounterSeeder::class,
        ]);
    }
}
