<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class KunjunganAntenatalSeeder extends Seeder
{
    /**
     * Menjalankan seeder untuk 20 kunjungan antenatal.
     */
    public function run(): void
    {
        $this->call(AntenatalEncounterSeeder::class);
    }
}
