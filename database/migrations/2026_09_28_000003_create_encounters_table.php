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
        Schema::create('encounters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('pregnancy_id')->nullable()->constrained('pregnancies')->onDelete('cascade');
            $table->string('encounter_number', 50)->unique();
            $table->date('encounter_date');

            // Tanggal & Usia Kehamilan saat kunjungan
            $table->date('hpht')->nullable();
            $table->date('hpl')->nullable();
            $table->string('usia_kehamilan', 50)->nullable(); // e.g. "12 Minggu 3 Hari"

            // Data Pemeriksaan Fisik & Tanda Vital
            $table->decimal('lila', 5, 2)->nullable(); // cm
            $table->decimal('bb', 5, 2)->nullable();   // kg (misal 50.3)
            $table->decimal('tb', 5, 2)->nullable();   // cm
            $table->decimal('imt', 5, 2)->nullable();  // BB / (TB/100)^2
            $table->integer('td_sistolik')->nullable(); // mmHg
            $table->integer('td_diastolik')->nullable(); // mmHg
            $table->decimal('map', 5, 2)->nullable();  // (Sistolik + 2*Diastolik)/3
            $table->decimal('tfu', 5, 2)->nullable();  // cm (Tinggi Fundus Uteri)
            $table->integer('djj')->nullable();        // x/mnt (Denyut Jantung Janin)

            // Skoring Puji Rochyati
            $table->integer('skor_puji_rochyati')->default(2);
            $table->json('faktor_risiko_json')->nullable();

            // Skrining Jiwa / Kesehatan Mental
            $table->integer('skor_skrining_jiwa')->nullable(); // 1-20

            // Hasil Laboratorium
            $table->decimal('lab_hb', 4, 1)->nullable(); // g/dL
            $table->string('lab_hiv', 30)->nullable(); // Non Reaktif, Reaktif, Belum Diperiksa
            $table->string('lab_syphilis', 30)->nullable(); // Non Reaktif, Reaktif, Belum Diperiksa
            $table->string('lab_hbsag', 30)->nullable(); // Non Reaktif, Reaktif, Belum Diperiksa
            $table->string('lab_protein_urine', 20)->nullable(); // Negatif, Trace, +1, +2, +3, +4
            $table->integer('lab_gda')->nullable(); // mg/dL
            $table->string('lab_reduksi_urine', 20)->nullable(); // Negatif, +1, +2, +3, +4

            // USG & Masalah Kehamilan
            $table->boolean('is_usg')->default(false); // Pemeriksaan USG dilakukan (ya/tidak)
            $table->string('masalah_kehamilan', 100)->nullable(); // Hiper Emisis, Preeklamsi/eklamsi, Perdarahan Kehamilan, dll
            $table->string('masalah_kehamilan_lainnya', 255)->nullable(); // Kolom isian jika kasus lainnya

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encounters');
    }
};
