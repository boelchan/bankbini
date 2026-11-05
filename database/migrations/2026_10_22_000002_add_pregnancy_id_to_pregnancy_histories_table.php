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
            $table->foreignId('pregnancy_id')->nullable()->after('patient_id')->constrained('pregnancies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pregnancy_histories', function (Blueprint $table) {
            $table->dropForeign(['pregnancy_id']);
            $table->dropColumn('pregnancy_id');
        });
    }
};
