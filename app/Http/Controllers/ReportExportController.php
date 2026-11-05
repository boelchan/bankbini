<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function __construct(
        private readonly FinancialReportService $reportService,
    ) {}

    /**
     * Mengunduh laporan keuangan dan rincian transaksi dalam format Excel (.xlsx).
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $userId = (int) Auth::id();
        [$startDate, $endDate] = $this->resolvePeriod($request);

        $report = $this->reportService->getPeriodReport($userId, $startDate, $endDate);

        $transactions = Transaction::query()
            ->with(['wallet', 'category'])
            ->where('user_id', $userId)
            ->whereBetween('transaction_date', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $fileName = sprintf(
            'Laporan-Bank-BINI-%s-sd-%s.xlsx',
            $startDate->format('Ymd'),
            $endDate->format('Ymd')
        );

        $writer = SimpleExcelWriter::streamDownload($fileName);

        foreach ($transactions as $index => $tx) {
            $writer->addRow([
                'No' => $index + 1,
                'Kode Transaksi' => $tx->transaction_code,
                'Tanggal & Waktu' => $tx->transaction_date->format('d/m/Y H:i'),
                'Jenis Transaksi' => $tx->type->label(),
                'Kantong' => $tx->displayWalletName(),
                'Kategori' => $tx->displayCategoryName(),
                'Keterangan' => $tx->description,
                'Catatan' => $tx->notes ?? '-',
                'Nominal (Rp)' => (float) $tx->amount,
                'Saldo Sebelum (Rp)' => (float) $tx->balance_before,
                'Saldo Sesudah (Rp)' => (float) $tx->balance_after,
                'Referensi Transfer' => $tx->transfer_reference ?? '-',
            ]);
        }

        if ($transactions->isEmpty()) {
            $writer->addRow([
                'No' => '-',
                'Kode Transaksi' => '-',
                'Tanggal & Waktu' => $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'),
                'Jenis Transaksi' => 'Tidak ada transaksi pada periode ini',
                'Kantong' => '-',
                'Kategori' => '-',
                'Keterangan' => '-',
                'Catatan' => '-',
                'Nominal (Rp)' => 0,
                'Saldo Sebelum (Rp)' => 0,
                'Saldo Sesudah (Rp)' => 0,
                'Referensi Transfer' => '-',
            ]);
        }

        // Baris ringkasan total di bagian bawah
        $writer->addRow([
            'No' => '',
            'Kode Transaksi' => 'RINGKASAN PERIODE',
            'Tanggal & Waktu' => $startDate->format('d/m/Y') . ' s/d ' . $endDate->format('d/m/Y'),
            'Jenis Transaksi' => 'Total Pemasukan: Rp' . number_format($report['total_income'], 0, ',', '.'),
            'Kantong' => 'Total Pengeluaran: Rp' . number_format($report['total_expense'], 0, ',', '.'),
            'Kategori' => 'Selisih Bersih: Rp' . number_format($report['net_cashflow'], 0, ',', '.'),
            'Keterangan' => 'Transfer antar kantong tidak dihitung sebagai pemasukan/pengeluaran keseluruhan',
            'Catatan' => '',
            'Nominal (Rp)' => $report['net_cashflow'],
            'Saldo Sebelum (Rp)' => '',
            'Saldo Sesudah (Rp)' => '',
            'Referensi Transfer' => '',
        ]);

        return $writer->toBrowser();
    }

    /**
     * Menampilkan halaman laporan keuangan siap cetak / simpan sebagai PDF.
     */
    public function exportPdf(Request $request): View
    {
        $userId = (int) Auth::id();
        [$startDate, $endDate] = $this->resolvePeriod($request);

        $report = $this->reportService->getPeriodReport($userId, $startDate, $endDate);

        $transactions = Transaction::query()
            ->with(['wallet', 'category'])
            ->where('user_id', $userId)
            ->whereBetween('transaction_date', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('reports.print-pdf', [
            'user' => Auth::user(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'report' => $report,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Menentukan rentang tanggal awal dan akhir dari parameter query.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(Request $request): array
    {
        $startParam = $request->query('from', $request->query('start_date'));
        $endParam = $request->query('to', $request->query('end_date'));

        try {
            $start = !empty($startParam)
                ? Carbon::parse((string) $startParam)->startOfDay()
                : Carbon::now()->startOfMonth();
        } catch (\Throwable) {
            $start = Carbon::now()->startOfMonth();
        }

        try {
            $end = !empty($endParam)
                ? Carbon::parse((string) $endParam)->endOfDay()
                : Carbon::now()->endOfMonth();
        } catch (\Throwable) {
            $end = Carbon::now()->endOfMonth();
        }

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }
}
