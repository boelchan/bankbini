<?php

declare(strict_types=1);

use App\Enums\CategoryTypeEnum;
use App\Enums\IncomeSourceEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WalletStatusEnum;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Wallet;
use App\Services\TransactionService;
use App\Services\TransferService;
use App\Services\WalletService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

new
#[Title('Home - Kantong Utama')]
class extends Component
{
    use Interactions;
    use WithPagination;

    public Wallet $wallet;

    /* Filter Riwayat Mutasi Kantong Utama */
    public string $search = '';
    public string $typeFilter = '';
    public string $flowFilter = '';
    public string $monthFilter = '';
    public ?int $categoryFilter = null;

    /* Modal State */
    public bool $incomeModal = false;
    public bool $expenseModal = false;
    public bool $transferModal = false;

    /* Form Tambah Uang (Langsung atau Ambil dari Kantong Lain) */
    public string $income_source_type = 'direct';
    public ?int $income_source_wallet_id = null;
    public ?int $income_category_id = null;
    public string|int|float $income_amount = '';
    public string $income_date = '';
    public string $income_description = '';

    /* Form Pengeluaran */
    public ?int $expense_category_id = null;
    public string|int|float $expense_amount = '';
    public string $expense_date = '';
    public string $expense_description = '';

    /* Form Pindah Uang ke Kantong Lain */
    public ?int $transfer_destination_wallet_id = null;
    public string|int|float $transfer_amount = '';
    public string $transfer_date = '';
    public string $transfer_description = '';

    /**
     * Inisialisasi halaman Home dengan kantong utama keluarga.
     */
    public function mount(WalletService $walletService): void
    {
        $this->wallet = $walletService->getPrimaryWallet(Auth::id());
        $now = now()->format('Y-m-d\TH:i');
        $this->income_date = $now;
        $this->expense_date = $now;
        $this->transfer_date = $now;
    }

    /**
     * Reset halaman paginasi saat pencarian berubah.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset halaman paginasi saat filter jenis transaksi berubah.
     */
    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Reset halaman paginasi saat filter bulan transaksi berubah.
     */
    public function updatedMonthFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Reset halaman paginasi saat filter kategori berubah.
     */
    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Mengatur filter cepat aliran dana.
     */
    public function setFlowFilter(string $flow): void
    {
        $this->flowFilter = $flow;
        $this->resetPage();
    }

    /**
     * Mengosongkan seluruh filter riwayat transaksi.
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->typeFilter = '';
        $this->flowFilter = '';
        $this->monthFilter = '';
        $this->categoryFilter = null;
        $this->resetPage();
    }

    /**
     * Membuka modal tambah uang ke kantong utama.
     */
    public function openIncomeModal(string $sourceType = 'direct'): void
    {
        $defaultIncomeCat = TransactionCategory::query()
            ->where('type', CategoryTypeEnum::INCOME->value)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->first();

        $this->income_source_type = $sourceType;
        $this->income_source_wallet_id = null;
        $this->income_category_id = $defaultIncomeCat?->id;
        $this->income_amount = '';
        $this->income_date = now()->format('Y-m-d\TH:i');
        $this->income_description = '';
        $this->incomeModal = true;
    }

