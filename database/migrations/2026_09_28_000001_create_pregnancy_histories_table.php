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
        Schema::create('pregnancy_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->date('tanggal_persalinan')->nullable();
            $table->string('cara_melahirkan', 50)->nullable(); // Normal, SC, Abortus, Vakum
            $table->string('tempat_persalinan', 100)->nullable(); // Klinik, RS, Puskesmas, BPM, Rumah
            $table->decimal('bb_bayi', 5, 2)->nullable(); // dalam kg (misal 3.20) atau gram
            $table->string('penolong', 100)->nullable(); // Dokter SpOG, Bidan, Dokter Umum
            $table->string('masalah_persalinan', 100)->nullable(); // Perdarahan, KPD, Preeklamsia, Tidak Ada
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pregnancy_histories');
    }
};
