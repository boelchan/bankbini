<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom pengaturan jenis tampilan riwayat transaksi pada tabel wallets.
     */
    public function up(): void
    {
        if (Schema::hasTable('wallets') && !Schema::hasColumn('wallets', 'display_mode')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->string('display_mode', 20)
                    ->default('monthly')
                    ->after('is_primary');
            });
        }
    }

    /**
     * Membatalkan penambahan kolom jenis tampilan pada tabel wallets.
     */
    public function down(): void
    {
        if (Schema::hasTable('wallets') && Schema::hasColumn('wallets', 'display_mode')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->dropColumn('display_mode');
            });
        }
    }
};