    /**
     * Menyimpan transaksi tambah uang ke kantong utama.
     */
    public function saveIncome(
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
                'income_date.required' => 'Tanggal dan waktu transaksi wajib diisi.',
            ]);

            try {
                $transferService->executeTransfer([
                    'user_id' => (int) Auth::id(),
                    'source_wallet_id' => (int) $this->income_source_wallet_id,
                    'destination_wallet_id' => $this->wallet->id,
                    'amount' => (float) $this->income_amount,
                    'description' => $this->income_description ?: sprintf('Ambil dana dari %s', Wallet::find($this->income_source_wallet_id)?->name ?? 'Kantong Lain'),
                    'transaction_date' => $this->income_date,
                    'idempotency_key' => 'trf-in-' . (string) Str::uuid(),
                ]);

                $this->wallet->refresh();
                $this->incomeModal = false;
                $this->toast()->success('Berhasil!', 'Dana berhasil dipindahkan ke kantong utama.');
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
            'income_date.required' => 'Tanggal dan waktu transaksi wajib diisi.',
        ]);

        try {
            $transactionService->createIncomeTransaction([
                'user_id' => (int) Auth::id(),
                'wallet_id' => $this->wallet->id,
                'category_id' => $this->income_category_id ? (int) $this->income_category_id : null,
                'amount' => (float) $this->income_amount,
                'description' => $this->income_description ?: 'Pemasukan ke ' . $this->wallet->name,
                'transaction_date' => $this->income_date,
                'idempotency_key' => 'inc-' . (string) Str::uuid(),
            ]);

            $this->wallet->refresh();
            $this->incomeModal = false;
            $this->toast()->success('Berhasil!', 'Pemasukan ke kantong utama berhasil dicatat.');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'income_amount' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Membuka modal catat pengeluaran dari kantong utama.
     */
    public function openExpenseModal(): void
    {
        $defaultExpenseCat = TransactionCategory::query()
            ->where('type', CategoryTypeEnum::EXPENSE->value)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->first();

        $this->expense_category_id = $defaultExpenseCat?->id;
        $this->expense_amount = '';
        $this->expense_date = now()->format('Y-m-d\TH:i');
        $this->expense_description = '';
        $this->expenseModal = true;
    }

    /**
     * Menyimpan transaksi pengeluaran dari kantong utama.
     */
    public function saveExpense(TransactionService $transactionService): void
    {
        $this->validate([
            'expense_category_id' => ['nullable', 'integer', 'exists:transaction_categories,id'],
            'expense_amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['required', 'date'],
            'expense_description' => ['nullable', 'string', 'max:255'],
        ], [
            'expense_amount.required' => 'Nominal pengeluaran wajib diisi.',
            'expense_amount.gt' => 'Nominal pengeluaran harus lebih dari Rp0.',
            'expense_date.required' => 'Tanggal transaksi wajib diisi.',
        ]);

        try {
            $transactionService->createExpenseTransaction([
                'user_id' => (int) Auth::id(),
                'wallet_id' => $this->wallet->id,
                'category_id' => $this->expense_category_id ? (int) $this->expense_category_id : null,
                'amount' => (float) $this->expense_amount,
                'description' => $this->expense_description ?: 'Pengeluaran dari ' . $this->wallet->name,
                'transaction_date' => $this->expense_date,
                'idempotency_key' => 'exp-' . (string) Str::uuid(),
            ]);

            $this->wallet->refresh();
            $this->expenseModal = false;
            $this->toast()->success('Berhasil!', 'Pengeluaran berhasil dicatat.');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'expense_amount' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Membuka modal pindah uang dari kantong utama ke kantong lain.
     */
    public function openTransferModal(): void
    {
        $this->transfer_destination_wallet_id = null;
        $this->transfer_amount = '';
        $this->transfer_date = now()->format('Y-m-d\TH:i');
        $this->transfer_description = '';
        $this->transferModal = true;
    }

    /**
     * Menyimpan transaksi pindah uang ke kantong lain.
     */
    public function saveTransfer(TransferService $transferService): void
    {
        $this->validate([
            'transfer_destination_wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'transfer_amount' => ['required', 'numeric', 'gt:0'],
            'transfer_date' => ['required', 'date'],
            'transfer_description' => ['nullable', 'string', 'max:255'],
        ], [
            'transfer_destination_wallet_id.required' => 'Pilih kantong tujuan terlebih dahulu.',
            'transfer_amount.required' => 'Nominal pindah uang wajib diisi.',
            'transfer_amount.gt' => 'Nominal pindah uang harus lebih dari Rp0.',
            'transfer_date.required' => 'Tanggal transaksi wajib diisi.',
        ]);

        try {
            $transferService->executeTransfer([
                'user_id' => (int) Auth::id(),
                'source_wallet_id' => $this->wallet->id,
                'destination_wallet_id' => (int) $this->transfer_destination_wallet_id,
                'amount' => (float) $this->transfer_amount,
                'description' => $this->transfer_description ?: sprintf('Pindah uang ke %s', Wallet::find($this->transfer_destination_wallet_id)?->name ?? 'Kantong Lain'),
                'transaction_date' => $this->transfer_date,
                'idempotency_key' => 'trf-' . (string) Str::uuid(),
            ]);

            $this->wallet->refresh();
            $this->transferModal = false;
            $this->toast()->success('Berhasil!', 'Uang berhasil dipindahkan ke kantong tujuan.');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'transfer_amount' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Konfirmasi hapus transaksi terakhir.
     */
    public function askDeleteTransaction(int $transactionId): void
    {
        $this->dialog()
            ->question('Hapus Transaksi Ini?', 'Transaksi akan dibatalkan dan saldo kantong akan dipulihkan secara otomatis.')
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
            $transaction = Transaction::query()
                ->where('wallet_id', $this->wallet->id)
                ->findOrFail($transactionId);

            $transactionService->deleteTransaction($transaction->id);
            $this->wallet->refresh();
            $this->toast()->success('Dihapus!', 'Transaksi berhasil dihapus dan saldo telah diperbarui.');
        } catch (\Throwable $e) {
            $this->toast()->error('Gagal!', $e->getMessage());
        }
    }

    /**
     * Menghitung total uang masuk dan keluar pada kantong utama.
     *
     * @return array{total_in: float, total_out: float}
     */
    #[Computed]
    public function walletStats(): array
    {
        $baseQuery = Transaction::query()
            ->where('wallet_id', $this->wallet->id);

        $incomeSum = (float) (clone $baseQuery)
            ->whereIn('type', [
                TransactionTypeEnum::INCOME,
                TransactionTypeEnum::TRANSFER_IN,
            ])
            ->sum('amount');

        $expenseSum = (float) (clone $baseQuery)
            ->whereIn('type', [
                TransactionTypeEnum::EXPENSE,
                TransactionTypeEnum::TRANSFER_OUT,
            ])
            ->sum('amount');

        return [
            'total_in' => round($incomeSum, 2),
            'total_out' => round($expenseSum, 2),
        ];
    }

    /**
     * Daftar pilihan kantong aktif lain untuk transfer.
     *
     * @return array<int, array{label: string, value: int, description: string}>
     */
    #[Computed]
    public function otherActiveWalletOptions(): array
    {
        return Wallet::query()
            ->active()
            ->where('id', '!=', $this->wallet->id)
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
     * Pilihan filter kategori (semua kategori untuk kantong ini).
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function categoryFilterOptions(): array
    {
        return TransactionCategory::query()
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
     * Pilihan bulan transaksi yang tersedia pada kantong utama.
     *
     * @return array<int, array{label: string, value: string}>
     */
    #[Computed]
    public function monthOptions(): array
    {
        $months = Transaction::query()
            ->where('wallet_id', $this->wallet->id)
            ->selectRaw("DISTINCT DATE_FORMAT(transaction_date, '%Y-%m') as ym")
            ->orderByDesc('ym')
            ->pluck('ym')
            ->filter()
            ->values();

        $currentYm = now()->format('Y-m');
        if (!$months->contains($currentYm)) {
            $months->prepend($currentYm);
        }

        return $months->map(function (string $ym) {
            $date = \Carbon\Carbon::createFromFormat('Y-m', $ym);
            return [
                'label' => $date ? $date->translatedFormat('F Y') : $ym,
                'value' => $ym,
            ];
        })->all();
    }

    /**
     * Daftar riwayat mutasi transaksi kantong utama dengan paginasi dan filter lengkap.
     *
     * @return LengthAwarePaginator<Transaction>
     */
    #[Computed]
    public function transactions(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return Transaction::query()
            ->with(['category', 'user'])
            ->where('wallet_id', $this->wallet->id)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('description', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhere('counterpart_wallet_name_snapshot', 'like', "%{$search}%")
                        ->orWhere('category_name_snapshot', 'like', "%{$search}%");
                });
            })
            ->when($this->monthFilter !== '', function ($q) {
                $q->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$this->monthFilter]);
            })
            ->when($this->typeFilter !== '', function ($q) {
                $q->where('type', $this->typeFilter);
            })
            ->when($this->flowFilter !== '', function ($q) {
                match ($this->flowFilter) {
                    'income' => $q->whereIn('type', [TransactionTypeEnum::INCOME, TransactionTypeEnum::TRANSFER_IN]),
                    'expense' => $q->whereIn('type', [TransactionTypeEnum::EXPENSE, TransactionTypeEnum::TRANSFER_OUT]),
                    'transfer' => $q->whereIn('type', [TransactionTypeEnum::TRANSFER_IN, TransactionTypeEnum::TRANSFER_OUT]),
                    default => null,
                };
            })
            ->when($this->categoryFilter !== null, function ($q) {
                $q->where('category_id', $this->categoryFilter);
            })
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(15);
    }
};
?>