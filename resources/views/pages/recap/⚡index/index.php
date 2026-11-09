<?php

declare(strict_types=1);

use App\Enums\CategoryTypeEnum;
use App\Enums\IncomeSourceEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WalletStatusEnum;
use App\Models\RecapKeyword;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Wallet;
use App\Services\TransactionService;
use App\Services\TransferService;
use Carbon\Carbon;
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
#[Title('Rekap Seluruh Transaksi')]
class extends Component
{
    use Interactions;
    use WithPagination;

    /* Filter Riwayat Transaksi */
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'wallet', except: null)]
    public ?int $walletFilter = null;

    #[Url(as: 'type', except: '')]
    public string $typeFilter = '';

    #[Url(as: 'month', except: '')]
    public string $monthFilter = '';

    /* Tab Rekap Kategori: Pengeluaran atau Pemasukan */
    public string $categoryRecapTab = 'expense';

    /* State Modal */
    public bool $incomeModal = false;
    public bool $expenseModal = false;
    public bool $transferModal = false;
    public bool $keywordModal = false;

    /* Form Kata Kunci Rekap */
    public ?int $editingKeywordId = null;
    public string $keyword_text = '';
    public string|int|float $keyword_budget_limit = '';

    /* Form Pemasukan / Tambah Uang */
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

    /* Form Pindah Uang Antar Kantong */
    public ?int $transfer_source_wallet_id = null;
    public ?int $transfer_destination_wallet_id = null;
    public string|int|float $transfer_amount = '';
    public string $transfer_date = '';
    public string $transfer_description = '';

    /**
     * Inisialisasi komponen.
     */
    public function mount(): void
    {
        $now = now()->format('Y-m-d\TH:i');
        $this->income_date = $now;
        $this->expense_date = $now;
        $this->transfer_date = $now;
        $this->monthFilter = now()->format('Y-m');

        $defaultWallet = Wallet::query()
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
     * Reset halaman paginasi saat filter berubah.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'walletFilter', 'typeFilter', 'monthFilter'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Mengatur filter cepat jenis transaksi.
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
        $this->typeFilter = '';
        $this->monthFilter = '';
        $this->resetPage();
    }

    /**
     * Pilihan bulan yang tersedia dari riwayat transaksi.
     *
     * @return array<int, array{label: string, value: string}>
     */
    #[Computed]
    public function monthOptions(): array
    {
        $months = Transaction::query()
            ->whereNotNull('transaction_date')
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
            $date = Carbon::createFromFormat('Y-m', $ym);
            return [
                'label' => $date ? $date->translatedFormat('F Y') : $ym,
                'value' => $ym,
            ];
        })->all();
    }

    /**
     * Ringkasan statistik transaksi keluarga sesuai filter bulan & kantong yang dipilih.
     *
     * @return array{total_income: float, total_expense: float, net_cashflow: float, count: int}
     */
    #[Computed]
    public function recapSummary(): array
    {
        $query = Transaction::query()
            ->when($this->walletFilter !== null, fn ($q) => $q->where('wallet_id', $this->walletFilter))
            ->when($this->monthFilter !== '', fn ($q) => $q->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$this->monthFilter]));

        $income = (float) (clone $query)
            ->where('type', TransactionTypeEnum::INCOME)
            ->sum('amount');

        $expense = (float) (clone $query)
            ->where('type', TransactionTypeEnum::EXPENSE)
            ->sum('amount');

        $count = (int) (clone $query)->count();

        return [
            'total_income' => round($income, 2),
            'total_expense' => round($expense, 2),
            'net_cashflow' => round($income - $expense, 2),
            'count' => $count,
        ];
    }

    /**
     * Membuka modal tambah kata kunci baru.
     */
    public function openAddKeywordModal(): void
    {
        $this->editingKeywordId = null;
        $this->keyword_text = '';
        $this->keyword_budget_limit = '';
        $this->keywordModal = true;
    }

    /**
     * Membuka modal edit kata kunci.
     */
    public function openEditKeywordModal(int $id): void
    {
        $kw = RecapKeyword::findOrFail($id);
        $this->editingKeywordId = $kw->id;
        $this->keyword_text = $kw->keyword;
        $this->keyword_budget_limit = (float) $kw->budget_limit > 0 ? (float) $kw->budget_limit : '';
        $this->keywordModal = true;
    }

    /**
     * Menyimpan kata kunci rekap ke database.
     */
    public function saveKeyword(): void
    {
        $this->validate([
            'keyword_text' => ['required', 'string', 'max:100'],
            'keyword_budget_limit' => ['nullable', 'numeric', 'gte:0'],
        ], [
            'keyword_text.required' => 'Kata kunci wajib diisi.',
            'keyword_budget_limit.gte' => 'Batas atas tidak boleh negatif.',
        ]);

        RecapKeyword::updateOrCreate(
            ['id' => $this->editingKeywordId],
            [
                'user_id' => (int) Auth::id(),
                'keyword' => trim($this->keyword_text),
                'budget_limit' => $this->keyword_budget_limit ? (float) $this->keyword_budget_limit : 0,
            ]
        );

        $this->keywordModal = false;
        unset($this->keywordRecap);
        $this->toast()->success('Berhasil!', 'Kata kunci berhasil disimpan.');
    }

    /**
     * Menghapus kata kunci dari database.
     */
    public function deleteKeyword(int $id): void
    {
        RecapKeyword::destroy($id);
        unset($this->keywordRecap);
        $this->toast()->success('Dihapus!', 'Kata kunci berhasil dihapus.');
    }

    /**
     * Rekapitulasi pengeluaran berdasarkan kata kunci pencarian keterangan yang disimpan di DB.
     *
     * @return array<int, array{id: int, keyword: string, amount: float, formatted_amount: string, total_tx: int, limit: float, formatted_limit: string, is_over_limit: bool, over_amount: float, percentage: ?float}>
     */
    #[Computed]
    public function keywordRecap(): array
    {
        $keywords = RecapKeyword::orderBy('keyword')->get();

        return $keywords->map(function (RecapKeyword $kw): array {
            $term = '%' . trim($kw->keyword) . '%';

            $query = Transaction::query()
                ->whereIn('type', [TransactionTypeEnum::EXPENSE, TransactionTypeEnum::TRANSFER_OUT])
                ->when($this->walletFilter !== null, fn ($q) => $q->where('wallet_id', $this->walletFilter))
                ->when($this->monthFilter !== '', fn ($q) => $q->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$this->monthFilter]))
                ->where(function ($sub) use ($term): void {
                    $sub->where('description', 'like', $term)
                        ->orWhere('notes', 'like', $term);
                });

            $amount = (float) (clone $query)->sum('amount');
            $totalTx = (int) (clone $query)->count();
            $limit = (float) $kw->budget_limit;
            $isOverLimit = $limit > 0 && $amount > $limit;
            $overAmount = $isOverLimit ? $amount - $limit : 0.0;
            $percentage = $limit > 0 ? round(($amount / $limit) * 100, 1) : null;

            return [
                'id' => $kw->id,
                'keyword' => $kw->keyword,
                'amount' => $amount,
                'formatted_amount' => number_format($amount, 0, ',', '.'),
                'total_tx' => $totalTx,
                'limit' => $limit,
                'formatted_limit' => $kw->formattedLimit(),
                'is_over_limit' => $isOverLimit,
                'over_amount' => $overAmount,
                'percentage' => $percentage,
            ];
        })->all();
    }

    /**
     * Rekapitulasi transaksi pengeluaran per kategori.
     *
     * @return array<int, array{name: string, icon: string, color: string, amount: float, formatted_amount: string, total_tx: int, percentage: float, limit: float, formatted_limit: string, is_over_limit: bool, over_amount: float, budget_percentage: ?float}>
     */
    #[Computed]
    public function categoryExpenseRecap(): array
    {
        $query = Transaction::query()
            ->where('type', TransactionTypeEnum::EXPENSE)
            ->when($this->walletFilter !== null, fn ($q) => $q->where('wallet_id', $this->walletFilter))
            ->when($this->monthFilter !== '', fn ($q) => $q->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$this->monthFilter]));

        $totalExpense = (float) (clone $query)->sum('amount');

        $rows = (clone $query)
            ->with('category')
            ->selectRaw('category_id, category_name_snapshot, SUM(amount) as total_amount, COUNT(id) as total_tx')
            ->groupBy('category_id', 'category_name_snapshot')
            ->orderByDesc('total_amount')
            ->get();

        return $rows->map(function ($row) use ($totalExpense) {
            $amount = (float) $row->total_amount;
            $percentage = $totalExpense > 0 ? round(($amount / $totalExpense) * 100, 1) : 0.0;
            $limit = (float) ($row->category?->budget_limit ?? 0);
            $isOverLimit = $limit > 0 && $amount > $limit;
            $overAmount = $isOverLimit ? $amount - $limit : 0.0;
            $budgetPercentage = $limit > 0 ? round(($amount / $limit) * 100, 1) : null;

            return [
                'name' => $row->category?->name ?? $row->category_name_snapshot ?? 'Lainnya',
                'icon' => $row->category?->icon ?? 'shopping-bag',
                'color' => $row->category?->color ?? 'rose',
                'amount' => $amount,
                'formatted_amount' => number_format($amount, 0, ',', '.'),
                'total_tx' => (int) $row->total_tx,
                'percentage' => $percentage,
                'limit' => $limit,
                'formatted_limit' => $limit > 0 ? number_format($limit, 0, ',', '.') : 'Tanpa Batas',
                'is_over_limit' => $isOverLimit,
                'over_amount' => $overAmount,
                'budget_percentage' => $budgetPercentage,
            ];
        })->all();
    }

    /**
     * Rekapitulasi transaksi pemasukan per kategori.
     *
     * @return array<int, array{name: string, icon: string, color: string, amount: float, formatted_amount: string, total_tx: int, percentage: float}>
     */
    #[Computed]
    public function categoryIncomeRecap(): array
    {
        $query = Transaction::query()
            ->where('type', TransactionTypeEnum::INCOME)
            ->when($this->walletFilter !== null, fn ($q) => $q->where('wallet_id', $this->walletFilter))
            ->when($this->monthFilter !== '', fn ($q) => $q->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$this->monthFilter]));

        $totalIncome = (float) (clone $query)->sum('amount');

        $rows = (clone $query)
            ->with('category')
            ->selectRaw('category_id, category_name_snapshot, SUM(amount) as total_amount, COUNT(id) as total_tx')
            ->groupBy('category_id', 'category_name_snapshot')
            ->orderByDesc('total_amount')
            ->get();

        return $rows->map(function ($row) use ($totalIncome) {
            $amount = (float) $row->total_amount;
            $percentage = $totalIncome > 0 ? round(($amount / $totalIncome) * 100, 1) : 0.0;

            return [
                'name' => $row->category?->name ?? $row->category_name_snapshot ?? 'Pemasukan Lainnya',
                'icon' => $row->category?->icon ?? 'wallet',
                'color' => $row->category?->color ?? 'emerald',
                'amount' => $amount,
                'formatted_amount' => number_format($amount, 0, ',', '.'),
                'total_tx' => (int) $row->total_tx,
                'percentage' => $percentage,
            ];
        })->all();
    }

    /**
     * Mengambil daftar transaksi terpaginasi sesuai filter.
     *
     * @return LengthAwarePaginator<Transaction>
     */
    #[Computed]
    public function transactions(): LengthAwarePaginator
    {
        return Transaction::query()
            ->with([
                'user',
                'wallet' => fn ($q) => $q->withTrashed(),
                'counterpartWallet' => fn ($q) => $q->withTrashed(),
                'category',
            ])
            ->when($this->search !== '', function ($query): void {
                $term = '%' . trim($this->search) . '%';
                $query->where(function ($sub) use ($term): void {
                    $sub->where('description', 'like', $term)
                        ->orWhere('notes', 'like', $term)
                        ->orWhere('wallet_name_snapshot', 'like', $term)
                        ->orWhere('counterpart_wallet_name_snapshot', 'like', $term);
                });
            })
            ->when($this->walletFilter !== null, function ($query): void {
                $query->where('wallet_id', $this->walletFilter);
            })
            ->when($this->typeFilter !== '', function ($query): void {
                if ($this->typeFilter === 'transfer') {
                    $query->whereIn('type', [TransactionTypeEnum::TRANSFER_IN, TransactionTypeEnum::TRANSFER_OUT]);
                } else {
                    $query->where('type', $this->typeFilter);
                }
            })
            ->when($this->monthFilter !== '', function ($query): void {
                $query->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$this->monthFilter]);
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(15);
    }

    /**
     * Mengambil opsi seluruh kantong (termasuk yang sudah ditutup) untuk filter riwayat.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function allWalletFilterOptions(): array
    {
        return Wallet::withTrashed()
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
     * Mengambil opsi kantong aktif untuk pencatatan transaksi baru.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function activeWalletOptions(): array
    {
        return Wallet::query()
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
     * Mengambil opsi kantong aktif selain kantong tujuan pemasukan.
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
     * Membuka modal tambah pemasukan baru.
     */
    public function openIncomeModal(): void
    {
        $this->income_source_type = 'direct';
        $this->income_source_wallet_id = null;
        $this->income_category_id = null;
        $this->income_amount = '';
        $this->income_date = now()->format('Y-m-d\TH:i');
        $this->income_description = '';
        $this->incomeModal = true;
    }

    /**
     * Menyimpan transaksi pemasukan baru.
     */
    public function saveIncome(
        TransactionService $transactionService,
        TransferService $transferService
    ): void {
        $rules = [
            'income_wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'income_source_type' => ['required', 'string'],
            'income_amount' => ['required', 'numeric', 'gt:0'],
            'income_date' => ['required', 'date'],
            'income_description' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->income_source_type === IncomeSourceEnum::FROM_POCKET->value) {
            $rules['income_source_wallet_id'] = ['required', 'integer', 'exists:wallets,id', 'different:income_wallet_id'];
        } else {
            $rules['income_category_id'] = ['nullable', 'integer', 'exists:transaction_categories,id'];
        }

        $this->validate($rules, [
            'income_wallet_id.required' => 'Pilih kantong tujuan terlebih dahulu.',
            'income_source_wallet_id.required' => 'Pilih kantong sumber dana.',
            'income_source_wallet_id.different' => 'Kantong asal dan tujuan tidak boleh sama.',
            'income_amount.required' => 'Nominal uang wajib diisi.',
            'income_amount.gt' => 'Nominal uang harus lebih dari Rp0.',
            'income_date.required' => 'Tanggal transaksi wajib diisi.',
        ]);

        try {
            if ($this->income_source_type === IncomeSourceEnum::FROM_POCKET->value) {
                $transferService->executeTransfer([
                    'user_id' => (int) Auth::id(),
                    'source_wallet_id' => (int) $this->income_source_wallet_id,
                    'destination_wallet_id' => (int) $this->income_wallet_id,
                    'amount' => (float) $this->income_amount,
                    'description' => $this->income_description ?: sprintf('Terima dari %s', Wallet::find($this->income_source_wallet_id)?->name ?? 'Kantong Lain'),
                    'transaction_date' => $this->income_date,
                    'idempotency_key' => 'trf-in-' . (string) Str::uuid(),
                ]);
            } else {
                $transactionService->createIncomeTransaction([
                    'user_id' => (int) Auth::id(),
                    'wallet_id' => (int) $this->income_wallet_id,
                    'category_id' => $this->income_category_id ? (int) $this->income_category_id : null,
                    'amount' => (float) $this->income_amount,
                    'description' => $this->income_description ?: 'Pemasukan ke ' . Wallet::find($this->income_wallet_id)?->name,
                    'transaction_date' => $this->income_date,
                    'idempotency_key' => 'inc-' . (string) Str::uuid(),
                ]);
            }

            $this->incomeModal = false;
            unset($this->transactions);
            unset($this->recapSummary);
            unset($this->categoryExpenseRecap);
            unset($this->categoryIncomeRecap);
            $this->toast()->success('Berhasil!', 'Pemasukan berhasil dicatat.');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'income_amount' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Membuka modal catat pengeluaran baru.
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
     * Menyimpan transaksi pengeluaran baru.
     */
    public function saveExpense(TransactionService $transactionService): void
    {
        $this->validate([
            'expense_wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'expense_category_id' => ['nullable', 'integer', 'exists:transaction_categories,id'],
            'expense_amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['required', 'date'],
            'expense_description' => ['nullable', 'string', 'max:255'],
        ], [
            'expense_wallet_id.required' => 'Pilih kantong sumber pengeluaran.',
            'expense_amount.required' => 'Nominal pengeluaran wajib diisi.',
            'expense_amount.gt' => 'Nominal pengeluaran harus lebih dari Rp0.',
            'expense_date.required' => 'Tanggal transaksi wajib diisi.',
        ]);

        try {
            $transactionService->createExpenseTransaction([
                'user_id' => (int) Auth::id(),
                'wallet_id' => (int) $this->expense_wallet_id,
                'category_id' => $this->expense_category_id ? (int) $this->expense_category_id : null,
                'amount' => (float) $this->expense_amount,
                'description' => $this->expense_description ?: 'Pengeluaran dari ' . Wallet::find($this->expense_wallet_id)?->name,
                'transaction_date' => $this->expense_date,
                'idempotency_key' => 'exp-' . (string) Str::uuid(),
            ]);

            $this->expenseModal = false;
            unset($this->transactions);
            unset($this->recapSummary);
            unset($this->categoryExpenseRecap);
            unset($this->categoryIncomeRecap);
            $this->toast()->success('Berhasil!', 'Pengeluaran berhasil dicatat.');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'expense_amount' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Membuka modal pindah uang antar kantong.
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
     * Menyimpan pemindahan saldo antar kantong.
     */
    public function saveTransfer(TransferService $transferService): void
    {
        $this->validate([
            'transfer_source_wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'transfer_destination_wallet_id' => ['required', 'integer', 'exists:wallets,id', 'different:transfer_source_wallet_id'],
            'transfer_amount' => ['required', 'numeric', 'gt:0'],
            'transfer_date' => ['required', 'date'],
            'transfer_description' => ['nullable', 'string', 'max:255'],
        ], [
            'transfer_source_wallet_id.required' => 'Pilih kantong asal.',
            'transfer_destination_wallet_id.required' => 'Pilih kantong tujuan.',
            'transfer_destination_wallet_id.different' => 'Kantong asal dan tujuan harus berbeda.',
            'transfer_amount.required' => 'Nominal pindah uang wajib diisi.',
            'transfer_amount.gt' => 'Nominal pindah uang harus lebih dari Rp0.',
            'transfer_date.required' => 'Tanggal transaksi wajib diisi.',
        ]);

        try {
            $transferService->executeTransfer([
                'user_id' => (int) Auth::id(),
                'source_wallet_id' => (int) $this->transfer_source_wallet_id,
                'destination_wallet_id' => (int) $this->transfer_destination_wallet_id,
                'amount' => (float) $this->transfer_amount,
                'description' => $this->transfer_description ?: sprintf(
                    'Pindah ke %s',
                    Wallet::find($this->transfer_destination_wallet_id)?->name ?? 'Kantong Lain'
                ),
                'transaction_date' => $this->transfer_date,
                'idempotency_key' => 'trf-' . (string) Str::uuid(),
            ]);

            $this->transferModal = false;
            unset($this->transactions);
            unset($this->recapSummary);
            unset($this->categoryExpenseRecap);
            unset($this->categoryIncomeRecap);
            $this->toast()->success('Berhasil!', 'Saldo berhasil dipindahkan antar kantong.');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'transfer_amount' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Konfirmasi dialog hapus transaksi.
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
     * Eksekusi hapus transaksi.
     */
    public function confirmDeleteTransaction(int $transactionId, TransactionService $transactionService): void
    {
        try {
            $transaction = Transaction::query()->findOrFail($transactionId);
            $transactionService->deleteTransaction($transaction);

            unset($this->transactions);
            unset($this->recapSummary);
            unset($this->categoryExpenseRecap);
            unset($this->categoryIncomeRecap);
            $this->toast()->success('Dihapus!', 'Transaksi berhasil dibatalkan.');
        } catch (\Throwable $e) {
            $this->toast()->error('Gagal!', $e->getMessage());
        }
    }

    /**
     * Opsi kategori pengeluaran.
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
            ->map(fn (TransactionCategory $c): array => [
                'label' => $c->name,
                'value' => $c->id,
            ])
            ->all();
    }

    /**
     * Opsi kategori pemasukan.
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
            ->map(fn (TransactionCategory $c): array => [
                'label' => $c->name,
                'value' => $c->id,
            ])
            ->all();
    }
};
?>