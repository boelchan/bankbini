<?php

declare(strict_types=1);

use App\Enums\CategoryTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WalletColorEnum;
use App\Enums\WalletIconEnum;
use App\Enums\WalletStatusEnum;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Wallet;
use App\Services\FinancialReportService;
use App\Services\TransactionService;
use App\Services\TransferService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new
#[Title('Dasbor Keuangan')]
class extends Component
{
    use Interactions;

    /* State Modal Aksi Cepat */
    public bool $incomeModal = false;
    public bool $expenseModal = false;
    public bool $transferModal = false;
    public bool $pocketModal = false;
    public bool $detailModal = false;

    /* Form Pemasukan Cepat */
    public ?int $income_wallet_id = null;
    public ?int $income_category_id = null;
    public string|int|float $income_amount = '';
    public string $income_date = '';
    public string $income_description = '';
    public string $income_notes = '';

    /* Form Pengeluaran Cepat */
    public ?int $expense_wallet_id = null;
    public ?int $expense_category_id = null;
    public string|int|float $expense_amount = '';
    public string $expense_date = '';
    public string $expense_description = '';
    public string $expense_notes = '';

    /* Form Transfer Cepat */
    public ?int $transfer_source_wallet_id = null;
    public ?int $transfer_destination_wallet_id = null;
    public string|int|float $transfer_amount = '';
    public string $transfer_date = '';
    public string $transfer_description = '';
    public string $transfer_notes = '';

    /* Form Kantong Baru Cepat */
    public string $pocket_name = '';
    public string $pocket_icon = 'wallet';
    public string $pocket_color = 'emerald';
    public string $pocket_description = '';
    public string|int|float $pocket_initial_balance = '';

    /* Detail Transaksi Terpilih */
    public ?int $selectedTransactionId = null;

    /**
     * Inisialisasi nilai awal tanggal form aksi cepat.
     */
    public function mount(): void
    {
        $now = now()->format('Y-m-d\TH:i');
        $this->income_date = $now;
        $this->expense_date = $now;
        $this->transfer_date = $now;

        $defaultWallet = Wallet::query()
            ->where('user_id', Auth::id())
            ->where('status', WalletStatusEnum::ACTIVE)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();

        if ($defaultWallet) {
            $this->income_wallet_id = $defaultWallet->id;
            $this->expense_wallet_id = $defaultWallet->id;
            $this->transfer_source_wallet_id = $defaultWallet->id;
        }
    }

    /**
     * Mengambil daftar seluruh kantong aktif dan dibekukan milik pengguna.
     *
     * @return Collection<int, Wallet>
     */
    #[Computed]
    public function wallets(): Collection
    {
        return Wallet::query()
            ->where('user_id', Auth::id())
            ->whereIn('status', [WalletStatusEnum::ACTIVE, WalletStatusEnum::FROZEN])
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();
    }

    /**
     * Menghitung ringkasan saldo dan arus kas bulan berjalan.
     *
     * @return array<string, float|int>
     */
    #[Computed]
    public function summary(): array
    {
        $userId = (int) Auth::id();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $wallets = $this->wallets;
        $totalBalance = (float) $wallets->sum('balance');
        $activeCount = $wallets->where('status', WalletStatusEnum::ACTIVE)->count();
        $frozenCount = $wallets->where('status', WalletStatusEnum::FROZEN)->count();

        $monthlyIncome = (float) Transaction::query()
            ->where('user_id', $userId)
            ->where('type', TransactionTypeEnum::INCOME)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyExpense = (float) Transaction::query()
            ->where('user_id', $userId)
            ->where('type', TransactionTypeEnum::EXPENSE)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        return [
            'total_balance' => $totalBalance,
            'monthly_income' => $monthlyIncome,
            'monthly_expense' => $monthlyExpense,
            'monthly_net' => $monthlyIncome - $monthlyExpense,
            'active_pockets' => $activeCount,
            'frozen_pockets' => $frozenCount,
        ];
    }

    /**
     * Mengambil data grafik arus kas 6 bulan terakhir.
     *
     * @return array{labels: array<int, string>, series: array<int, array<string, mixed>>}
     */
    #[Computed]
    public function cashflowChart(): array
    {
        return app(FinancialReportService::class)->getMonthlyCashflowSeries((int) Auth::id(), 6);
    }

    /**
     * Mengambil 8 transaksi terbaru milik pengguna.
     *
     * @return Collection<int, Transaction>
     */
    #[Computed]
    public function recentTransactions(): Collection
    {
        return Transaction::query()
            ->with(['wallet' => fn ($query) => $query->withTrashed(), 'category', 'counterpartWallet' => fn ($query) => $query->withTrashed()])
            ->where('user_id', Auth::id())
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();
    }

