<?php

declare(strict_types=1);

use App\Enums\TransactionTypeEnum;
use App\Enums\WalletStatusEnum;
use App\Models\RecapKeyword;
use App\Models\Transaction;
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

    #[Url(as: 'month', except: '')]
    public string $monthFilter = '';

    /* Tab Rekap Kategori: Pengeluaran atau Pemasukan */
    public string $categoryRecapTab = 'expense';

    /* State Modal Kata Kunci */
    public bool $keywordModal = false;

    /* Form Kata Kunci Rekap */
    public ?int $editingKeywordId = null;
    public string $keyword_text = '';
    public string|int|float $keyword_budget_limit = '';

    /**
     * Inisialisasi komponen.
     */
    public function mount(): void
    {
        $this->monthFilter = now()->format('Y-m');
    }

    /**
     * Mengatur ulang filter rekap.
     */
    public function resetFilters(): void
    {
        $this->walletFilter = null;
        $this->monthFilter = '';
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