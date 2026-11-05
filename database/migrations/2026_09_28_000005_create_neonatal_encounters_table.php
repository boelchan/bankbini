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
        Schema::create('neonatal_encounters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('pregnancy_id')->nullable()->constrained('pregnancies')->nullOnDelete();
            $table->date('encounter_date'); // Tanggal Kunjungan
            $table->string('kn_type', 20)->default('KN'); // 'KN' (Kunjungan Neonatal) atau 'KOMPLIKASI' (Skrining Kasus / Komplikasi)

            // Pemeriksaan Antropometri & Vital Bayi
            $table->decimal('bb', 5, 2)->nullable(); // Berat Badan (kg)
            $table->decimal('tb', 5, 1)->nullable(); // Panjang Badan (cm)
            $table->integer('rr')->nullable(); // Pernapasan / Respiratory Rate (x/menit)
            $table->decimal('suhu', 4, 1)->nullable(); // Suhu tubuh (°C, terutama KN2 & KN3)
            $table->string('warna_kulit', 50)->nullable(); // Kemerahan, Ikterus/Kuning, Sianosis, Pucat
            $table->string('tali_pusat', 50)->nullable(); // Bersih & Kering, Basah, Berbau, Lepas

            // Skrining & Pelayanan Khusus KN1
            $table->string('salep_mata', 10)->nullable(); // ya / tidak
            $table->string('vit_k', 10)->nullable(); // ya / tidak
            $table->string('imd', 10)->nullable(); // ya / tidak
            $table->string('hb0', 10)->nullable(); // ya / tidak
            $table->string('shk', 10)->nullable(); // ya / tidak
            $table->string('hbig', 10)->nullable(); // ya / tidak
            $table->string('pjb', 20)->nullable(); // Normal / Tidak (Skrining Penyakit Jantung Bawaan)

            // Kasus / Masalah Neonatal Yang Ditemukan
            $table->string('trauma_lahir', 10)->nullable(); // ya / tidak
            $table->string('asfiksia', 20)->nullable(); // normal / ringan / sedang / berat
            $table->string('bblr', 10)->nullable(); // ya / tidak (BBLR < 2500 gr)
            $table->string('infeksi', 10)->nullable(); // ya / tidak
            $table->string('tn', 10)->nullable(); // ya / tidak (Tetanus Neonatorum)
            $table->string('kelainan_bawaan', 10)->nullable(); // ya / tidak
            $table->string('hk_positif', 20)->nullable(); // positif / negatif / tidak diperiksa (Hipotiroid Kongenital SHK +/-)
            $table->string('covid19', 10)->nullable(); // ya / tidak
            $table->string('masalah_lainnya', 255)->nullable(); // Masalah lain (kolom isian)
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
        Schema::dropIfExists('neonatal_encounters');
    }
};
