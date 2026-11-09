<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan batas anggaran pada kategori transaksi dan membuat tabel kata kunci rekap.
     */
    public function up(): void
    {
        if (Schema::hasTable('transaction_categories') && !Schema::hasColumn('transaction_categories', 'budget_limit')) {
            Schema::table('transaction_categories', function (Blueprint $table) {
                $table->decimal('budget_limit', 18, 2)->default(0.00)->after('is_default');
            });
        }

        if (!Schema::hasTable('recap_keywords')) {
            Schema::create('recap_keywords', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('keyword', 100);
                $table->decimal('budget_limit', 18, 2)->default(0.00);
                $table->string('color', 30)->default('rose');
                $table->timestamps();

                $table->index('keyword', 'idx_recap_keywords_keyword');
            });
        }
    }

    /**
     * Membatalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('recap_keywords');

        if (Schema::hasTable('transaction_categories') && Schema::hasColumn('transaction_categories', 'budget_limit')) {
            Schema::table('transaction_categories', function (Blueprint $table) {
                $table->dropColumn('budget_limit');
            });
        }
    }
};
