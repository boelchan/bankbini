<?php

declare(strict_types=1);

use App\Enums\CategoryTypeEnum;
use App\Enums\ExpenseSourceEnum;
use App\Enums\IncomeSourceEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WalletColorEnum;
use App\Enums\WalletIconEnum;
use App\Enums\WalletStatusEnum;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Wallet;
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
#[Title('Daftar Kantong')]
class extends Component
{
    use Interactions;

    /* State Modal & Expand Transaksi Kantong */
    public bool $transactionModal = false;
    public bool $pocketModal = false;

    /**
     * Daftar ID kantong yang sedang dibuka (expand) riwayat transaksinya hingga 100 baris.
     *
     * @var array<int, bool>
     */
    public array $expandedWallets = [];

    /* Form Transaksi Terpadu (Pengeluaran / Pemasukan) */
    public string $transaction_mode = 'expense';
    public ?int $income_wallet_id = null;
    public string $expense_source_type = 'direct';
    public string $income_source_type = 'direct';
    public ?int $income_source_wallet_id = null;
    public ?int $income_category_id = null;
    public ?int $expense_category_id = null;
    public string|int|float $income_amount = '';
    public string $income_date = '';
    public string $income_description = '';

    /* Form Tambah Kantong Baru */
    public string $pocket_name = '';
    public string $pocket_icon = 'wallet';
    public string $pocket_color = 'rose';
    public string $pocket_description = '';
    public string|int|float $pocket_initial_balance = '';

    /**
     * Inisialisasi tanggal default form.
     */
    public function mount(): void
    {
        $this->income_date = now()->format('Y-m-d\TH:i');
    }

    /**
     * Mengambil daftar seluruh kantong aktif dan dibekukan keluarga beserta transaksi terbarunya.
     *
     * @return Collection<int, Wallet>
     */
    #[Computed]
    public function wallets(): Collection
    {
        return Wallet::query()
            ->withCount('transactions')
            ->with([
                'transactions' => fn ($query) => $query
                    ->with(['category', 'user'])
                    ->orderByDesc('transaction_date')
                    ->orderByDesc('id'),
            ])
            ->whereIn('status', [WalletStatusEnum::ACTIVE, WalletStatusEnum::FROZEN])
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();
    }

    /**
     * Membuka atau menutup tampilan 100 transaksi terakhir pada kartu kantong.
     */
    public function toggleExpandTransactions(int $walletId): void
    {
        $this->expandedWallets[$walletId] = empty($this->expandedWallets[$walletId]);
    }

    /**
     * Membuka modal transaksi terpadu untuk kantong tertentu.
     */
    public function openTransactionModal(int $walletId): void
    {
        $this->income_wallet_id = $walletId;
        $this->transaction_mode = CategoryTypeEnum::EXPENSE->value;
        $this->expense_source_type = ExpenseSourceEnum::DIRECT->value;
        $this->income_source_type = IncomeSourceEnum::DIRECT->value;
        $this->income_source_wallet_id = null;
        $this->income_category_id = null;
        $this->expense_category_id = null;
        $this->income_amount = '';
        $this->income_date = now()->format('Y-m-d\TH:i');
        $this->income_description = '';
        $this->transactionModal = true;
    }

    /**
     * Membuka modal pembuatan kantong baru.
     */
    public function openPocketModal(): void
    {
        $this->pocket_name = '';
        $this->pocket_icon = WalletIconEnum::WALLET->value;
        $this->pocket_color = WalletColorEnum::ROSE->value;
        $this->pocket_description = '';
        $this->pocket_initial_balance = '';
        $this->pocketModal = true;
    }

    /**
     * Menyimpan transaksi terpadu (pengeluaran atau pemasukan kantong).
     */
    public function saveTransaction(
        TransactionService $transactionService,
        TransferService $transferService
    ): void {
        $wallet = Wallet::query()->active()->findOrFail($this->income_wallet_id);

        if ($this->transaction_mode === CategoryTypeEnum::EXPENSE->value) {
            $this->processExpense($wallet, $transactionService, $transferService);
        } else {
            $this->processIncome($wallet, $transactionService, $transferService);
        }

        $this->transactionModal = false;
        unset($this->wallets);
        unset($this->summary);
    }

