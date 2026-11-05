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
        Schema::create('patients', function (Blueprint $table) {
            // === IDENTITAS UTAMA ===
            $table->id();
            $table->uuid();
            $table->string('medical_record_number')->unique(); // Nomor Rekam Medis
            $table->char('nik', 16)->unique()->nullable();         // NIK (16 digit)
            $table->string('ihs_number', 20)->unique()->nullable(); // Nomor IHS
            $table->string('passport_kitas', 30)->nullable();      // Khusus WNA

            // === DATA PERSONAL ===
            $table->string('full_name', 100);                      // Nama Lengkap
            $table->string('family_name', 100)->nullable();        // Nama Ibu Kandung
            $table->date('birth_date')->nullable();                            // Tanggal Lahir
            $table->string('birth_place', 100)->nullable();        // Tempat Lahir
            $table->string('gender', 1);                         // L, P

            // === KONTAK ===
            $table->string('phone', 20)->nullable();               // Nomor Telepon
            $table->string('mobile_phone', 20)->nullable();        // Nomor HP
            $table->string('email', 100)->nullable();

            // === ALAMAT ===
            $table->text('address')->nullable();                   // Alamat lengkap
            $table->bigInteger('province_code')->nullable();
            $table->bigInteger('regency_code')->nullable();
            $table->bigInteger('district_code')->nullable();
            $table->bigInteger('village_code')->nullable();
            $table->string('postal_code', 10)->nullable();

            $table->foreign('province_code')->references('code')->on('indonesia_regions')->onDelete('set null');
            $table->foreign('regency_code')->references('code')->on('indonesia_regions')->onDelete('set null');
            $table->foreign('district_code')->references('code')->on('indonesia_regions')->onDelete('set null');
            $table->foreign('village_code')->references('code')->on('indonesia_regions')->onDelete('set null');

            // === DATA KEPENDUDUKAN ===
            $table->string('nationality', 50)->default('WNI');     // WNI / WNA
            $table->string('religion', 30)->nullable();            // Agama
            $table->string('education', 30)->nullable();           // Pendidikan terakhir
            $table->string('occupation', 50)->nullable();          // Pekerjaan
            $table->string('marital_status', 20)->nullable();      // Status perkawinan

            // === CARA PEMBAYARAN ===
            $table->string('insurance_number', 30)->nullable();    // No. BPJS / Asuransi
            $table->string('insurance_name', 50)->nullable();      // Nama asuransi (jika lainnya)

            // === PENANGGUNG JAWAB / KELUARGA ===
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_relation', 50)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();

            // === SISTEM ===
            $table->boolean('is_active')->default(true);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();

            // INDEX
            $table->index('nik');
            $table->index('medical_record_number');
            $table->index('full_name');
            $table->index('birth_date');
            $table->index('province_code');
            $table->index('regency_code');
            $table->index('district_code');
            $table->index('village_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
