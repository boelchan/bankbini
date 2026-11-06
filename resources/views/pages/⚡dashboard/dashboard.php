<?php

declare(strict_types=1);

use App\Enums\CategoryTypeEnum;
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
#[Title('Dashboard Kantong')]
class extends Component
{
    use Interactions;

    /* State Modal & Expand Transaksi Kantong */
    public bool $incomeModal = false;
    public bool $expenseModal = false;
    public bool $pocketModal = false;

    /**
     * Daftar ID kantong yang sedang dibuka (expand) riwayat transaksinya hingga 100 baris.
     *
     * @var array<int, bool>
     */
    public array $expandedWallets = [];

    /* Form Tambah Uang / Pemasukan (Bisa Langsung atau Ambil dari Kantong Lain) */
    public ?int $income_wallet_id = null;
    public string $income_source_type = 'direct';
    public ?int $income_source_wallet_id = null;
    public ?int $income_category_id = null;
    public string|int|float $income_amount = '';
    public string $income_date = '';
    public string $income_description = '';

    /* Form Pengeluaran */
    public ?int $expense_wallet_id = null;
    public ?int $expense_category_id = null;
    public string|int|float $expense_amount = '';
    public string $expense_date = '';
    public string $expense_description = '';

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
        $now = now()->format('Y-m-d\TH:i');
        $this->income_date = $now;
        $this->expense_date = $now;
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
     * Menghitung ringkasan saldo total dan arus kas bulan ini.
     *
     * @return array<string, float|int>
     */
    #[Computed]
    public function summary(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $wallets = $this->wallets;
        $totalBalance = (float) $wallets->sum('balance');

        $monthlyIncome = (float) Transaction::query()
            ->where('type', TransactionTypeEnum::INCOME)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyExpense = (float) Transaction::query()
            ->where('type', TransactionTypeEnum::EXPENSE)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        return [
            'total_balance' => $totalBalance,
            'monthly_income' => $monthlyIncome,
            'monthly_expense' => $monthlyExpense,
            'pocket_count' => $wallets->count(),
        ];
    }

    /**
     * Mengambil 5 transaksi terbaru keluarga.
     *
     * @return Collection<int, Transaction>
     */
    #[Computed]
    public function recentTransactions(): Collection
    {
        return Transaction::query()
            ->with(['user', 'wallet' => fn ($q) => $q->withTrashed(), 'category', 'counterpartWallet' => fn ($q) => $q->withTrashed()])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }

    /**
     * Mengambil opsi kantong aktif untuk dropdown.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function activeWalletOptions(): array
    {
        return $this->wallets
            ->where('status', WalletStatusEnum::ACTIVE)
            ->map(fn (Wallet $w): array => [
                'label' => $w->name . ' (' . $w->formattedBalance() . ')',
                'value' => $w->id,
            ])
            ->values()
            ->all();
    }

    /**
     * Mengambil opsi kantong aktif selain kantong tujuan untuk ambil dana dari kantong lain.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function otherActiveWalletOptions(): array
    {
        return Wallet::query()
            ->where('status', WalletStatusEnum::ACTIVE)
            ->when($this->income_wallet_id, fn ($q) => $q->where('id', '!=', (int) $this->income_wallet_id))
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get()
            ->map(fn (Wallet $w): array => [
                'label' => $w->name . ' (' . $w->formattedBalance() . ')',
                'value' => $w->id,
            ])
            ->all();
    }

    /**
     * Menyesuaikan pilihan kantong asal saat kantong tujuan diubah.
     */
    public function updatedIncomeWalletId(): void
    {
        if ($this->income_source_wallet_id !== null && (int) $this->income_source_wallet_id === (int) $this->income_wallet_id) {
            $this->income_source_wallet_id = null;
        }

        unset($this->otherActiveWalletOptions);
    }

    /**
     * Mereset pilihan dan pesan error saat jenis sumber uang diubah.
     */
    public function updatedIncomeSourceType(): void
    {
        $this->resetValidation();
        $this->income_source_wallet_id = null;
        $this->income_category_id = null;
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
            ->orderBy('name')
            ->get()
            ->map(fn (TransactionCategory $cat): array => [
                'label' => $cat->name,
                'value' => $cat->id,
            ])
            ->all();
    }

    /**
     * Membuka modal pemasukan / tambah uang pada kantong tertentu.
     */
    public function openIncomeModal(int $walletId): void
    {
        $this->resetValidation();
        $this->income_wallet_id = $walletId;
        $this->income_source_type = IncomeSourceEnum::DIRECT->value;
        $this->income_source_wallet_id = null;
        $this->income_category_id = null;
        $this->income_amount = '';
        $this->income_description = '';
        $this->income_date = now()->format('Y-m-d\TH:i');
        unset($this->otherActiveWalletOptions);
        $this->incomeModal = true;
    }

