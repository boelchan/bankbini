<?php

declare(strict_types=1);

use App\Enums\CategoryTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WalletStatusEnum;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Wallet;
use App\Services\TransactionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

new
#[Title('Riwayat Transaksi & Pencatatan')]
class extends Component
{
    use Interactions;
    use WithPagination;

    /* Filter Riwayat Transaksi */
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'wallet', except: null)]
    public ?int $walletFilter = null;

    #[Url(as: 'category', except: null)]
    public ?int $categoryFilter = null;

    #[Url(as: 'type', except: '')]
    public string $typeFilter = '';

    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    /* State Modal */
    public bool $incomeModal = false;
    public bool $expenseModal = false;
    public bool $detailModal = false;

    /* Form Pemasukan */
    public ?int $income_wallet_id = null;
    public ?int $income_category_id = null;
    public string|int|float $income_amount = '';
    public string $income_date = '';
    public string $income_description = '';
    public string $income_notes = '';

    /* Form Pengeluaran */
    public ?int $expense_wallet_id = null;
    public ?int $expense_category_id = null;
    public string|int|float $expense_amount = '';
    public string $expense_date = '';
    public string $expense_description = '';
    public string $expense_notes = '';

    /* Detail Transaksi */
    public ?int $selectedTransactionId = null;

    /**
     * Inisialisasi komponen dan buka modal jika parameter query action tersedia.
     */
    public function mount(): void
    {
        $now = now()->format('Y-m-d\TH:i');
        $this->income_date = $now;
        $this->expense_date = $now;

        $defaultWallet = Wallet::query()
            ->where('user_id', Auth::id())
            ->where('status', WalletStatusEnum::ACTIVE)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();

        if ($defaultWallet) {
            $this->income_wallet_id = $defaultWallet->id;
            $this->expense_wallet_id = $defaultWallet->id;
        }

        $action = request()->query('action');
        if ($action === 'income') {
            $this->incomeModal = true;
        } elseif ($action === 'expense') {
            $this->expenseModal = true;
        }
    }

    /**
     * Reset halaman paginasi saat filter berubah.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'walletFilter', 'categoryFilter', 'typeFilter', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Mengatur filter cepat jenis transaksi (Semua, Pemasukan, Pengeluaran, Transfer).
     */
    public function setQuickTypeFilter(string $filter): void
    {
        $this->typeFilter = $filter;
        $this->resetPage();
    }

    /**
     * Mengatur ulang seluruh filter pencarian.
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->walletFilter = null;
        $this->categoryFilter = null;
        $this->typeFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    /**
     * Mengambil daftar transaksi terpaginasi sesuai filter.
     */
    #[Computed]
    public function transactions(): LengthAwarePaginator
    {
        return Transaction::query()
            ->with([
                'wallet' => fn ($q) => $q->withTrashed(),
                'counterpartWallet' => fn ($q) => $q->withTrashed(),
                'category',
            ])
            ->where('user_id', Auth::id())
            ->when($this->search !== '', function ($query): void {
                $term = '%' . trim($this->search) . '%';
                $query->where(function ($sub) use ($term): void {
                    $sub->where('description', 'like', $term)
                        ->orWhere('transaction_code', 'like', $term)
                        ->orWhere('notes', 'like', $term)
                        ->orWhere('wallet_name_snapshot', 'like', $term)
                        ->orWhere('counterpart_wallet_name_snapshot', 'like', $term);
                });
            })
            ->when($this->walletFilter !== null, function ($query): void {
                $query->where('wallet_id', $this->walletFilter);
            })
            ->when($this->categoryFilter !== null, function ($query): void {
                $query->where('category_id', $this->categoryFilter);
            })
            ->when($this->typeFilter !== '', function ($query): void {
                if ($this->typeFilter === 'transfer') {
                    $query->whereIn('type', [TransactionTypeEnum::TRANSFER_IN, TransactionTypeEnum::TRANSFER_OUT]);
                } else {
                    $query->where('type', $this->typeFilter);
                }
            })
            ->when($this->dateFrom !== '', function ($query): void {
                $query->whereDate('transaction_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo !== '', function ($query): void {
                $query->whereDate('transaction_date', '<=', $this->dateTo);
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(15);
    }

    /**
     * Menghitung ringkasan hasil filter transaksi saat ini.
     *
     * @return array<string, float|int>
     */
    #[Computed]
    public function filteredSummary(): array
    {
        $baseQuery = Transaction::query()
            ->where('user_id', Auth::id())
            ->when($this->search !== '', function ($query): void {
                $term = '%' . trim($this->search) . '%';
                $query->where(function ($sub) use ($term): void {
                    $sub->where('description', 'like', $term)
                        ->orWhere('transaction_code', 'like', $term)
                        ->orWhere('notes', 'like', $term)
                        ->orWhere('wallet_name_snapshot', 'like', $term);
                });
            })
            ->when($this->walletFilter !== null, fn ($q) => $q->where('wallet_id', $this->walletFilter))
            ->when($this->categoryFilter !== null, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('transaction_date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('transaction_date', '<=', $this->dateTo));

        $income = (float) (clone $baseQuery)->where('type', TransactionTypeEnum::INCOME)->sum('amount');
        $expense = (float) (clone $baseQuery)->where('type', TransactionTypeEnum::EXPENSE)->sum('amount');
        $transferVolume = (float) (clone $baseQuery)->where('type', TransactionTypeEnum::TRANSFER_OUT)->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'transfer_volume' => $transferVolume,
        ];
    }

    /**
     * Mengambil opsi seluruh kantong (termasuk yang sudah dihapus/ditutup) untuk filter histori global.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function allWalletFilterOptions(): array
    {
        return Wallet::withTrashed()
            ->where('user_id', Auth::id())
            ->orderBy('deleted_at')
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get()
            ->map(function (Wallet $wallet): array {
                $suffix = $wallet->trashed() || $wallet->status === WalletStatusEnum::DELETED
                    ? ' (Telah Ditutup)'
                    : ($wallet->isFrozen() ? ' (Dibekukan)' : '');

                return [
                    'label' => $wallet->name . $suffix,
                    'value' => $wallet->id,
                ];
            })
            ->all();
    }

    /**
     * Mengambil opsi kantong aktif untuk pencatatan pemasukan/pengeluaran baru.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function activeWalletOptions(): array
    {
        return Wallet::query()
            ->where('user_id', Auth::id())
            ->where('status', WalletStatusEnum::ACTIVE)
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
     * Mengambil opsi seluruh kategori untuk filter riwayat.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function allCategoryFilterOptions(): array
    {
        return TransactionCategory::query()
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', Auth::id()))
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(fn (TransactionCategory $cat): array => [
                'label' => $cat->name . ' (' . $cat->type->label() . ')',
                'value' => $cat->id,
            ])
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
     * Mengambil detail transaksi yang dipilih.
     */
    #[Computed]
    public function selectedTransaction(): ?Transaction
    {
        if (! $this->selectedTransactionId) {
            return null;
        }

        return Transaction::query()
            ->with([
                'wallet' => fn ($q) => $q->withTrashed(),
                'counterpartWallet' => fn ($q) => $q->withTrashed(),
                'category',
            ])
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
     * Membuka modal tambah pemasukan.
     */
    public function openIncomeModal(): void
    {
        $this->resetValidation();
        $this->income_amount = '';
        $this->income_description = '';
        $this->income_notes = '';
        $this->income_date = now()->format('Y-m-d\TH:i');
        $this->incomeModal = true;
    }

    /**
     * Menyimpan transaksi pemasukan baru.
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
            'income_wallet_id.required' => 'Pilih kantong tujuan pemasukan.',
            'income_category_id.required' => 'Pilih kategori pemasukan.',
            'income_amount.required' => 'Nominal pemasukan wajib diisi.',
            'income_amount.gt' => 'Nominal pemasukan harus lebih besar dari Rp0.',
            'income_description.required' => 'Keterangan pemasukan wajib diisi.',
        ]);

        $transactionService->recordIncome((int) Auth::id(), [
            'wallet_id' => $validated['income_wallet_id'],
            'category_id' => $validated['income_category_id'],
            'amount' => $validated['income_amount'],
            'transaction_date' => $validated['income_date'],
            'description' => $validated['income_description'],
            'notes' => $validated['income_notes'],
            'idempotency_key' => 'tx-inc-' . Str::uuid()->toString(),
        ]);

        $this->incomeModal = false;
        unset($this->transactions, $this->filteredSummary, $this->activeWalletOptions);
        $this->toast()->success('Pemasukan Disimpan', 'Transaksi pemasukan berhasil dicatat ke kantong.')->send();
    }

    /**
     * Membuka modal catat pengeluaran.
     */
    public function openExpenseModal(): void
    {
        $this->resetValidation();
        $this->expense_amount = '';
        $this->expense_description = '';
        $this->expense_notes = '';
        $this->expense_date = now()->format('Y-m-d\TH:i');
        $this->expenseModal = true;
    }

    /**
     * Menyimpan transaksi pengeluaran baru.
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
            'expense_wallet_id.required' => 'Pilih kantong sumber pengeluaran.',
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
                'idempotency_key' => 'tx-exp-' . Str::uuid()->toString(),
            ]);

            $this->expenseModal = false;
            unset($this->transactions, $this->filteredSummary, $this->activeWalletOptions);
            $this->toast()->success('Pengeluaran Disimpan', 'Transaksi pengeluaran berhasil dicatat.')->send();
        } catch (\DomainException|ValidationException $e) {
            $firstMessage = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal mencatat pengeluaran.')
                : $e->getMessage();
            $this->addError('expense_amount', $firstMessage);
        }
    }
};
