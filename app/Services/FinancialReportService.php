<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionTypeEnum;
use App\Enums\WalletStatusEnum;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;

class FinancialReportService
{
    /**
     * Mengambil ringkasan metrik utama dashboard untuk pengguna.
     * Transfer antar kantong tidak dihitung sebagai pemasukan atau pengeluaran keseluruhan.
     *
     * @return array{
     *     total_balance: float,
     *     active_wallets_count: int,
     *     frozen_wallets_count: int,
     *     monthly_income: float,
     *     monthly_expense: float,
     *     monthly_net: float
     * }
     */
    public function getDashboardSummary(int $userId, ?Carbon $referenceDate = null): array
    {
        $now = $referenceDate ?? Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $wallets = Wallet::query()
            ->where('user_id', $userId)
            ->visible()
            ->get(['id', 'balance', 'status']);

        $totalBalance = round((float) $wallets->sum('balance'), 2);
        $activeCount = $wallets->where('status', WalletStatusEnum::ACTIVE)->count();
        $frozenCount = $wallets->where('status', WalletStatusEnum::FROZEN)->count();

        $monthlyIncome = round((float) Transaction::query()
            ->where('user_id', $userId)
            ->where('type', TransactionTypeEnum::INCOME->value)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount'), 2);

        $monthlyExpense = round((float) Transaction::query()
            ->where('user_id', $userId)
            ->where('type', TransactionTypeEnum::EXPENSE->value)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount'), 2);

        return [
            'total_balance' => $totalBalance,
            'active_wallets_count' => $activeCount,
            'frozen_wallets_count' => $frozenCount,
            'monthly_income' => $monthlyIncome,
            'monthly_expense' => $monthlyExpense,
            'monthly_net' => round($monthlyIncome - $monthlyExpense, 2),
        ];
    }

    /**
     * Mengambil data grafik arus kas (Pemasukan vs Pengeluaran) untuk 6 bulan terakhir.
     *
     * @return array{
     *     labels: array<int, string>,
     *     income: array<int, float>,
     *     expense: array<int, float>
     * }
     */
    public function getMonthlyCashflowTrend(int $userId, int $months = 6, ?Carbon $endDate = null): array
    {
        $end = ($endDate ?? Carbon::now())->copy()->endOfMonth();
        $start = $end->copy()->subMonths($months - 1)->startOfMonth();

        $transactions = Transaction::query()
            ->where('user_id', $userId)
            ->whereIn('type', [TransactionTypeEnum::INCOME->value, TransactionTypeEnum::EXPENSE->value])
            ->whereBetween('transaction_date', [$start, $end])
            ->get(['type', 'amount', 'transaction_date']);

        $labels = [];
        $incomeSeries = [];
        $expenseSeries = [];

        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthPoint = $end->copy()->subMonths($i);
            $ymKey = $monthPoint->format('Y-m');
            $labels[] = $monthNames[(int) $monthPoint->format('n')] . ' ' . $monthPoint->format('y');

            $monthItems = $transactions->filter(
                fn (Transaction $t): bool => $t->transaction_date->format('Y-m') === $ymKey
            );

            $incomeSeries[] = round((float) $monthItems
                ->where('type', TransactionTypeEnum::INCOME)
                ->sum('amount'), 2);

            $expenseSeries[] = round((float) $monthItems
                ->where('type', TransactionTypeEnum::EXPENSE)
                ->sum('amount'), 2);
        }

        return [
            'labels' => $labels,
            'income' => $incomeSeries,
            'expense' => $expenseSeries,
        ];
    }

    /**
     * Mengambil data grafik arus kas 6 bulan dalam format series TallStackUI Chart.
     *
     * @return array{labels: array<int, string>, series: array<int, array{name: string, data: array<int, float>, color: string}>}
     */
    public function getMonthlyCashflowSeries(int $userId, int $months = 6, ?Carbon $endDate = null): array
    {
        $trend = $this->getMonthlyCashflowTrend($userId, $months, $endDate);

        return [
            'labels' => $trend['labels'],
            'series' => [
                [
                    'name' => 'Pemasukan',
                    'data' => $trend['income'],
                    'color' => '#10b981',
                ],
                [
                    'name' => 'Pengeluaran',
                    'data' => $trend['expense'],
                    'color' => '#f43f5e',
                ],
            ],
        ];
    }

    /**
     * Menghasilkan struktur laporan lengkap untuk halaman Laporan Keuangan.
     *
     * @return array<string, mixed>
     */
    public function generateReport(int $userId, string $dateFrom, string $dateTo): array
    {
        $start = $dateFrom !== '' ? Carbon::parse($dateFrom)->startOfDay() : Carbon::now()->startOfMonth();
        $end = $dateTo !== '' ? Carbon::parse($dateTo)->endOfDay() : Carbon::now()->endOfMonth();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $period = $this->getPeriodReport($userId, $start, $end);
        $totalBalance = app(WalletService::class)->getTotalBalance($userId);

        return [
            'summary' => [
                'total_income' => $period['total_income'],
                'total_expense' => $period['total_expense'],
                'net_cashflow' => $period['net_cashflow'],
                'transfer_volume' => $period['total_transfer_volume'],
                'savings_rate' => $period['savings_rate'],
                'total_balance' => $totalBalance,
            ],
            'expense_by_category' => $period['expense_by_category'],
            'income_by_category' => $period['income_by_category'],
            'per_wallet_recap' => $period['wallet_recap'],
        ];
    }

    /**
     * Mengambil laporan keuangan komprehensif berdasarkan rentang tanggal.
     * Transfer internal tidak dimasukkan dalam total pemasukan/pengeluaran keseluruhan.
     *
     * @return array{
     *     total_income: float,
     *     total_expense: float,
     *     net_cashflow: float,
     *     total_transfer_volume: float,
     *     savings_rate: float,
     *     expense_by_category: array<int, array{category_name: string, total: float, percentage: float, count: int}>,
     *     income_by_category: array<int, array{category_name: string, total: float, percentage: float, count: int}>,
     *     wallet_recap: array<int, array{
     *         wallet_id: int,
     *         wallet_name: string,
     *         wallet_color: string,
     *         wallet_icon: string,
     *         status_label: string,
     *         current_balance: float,
     *         income: float,
     *         expense: float,
     *         transfer_in: float,
     *         transfer_out: float,
     *         tx_count: int
     *     }>
     * }
     */
    public function getPeriodReport(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();

        $transactions = Transaction::query()
            ->with(['wallet', 'category'])
            ->where('user_id', $userId)
            ->whereBetween('transaction_date', [$start, $end])
            ->orderBy('transaction_date', 'desc')
            ->get();

        $totalIncome = round((float) $transactions
            ->where('type', TransactionTypeEnum::INCOME)
            ->sum('amount'), 2);

        $totalExpense = round((float) $transactions
            ->where('type', TransactionTypeEnum::EXPENSE)
            ->sum('amount'), 2);

        $totalTransferVolume = round((float) $transactions
            ->where('type', TransactionTypeEnum::TRANSFER_OUT)
            ->sum('amount'), 2);

        $netCashflow = round($totalIncome - $totalExpense, 2);
        $savingsRate = $totalIncome > 0
            ? round(max(0, ($netCashflow / $totalIncome) * 100), 1)
            : 0.0;

        // Pengeluaran per kategori
        $expenseByCategory = [];
        $expenseGroups = $transactions
            ->where('type', TransactionTypeEnum::EXPENSE)
            ->groupBy(fn (Transaction $t): string => $t->displayCategoryName());

        foreach ($expenseGroups as $catName => $items) {
            $sum = round((float) $items->sum('amount'), 2);
            $expenseByCategory[] = [
                'category_name' => (string) $catName,
                'total' => $sum,
                'percentage' => $totalExpense > 0 ? round(($sum / $totalExpense) * 100, 1) : 0.0,
                'count' => $items->count(),
            ];
        }
        usort($expenseByCategory, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        // Pemasukan per kategori
        $incomeByCategory = [];
        $incomeGroups = $transactions
            ->where('type', TransactionTypeEnum::INCOME)
            ->groupBy(fn (Transaction $t): string => $t->displayCategoryName());

        foreach ($incomeGroups as $catName => $items) {
            $sum = round((float) $items->sum('amount'), 2);
            $incomeByCategory[] = [
                'category_name' => (string) $catName,
                'total' => $sum,
                'percentage' => $totalIncome > 0 ? round(($sum / $totalIncome) * 100, 1) : 0.0,
                'count' => $items->count(),
            ];
        }
        usort($incomeByCategory, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        // Rekap per kantong (seluruh kantong aktif/dibekukan + kantong dihapus yang punya transaksi pada periode ini)
        $allWallets = Wallet::query()
            ->withTrashed()
            ->where('user_id', $userId)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'frozen' THEN 1 ELSE 2 END")
            ->orderBy('id')
            ->get();

        $walletRecap = [];
        foreach ($allWallets as $wallet) {
            $walletTxs = $transactions->where('wallet_id', $wallet->id);

            if ($wallet->isRemoved() && $walletTxs->isEmpty()) {
                continue;
            }

            $walletRecap[] = [
                'wallet_id' => $wallet->id,
                'wallet_name' => $wallet->name,
                'wallet_color' => $wallet->color,
                'wallet_icon' => $wallet->icon,
                'status' => $wallet->status,
                'status_label' => $wallet->status->label(),
                'current_balance' => round((float) $wallet->balance, 2),
                'income' => round((float) $walletTxs->where('type', TransactionTypeEnum::INCOME)->sum('amount'), 2),
                'expense' => round((float) $walletTxs->where('type', TransactionTypeEnum::EXPENSE)->sum('amount'), 2),
                'transfer_in' => round((float) $walletTxs->where('type', TransactionTypeEnum::TRANSFER_IN)->sum('amount'), 2),
                'transfer_out' => round((float) $walletTxs->where('type', TransactionTypeEnum::TRANSFER_OUT)->sum('amount'), 2),
                'tx_count' => $walletTxs->count(),
            ];
        }

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_cashflow' => $netCashflow,
            'total_transfer_volume' => $totalTransferVolume,
            'savings_rate' => $savingsRate,
            'expense_by_category' => $expenseByCategory,
            'income_by_category' => $incomeByCategory,
            'wallet_recap' => $walletRecap,
        ];
    }
}
