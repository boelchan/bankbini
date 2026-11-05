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
        Schema::table('postnatal_encounters', function (Blueprint $table) {
            $table->dropColumn(['kf_type', 'hari_ke']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('postnatal_encounters', function (Blueprint $table) {
            $table->string('kf_type', 10)->nullable()->after('encounter_date');
            $table->integer('hari_ke')->nullable()->after('kf_type');
        });
    }
};
