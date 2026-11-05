<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan Bank BINI ({{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }})</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        @media print {
            body {
                background-color: #ffffff;
            }
            .no-print {
                display: none !important;
            }
            .print-sheet {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body class="p-4 sm:p-8">
    <div class="max-w-5xl mx-auto mb-4 flex flex-wrap items-center justify-between gap-3 no-print">
        <a href="{{ route('reports.index', ['date_from' => $startDate->format('Y-m-d'), 'date_to' => $endDate->format('Y-m-d')]) }}"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-xs">
            <span>&larr; Kembali ke Laporan</span>
        </a>
        <button type="button" onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 shadow-xs cursor-pointer">
            <span>Cetak / Simpan sebagai PDF</span>
        </button>
    </div>

    <div class="print-sheet max-w-5xl mx-auto bg-white rounded-2xl border border-slate-200 p-6 sm:p-10 shadow-xs space-y-6">
        {{-- Header Laporan --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold mb-2">
                    <span>Bank BINI &bull; Dompet Keuangan Keluarga</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Laporan Keuangan & Rekapitulasi Kantong</h1>
                <p class="text-sm text-slate-600 mt-0.5">
                    Periode: <strong>{{ $startDate->translatedFormat('d F Y') }}</strong> s/d <strong>{{ $endDate->translatedFormat('d F Y') }}</strong>
                </p>
            </div>
            <div class="text-left sm:text-right text-xs text-slate-600 space-y-1">
                <div>Pemilik Dompet: <strong class="text-slate-900">{{ $user->name }}</strong></div>
                <div>Email: {{ $user->email }}</div>
                <div>Dicetak pada: {{ now()->translatedFormat('d M Y, H:i') }} WIB</div>
            </div>
        </div>

        {{-- Ringkasan Arus Kas --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4">
                <div class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Total Pemasukan</div>
                <div class="mt-1 text-xl font-bold text-emerald-950 tabular-nums">
                    Rp{{ number_format($report['total_income'], 0, ',', '.') }}
                </div>
            </div>
            <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4">
                <div class="text-xs font-semibold uppercase tracking-wider text-rose-800">Total Pengeluaran</div>
                <div class="mt-1 text-xl font-bold text-rose-950 tabular-nums">
                    Rp{{ number_format($report['total_expense'], 0, ',', '.') }}
                </div>
            </div>
            <div class="rounded-xl border border-sky-200 bg-sky-50/70 p-4">
                <div class="text-xs font-semibold uppercase tracking-wider text-sky-800">Selisih Bersih (Net)</div>
                <div class="mt-1 text-xl font-bold {{ $report['net_cashflow'] >= 0 ? 'text-emerald-900' : 'text-rose-900' }} tabular-nums">
                    {{ $report['net_cashflow'] >= 0 ? '+' : '-' }}Rp{{ number_format(abs($report['net_cashflow']), 0, ',', '.') }}
                </div>
            </div>
        </div>

        <p class="text-xs text-slate-500">
            Catatan: Transfer antar kantong sebesar Rp{{ number_format($report['total_transfer_volume'], 0, ',', '.') }} merupakan perpindahan dana internal sehingga tidak dihitung dalam total pemasukan atau pengeluaran keseluruhan.
        </p>

        {{-- Rekapitulasi Per Kantong --}}
        <div class="space-y-2.5">
            <h2 class="text-sm font-bold text-slate-900">1. Rekapitulasi Saldo & Mutasi per Kantong</h2>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                            <th class="p-2.5">Nama Kantong</th>
                            <th class="p-2.5">Status</th>
                            <th class="p-2.5 text-right">Pemasukan</th>
                            <th class="p-2.5 text-right">Pengeluaran</th>
                            <th class="p-2.5 text-right">Transfer Masuk</th>
                            <th class="p-2.5 text-right">Transfer Keluar</th>
                            <th class="p-2.5 text-right">Saldo Saat Ini</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($report['wallet_recap'] as $row)
                            <tr>
                                <td class="p-2.5 font-semibold text-slate-900">{{ $row['wallet_name'] }}</td>
                                <td class="p-2.5 text-slate-700">{{ $row['status_label'] }}</td>
                                <td class="p-2.5 text-right text-emerald-700 tabular-nums">+Rp{{ number_format($row['income'], 0, ',', '.') }}</td>
                                <td class="p-2.5 text-right text-rose-700 tabular-nums">-Rp{{ number_format($row['expense'], 0, ',', '.') }}</td>
                                <td class="p-2.5 text-right text-sky-700 tabular-nums">+Rp{{ number_format($row['transfer_in'], 0, ',', '.') }}</td>
                                <td class="p-2.5 text-right text-amber-700 tabular-nums">-Rp{{ number_format($row['transfer_out'], 0, ',', '.') }}</td>
                                <td class="p-2.5 text-right font-bold text-slate-900 tabular-nums">Rp{{ number_format($row['current_balance'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-4 text-center text-slate-500">Belum ada data kantong.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Rincian Pengeluaran per Kategori --}}
        <div class="space-y-2.5">
            <h2 class="text-sm font-bold text-slate-900">2. Rincian Pengeluaran Berdasarkan Kategori</h2>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                            <th class="p-2.5">Kategori Pengeluaran</th>
                            <th class="p-2.5 text-center">Jumlah Transaksi</th>
                            <th class="p-2.5 text-right">Persentase</th>
                            <th class="p-2.5 text-right">Total Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($report['expense_by_category'] as $cat)
                            <tr>
                                <td class="p-2.5 font-semibold text-slate-900">{{ $cat['category_name'] }}</td>
                                <td class="p-2.5 text-center text-slate-700">{{ $cat['count'] }}x</td>
                                <td class="p-2.5 text-right text-slate-700 tabular-nums">{{ $cat['percentage'] }}%</td>
                                <td class="p-2.5 text-right font-bold text-rose-700 tabular-nums">Rp{{ number_format($cat['total'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-4 text-center text-slate-500">Tidak ada pengeluaran pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Rincian Transaksi Periode Ini --}}
        <div class="space-y-2.5">
            <h2 class="text-sm font-bold text-slate-900">3. Rincian Riwayat Transaksi ({{ $transactions->count() }} Transaksi)</h2>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                            <th class="p-2.5">Tanggal</th>
                            <th class="p-2.5">Kode</th>
                            <th class="p-2.5">Kantong</th>
                            <th class="p-2.5">Jenis & Kategori</th>
                            <th class="p-2.5">Keterangan</th>
                            <th class="p-2.5 text-right">Nominal</th>
                            <th class="p-2.5 text-right">Saldo Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($transactions as $tx)
                            <tr>
                                <td class="p-2.5 whitespace-nowrap text-slate-700">{{ $tx->transaction_date->format('d/m/Y H:i') }}</td>
                                <td class="p-2.5 font-mono text-slate-600">{{ $tx->transaction_code }}</td>
                                <td class="p-2.5 font-medium text-slate-900">{{ $tx->displayWalletName() }}</td>
                                <td class="p-2.5 text-slate-700">{{ $tx->type->label() }} ({{ $tx->displayCategoryName() }})</td>
                                <td class="p-2.5 text-slate-800">{{ $tx->description }}</td>
                                <td class="p-2.5 text-right font-bold {{ $tx->type->amountClass() }} tabular-nums whitespace-nowrap">
                                    {{ $tx->type->sign() }}Rp{{ number_format((float) $tx->amount, 0, ',', '.') }}
                                </td>
                                <td class="p-2.5 text-right text-slate-700 tabular-nums whitespace-nowrap">
                                    Rp{{ number_format((float) $tx->balance_after, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-4 text-center text-slate-500">Tidak ada transaksi pada rentang tanggal ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

