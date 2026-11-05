<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class IndonesiaRegionSeeder extends Seeder
{
    public function run(): void
    {
        // Define the path to the SQL file
        $sqlPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'indonesia_regions_jatim.sql';

        // Cek jika data wilayah (khususnya Kecamatan Pragaan) sudah ada di database, lewati proses import SQL yang memakan waktu
        if (DB::table('indonesia_regions')->where('parent', 352911)->exists()) {
            $this->command->info('Wilayah Kecamatan Pragaan sudah tersedia di database. Melewati import SQL.');
            return;
        }

        // Tingkatkan memory limit & waktu eksekusi untuk file SQL berukuran besar
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        // Output start message
        $this->command->info('Starting to seed Indonesia regions from SQL...');

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate the table before seeding
        DB::table('indonesia_regions')->truncate();

        // Read the SQL file
        $sql = File::get($sqlPath);

        // Execute the SQL statements
        DB::unprepared($sql);

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Output success message
        $this->command->info('Indonesia regions seeded successfully!');
    }
}
