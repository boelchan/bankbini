<?php

declare(strict_types=1);

use App\Enums\TransactionTypeEnum;
use App\Enums\WalletStatusEnum;
use App\Models\RecapKeyword;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new
#[Title('Rekap Seluruh Transaksi')]
class extends Component
{
    use Interactions;

    #[Url(as: 'wallet', except: null)]
    public ?int $walletFilter = null;

    #[Url(as: 'month', except: null)]
    public ?string $monthFilter = null;

    /* Tab Rekap Kategori: Pengeluaran atau Pemasukan */
    public string $categoryRecapTab = 'expense';

    /* State Modal Kata Kunci */
    public bool $keywordModal = false;

    /* Form Kata Kunci Rekap */
    public ?int $editingKeywordId = null;
    public string $keyword_text = '';
    public string|int|float $keyword_budget_limit = '';

    /* State Modal Batas Kategori */
    public bool $categoryBudgetModal = false;
    public ?int $editingCategoryId = null;
    public string $editingCategoryName = '';
    public string|int|float $category_budget_limit = '';

    /* Kantong utama yang menjadi pilihan bawaan filter */
    public ?int $defaultWalletId = null;

    /**
     * Inisialisasi komponen.
     */
    public function mount(): void
    {
        $this->defaultWalletId = Wallet::query()
            ->where('is_primary', true)
            ->value('id');

        $this->monthFilter ??= now()->format('Y-m');

        if ($this->walletFilter === null) {
            $this->walletFilter = $this->defaultWalletId;
        }
    }

    /**
     * Menormalkan filter bulan yang dikosongkan menjadi tanpa filter.
     */
    public function updatedMonthFilter(?string $value): void
    {
        $this->monthFilter = $value === '' ? null : $value;
    }

