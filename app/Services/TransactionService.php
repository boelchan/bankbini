<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CategoryTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Transfer;
use App\Models\Wallet;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    /**
     * Mencatat transaksi pemasukan baru ke kantong tertentu dengan penguncian baris database.
     *
     * @param int|array<string, mixed> $userOrData
     * @param array<string, mixed> $extraData
     */
    public function recordIncome(int|array $userOrData, array $extraData = []): Transaction
    {
        $data = is_int($userOrData)
            ? array_merge($extraData, ['user_id' => $userOrData])
            : $userOrData;

        return DB::transaction(function () use ($data): Transaction {
            $userId = (int) $data['user_id'];
            $walletId = (int) $data['wallet_id'];
            $idempotencyKey = !empty($data['idempotency_key']) ? trim((string) $data['idempotency_key']) : null;

            if ($idempotencyKey !== null) {
                $existing = Transaction::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0) {
                throw new DomainException('Nominal pemasukan harus lebih besar dari nol.');
            }

            $description = trim((string) ($data['description'] ?? ''));
            if ($description === '') {
                throw new DomainException('Keterangan transaksi pemasukan wajib diisi.');
            }

            $wallet = Wallet::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($walletId);

            $this->assertWalletCanTransact($wallet, 'menerima pemasukan');

            $category = null;
            if (!empty($data['category_id'])) {
                $category = TransactionCategory::query()
                    ->where('type', CategoryTypeEnum::INCOME->value)
                    ->findOrFail((int) $data['category_id']);
            }

            $balanceBefore = round((float) $wallet->balance, 2);
            $balanceAfter = round($balanceBefore + $amount, 2);

            $wallet->update([
                'balance' => number_format($balanceAfter, 2, '.', ''),
            ]);

            $transactionDate = !empty($data['transaction_date'])
                ? Carbon::parse($data['transaction_date'])
                : Carbon::now();

            return Transaction::create([
                'transaction_code' => $this->generateTransactionCode(),
                'idempotency_key' => $idempotencyKey,
                'user_id' => $userId,
                'wallet_id' => $wallet->id,
                'wallet_name_snapshot' => $wallet->name,
                'type' => TransactionTypeEnum::INCOME,
                'category_id' => $category?->id,
                'category_name_snapshot' => $category?->name ?? 'Lainnya',
                'amount' => number_format($amount, 2, '.', ''),
                'balance_before' => number_format($balanceBefore, 2, '.', ''),
                'balance_after' => number_format($balanceAfter, 2, '.', ''),
                'description' => $description,
                'notes' => isset($data['notes']) && trim((string) $data['notes']) !== ''
                    ? trim((string) $data['notes'])
                    : null,
                'transaction_date' => $transactionDate,
            ]);
        });
    }

    /**
     * Mencatat transaksi pengeluaran dari kantong tertentu dengan validasi kecukupan saldo dan SELECT FOR UPDATE.
     *
     * @param int|array<string, mixed> $userOrData
     * @param array<string, mixed> $extraData
     */
    public function recordExpense(int|array $userOrData, array $extraData = []): Transaction
    {
        $data = is_int($userOrData)
            ? array_merge($extraData, ['user_id' => $userOrData])
            : $userOrData;

        return DB::transaction(function () use ($data): Transaction {
            $userId = (int) $data['user_id'];
            $walletId = (int) $data['wallet_id'];
            $idempotencyKey = !empty($data['idempotency_key']) ? trim((string) $data['idempotency_key']) : null;

            if ($idempotencyKey !== null) {
                $existing = Transaction::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0) {
                throw new DomainException('Nominal pengeluaran harus lebih besar dari nol.');
            }

            $description = trim((string) ($data['description'] ?? ''));
            if ($description === '') {
                throw new DomainException('Keterangan transaksi pengeluaran wajib diisi.');
            }

            $wallet = Wallet::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($walletId);

            $this->assertWalletCanTransact($wallet, 'melakukan pengeluaran');

            $balanceBefore = round((float) $wallet->balance, 2);
            if ($balanceBefore < $amount) {
                throw new DomainException(
                    sprintf(
                        'Saldo pada %s (Rp%s) tidak mencukupi untuk pengeluaran sebesar Rp%s.',
                        $wallet->name,
                        number_format($balanceBefore, 0, ',', '.'),
                        number_format($amount, 0, ',', '.')
                    )
                );
            }

            $category = null;
            if (!empty($data['category_id'])) {
                $category = TransactionCategory::query()
                    ->where('type', CategoryTypeEnum::EXPENSE->value)
                    ->findOrFail((int) $data['category_id']);
            }

            $balanceAfter = round($balanceBefore - $amount, 2);

            $wallet->update([
                'balance' => number_format($balanceAfter, 2, '.', ''),
            ]);

            $transactionDate = !empty($data['transaction_date'])
                ? Carbon::parse($data['transaction_date'])
                : Carbon::now();

            return Transaction::create([
                'transaction_code' => $this->generateTransactionCode(),
                'idempotency_key' => $idempotencyKey,
                'user_id' => $userId,
                'wallet_id' => $wallet->id,
                'wallet_name_snapshot' => $wallet->name,
                'type' => TransactionTypeEnum::EXPENSE,
                'category_id' => $category?->id,
                'category_name_snapshot' => $category?->name ?? 'Lainnya',
                'amount' => number_format($amount, 2, '.', ''),
                'balance_before' => number_format($balanceBefore, 2, '.', ''),
                'balance_after' => number_format($balanceAfter, 2, '.', ''),
                'description' => $description,
                'notes' => isset($data['notes']) && trim((string) $data['notes']) !== ''
                    ? trim((string) $data['notes'])
                    : null,
                'transaction_date' => $transactionDate,
            ]);
        });
    }

    /**
     * Menghapus transaksi dan mengembalikan saldo kantong terkait secara atomik.
     * Jika transaksi adalah pindah uang (Pindah ke / Terima dari), maka kedua transaksi pasangan
     * pada kantong asal dan kantong tujuan akan dihapus sekaligus beserta pengembalian saldonya.
     */
    public function deleteTransaction(int $transactionId): void
    {
        DB::transaction(function () use ($transactionId): void {
            $transaction = Transaction::query()
                ->lockForUpdate()
                ->findOrFail($transactionId);

            $isTransfer = in_array(
                $transaction->type,
                [TransactionTypeEnum::TRANSFER_IN, TransactionTypeEnum::TRANSFER_OUT],
                true
            ) || !empty($transaction->transfer_reference);

            if ($isTransfer) {
                $transferRef = $transaction->transfer_reference;

                $pairedTransactions = !empty($transferRef)
                    ? Transaction::query()
                        ->where('transfer_reference', $transferRef)
                        ->lockForUpdate()
                        ->get()
                    : collect([$transaction]);

                if ($pairedTransactions->isEmpty()) {
                    $pairedTransactions = collect([$transaction]);
                }

                $walletIds = $pairedTransactions
                    ->pluck('wallet_id')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                $wallets = Wallet::query()
                    ->withTrashed()
                    ->whereIn('id', $walletIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($pairedTransactions as $tx) {
                    $wallet = $wallets->get($tx->wallet_id);

                    if ($wallet !== null) {
                        $amount = round((float) $tx->amount, 2);
                        $currentBalance = round((float) $wallet->balance, 2);

                        if ($tx->type === TransactionTypeEnum::TRANSFER_OUT) {
                            $newBalance = round($currentBalance + $amount, 2);
                        } else {
                            $newBalance = round($currentBalance - $amount, 2);

                            if ($newBalance < 0) {
                                throw new DomainException(
                                    sprintf(
                                        'Saldo pada %s (Rp%s) tidak mencukupi untuk membatalkan perpindahan uang sebesar Rp%s.',
                                        $wallet->name,
                                        number_format($currentBalance, 0, ',', '.'),
                                        number_format($amount, 0, ',', '.')
                                    )
                                );
                            }
                        }

                        $wallet->update([
                            'balance' => number_format($newBalance, 2, '.', ''),
                        ]);
                    }

                    $tx->delete();
                }

                if (!empty($transferRef)) {
                    Transfer::query()
                        ->where('transfer_code', $transferRef)
                        ->delete();
                }

                return;
            }

            $wallet = Wallet::query()
                ->withTrashed()
                ->lockForUpdate()
                ->find($transaction->wallet_id);

            if ($wallet !== null) {
                $amount = round((float) $transaction->amount, 2);
                $currentBalance = round((float) $wallet->balance, 2);

                if ($transaction->type === TransactionTypeEnum::EXPENSE) {
                    $newBalance = round($currentBalance + $amount, 2);
                } else {
                    $newBalance = round($currentBalance - $amount, 2);

                    if ($newBalance < 0) {
                        throw new DomainException(
                            sprintf(
                                'Saldo pada %s (Rp%s) tidak mencukupi untuk menghapus transaksi pemasukan sebesar Rp%s.',
                                $wallet->name,
                                number_format($currentBalance, 0, ',', '.'),
                                number_format($amount, 0, ',', '.')
                            )
                        );
                    }
                }

                $wallet->update([
                    'balance' => number_format($newBalance, 2, '.', ''),
                ]);
            }

            $transaction->delete();
        });
    }

    /**
     * Memastikan kantong dalam keadaan aktif dan tidak dibekukan ataupun dihapus.
     */
    public function assertWalletCanTransact(Wallet $wallet, string $actionLabel): void
    {
        if ($wallet->isRemoved()) {
            throw new DomainException(
                sprintf('Kantong "%s" sudah dihapus dan tidak dapat digunakan untuk %s.', $wallet->name, $actionLabel)
            );
        }

        if ($wallet->isFrozen()) {
            throw new DomainException(
                sprintf('Kantong "%s" sedang dibekukan dan tidak dapat digunakan untuk %s.', $wallet->name, $actionLabel)
            );
        }

        if (!$wallet->isActive()) {
            throw new DomainException(
                sprintf('Kantong "%s" tidak aktif untuk %s.', $wallet->name, $actionLabel)
            );
        }
    }

    /**
     * Menghasilkan kode unik transaksi.
     */
    public function generateTransactionCode(): string
    {
        return 'TRX-' . Carbon::now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
    }
}
