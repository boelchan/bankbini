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
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropColumn(['hpht', 'hpl', 'usia_kehamilan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->date('hpht')->nullable()->after('next_encounter_date');
            $table->date('hpl')->nullable()->after('hpht');
            $table->string('usia_kehamilan', 50)->nullable()->after('hpl');
        });
    }
};
