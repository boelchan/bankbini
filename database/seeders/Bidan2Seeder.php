<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class Bidan2Seeder extends Seeder
{
    /**
     * Menjalankan seeder khusus Bidan 2.
     */
    public function run(): void
    {
        $this->call(BidanSeeder::class);
    }
}
