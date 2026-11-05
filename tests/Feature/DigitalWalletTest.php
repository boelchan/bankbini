<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CategoryTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WalletStatusEnum;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FinancialReportService;
use App\Services\TransactionService;
use App\Services\TransferService;
use App\Services\WalletService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class DigitalWalletTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WalletService $walletService;
    private TransactionService $transactionService;
    private TransferService $transferService;
    private FinancialReportService $reportService;
    private TransactionCategory $incomeCategory;
    private TransactionCategory $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Istri Tercinta',
            'email' => 'istri@bankbini.test',
            'status' => 'active',
        ]);

        $this->walletService = app(WalletService::class);
        $this->transactionService = app(TransactionService::class);
        $this->transferService = app(TransferService::class);
        $this->reportService = app(FinancialReportService::class);

        $this->incomeCategory = TransactionCategory::create([
            'name' => 'Gaji',
            'type' => CategoryTypeEnum::INCOME->value,
            'icon' => 'briefcase',
            'color' => 'emerald',
            'is_default' => true,
        ]);

        $this->expenseCategory = TransactionCategory::create([
            'name' => 'Makanan',
            'type' => CategoryTypeEnum::EXPENSE->value,
            'icon' => 'tools-kitchen-2',
            'color' => 'orange',
            'is_default' => true,
        ]);
    }

    public function test_income_transaction_increases_wallet_and_total_balance_and_records_history(): void
    {
        $wallet = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Utama',
            'initial_balance' => 1000000,
        ]);

        $tx = $this->transactionService->recordIncome([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $this->incomeCategory->id,
            'amount' => 2500000,
            'description' => 'Gaji Bulanan',
            'notes' => 'Setoran bulan ini',
            'idempotency_key' => 'inc-test-001',
        ]);

        $wallet->refresh();

        $this->assertSame(3500000.00, (float) $wallet->balance);
        $this->assertSame(3500000.00, $this->walletService->getTotalBalance($this->user->id));
        $this->assertSame(1000000.00, (float) $tx->balance_before);
        $this->assertSame(3500000.00, (float) $tx->balance_after);
        $this->assertSame(TransactionTypeEnum::INCOME, $tx->type);

        // Pengujian idempotency key mencegah transaksi ganda
        $duplicateTx = $this->transactionService->recordIncome([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $this->incomeCategory->id,
            'amount' => 2500000,
            'description' => 'Gaji Bulanan',
            'idempotency_key' => 'inc-test-001',
        ]);

        $this->assertSame($tx->id, $duplicateTx->id);
        $this->assertSame(3500000.00, (float) $wallet->fresh()->balance);
    }

    public function test_expense_transaction_decreases_wallet_and_total_balance(): void
    {
        $wallet = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Kebutuhan',
            'initial_balance' => 3000000,
        ]);

        $tx = $this->transactionService->recordExpense([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 450000,
            'description' => 'Belanja sayur dan lauk',
        ]);

        $wallet->refresh();

        $this->assertSame(2550000.00, (float) $wallet->balance);
        $this->assertSame(2550000.00, $this->walletService->getTotalBalance($this->user->id));
        $this->assertSame(3000000.00, (float) $tx->balance_before);
        $this->assertSame(2550000.00, (float) $tx->balance_after);
        $this->assertSame(TransactionTypeEnum::EXPENSE, $tx->type);
    }

    public function test_insufficient_balance_prevents_negative_balance_on_expense_and_transfer(): void
    {
        $walletA = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Belanja',
            'initial_balance' => 500000,
        ]);

        $walletB = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Tabungan',
            'initial_balance' => 1000000,
        ]);

        try {
            $this->transactionService->recordExpense([
                'user_id' => $this->user->id,
                'wallet_id' => $walletA->id,
                'category_id' => $this->expenseCategory->id,
                'amount' => 600000,
                'description' => 'Belanja melebihi saldo',
            ]);
            $this->fail('Pengeluaran melebihi saldo seharusnya gagal.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('tidak mencukupi', $e->getMessage());
        }

        try {
            $this->transferService->executeTransfer([
                'user_id' => $this->user->id,
                'source_wallet_id' => $walletA->id,
                'destination_wallet_id' => $walletB->id,
                'amount' => 750000,
                'description' => 'Transfer melebihi saldo',
            ]);
            $this->fail('Transfer melebihi saldo seharusnya gagal.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('tidak mencukupi', $e->getMessage());
        }

        $this->assertSame(500000.00, (float) $walletA->fresh()->balance);
        $this->assertSame(1000000.00, (float) $walletB->fresh()->balance);
    }

    public function test_transfer_between_wallets_is_atomic_and_keeps_total_balance_constant(): void
    {
        $utama = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Utama',
            'initial_balance' => 5000000,
        ]);

        $tabungan = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Tabungan',
            'initial_balance' => 4000000,
        ]);

        $totalBefore = $this->walletService->getTotalBalance($this->user->id);

        $transfer = $this->transferService->executeTransfer([
            'user_id' => $this->user->id,
            'source_wallet_id' => $utama->id,
            'destination_wallet_id' => $tabungan->id,
            'amount' => 1000000,
            'description' => 'Tabungan rutin bulanan',
        ]);

        $this->assertSame(4000000.00, (float) $utama->fresh()->balance);
        $this->assertSame(5000000.00, (float) $tabungan->fresh()->balance);
        $this->assertSame($totalBefore, $this->walletService->getTotalBalance($this->user->id));

        $pairedTransactions = Transaction::where('transfer_reference', $transfer->transfer_code)->get();
        $this->assertCount(2, $pairedTransactions);

        $outTx = $pairedTransactions->firstWhere('type', TransactionTypeEnum::TRANSFER_OUT);
        $inTx = $pairedTransactions->firstWhere('type', TransactionTypeEnum::TRANSFER_IN);

        $this->assertNotNull($outTx);
        $this->assertNotNull($inTx);
        $this->assertSame('Transfer keluar ke Kantong Tabungan', $outTx->description);
        $this->assertSame('Transfer masuk dari Kantong Utama', $inTx->description);
    }

    public function test_failed_transfer_to_same_wallet_or_non_positive_amount_is_rejected(): void
    {
        $wallet = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Utama',
            'initial_balance' => 2000000,
        ]);

        $other = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Kedua',
            'initial_balance' => 1000000,
        ]);

        try {
            $this->transferService->executeTransfer([
                'user_id' => $this->user->id,
                'source_wallet_id' => $wallet->id,
                'destination_wallet_id' => $wallet->id,
                'amount' => 100000,
            ]);
            $this->fail('Transfer ke kantong yang sama seharusnya ditolak.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('harus berbeda', $e->getMessage());
        }

        try {
            $this->transferService->executeTransfer([
                'user_id' => $this->user->id,
                'source_wallet_id' => $wallet->id,
                'destination_wallet_id' => $other->id,
                'amount' => 0,
            ]);
            $this->fail('Transfer dengan nominal 0 seharusnya ditolak.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('lebih besar dari nol', $e->getMessage());
        }
    }

    public function test_frozen_wallet_blocks_all_transactions_but_counts_in_total_balance(): void
    {
        $utama = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Utama',
            'initial_balance' => 5000000,
        ]);

        $tabungan = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Tabungan',
            'initial_balance' => 3000000,
        ]);

        $this->walletService->freezeWallet($this->user->id, $tabungan->id);
        $this->assertSame(WalletStatusEnum::FROZEN, $tabungan->fresh()->status);

        // Total saldo tetap menghitung kantong yang dibekukan (5.000.000 + 3.000.000 = 8.000.000)
        $this->assertSame(8000000.00, $this->walletService->getTotalBalance($this->user->id));

        // 1. Tidak dapat menerima pemasukan
        try {
            $this->transactionService->recordIncome([
                'user_id' => $this->user->id,
                'wallet_id' => $tabungan->id,
                'amount' => 100000,
                'description' => 'Tes masuk ke kantong beku',
            ]);
            $this->fail('Kantong beku tidak boleh menerima pemasukan.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('dibekukan', $e->getMessage());
        }

        // 2. Tidak dapat melakukan pengeluaran
        try {
            $this->transactionService->recordExpense([
                'user_id' => $this->user->id,
                'wallet_id' => $tabungan->id,
                'amount' => 100000,
                'description' => 'Tes keluar dari kantong beku',
            ]);
            $this->fail('Kantong beku tidak boleh melakukan pengeluaran.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('dibekukan', $e->getMessage());
        }

        // 3. Tidak dapat menjadi tujuan transfer
        try {
            $this->transferService->executeTransfer([
                'user_id' => $this->user->id,
                'source_wallet_id' => $utama->id,
                'destination_wallet_id' => $tabungan->id,
                'amount' => 100000,
            ]);
            $this->fail('Kantong beku tidak boleh menjadi tujuan transfer.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('dibekukan', $e->getMessage());
        }

        // 4. Tidak dapat menjadi sumber transfer
        try {
            $this->transferService->executeTransfer([
                'user_id' => $this->user->id,
                'source_wallet_id' => $tabungan->id,
                'destination_wallet_id' => $utama->id,
                'amount' => 100000,
            ]);
            $this->fail('Kantong beku tidak boleh menjadi sumber transfer.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('dibekukan', $e->getMessage());
        }

        // 5. Aktifkan kembali kantong dan pastikan transaksi kembali normal
        $this->walletService->unfreezeWallet($this->user->id, $tabungan->id);
        $this->assertSame(WalletStatusEnum::ACTIVE, $tabungan->fresh()->status);

        $this->transferService->executeTransfer([
            'user_id' => $this->user->id,
            'source_wallet_id' => $utama->id,
            'destination_wallet_id' => $tabungan->id,
            'amount' => 500000,
        ]);

        $this->assertSame(3500000.00, (float) $tabungan->fresh()->balance);
    }

    public function test_deleted_wallet_transfers_remaining_balance_and_preserves_historical_records(): void
    {
        $utama = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Utama',
            'initial_balance' => 5000000,
        ]);

        $liburan = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Liburan',
            'initial_balance' => 2000000,
        ]);

        // Gagal jika mencoba menghapus kantong bersaldo tanpa memindahkan saldo
        try {
            $this->walletService->deleteWallet($this->user->id, $liburan->id);
            $this->fail('Menghapus kantong yang masih memiliki saldo tanpa tujuan harus ditolak.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('masih memiliki saldo', $e->getMessage());
        }

        // Hapus Kantong Liburan dengan memindahkan saldo Rp2.000.000 ke Kantong Utama
        $deletedLiburan = $this->walletService->deleteWallet($this->user->id, $liburan->id, $utama->id);

        $this->assertSame(0.00, (float) $deletedLiburan->balance);
        $this->assertSame(WalletStatusEnum::DELETED, $deletedLiburan->status);
        $this->assertNotNull($deletedLiburan->deleted_at);

        // Saldo Kantong Utama menjadi Rp7.000.000 dan total saldo tetap Rp7.000.000
        $this->assertSame(7000000.00, (float) $utama->fresh()->balance);
        $this->assertSame(7000000.00, $this->walletService->getTotalBalance($this->user->id));

        // Kantong yang dihapus tidak muncul pada daftar kantong visible
        $visibleIds = Wallet::where('user_id', $this->user->id)->visible()->pluck('id')->all();
        $this->assertContains($utama->id, $visibleIds);
        $this->assertNotContains($liburan->id, $visibleIds);

        // Histori pada Kantong Utama tetap menampilkan "Transfer masuk dari Kantong Liburan"
        $incomingTx = Transaction::where('wallet_id', $utama->id)
            ->where('type', TransactionTypeEnum::TRANSFER_IN->value)
            ->first();

        $this->assertNotNull($incomingTx);
        $this->assertSame('Transfer masuk dari Kantong Liburan', $incomingTx->description);
        $this->assertSame('Kantong Liburan', $incomingTx->counterpart_wallet_name_snapshot);
        $this->assertSame(2000000.00, (float) $incomingTx->amount);

        // Histori Kantong Liburan tetap tersimpan utuh di database
        $liburanHistoryCount = Transaction::where('wallet_id', $liburan->id)->count();
        $this->assertGreaterThanOrEqual(2, $liburanHistoryCount);

        // Kantong yang sudah dihapus tidak dapat digunakan untuk transaksi baru
        try {
            $this->transactionService->recordIncome([
                'user_id' => $this->user->id,
                'wallet_id' => $liburan->id,
                'amount' => 100000,
                'description' => 'Coba isi kantong terhapus',
            ]);
            $this->fail('Kantong yang dihapus tidak boleh menerima transaksi baru.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('sudah dihapus', $e->getMessage());
        }

        // Data transaksi bersifat immutable (tidak dapat dihapus)
        $this->expectException(LogicException::class);
        $incomingTx->delete();
    }

    public function test_balance_consistency_across_multiple_pockets_and_report_excludes_internal_transfers(): void
    {
        $utama = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Utama',
            'initial_balance' => 0,
        ]);
        $kebutuhan = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Kebutuhan',
            'initial_balance' => 0,
        ]);
        $tabungan = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Tabungan',
            'initial_balance' => 0,
        ]);
        $liburan = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Liburan',
            'initial_balance' => 0,
        ]);

        // Pemasukan Rp16.000.000 ke Kantong Utama
        $this->transactionService->recordIncome([
            'user_id' => $this->user->id,
            'wallet_id' => $utama->id,
            'category_id' => $this->incomeCategory->id,
            'amount' => 16000000,
            'description' => 'Gaji dan Bonus',
        ]);

        // Distribusi ke kantong lain
        $this->transferService->executeTransfer([
            'user_id' => $this->user->id,
            'source_wallet_id' => $utama->id,
            'destination_wallet_id' => $kebutuhan->id,
            'amount' => 4000000,
        ]);
        $this->transferService->executeTransfer([
            'user_id' => $this->user->id,
            'source_wallet_id' => $utama->id,
            'destination_wallet_id' => $tabungan->id,
            'amount' => 5000000,
        ]);
        $this->transferService->executeTransfer([
            'user_id' => $this->user->id,
            'source_wallet_id' => $utama->id,
            'destination_wallet_id' => $liburan->id,
            'amount' => 2000000,
        ]);

        // Pengeluaran Rp1.000.000 dari Kantong Kebutuhan -> sisa Rp3.000.000
        $this->transactionService->recordExpense([
            'user_id' => $this->user->id,
            'wallet_id' => $kebutuhan->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 1000000,
            'description' => 'Belanja Dapur Bulanan',
        ]);

        // Cek saldo masing-masing kantong: 5jt + 3jt + 5jt + 2jt = 15jt
        $this->assertSame(5000000.00, (float) $utama->fresh()->balance);
        $this->assertSame(3000000.00, (float) $kebutuhan->fresh()->balance);
        $this->assertSame(5000000.00, (float) $tabungan->fresh()->balance);
        $this->assertSame(2000000.00, (float) $liburan->fresh()->balance);
        $this->assertSame(15000000.00, $this->walletService->getTotalBalance($this->user->id));

        // Pastikan laporan keuangan mengecualikan transfer internal dari pemasukan/pengeluaran agregat
        $report = $this->reportService->getPeriodReport(
            $this->user->id,
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );

        $this->assertSame(16000000.00, $report['total_income']);
        $this->assertSame(1000000.00, $report['total_expense']);
        $this->assertSame(15000000.00, $report['net_cashflow']);
        $this->assertSame(11000000.00, $report['total_transfer_volume']);
    }

    public function test_authenticated_user_can_access_all_digital_wallet_pages_and_exports(): void
    {
        $wallet = $this->walletService->createWallet([
            'user_id' => $this->user->id,
            'name' => 'Kantong Utama Keluarga',
            'initial_balance' => 5000000,
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kantong Utama Keluarga');

        $this->actingAs($this->user)
            ->get(route('pockets.index'))
            ->assertOk()
            ->assertSee('Manajemen Kantong');

        $this->actingAs($this->user)
            ->get(route('pockets.show', $wallet))
            ->assertOk()
            ->assertSee('Kantong Utama Keluarga');

        $this->actingAs($this->user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('Riwayat Transaksi');

        $this->actingAs($this->user)
            ->get(route('transfers.index'))
            ->assertOk()
            ->assertSee('Transfer Antar Kantong');

        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Laporan Keuangan Keluarga');

        $this->actingAs($this->user)
            ->get(route('reports.export.pdf'))
            ->assertOk()
            ->assertSee('Kantong Utama Keluarga');

        $this->actingAs($this->user)
            ->get(route('account'))
            ->assertOk();
    }
}
