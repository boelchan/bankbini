<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\Wallet;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransferService
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    /**
     * Memindahkan saldo dari kantong pengirim ke kantong penerima secara atomik menggunakan SELECT FOR UPDATE.
     * Mencatat 1 record Transfer dan 2 record Transaction (transfer_out & transfer_in) dengan transfer_reference yang sama.
     *
     * @param int|array<string, mixed> $userOrData
     * @param array<string, mixed> $extraData
     */
    public function executeTransfer(int|array $userOrData, array $extraData = []): Transfer
    {
        $data = is_int($userOrData)
            ? array_merge($extraData, ['user_id' => $userOrData])
            : $userOrData;

        return DB::transaction(function () use ($data): Transfer {
            $userId = (int) $data['user_id'];
            $sourceWalletId = (int) $data['source_wallet_id'];
            $destinationWalletId = (int) $data['destination_wallet_id'];
            $idempotencyKey = !empty($data['idempotency_key']) ? trim((string) $data['idempotency_key']) : null;

            if ($idempotencyKey !== null) {
                $existing = Transfer::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            if ($sourceWalletId === $destinationWalletId) {
                throw new DomainException('Kantong pengirim dan kantong penerima harus berbeda.');
            }

            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0) {
                throw new DomainException('Nominal transfer harus lebih besar dari nol.');
            }

            // Kunci kedua kantong berdasarkan urutan ID terkecil untuk mencegah deadlock pada transaksi konkuren
            $orderedIds = [$sourceWalletId, $destinationWalletId];
            sort($orderedIds);

            $lockedWallets = Wallet::query()
                ->withTrashed()
                ->where('user_id', $userId)
                ->whereIn('id', $orderedIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            /** @var Wallet|null $sourceWallet */
            $sourceWallet = $lockedWallets->get($sourceWalletId);
            /** @var Wallet|null $destinationWallet */
            $destinationWallet = $lockedWallets->get($destinationWalletId);

            if ($sourceWallet === null || $destinationWallet === null) {
                throw new DomainException('Kantong pengirim atau kantong penerima tidak ditemukan.');
            }

            $this->transactionService->assertWalletCanTransact($sourceWallet, 'menjadi sumber transfer');
            $this->transactionService->assertWalletCanTransact($destinationWallet, 'menjadi tujuan transfer');

            $sourceBefore = round((float) $sourceWallet->balance, 2);
            if ($sourceBefore < $amount) {
                throw new DomainException(
                    sprintf(
                        'Saldo pengirim pada %s (Rp%s) tidak mencukupi untuk transfer sebesar Rp%s.',
                        $sourceWallet->name,
                        number_format($sourceBefore, 0, ',', '.'),
                        number_format($amount, 0, ',', '.')
                    )
                );
            }

            $destinationBefore = round((float) $destinationWallet->balance, 2);

            $sourceAfter = round($sourceBefore - $amount, 2);
            $destinationAfter = round($destinationBefore + $amount, 2);

            $sourceWallet->update([
                'balance' => number_format($sourceAfter, 2, '.', ''),
            ]);

            $destinationWallet->update([
                'balance' => number_format($destinationAfter, 2, '.', ''),
            ]);

            $transferCode = $this->generateTransferCode();
            $transactionDate = !empty($data['transaction_date'])
                ? Carbon::parse($data['transaction_date'])
                : Carbon::now();

            $userNote = isset($data['description']) && trim((string) $data['description']) !== ''
                ? trim((string) $data['description'])
                : null;

            $transfer = Transfer::create([
                'transfer_code' => $transferCode,
                'idempotency_key' => $idempotencyKey,
                'user_id' => $userId,
                'source_wallet_id' => $sourceWallet->id,
                'source_wallet_name' => $sourceWallet->name,
                'destination_wallet_id' => $destinationWallet->id,
                'destination_wallet_name' => $destinationWallet->name,
                'amount' => number_format($amount, 2, '.', ''),
                'description' => $userNote,
                'transaction_date' => $transactionDate,
            ]);

            $outDescription = sprintf('Transfer keluar ke %s', $destinationWallet->name);
            $inDescription = sprintf('Transfer masuk dari %s', $sourceWallet->name);

            Transaction::create([
                'transaction_code' => $this->transactionService->generateTransactionCode(),
                'idempotency_key' => $idempotencyKey ? $idempotencyKey . '-out' : null,
                'user_id' => $userId,
                'wallet_id' => $sourceWallet->id,
                'wallet_name_snapshot' => $sourceWallet->name,
                'counterpart_wallet_id' => $destinationWallet->id,
                'counterpart_wallet_name_snapshot' => $destinationWallet->name,
                'transfer_reference' => $transferCode,
                'type' => TransactionTypeEnum::TRANSFER_OUT,
                'category_id' => null,
                'category_name_snapshot' => 'Transfer Antar Kantong',
                'amount' => number_format($amount, 2, '.', ''),
                'balance_before' => number_format($sourceBefore, 2, '.', ''),
                'balance_after' => number_format($sourceAfter, 2, '.', ''),
                'description' => $outDescription,
                'notes' => $userNote,
                'transaction_date' => $transactionDate,
            ]);

            Transaction::create([
                'transaction_code' => $this->transactionService->generateTransactionCode(),
                'idempotency_key' => $idempotencyKey ? $idempotencyKey . '-in' : null,
                'user_id' => $userId,
                'wallet_id' => $destinationWallet->id,
                'wallet_name_snapshot' => $destinationWallet->name,
                'counterpart_wallet_id' => $sourceWallet->id,
                'counterpart_wallet_name_snapshot' => $sourceWallet->name,
                'transfer_reference' => $transferCode,
                'type' => TransactionTypeEnum::TRANSFER_IN,
                'category_id' => null,
                'category_name_snapshot' => 'Transfer Antar Kantong',
                'amount' => number_format($amount, 2, '.', ''),
                'balance_before' => number_format($destinationBefore, 2, '.', ''),
                'balance_after' => number_format($destinationAfter, 2, '.', ''),
                'description' => $inDescription,
                'notes' => $userNote,
                'transaction_date' => $transactionDate,
            ]);

            return $transfer->load('transactions');
        });
    }

    /**
     * Menghasilkan kode unik referensi transfer.
     */
    private function generateTransferCode(): string
    {
        return 'TRF-' . Carbon::now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
    }
}