    /**
     * Memproses pencatatan transaksi pengeluaran.
     */
    protected function processExpense(
        Wallet $wallet,
        TransactionService $transactionService,
        TransferService $transferService
    ): void {
        if ($this->expense_source_type === ExpenseSourceEnum::TO_POCKET->value) {
            $this->validate([
                'income_source_wallet_id' => ['required', 'integer', 'exists:wallets,id'],
                'income_amount' => ['required', 'numeric', 'gt:0'],
                'income_date' => ['required', 'date'],
                'income_description' => ['nullable', 'string', 'max:255'],
            ], [
                'income_source_wallet_id.required' => 'Pilih kantong tujuan terlebih dahulu.',
                'income_amount.required' => 'Nominal uang wajib diisi.',
                'income_amount.gt' => 'Nominal uang harus lebih dari Rp0.',
                'income_date.required' => 'Tanggal transaksi wajib diisi.',
            ]);

            try {
                $transferService->executeTransfer([
                    'user_id' => (int) Auth::id(),
                    'source_wallet_id' => $wallet->id,
                    'destination_wallet_id' => (int) $this->income_source_wallet_id,
                    'amount' => (float) $this->income_amount,
                    'description' => $this->income_description ?: sprintf('Pindah ke %s', Wallet::find($this->income_source_wallet_id)?->name ?? 'Kantong Lain'),
                    'transaction_date' => $this->income_date,
                    'idempotency_key' => 'trf-' . (string) Str::uuid(),
                ]);

                $this->toast()->success('Berhasil!', 'Uang berhasil dipindahkan ke kantong tujuan.');
            } catch (\Throwable $e) {
                throw ValidationException::withMessages([
                    'income_amount' => $e->getMessage(),
                ]);
            }

            return;
        }

        $this->validate([
            'expense_category_id' => ['nullable', 'integer', 'exists:transaction_categories,id'],
            'income_amount' => ['required', 'numeric', 'gt:0'],
            'income_date' => ['required', 'date'],
            'income_description' => ['nullable', 'string', 'max:255'],
        ], [
            'income_amount.required' => 'Nominal pengeluaran wajib diisi.',
            'income_amount.gt' => 'Nominal pengeluaran harus lebih dari Rp0.',
            'income_date.required' => 'Tanggal transaksi wajib diisi.',
        ]);

        try {
            $transactionService->createExpenseTransaction([
                'user_id' => (int) Auth::id(),
                'wallet_id' => $wallet->id,
                'category_id' => $this->expense_category_id ? (int) $this->expense_category_id : null,
                'amount' => (float) $this->income_amount,
                'description' => $this->income_description ?: 'Pengeluaran dari ' . $wallet->name,
                'transaction_date' => $this->income_date,
                'idempotency_key' => 'exp-' . (string) Str::uuid(),
            ]);

            $this->toast()->success('Berhasil!', 'Pengeluaran berhasil dicatat.');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'income_amount' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Memproses pencatatan transaksi pemasukan.
     */
    protected function processIncome(
        Wallet $wallet,
        TransactionService $transactionService,
        TransferService $transferService
    ): void {
        if ($this->income_source_type === IncomeSourceEnum::FROM_POCKET->value) {
            $this->validate([
                'income_source_wallet_id' => ['required', 'integer', 'exists:wallets,id'],
                'income_amount' => ['required', 'numeric', 'gt:0'],
                'income_date' => ['required', 'date'],
                'income_description' => ['nullable', 'string', 'max:255'],
            ], [
                'income_source_wallet_id.required' => 'Pilih kantong sumber dana terlebih dahulu.',
                'income_amount.required' => 'Nominal uang wajib diisi.',
                'income_amount.gt' => 'Nominal uang harus lebih dari Rp0.',
                'income_date.required' => 'Tanggal transaksi wajib diisi.',
            ]);

            try {
                $transferService->executeTransfer([
                    'user_id' => (int) Auth::id(),
                    'source_wallet_id' => (int) $this->income_source_wallet_id,
                    'destination_wallet_id' => $wallet->id,
                    'amount' => (float) $this->income_amount,
                    'description' => $this->income_description ?: sprintf('Terima dari %s', Wallet::find($this->income_source_wallet_id)?->name ?? 'Kantong Lain'),
                    'transaction_date' => $this->income_date,
                    'idempotency_key' => 'trf-in-' . (string) Str::uuid(),
                ]);

                $this->toast()->success('Berhasil!', 'Dana berhasil dipindahkan ke kantong ini.');
            } catch (\Throwable $e) {
                throw ValidationException::withMessages([
                    'income_amount' => $e->getMessage(),
                ]);
            }

            return;
        }

        $this->validate([
            'income_category_id' => ['nullable', 'integer', 'exists:transaction_categories,id'],
            'income_amount' => ['required', 'numeric', 'gt:0'],
            'income_date' => ['required', 'date'],
            'income_description' => ['nullable', 'string', 'max:255'],
        ], [
            'income_amount.required' => 'Nominal uang masuk wajib diisi.',
            'income_amount.gt' => 'Nominal uang masuk harus lebih dari Rp0.',
            'income_date.required' => 'Tanggal transaksi wajib diisi.',
        ]);

        try {
            $transactionService->createIncomeTransaction([
                'user_id' => (int) Auth::id(),
                'wallet_id' => $wallet->id,
                'category_id' => $this->income_category_id ? (int) $this->income_category_id : null,
                'amount' => (float) $this->income_amount,
                'description' => $this->income_description ?: 'Pemasukan ke ' . $wallet->name,
                'transaction_date' => $this->income_date,
                'idempotency_key' => 'inc-' . (string) Str::uuid(),
            ]);

            $this->toast()->success('Berhasil!', 'Pemasukan berhasil dicatat.');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'income_amount' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Konfirmasi dialog hapus transaksi terakhir.
     */
    public function askDeleteTransaction(int $transactionId): void
    {
        $this->dialog()
            ->question('Hapus Transaksi Terakhir?', 'Transaksi akan dibatalkan dan saldo kantong akan dipulihkan secara otomatis.')
            ->confirm('Ya, Hapus', 'confirmDeleteTransaction', $transactionId)
            ->cancel('Batal')
            ->send();
    }

    /**
     * Eksekusi hapus transaksi terakhir.
     */
    public function confirmDeleteTransaction(int $transactionId, TransactionService $transactionService): void
    {
        try {
            $transaction = Transaction::query()->findOrFail($transactionId);
            $transactionService->deleteTransaction($transaction);

            unset($this->wallets);
            unset($this->summary);

            $this->toast()->success('Dihapus!', 'Transaksi berhasil dihapus dan saldo telah diperbarui.');
        } catch (\Throwable $e) {
            $this->toast()->error('Gagal!', $e->getMessage());
        }
    }

    /**
     * Membuat kantong keuangan baru.
     */
    public function savePocket(WalletService $walletService): void
    {
        $this->validate([
            'pocket_name' => ['required', 'string', 'max:100'],
            'pocket_icon' => ['required', 'string'],
            'pocket_color' => ['required', 'string'],
            'pocket_initial_balance' => ['nullable', 'numeric', 'gte:0'],
            'pocket_description' => ['nullable', 'string', 'max:255'],
        ], [
            'pocket_name.required' => 'Nama kantong wajib diisi.',
            'pocket_icon.required' => 'Ikon kantong wajib dipilih.',
            'pocket_color.required' => 'Warna kantong wajib dipilih.',
            'pocket_initial_balance.gte' => 'Saldo awal tidak boleh negatif.',
        ]);

        try {
            $walletService->createWallet([
                'user_id' => (int) Auth::id(),
                'name' => $this->pocket_name,
                'icon' => $this->pocket_icon,
                'color' => $this->pocket_color,
                'description' => $this->pocket_description,
                'initial_balance' => $this->pocket_initial_balance ? (float) $this->pocket_initial_balance : 0,
            ]);

            $this->pocketModal = false;
            unset($this->wallets);
            unset($this->summary);

            $this->toast()->success('Berhasil!', 'Kantong baru berhasil dibuat.');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'pocket_name' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Ringkasan saldo seluruh kantong, pemasukan bulan ini, dan pengeluaran bulan ini.
     *
     * @return array{total_balance: float, monthly_income: float, monthly_expense: float}
     */
    #[Computed]
    public function summary(): array
    {
        $totalBalance = (float) Wallet::query()->visible()->sum('balance');

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthlyIncome = (float) Transaction::query()
            ->where('type', TransactionTypeEnum::INCOME)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyExpense = (float) Transaction::query()
            ->where('type', TransactionTypeEnum::EXPENSE)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        return [
            'total_balance' => round($totalBalance, 2),
            'monthly_income' => round($monthlyIncome, 2),
            'monthly_expense' => round($monthlyExpense, 2),
        ];
    }

    /**
     * Pilihan kantong aktif lain (selain kantong yang sedang dipilih).
     *
     * @return array<int, array{label: string, value: int, description: string}>
     */
    #[Computed]
    public function otherActiveWalletOptions(): array
    {
        if ($this->income_wallet_id === null) {
            return [];
        }

        return Wallet::query()
            ->active()
            ->where('id', '!=', $this->income_wallet_id)
            ->orderBy('name')
            ->get()
            ->map(fn (Wallet $w) => [
                'label' => $w->name,
                'value' => $w->id,
                'description' => 'Saldo: ' . $w->formattedBalance(),
            ])
            ->values()
            ->all();
    }

    /**
     * Pilihan kategori pengeluaran.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function expenseCategoryOptions(): array
    {
        return TransactionCategory::query()
            ->where('type', CategoryTypeEnum::EXPENSE->value)
            ->orderBy('name')
            ->get()
            ->map(fn (TransactionCategory $c) => [
                'label' => $c->name,
                'value' => $c->id,
            ])
            ->values()
            ->all();
    }

    /**
     * Pilihan kategori pemasukan.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function incomeCategoryOptions(): array
    {
        return TransactionCategory::query()
            ->where('type', CategoryTypeEnum::INCOME->value)
            ->orderBy('name')
            ->get()
            ->map(fn (TransactionCategory $c) => [
                'label' => $c->name,
                'value' => $c->id,
            ])
            ->values()
            ->all();
    }
};
?>