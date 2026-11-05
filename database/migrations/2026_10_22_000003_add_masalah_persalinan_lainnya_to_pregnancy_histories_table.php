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
            $table->string('masalah_persalinan_lainnya', 255)->nullable()->after('masalah_persalinan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pregnancy_histories', function (Blueprint $table) {
            $table->dropColumn('masalah_persalinan_lainnya');
        });
    }
};
