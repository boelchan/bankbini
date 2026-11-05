<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pregnancy_histories', function (Blueprint $table) {
            $table->string('kondisi_bayi', 50)->nullable()->default('Lahir Hidup')->after('tempat_persalinan'); // Lahir Hidup, Lahir Mati
            $table->string('jenis_kelamin', 20)->nullable()->default('Laki-laki')->after('kondisi_bayi'); // Laki-laki, Perempuan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pregnancy_histories', function (Blueprint $table) {
            $table->dropColumn(['kondisi_bayi', 'jenis_kelamin']);
        });
    }
};
