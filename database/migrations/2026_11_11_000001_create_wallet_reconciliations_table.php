<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel catatan saldo hitungan manual untuk rekonsiliasi kantong.
     */
    public function up(): void
    {
        if (Schema::hasTable('wallet_reconciliations')) {
            return;
        }

        Schema::create('wallet_reconciliations', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('label', 100);
            $table->decimal('amount', 18, 2)->default(0.00);
            $table->timestamps();

            $table->index('wallet_id', 'idx_wallet_reconciliations_wallet');
        });
    }

    /**
     * Membatalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_reconciliations');
    }
};
