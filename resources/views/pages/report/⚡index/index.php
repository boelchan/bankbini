<?php

declare(strict_types=1);

use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Title('Laporan Keuangan Keluarga')]
class extends Component
{
    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    public string $activePreset = 'this_month';

    /**
     * Inisialisasi rentang tanggal default ke awal dan akhir bulan berjalan.
     */
    public function mount(): void
    {
        if ($this->dateFrom === '' || $this->dateTo === '') {
            $this->dateFrom = Carbon::now()->startOfMonth()->toDateString();
            $this->dateTo = Carbon::now()->endOfMonth()->toDateString();
            $this->activePreset = 'this_month';
        } else {
            $this->activePreset = 'custom';
        }
    }

    /**
     * Mengubah preset menjadi custom saat pengguna mengubah tanggal secara manual.
     */
    public function updatedDateFrom(): void
    {
        $this->activePreset = 'custom';
    }

    /**
     * Mengubah preset menjadi custom saat pengguna mengubah tanggal akhir secara manual.
     */
    public function updatedDateTo(): void
    {
        $this->activePreset = 'custom';
    }

    /**
     * Menerapkan preset rentang waktu cepat.
     */
    public function applyPreset(string $preset): void
    {
        $this->activePreset = $preset;
        $now = Carbon::now();

        match ($preset) {
            'this_month' => [
                $this->dateFrom = $now->copy()->startOfMonth()->toDateString(),
                $this->dateTo = $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_month' => [
                $this->dateFrom = $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $this->dateTo = $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'last_3_months' => [
                $this->dateFrom = $now->copy()->subMonthsNoOverflow(2)->startOfMonth()->toDateString(),
                $this->dateTo = $now->copy()->endOfMonth()->toDateString(),
            ],
            'this_year' => [
                $this->dateFrom = $now->copy()->startOfYear()->toDateString(),
                $this->dateTo = $now->copy()->endOfYear()->toDateString(),
            ],
            default => null,
        };
    }

    /**
     * Mengambil data lengkap laporan keuangan dari service.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function report(): array
    {
        return app(FinancialReportService::class)->generateReport(
            (int) Auth::id(),
            $this->dateFrom,
            $this->dateTo
        );
    }

    /**
     * Mengambil data grafik arus kas 6 bulan terakhir.
     *
     * @return array{labels: array<int, string>, series: array<int, array<string, mixed>>}
     */
    #[Computed]
    public function cashflowChart(): array
    {
        return app(FinancialReportService::class)->getMonthlyCashflowSeries((int) Auth::id(), 6);
    }

    /**
     * Mengambil data grafik donut pengeluaran per kategori.
     *
     * @return array{labels: array<int, string>, series: array<int, float>}
     */
    #[Computed]
    public function expenseDonutChart(): array
    {
        $breakdown = $this->report['expense_by_category'] ?? [];

        if (empty($breakdown)) {
            return [
                'labels' => ['Belum Ada Pengeluaran'],
                'series' => [0],
            ];
        }

        return [
            'labels' => array_map(fn (array $item): string => $item['category_name'], $breakdown),
            'series' => array_map(fn (array $item): float => (float) $item['total'], $breakdown),
        ];
    }
};