    /**
     * Mengambil opsi kantong aktif untuk dropdown transaksi.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function activeWalletOptions(): array
    {
        return $this->wallets
            ->where('status', WalletStatusEnum::ACTIVE)
            ->map(fn (Wallet $wallet): array => [
                'label' => $wallet->name . ' (' . $wallet->formattedBalance() . ')',
                'value' => $wallet->id,
            ])
            ->values()
            ->all();
    }

    /**
     * Mengambil opsi kategori pemasukan.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function incomeCategoryOptions(): array
    {
        return TransactionCategory::query()
            ->where('type', CategoryTypeEnum::INCOME)
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', Auth::id()))
            ->orderBy('name')
            ->get()
            ->map(fn (TransactionCategory $cat): array => [
                'label' => $cat->name,
                'value' => $cat->id,
            ])
            ->all();
    }

    /**
     * Mengambil opsi kategori pengeluaran.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function expenseCategoryOptions(): array
    {
        return TransactionCategory::query()
            ->where('type', CategoryTypeEnum::EXPENSE)
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', Auth::id()))
            ->orderBy('name')
            ->get()
            ->map(fn (TransactionCategory $cat): array => [
                'label' => $cat->name,
                'value' => $cat->id,
            ])
            ->all();
    }

    /**
     * Mengambil detail transaksi yang sedang dipilih untuk modal.
     */
    #[Computed]
    public function selectedTransaction(): ?Transaction
    {
        if (! $this->selectedTransactionId) {
            return null;
        }

        return Transaction::query()
            ->with(['wallet' => fn ($query) => $query->withTrashed(), 'category', 'counterpartWallet' => fn ($query) => $query->withTrashed()])
            ->where('user_id', Auth::id())
            ->find($this->selectedTransactionId);
    }

    /**
     * Membuka modal detail transaksi.
     */
    public function showTransactionDetail(int $transactionId): void
    {
        $this->selectedTransactionId = $transactionId;
        unset($this->selectedTransaction);
        $this->detailModal = true;
    }

    /**
     * Membuka modal tambah pemasukan cepat.
     */
    public function openIncomeModal(?int $walletId = null): void
    {
        $this->resetValidation();
        if ($walletId) {
            $this->income_wallet_id = $walletId;
        }
        $this->income_amount = '';
        $this->income_description = '';
        $this->income_notes = '';
        $this->income_date = now()->format('Y-m-d\TH:i');
        $this->incomeModal = true;
    }

    /**
     * Menyimpan transaksi pemasukan dari modal cepat dasbor.
     */
    public function saveIncome(TransactionService $transactionService): void
    {
        $validated = $this->validate([
            'income_wallet_id' => ['required', 'integer'],
            'income_category_id' => ['required', 'integer'],
            'income_amount' => ['required', 'numeric', 'gt:0'],
            'income_date' => ['required', 'date'],
            'income_description' => ['required', 'string', 'max:190'],
            'income_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'income_wallet_id.required' => 'Pilih kantong tujuan terlebih dahulu.',
            'income_category_id.required' => 'Pilih kategori pemasukan.',
            'income_amount.required' => 'Nominal pemasukan wajib diisi.',
            'income_amount.gt' => 'Nominal pemasukan harus lebih besar dari Rp0.',
            'income_description.required' => 'Keterangan pemasukan wajib diisi.',
        ]);

        try {
            $transactionService->recordIncome((int) Auth::id(), [
                'wallet_id' => $validated['income_wallet_id'],
                'category_id' => $validated['income_category_id'],
                'amount' => $validated['income_amount'],
                'transaction_date' => $validated['income_date'],
                'description' => $validated['income_description'],
                'notes' => $validated['income_notes'],
                'idempotency_key' => 'dash-inc-' . Str::uuid()->toString(),
            ]);

            $this->incomeModal = false;
            $this->refreshComputedStats();
            $this->toast()->success('Pemasukan Tercatat', 'Saldo kantong berhasil ditambahkan.')->send();
        } catch (ValidationException $e) {
            throw $e;
        }
    }

    /**
     * Membuka modal catat pengeluaran cepat.
     */
    public function openExpenseModal(?int $walletId = null): void
    {
        $this->resetValidation();
        if ($walletId) {
            $this->expense_wallet_id = $walletId;
        }
        $this->expense_amount = '';
        $this->expense_description = '';
        $this->expense_notes = '';
        $this->expense_date = now()->format('Y-m-d\TH:i');
        $this->expenseModal = true;
    }

    /**
     * Menyimpan transaksi pengeluaran dari modal cepat dasbor.
     */
    public function saveExpense(TransactionService $transactionService): void
    {
        $validated = $this->validate([
            'expense_wallet_id' => ['required', 'integer'],
            'expense_category_id' => ['required', 'integer'],
            'expense_amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['required', 'date'],
            'expense_description' => ['required', 'string', 'max:190'],
            'expense_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'expense_wallet_id.required' => 'Pilih kantong sumber dana.',
            'expense_category_id.required' => 'Pilih kategori pengeluaran.',
            'expense_amount.required' => 'Nominal pengeluaran wajib diisi.',
            'expense_amount.gt' => 'Nominal pengeluaran harus lebih besar dari Rp0.',
            'expense_description.required' => 'Keterangan pengeluaran wajib diisi.',
        ]);

        try {
            $transactionService->recordExpense((int) Auth::id(), [
                'wallet_id' => $validated['expense_wallet_id'],
                'category_id' => $validated['expense_category_id'],
                'amount' => $validated['expense_amount'],
                'transaction_date' => $validated['expense_date'],
                'description' => $validated['expense_description'],
                'notes' => $validated['expense_notes'],
                'idempotency_key' => 'dash-exp-' . Str::uuid()->toString(),
            ]);

            $this->expenseModal = false;
            $this->refreshComputedStats();
            $this->toast()->success('Pengeluaran Tercatat', 'Transaksi pengeluaran berhasil disimpan.')->send();
        } catch (\DomainException|ValidationException $e) {
            $firstMessage = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal mencatat pengeluaran.')
                : $e->getMessage();
            $this->addError('expense_amount', $firstMessage);
        }
    }