    /**
     * Mengembalikan filter ke tampilan awal: bulan ini dan kantong utama.
     */
    public function resetFilters(): void
    {
        $this->walletFilter = $this->defaultWalletId;
        $this->monthFilter = now()->format('Y-m');
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
            ->when(filled($this->monthFilter), fn ($q) => $q->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$this->monthFilter]));

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
     * Menampilkan dialog konfirmasi hapus kata kunci.
     */
    public function askDeleteKeyword(int $id): void
    {
        $keyword = RecapKeyword::find($id);

        if (!$keyword) {
            return;
        }

        $this->dialog()
            ->question('Hapus Kata Kunci Ini?', sprintf('Kata kunci "%s" akan dihapus dari rekap.', $keyword->keyword))
            ->confirm('Ya, Hapus', 'deleteKeyword', $id)
            ->cancel('Batal')
            ->send();
    }

    /**
     * Menghapus kata kunci dari database.
     */
    public function deleteKeyword(int $id): void
    {
        RecapKeyword::destroy($id);
        $this->keywordModal = false;
        unset($this->keywordRecap);
        $this->toast()->success('Dihapus!', 'Kata kunci berhasil dihapus.');
    }

    /**
     * Membuka modal pengaturan batas anggaran kategori.
     */
    public function openEditCategoryBudget(int $categoryId): void
    {
        $category = TransactionCategory::find($categoryId);

        if (!$category) {
            return;
        }

        $this->resetValidation('category_budget_limit');
        $this->editingCategoryId = $category->id;
        $this->editingCategoryName = $category->name;
        $this->category_budget_limit = (float) $category->budget_limit > 0 ? (float) $category->budget_limit : '';
        $this->categoryBudgetModal = true;
    }

    /**
     * Menyimpan batas anggaran kategori.
     */
    public function saveCategoryBudget(): void
    {
        $this->validate([
            'category_budget_limit' => ['nullable', 'numeric', 'gte:0'],
        ], [
            'category_budget_limit.gte' => 'Batas anggaran tidak boleh negatif.',
        ]);

        $category = TransactionCategory::find($this->editingCategoryId);

        if (!$category) {
            $this->categoryBudgetModal = false;

            return;
        }

        $category->update([
            'budget_limit' => $this->category_budget_limit ? (float) $this->category_budget_limit : 0,
        ]);

        $this->categoryBudgetModal = false;
        unset($this->categoryExpenseRecap, $this->categoryIncomeRecap);
        $this->toast()->success('Berhasil!', 'Batas kategori berhasil disimpan.')->send();
    }

    /**
     * Rekapitulasi pengeluaran berdasarkan kata kunci pencarian keterangan yang disimpan di DB.
     *
     * @return array<int, array{id: int, keyword: string, amount: float, formatted_amount: string, total_tx: int, limit: float, formatted_limit: string, is_over_limit: bool, over_amount: float, percentage: float}>
     */
    #[Computed]
    public function keywordRecap(): array
    {
        $baseQuery = Transaction::query()
            ->whereIn('type', [TransactionTypeEnum::EXPENSE, TransactionTypeEnum::TRANSFER_OUT])
            ->when($this->walletFilter !== null, fn ($q) => $q->where('wallet_id', $this->walletFilter))
            ->when(filled($this->monthFilter), fn ($q) => $q->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$this->monthFilter]));

        $totalExpense = (float) (clone $baseQuery)->sum('amount');
        $keywords = RecapKeyword::orderBy('keyword')->get();

        return $keywords->map(function (RecapKeyword $kw) use ($baseQuery, $totalExpense): array {
            $term = '%' . trim($kw->keyword) . '%';

            $query = (clone $baseQuery)->where(function ($sub) use ($term): void {
                $sub->where('description', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });

            $amount = (float) (clone $query)->sum('amount');
            $totalTx = (int) (clone $query)->count();
            $limit = (float) $kw->budget_limit;
            $isOverLimit = $limit > 0 && $amount > $limit;
            $overAmount = $isOverLimit ? $amount - $limit : 0.0;
            $percentage = $limit > 0
                ? round(($amount / $limit) * 100, 2)
                : ($totalExpense > 0 ? round(($amount / $totalExpense) * 100, 2) : 0.0);

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
     * Menentukan warna angka aktual pemakaian anggaran.
     *
     * Aturan warna: lebih dari batas = merah, tepat 100% = hijau,
     * kurang dari batas atau tanpa batas = netral (warna teks default).
     *
     * @return array{text: string}
     */
    public function budgetTone(float $usage, float $limit): array
    {
        if ($limit <= 0) {
            return ['text' => 'text-slate-500'];
        }

        $rounded = round($usage, 2);

        if ($rounded > 100.0) {
            return ['text' => 'text-rose-700 font-semibold'];
        }

        if ($rounded === 100.0) {
            return ['text' => 'text-emerald-700 font-semibold'];
        }

        return ['text' => 'text-slate-900'];
    }

    /**
     * Memformat angka persentase tanpa angka nol di belakang koma.
     */
    public function formatPercentage(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }

    /**
     * Rekapitulasi transaksi pengeluaran per kategori.
     *
     * @return array<int, array{category_id: ?int, name: string, icon: string, color: string, amount: float, formatted_amount: string, total_tx: int, percentage: float, limit: float, formatted_limit: string, is_over_limit: bool, over_amount: float, budget_percentage: ?float}>
     */
    #[Computed]
    public function categoryExpenseRecap(): array
    {
        $query = Transaction::query()
            ->where('type', TransactionTypeEnum::EXPENSE)
            ->when($this->walletFilter !== null, fn ($q) => $q->where('wallet_id', $this->walletFilter))
            ->when(filled($this->monthFilter), fn ($q) => $q->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$this->monthFilter]));

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
            $budgetPercentage = $limit > 0 ? round(($amount / $limit) * 100, 2) : null;

            return [
                'category_id' => $row->category?->id,
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
     * @return array<int, array{category_id: ?int, name: string, icon: string, color: string, amount: float, formatted_amount: string, total_tx: int, percentage: float, limit: float, formatted_limit: string, is_over_limit: bool, over_amount: float, budget_percentage: ?float}>
     */
    #[Computed]
    public function categoryIncomeRecap(): array
    {
        $query = Transaction::query()
            ->where('type', TransactionTypeEnum::INCOME)
            ->when($this->walletFilter !== null, fn ($q) => $q->where('wallet_id', $this->walletFilter))
            ->when(filled($this->monthFilter), fn ($q) => $q->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$this->monthFilter]));

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
            $limit = (float) ($row->category?->budget_limit ?? 0);
            $budgetPercentage = $limit > 0 ? round(($amount / $limit) * 100, 2) : null;

            return [
                'category_id' => $row->category?->id,
                'name' => $row->category?->name ?? $row->category_name_snapshot ?? 'Pemasukan Lainnya',
                'icon' => $row->category?->icon ?? 'wallet',
                'color' => $row->category?->color ?? 'emerald',
                'amount' => $amount,
                'formatted_amount' => number_format($amount, 0, ',', '.'),
                'total_tx' => (int) $row->total_tx,
                'percentage' => $percentage,
                'limit' => $limit,
                'formatted_limit' => $limit > 0 ? number_format($limit, 0, ',', '.') : 'Tanpa Batas',
                'is_over_limit' => false,
                'over_amount' => 0.0,
                'budget_percentage' => $budgetPercentage,
            ];
        })->all();
    }

    /**
     * Mengambil opsi seluruh kantong (termasuk yang sudah ditutup) untuk filter rekap.
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
};
?>