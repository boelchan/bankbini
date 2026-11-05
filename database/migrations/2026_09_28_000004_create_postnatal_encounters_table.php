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
        Schema::create('postnatal_encounters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('pregnancy_id')->nullable()->constrained('pregnancies')->nullOnDelete();
            $table->date('encounter_date'); // Tanggal Kunjungan
            $table->string('kf_type', 10)->nullable(); // KF1 (0-2 hari), KF2 (3-7 hari), KF3 (8-28 hari), KF4 (29-42 hari)
            $table->integer('hari_ke')->nullable(); // Usia nifas (hari setelah persalinan)

            // Tanda Vital & Pemeriksaan Fisik Nifas
            $table->integer('td_sistolik')->nullable(); // mmHg (Sistol)
            $table->integer('td_diastolik')->nullable(); // mmHg (Diastol)
            $table->decimal('suhu', 4, 1)->nullable(); // Celcius (Suhu)
            $table->integer('nadi')->nullable(); // bpm (Nadi)
            $table->string('tfu', 50)->nullable(); // Tinggi Fundus Uteri (misal: "2 jari bawah pusat")
            $table->string('lochea', 50)->nullable(); // Rubra, Sanguinolenta, Serosa, Alba
            $table->string('jalan_lahir', 100)->nullable(); // Baik / Kering, Bengkak, Kemerahan, Jahitan Utuh
            $table->string('asi', 50)->nullable(); // Lancar, Belum Lancar, Sedikit, Tidak Keluar
            $table->string('bab', 10)->default('ya'); // ya / tidak
            $table->string('bak', 10)->default('ya'); // ya / tidak

            // Masalah Nifas
            $table->string('masalah_nifas', 100)->nullable(); // Preeklamsi/eklamsi, Perdarahan Nifas, Infeksi, TB, Malaria, Jantung, DM, Positif Covid19, Obesitas, Lainnya
            $table->string('masalah_nifas_lainnya', 255)->nullable(); // Jika memilih Lainnya
            $table->text('catatan')->nullable();

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
        Schema::dropIfExists('postnatal_encounters');
    }
};