    /**
     * Membuka modal transfer cepat antar kantong.
     */
    public function openTransferModal(?int $sourceWalletId = null): void
    {
        $this->resetValidation();
        if ($sourceWalletId) {
            $this->transfer_source_wallet_id = $sourceWalletId;
        }
        $this->transfer_destination_wallet_id = null;
        $this->transfer_amount = '';
        $this->transfer_description = '';
        $this->transfer_notes = '';
        $this->transfer_date = now()->format('Y-m-d\TH:i');
        $this->transferModal = true;
    }

    /**
     * Mengeksekusi transfer antar kantong dari modal cepat dasbor.
     */
    public function saveTransfer(TransferService $transferService): void
    {
        $validated = $this->validate([
            'transfer_source_wallet_id' => ['required', 'integer'],
            'transfer_destination_wallet_id' => ['required', 'integer', 'different:transfer_source_wallet_id'],
            'transfer_amount' => ['required', 'numeric', 'gt:0'],
            'transfer_date' => ['required', 'date'],
            'transfer_description' => ['nullable', 'string', 'max:190'],
            'transfer_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'transfer_source_wallet_id.required' => 'Pilih kantong pengirim.',
            'transfer_destination_wallet_id.required' => 'Pilih kantong penerima.',
            'transfer_destination_wallet_id.different' => 'Kantong penerima harus berbeda dengan kantong pengirim.',
            'transfer_amount.required' => 'Nominal transfer wajib diisi.',
            'transfer_amount.gt' => 'Nominal transfer harus lebih besar dari Rp0.',
        ]);

        try {
            $transferService->executeTransfer((int) Auth::id(), [
                'source_wallet_id' => $validated['transfer_source_wallet_id'],
                'destination_wallet_id' => $validated['transfer_destination_wallet_id'],
                'amount' => $validated['transfer_amount'],
                'transaction_date' => $validated['transfer_date'],
                'description' => $validated['transfer_description'],
                'notes' => $validated['transfer_notes'],
                'idempotency_key' => 'dash-trf-' . Str::uuid()->toString(),
            ]);

            $this->transferModal = false;
            $this->refreshComputedStats();
            $this->toast()->success('Transfer Berhasil', 'Dana berhasil dipindahkan antar kantong.')->send();
        } catch (\DomainException|ValidationException $e) {
            $firstMessage = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal memproses transfer.')
                : $e->getMessage();
            $this->addError('transfer_amount', $firstMessage);
        }
    }

    /**
     * Membuka modal pembuatan kantong baru dari dasbor.
     */
    public function openPocketModal(): void
    {
        $this->resetValidation();
        $this->pocket_name = '';
        $this->pocket_icon = WalletIconEnum::SPARKLES->value;
        $this->pocket_color = WalletColorEnum::ROSE->value;
        $this->pocket_description = '';
        $this->pocket_initial_balance = '';
        $this->pocketModal = true;
    }

    /**
     * Menyimpan kantong baru dari modal cepat dasbor.
     */
    public function savePocket(WalletService $walletService): void
    {
        $validated = $this->validate([
            'pocket_name' => ['required', 'string', 'max:100'],
            'pocket_icon' => ['required', 'string'],
            'pocket_color' => ['required', 'string'],
            'pocket_description' => ['nullable', 'string', 'max:255'],
            'pocket_initial_balance' => ['nullable', 'numeric', 'min:0'],
        ], [
            'pocket_name.required' => 'Nama kantong wajib diisi.',
            'pocket_initial_balance.min' => 'Saldo awal tidak boleh negatif.',
        ]);

        $walletService->createWallet((int) Auth::id(), [
            'name' => $validated['pocket_name'],
            'icon' => $validated['pocket_icon'],
            'color' => $validated['pocket_color'],
            'description' => $validated['pocket_description'],
            'initial_balance' => $validated['pocket_initial_balance'] !== '' ? $validated['pocket_initial_balance'] : 0,
        ]);

        $this->pocketModal = false;
        $this->refreshComputedStats();
        $this->toast()->success('Kantong Baru Siap', 'Kantong keuangan baru berhasil dibuat.')->send();
    }

    /**
     * Menyegarkan cache properti computed setelah mutasi data.
     */
    private function refreshComputedStats(): void
    {
        unset(
            $this->wallets,
            $this->summary,
            $this->cashflowChart,
            $this->recentTransactions,
            $this->activeWalletOptions,
        );
    }
};