    /**
     * Menyimpan tambah uang ke kantong (baik pemasukan langsung maupun pindahan dari kantong lain).
     */
    public function saveIncome(TransactionService $transactionService, TransferService $transferService): void
    {
        if ($this->income_source_type === IncomeSourceEnum::FROM_POCKET->value) {
            $validated = $this->validate([
                'income_wallet_id' => ['required', 'integer'],
                'income_source_wallet_id' => ['required', 'integer', 'different:income_wallet_id'],
                'income_amount' => ['required', 'numeric', 'gt:0'],
                'income_date' => ['required', 'date'],
                'income_description' => ['nullable', 'string', 'max:190'],
            ], [
                'income_source_wallet_id.required' => 'Pilih kantong asal tempat mengambil uang.',
                'income_source_wallet_id.different' => 'Kantong asal harus berbeda dengan kantong tujuan.',
                'income_amount.required' => 'Nominal wajib diisi.',
                'income_amount.gt' => 'Nominal harus lebih besar dari Rp0.',
            ]);

            try {
                $transferService->executeTransfer((int) Auth::id(), [
                    'source_wallet_id' => $validated['income_source_wallet_id'],
                    'destination_wallet_id' => $validated['income_wallet_id'],
                    'amount' => $validated['income_amount'],
                    'transaction_date' => $validated['income_date'],
                    'description' => $validated['income_description'],
                    'idempotency_key' => 'dash-trf-' . Str::uuid()->toString(),
                ]);

                $this->incomeModal = false;
                $this->refreshStats();
                $this->toast()->success('Uang Berhasil Dipindahkan', 'Saldo kantong telah ditambahkan dari kantong lain.')->send();
            } catch (\DomainException|ValidationException $e) {
                $msg = $e instanceof ValidationException
                    ? (collect($e->errors())->flatten()->first() ?? 'Gagal memindahkan uang.')
                    : $e->getMessage();
                $this->addError('income_amount', $msg);
            }

            return;
        }

        $validated = $this->validate([
            'income_wallet_id' => ['required', 'integer'],
            'income_category_id' => ['required', 'integer'],
            'income_amount' => ['required', 'numeric', 'gt:0'],
            'income_date' => ['required', 'date'],
            'income_description' => ['required', 'string', 'max:190'],
        ], [
            'income_wallet_id.required' => 'Pilih kantong tujuan.',
            'income_category_id.required' => 'Pilih kategori pemasukan.',
            'income_amount.required' => 'Nominal pemasukan wajib diisi.',
            'income_amount.gt' => 'Nominal harus lebih besar dari Rp0.',
            'income_description.required' => 'Keterangan pemasukan wajib diisi.',
        ]);

        try {
            $transactionService->recordIncome((int) Auth::id(), [
                'wallet_id' => $validated['income_wallet_id'],
                'category_id' => $validated['income_category_id'],
                'amount' => $validated['income_amount'],
                'transaction_date' => $validated['income_date'],
                'description' => $validated['income_description'],
                'idempotency_key' => 'dash-inc-' . Str::uuid()->toString(),
            ]);

            $this->incomeModal = false;
            $this->refreshStats();
            $this->toast()->success('Pemasukan Disimpan', 'Saldo kantong berhasil ditambahkan.')->send();
        } catch (\DomainException|ValidationException $e) {
            $msg = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal menyimpan pemasukan.')
                : $e->getMessage();
            $this->addError('income_amount', $msg);
        }
    }

    /**
     * Membuka modal pengeluaran dari kantong tertentu.
     */
    public function openExpenseModal(int $walletId): void
    {
        $this->resetValidation();
        $this->expense_wallet_id = $walletId;
        $this->expense_category_id = null;
        $this->expense_amount = '';
        $this->expense_description = '';
        $this->expense_date = now()->format('Y-m-d\TH:i');
        $this->expenseModal = true;
    }

    /**
     * Menyimpan pengeluaran dari kantong.
     */
    public function saveExpense(TransactionService $transactionService): void
    {
        $validated = $this->validate([
            'expense_wallet_id' => ['required', 'integer'],
            'expense_category_id' => ['required', 'integer'],
            'expense_amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['required', 'date'],
            'expense_description' => ['required', 'string', 'max:190'],
        ], [
            'expense_wallet_id.required' => 'Pilih kantong sumber pengeluaran.',
            'expense_category_id.required' => 'Pilih kategori pengeluaran.',
            'expense_amount.required' => 'Nominal pengeluaran wajib diisi.',
            'expense_amount.gt' => 'Nominal harus lebih besar dari Rp0.',
            'expense_description.required' => 'Keterangan pengeluaran wajib diisi.',
        ]);

        try {
            $transactionService->recordExpense((int) Auth::id(), [
                'wallet_id' => $validated['expense_wallet_id'],
                'category_id' => $validated['expense_category_id'],
                'amount' => $validated['expense_amount'],
                'transaction_date' => $validated['expense_date'],
                'description' => $validated['expense_description'],
                'idempotency_key' => 'dash-exp-' . Str::uuid()->toString(),
            ]);

            $this->expenseModal = false;
            $this->refreshStats();
            $this->toast()->success('Pengeluaran Tercatat', 'Saldo kantong telah diperbarui.')->send();
        } catch (\DomainException|ValidationException $e) {
            $msg = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal mencatat pengeluaran.')
                : $e->getMessage();
            $this->addError('expense_amount', $msg);
        }
    }

    /**
     * Membuka modal tambah kantong baru.
     */
    public function openPocketModal(): void
    {
        $this->resetValidation();
        $this->pocket_name = '';
        $this->pocket_icon = WalletIconEnum::WALLET->value;
        $this->pocket_color = WalletColorEnum::ROSE->value;
        $this->pocket_description = '';
        $this->pocket_initial_balance = '';
        $this->pocketModal = true;
    }

    /**
     * Menyimpan kantong baru.
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
        $this->refreshStats();
        $this->toast()->success('Kantong Baru Dibuat', 'Kantong keuangan berhasil ditambahkan.')->send();
    }

    /**
     * Menyegarkan properti computed setelah aksi.
     */
    private function refreshStats(): void
    {
        unset(
            $this->wallets,
            $this->summary,
            $this->recentTransactions,
            $this->activeWalletOptions,
            $this->otherActiveWalletOptions,
        );
    }
};
