<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menjalankan migrasi tabel utama dompet digital (wallets, categories, transfers, transactions, logs).
     * Kompatibel penuh dengan MySQL 5.7, MariaDB, dan SQLite.
     */
    public function up(): void
    {
        // Bersihkan sisa tabel apabila migrasi sebelumnya sempat terputus di tengah jalan (DDL non-transactional pada MySQL/MariaDB)
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('wallet_activity_logs');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('transaction_categories');
        Schema::dropIfExists('wallets');
        Schema::enableForeignKeyConstraints();

        // Pastikan tabel users menggunakan engine InnoDB pada MySQL / MariaDB agar foreign key tidak gagal dengan errno 150
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) && Schema::hasTable('users')) {
            DB::statement('ALTER TABLE `users` ENGINE=InnoDB');
        }

        Schema::create('wallets', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('icon', 60)->default('wallet');
            $table->string('color', 30)->default('rose');
            $table->decimal('initial_balance', 18, 2)->default(0.00);
            $table->decimal('balance', 18, 2)->default(0.00);
            $table->boolean('is_primary')->default(false);
            $table->enum('status', ['active', 'frozen', 'deleted'])->default('active');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'idx_wallets_user_status');
        });

        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 100);
            $table->enum('type', ['income', 'expense']);
            $table->string('icon', 60)->default('wallet');
            $table->string('color', 30)->default('emerald');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'type'], 'idx_categories_user_type');
        });

        Schema::create('transfers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('transfer_code', 64)->unique();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('source_wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->string('source_wallet_name', 120);
            $table->foreignId('destination_wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->string('destination_wallet_name', 120);
            $table->decimal('amount', 18, 2);
            $table->string('description', 255)->nullable();
            $table->dateTime('transaction_date');
            $table->timestamps();

            $table->index(['user_id', 'transaction_date'], 'idx_transfers_user_date');
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('transaction_code', 64)->unique();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->string('wallet_name_snapshot', 120);
            $table->foreignId('counterpart_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->string('counterpart_wallet_name_snapshot', 120)->nullable();
            $table->string('transfer_reference', 64)->nullable();
            $table->enum('type', ['income', 'expense', 'transfer_in', 'transfer_out']);
            $table->foreignId('category_id')->nullable()->constrained('transaction_categories')->nullOnDelete();
            $table->string('category_name_snapshot', 100)->nullable();
            $table->decimal('amount', 18, 2);
            $table->decimal('balance_before', 18, 2);
            $table->decimal('balance_after', 18, 2);
            $table->string('description', 255);
            $table->text('notes')->nullable();
            $table->dateTime('transaction_date');
            $table->timestamps();

            $table->index(['user_id', 'transaction_date'], 'idx_tx_user_date');
            $table->index(['wallet_id', 'transaction_date'], 'idx_tx_wallet_date');
            $table->index(['user_id', 'type'], 'idx_tx_user_type');
            $table->index('transfer_reference', 'idx_tx_transfer_ref');
        });

        Schema::create('wallet_activity_logs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->string('wallet_name_snapshot', 120);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('activity', 60);
            $table->text('description');
            $table->timestamps();

            $table->index(['wallet_id', 'created_at'], 'idx_wallet_logs_wallet_date');
        });
    }

    /**
     * Membatalkan migrasi tabel dompet digital.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('wallet_activity_logs');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('transaction_categories');
        Schema::dropIfExists('wallets');
        Schema::enableForeignKeyConstraints();
    }
};
