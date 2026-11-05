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
            $table->string('status', 20)->default('Lahir')->after('patient_id'); // Lahir, Abortus, Meninggal
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pregnancy_histories', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
