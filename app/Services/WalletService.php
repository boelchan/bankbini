<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CategoryTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WalletColorEnum;
use App\Enums\WalletIconEnum;
use App\Enums\WalletStatusEnum;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Wallet;
use App\Models\WalletActivityLog;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    /**
     * Membuat kantong keuangan (Pocket) baru beserta transaksi saldo awal jika diisi.
     *
     * @param int|array<string, mixed> $userOrData
     * @param array<string, mixed> $extraData
     */
    public function createWallet(int|array $userOrData, array $extraData = []): Wallet
    {
        $data = is_int($userOrData)
            ? array_merge($extraData, ['user_id' => $userOrData])
            : $userOrData;

        return DB::transaction(function () use ($data): Wallet {
            $userId = (int) $data['user_id'];
            $name = trim((string) $data['name']);

            if ($name === '') {
                throw new DomainException('Nama kantong wajib diisi.');
            }

            $rawInitial = $data['initial_balance'] ?? 0;
            $initialAmount = round((float) $rawInitial, 2);

            if ($initialAmount < 0) {
                throw new DomainException('Saldo awal kantong tidak boleh bernilai negatif.');
            }

            $icon = isset($data['icon']) && in_array($data['icon'], WalletIconEnum::values(), true)
                ? $data['icon']
                : WalletIconEnum::WALLET->value;

            $color = isset($data['color']) && in_array($data['color'], WalletColorEnum::values(), true)
                ? $data['color']
                : WalletColorEnum::ROSE->value;

            $hasPrimary = Wallet::query()
                ->visible()
                ->where('is_primary', true)
                ->exists();

            $wallet = Wallet::create([
                'user_id' => $userId,
                'name' => $name,
                'description' => isset($data['description']) ? trim((string) $data['description']) : null,
                'icon' => $icon,
                'color' => $color,
                'initial_balance' => number_format($initialAmount, 2, '.', ''),
                'balance' => number_format($initialAmount, 2, '.', ''),
                'is_primary' => (bool) ($data['is_primary'] ?? !$hasPrimary),
                'status' => WalletStatusEnum::ACTIVE,
            ]);

            $this->logActivity(
                $wallet,
                'created',
                sprintf(
                    'Kantong "%s" dibuat dengan saldo awal Rp%s.',
                    $wallet->name,
                    number_format($initialAmount, 0, ',', '.')
                )
            );

            if ($initialAmount > 0) {
                $defaultCategory = TransactionCategory::query()
                    ->availableFor($userId)
                    ->where('type', CategoryTypeEnum::INCOME->value)
                    ->orderByRaw("CASE WHEN name = 'Lainnya' THEN 0 ELSE 1 END")
                    ->first();

                Transaction::create([
                    'transaction_code' => $this->generateTransactionCode(),
                    'idempotency_key' => 'init-wallet-' . $wallet->id,
                    'user_id' => $userId,
                    'wallet_id' => $wallet->id,
                    'wallet_name_snapshot' => $wallet->name,
                    'type' => TransactionTypeEnum::INCOME,
                    'category_id' => $defaultCategory?->id,
                    'category_name_snapshot' => $defaultCategory?->name ?? 'Saldo Awal',
                    'amount' => number_format($initialAmount, 2, '.', ''),
                    'balance_before' => '0.00',
                    'balance_after' => number_format($initialAmount, 2, '.', ''),
                    'description' => 'Saldo awal pembukaan ' . $wallet->name,
                    'notes' => 'Setoran awal saat pembuatan kantong baru.',
                    'transaction_date' => Carbon::now(),
                ]);
            }

            return $wallet->fresh();
        });
    }

    /**
     * Mengambil kantong utama keluarga. Jika belum ada yang ditandai utama,
     * otomatis menetapkan kantong pertama atau membuat kantong default.
     */
    public function getPrimaryWallet(?int $userId = null): Wallet
    {
        $primary = Wallet::query()
            ->visible()
            ->where('is_primary', true)
            ->first();

        if ($primary !== null) {
            return $primary;
        }

        $fallback = Wallet::query()
            ->visible()
            ->oldest()
            ->first();

        if ($fallback !== null) {
            $fallback->update(['is_primary' => true]);
            return $fallback->fresh();
        }

        // Jika belum ada kantong sama sekali di sistem, buatkan 1 kantong utama default
        return $this->createWallet([
            'user_id' => $userId ?? 1,
            'name' => 'Kantong Utama',
            'description' => 'Kantong utama operasional dan kebutuhan harian keluarga',
            'icon' => WalletIconEnum::WALLET->value,
            'color' => WalletColorEnum::EMERALD->value,
            'initial_balance' => 0,
            'is_primary' => true,
        ]);
    }

    /**
     * Menetapkan kantong tertentu sebagai kantong utama dan mereset status utama kantong lainnya.
     */
    public function setPrimaryWallet(int|Wallet $wallet): Wallet
    {
        $walletId = $wallet instanceof Wallet ? (int) $wallet->id : (int) $wallet;

        return DB::transaction(function () use ($walletId): Wallet {
            $target = Wallet::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($walletId);

            if ($target->isRemoved()) {
                throw new DomainException('Kantong yang sudah dihapus tidak dapat dijadikan kantong utama.');
            }

            Wallet::query()
                ->where('id', '!=', $target->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            $target->update(['is_primary' => true]);

            $this->logActivity(
                $target,
                'set_primary',
                sprintf('Kantong "%s" ditetapkan sebagai kantong utama keluarga.', $target->name)
            );

            return $target->fresh();
        });
    }

    /**
     * Memperbarui informasi nama, ikon, warna, dan deskripsi kantong tanpa mengubah histori transaksi lama.
     *
     * @param int|Wallet $userOrWallet
     * @param int|array<string, mixed> $walletIdOrData
     * @param array<string, mixed> $extraData
     */
    public function updateWallet(int|Wallet $userOrWallet, int|array $walletIdOrData, array $extraData = []): Wallet
    {
        if ($userOrWallet instanceof Wallet) {
            $userId = (int) $userOrWallet->user_id;
            $walletId = (int) $userOrWallet->id;
            $data = is_array($walletIdOrData) ? $walletIdOrData : $extraData;
        } else {
            $userId = $userOrWallet;
            $walletId = (int) $walletIdOrData;
            $data = $extraData;
        }

        return DB::transaction(function () use ($walletId, $data): Wallet {
            $wallet = Wallet::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($walletId);

            if ($wallet->isRemoved()) {
                throw new DomainException('Kantong yang sudah dihapus tidak dapat diubah kembali.');
            }

            $oldName = $wallet->name;
            $newName = trim((string) $data['name']);

            if ($newName === '') {
                throw new DomainException('Nama kantong wajib diisi.');
            }

            $icon = isset($data['icon']) && in_array($data['icon'], WalletIconEnum::values(), true)
                ? $data['icon']
                : $wallet->icon;

            $color = isset($data['color']) && in_array($data['color'], WalletColorEnum::values(), true)
                ? $data['color']
                : $wallet->color;

            $wallet->update([
                'name' => $newName,
                'description' => isset($data['description']) ? trim((string) $data['description']) : null,
                'icon' => $icon,
                'color' => $color,
            ]);

            $this->logActivity(
                $wallet,
                'updated',
                $oldName !== $newName
                    ? sprintf('Informasi kantong diperbarui dari "%s" menjadi "%s".', $oldName, $newName)
                    : sprintf('Informasi tampilan dan deskripsi kantong "%s" diperbarui.', $newName)
            );

            return $wallet->fresh();
        });
    }

    /**
     * Membekukan kantong sehingga tidak dapat melakukan atau menerima transaksi.
     */
    public function freezeWallet(int|Wallet $userOrWallet, ?int $walletId = null): Wallet
    {
        $walletId = $userOrWallet instanceof Wallet ? (int) $userOrWallet->id : (int) $walletId;

        return DB::transaction(function () use ($walletId): Wallet {
            $wallet = Wallet::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($walletId);

            if ($wallet->isRemoved()) {
                throw new DomainException('Kantong yang sudah dihapus tidak dapat dibekukan.');
            }

            if ($wallet->isFrozen()) {
                throw new DomainException('Kantong ini sudah dalam status dibekukan.');
            }

            $wallet->update([
                'status' => WalletStatusEnum::FROZEN,
            ]);

            $this->logActivity(
                $wallet,
                'frozen',
                sprintf('Kantong "%s" dibekukan sementara oleh pengguna.', $wallet->name)
            );

            return $wallet->fresh();
        });
    }

    /**
     * Mengaktifkan kembali kantong yang sebelumnya dibekukan.
     */
    public function unfreezeWallet(int|Wallet $userOrWallet, ?int $walletId = null): Wallet
    {
        $walletId = $userOrWallet instanceof Wallet ? (int) $userOrWallet->id : (int) $walletId;

        return DB::transaction(function () use ($walletId): Wallet {
            $wallet = Wallet::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($walletId);

            if ($wallet->isRemoved()) {
                throw new DomainException('Kantong yang sudah dihapus tidak dapat diaktifkan kembali.');
            }

            if ($wallet->isActive()) {
                throw new DomainException('Kantong ini sudah berstatus aktif.');
            }

            $wallet->update([
                'status' => WalletStatusEnum::ACTIVE,
            ]);

            $this->logActivity(
                $wallet,
                'activated',
                sprintf('Kantong "%s" diaktifkan kembali dan siap digunakan bertransaksi.', $wallet->name)
            );

            return $wallet->fresh();
        });
    }

    /**
     * Menghapus (menutup) kantong secara soft-delete dengan memastikan saldo menjadi nol.
     * Jika masih ada saldo dan kantong tujuan dipilih, saldo dipindahkan secara otomatis terlebih dahulu.
     */
    public function deleteWallet(
        int $userId,
        int $walletId,
        ?int $destinationWalletId = null,
        ?string $idempotencyKey = null
    ): Wallet {
        return DB::transaction(function () use ($userId, $walletId, $destinationWalletId, $idempotencyKey): Wallet {
            $wallet = Wallet::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($walletId);

            if ($wallet->isRemoved()) {
                throw new DomainException('Kantong ini sudah dihapus sebelumnya.');
            }

            $currentBalance = round((float) $wallet->balance, 2);

            if ($currentBalance > 0) {
                if ($destinationWalletId === null) {
                    throw new DomainException(
                        sprintf(
                            'Kantong "%s" masih memiliki saldo Rp%s. Silakan pindahkan saldo ke kantong aktif lain sebelum menghapus.',
                            $wallet->name,
                            number_format($currentBalance, 0, ',', '.')
                        )
                    );
                }

                if ($destinationWalletId === $wallet->id) {
                    throw new DomainException('Kantong tujuan pemindahan saldo harus berbeda dari kantong yang akan dihapus.');
                }

                // Jika kantong sedang dibekukan namun akan ditutup dengan pemindahan saldo, aktifkan sesaat di dalam transaksi
                if ($wallet->status === WalletStatusEnum::FROZEN) {
                    $wallet->update(['status' => WalletStatusEnum::ACTIVE]);
                }

                app(TransferService::class)->executeTransfer([
                    'user_id' => $userId,
                    'source_wallet_id' => $wallet->id,
                    'destination_wallet_id' => $destinationWalletId,
                    'amount' => $currentBalance,
                    'description' => sprintf('Penutupan dan pemindahan sisa saldo dari %s', $wallet->name),
                    'transaction_date' => Carbon::now(),
                    'idempotency_key' => $idempotencyKey ? $idempotencyKey . '-close-trf' : null,
                ]);

                $wallet->refresh();
            }

            if (round((float) $wallet->balance, 2) !== 0.00) {
                throw new DomainException('Penghapusan kantong gagal karena saldo akhir kantong belum bernilai nol.');
            }

            $wallet->update([
                'status' => WalletStatusEnum::DELETED,
                'is_primary' => false,
            ]);

            $this->logActivity(
                $wallet,
                'deleted',
                sprintf('Kantong "%s" ditutup dan dihapus dengan saldo akhir Rp0.', $wallet->name)
            );

            $wallet->delete();

            return $wallet->fresh();
        });
    }

    /**
     * Menghitung total saldo seluruh kantong aktif dan kantong yang dibekukan bersama.
     */
    public function getTotalBalance(?int $userId = null): float
    {
        $sum = Wallet::query()
            ->visible()
            ->sum('balance');

        return round((float) $sum, 2);
    }

    /**
     * Mencatat aktivitas pengelolaan kantong ke dalam tabel wallet_activity_logs.
     */
    public function logActivity(Wallet $wallet, string $activity, string $description): WalletActivityLog
    {
        return WalletActivityLog::create([
            'wallet_id' => $wallet->id,
            'wallet_name_snapshot' => $wallet->name,
            'user_id' => $wallet->user_id,
            'activity' => $activity,
            'description' => $description,
        ]);
    }

    /**
     * Menghasilkan kode transaksi unik.
     */
    private function generateTransactionCode(): string
    {
        return 'TRX-' . Carbon::now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
    }
}
