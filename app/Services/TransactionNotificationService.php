<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserStatusEnum;
use App\Models\FcmToken;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Menyiapkan dan mengirim notifikasi transaksi kantong ke akun lain.
 */
class TransactionNotificationService
{
    /**
     * Membuat service notifikasi transaksi.
     */
    public function __construct(private readonly FcmService $fcmService) {}

    /**
     * Mengirim notifikasi transaksi kepada semua akun aktif selain pengirim.
     */
    public function notify(Transaction $transaction): void
    {
        if (! $this->fcmService->isReady()) {
            return;
        }

        $tokens = FcmToken::query()
            ->whereHas('user', fn (Builder $query): Builder => $query->where('status', UserStatusEnum::ACTIVE->value))
            ->where('user_id', '!=', $transaction->user_id)
            ->pluck('token')
            ->all();

        if ($tokens === []) {
            return;
        }

        $actor = $transaction->user?->name ?? 'Seseorang';
        $typeLabel = strtolower($transaction->type->label());
        $amount = $transaction->sign().number_format((float) $transaction->amount, 0, ',', '.');
        $description = trim((string) $transaction->description);

        $body = sprintf(
            '%s mencatat %s %s di %s',
            $actor,
            $typeLabel,
            $amount,
            $transaction->displayWalletName()
        );

        if ($description !== '') {
            $body .= ' - '.Str::limit($description, 50);
        }

        $this->fcmService->send(
            $tokens,
            $transaction->type->label().' Baru',
            $body,
            route('pockets.show', $transaction->wallet_id)
        );
    }
}
