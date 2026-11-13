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
use Carbon\Carbon;
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
    public ?string $flowFilter = '';
    public ?string $monthFilter = '';
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

    /* Form Ubah Transaksi (pemasukan/pengeluaran) */
    public bool $editTransactionModal = false;
    public ?int $editTransactionId = null;
    public string $edit_tx_type = '';
    public ?int $edit_tx_category_id = null;
    public string|int|float $edit_tx_amount = '';
    public string $edit_tx_date = '';
    public string $edit_tx_description = '';

    /**
     * Inisialisasi halaman Home dengan kantong utama keluarga.
     */
    public function mount(WalletService $walletService): void
    {
        $this->wallet = $walletService->getPrimaryWallet(Auth::id());
        $this->monthFilter = now()->format('Y-m');
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
     * Berpindah ke riwayat transaksi bulan sebelumnya.
     */
    public function previousMonth(): void
    {
        $this->monthFilter = Carbon::createFromFormat('Y-m', $this->monthFilter ?: now()->format('Y-m'))
            ->subMonthNoOverflow()
            ->format('Y-m');
        $this->resetPage();
    }

    /**
     * Berpindah ke riwayat transaksi bulan berikutnya (maksimal bulan berjalan).
     */
    public function nextMonth(): void
    {
        $currentMonth = now()->format('Y-m');
        $targetMonth = Carbon::createFromFormat('Y-m', $this->monthFilter ?: $currentMonth)
            ->addMonthNoOverflow()
            ->format('Y-m');

        if ($targetMonth > $currentMonth) {
            return;
        }

        $this->monthFilter = $targetMonth;
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
     * Reset halaman paginasi saat filter aliran dana berubah.
     */
    public function updatedFlowFilter(): void
    {
        $this->flowFilter = $this->flowFilter ?? '';
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
     * Mengosongkan seluruh filter riwayat transaksi dan kembali ke bulan berjalan.
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->flowFilter = '';
        $this->monthFilter = now()->format('Y-m');
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
     * Opsi kategori sesuai jenis transaksi yang sedang diubah.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function editTxCategoryOptions(): array
    {
        $type = $this->edit_tx_type === TransactionTypeEnum::EXPENSE->value
            ? CategoryTypeEnum::EXPENSE
            : CategoryTypeEnum::INCOME;

        return TransactionCategory::query()
            ->where('type', $type)
            ->orderBy('name')
            ->get()
            ->map(fn (TransactionCategory $c): array => [
                'label' => $c->name,
                'value' => $c->id,
            ])
            ->values()
            ->all();
    }

    /**
     * Membuka modal ubah transaksi pemasukan/pengeluaran.
     */
    public function openEditTransaction(int $transactionId): void
    {
        $tx = Transaction::query()
            ->where('wallet_id', $this->wallet->id)
            ->find($transactionId);

        if ($tx === null) {
            return;
        }

        if (in_array($tx->type, [TransactionTypeEnum::TRANSFER_IN, TransactionTypeEnum::TRANSFER_OUT], true)
            || !empty($tx->transfer_reference)) {
            $this->toast()->warning('Tidak Bisa Diubah', 'Transaksi pindah uang tidak dapat diubah. Hapus lalu catat ulang jika perlu.');
            return;
        }

        $this->resetValidation();
        $this->editTransactionId = $tx->id;
        $this->edit_tx_type = $tx->type instanceof TransactionTypeEnum ? $tx->type->value : (string) $tx->type;
        $this->edit_tx_category_id = $tx->category_id;
        $this->edit_tx_amount = (float) $tx->amount;
        $this->edit_tx_date = $tx->transaction_date?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->edit_tx_description = (string) $tx->description;
        unset($this->editTxCategoryOptions);
        $this->editTransactionModal = true;
    }

    /**
     * Menyimpan perubahan transaksi pemasukan/pengeluaran.
     */
    public function saveEditTransaction(TransactionService $transactionService): void
    {
        $validated = $this->validate([
            'editTransactionId' => ['required', 'integer'],
            'edit_tx_category_id' => ['nullable', 'integer'],
            'edit_tx_amount' => ['required', 'numeric', 'gt:0'],
            'edit_tx_date' => ['required', 'date'],
            'edit_tx_description' => ['required', 'string', 'max:190'],
        ], [
            'edit_tx_amount.required' => 'Nominal transaksi wajib diisi.',
            'edit_tx_amount.gt' => 'Nominal harus lebih besar dari Rp0.',
            'edit_tx_description.required' => 'Keterangan transaksi wajib diisi.',
            'edit_tx_date.required' => 'Tanggal transaksi wajib diisi.',
        ]);

        try {
            $transactionService->updateTransaction((int) $validated['editTransactionId'], [
                'category_id' => $validated['edit_tx_category_id'] ? (int) $validated['edit_tx_category_id'] : null,
                'amount' => $validated['edit_tx_amount'],
                'transaction_date' => $validated['edit_tx_date'],
                'description' => $validated['edit_tx_description'],
            ]);

            $this->editTransactionModal = false;
            $this->wallet->refresh();
            unset($this->transactions, $this->walletStats);
            $this->toast()->success('Berhasil!', 'Transaksi berhasil diperbarui.');
        } catch (\Throwable $e) {
            $this->addError('edit_tx_amount', $e->getMessage());
        }
    }

    /**
     * Konfirmasi hapus transaksi.
     */
    public function askDeleteTransaction(int $transactionId): void
    {
        $tx = Transaction::query()->where('wallet_id', $this->wallet->id)->find($transactionId);
        if ($tx === null) {
            return;
        }

        $isTransfer = in_array($tx->type, [TransactionTypeEnum::TRANSFER_IN, TransactionTypeEnum::TRANSFER_OUT], true)
            || !empty($tx->transfer_reference);
        $description = $isTransfer
            ? 'Transaksi pindah/terima uang ini beserta pasangannya akan dihapus dan saldo dikembalikan.'
            : 'Transaksi akan dibatalkan dan saldo kantong akan dipulihkan secara otomatis.';

        $this->dialog()
            ->question('Hapus Transaksi Ini?', $description)
            ->confirm('Ya, Hapus', 'confirmDeleteTransaction', $transactionId)
            ->cancel('Batal')
            ->send();
    }

    /**
     * Eksekusi hapus transaksi.
     */
    public function confirmDeleteTransaction(int $transactionId, TransactionService $transactionService): void
    {
        try {
            $transaction = Transaction::query()
                ->where('wallet_id', $this->wallet->id)
                ->findOrFail($transactionId);

            $transactionService->deleteTransaction($transaction->id);
            $this->wallet->refresh();
            unset($this->transactions, $this->walletStats);
            $this->toast()->success('Dihapus!', 'Transaksi berhasil dihapus dan saldo telah diperbarui.');
        } catch (\Throwable $e) {
            $this->toast()->error('Gagal!', $e->getMessage());
        }
    }

    /**
     * Menghitung total uang masuk dan keluar kantong utama pada bulan aktif.
     *
     * @return array{total_in: float, total_out: float}
     */
    #[Computed]
    public function walletStats(): array
    {
        $baseQuery = Transaction::query()
            ->where('wallet_id', $this->wallet->id)
            ->when($this->monthFilter !== '', function ($q) {
                $q->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$this->monthFilter]);
            });

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
     * Label bulan aktif pada riwayat transaksi kantong utama.
     */
    #[Computed]
    public function activeMonthLabel(): string
    {
        return Carbon::createFromFormat('Y-m', $this->monthFilter ?: now()->format('Y-m'))
            ->locale('id')
            ->translatedFormat('F Y');
    }

    /**
     * Menandai apakah riwayat sudah menampilkan bulan berjalan.
     */
    #[Computed]
    public function isLatestMonth(): bool
    {
        return ($this->monthFilter ?: now()->format('Y-m')) >= now()->format('Y-m');
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