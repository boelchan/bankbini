<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel token FCM untuk perangkat yang diizinkan menerima notifikasi.
     */
    public function up(): void
    {
        if (Schema::hasTable('fcm_tokens')) {
            return;
        }

        Schema::create('fcm_tokens', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 512)->unique('uq_fcm_tokens_token');
            $table->string('platform', 20);
            $table->string('label', 100)->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_fcm_tokens_user');
        });
    }

    /**
     * Membatalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('fcm_tokens');
    }
};
