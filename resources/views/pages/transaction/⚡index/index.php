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
#[Title('Transaksi')]
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

    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    /* State Modal */
    public bool $incomeModal = false;
    public bool $expenseModal = false;
    public bool $transferModal = false;

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
        if (in_array($property, ['search', 'walletFilter', 'typeFilter', 'dateFrom', 'dateTo'], true)) {
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
                'user',
                'wallet' => fn ($q) => $q->withTrashed(),
                'counterpartWallet' => fn ($q) => $q->withTrashed(),
                'category',
            ])
            ->when($this->search !== '', function ($query): void {
                $term = '%' . trim($this->search) . '%';
                $query->where(function ($sub) use ($term): void {
                    $sub->where('description', 'like', $term)
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
     * Mereset pilihan dan pesan validasi saat sumber uang diubah.
     */
    public function updatedIncomeSourceType(): void
    {
        $this->resetValidation();
        $this->income_source_wallet_id = null;
        $this->income_category_id = null;
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
            ->orderBy('name')
            ->get()
            ->map(fn (TransactionCategory $cat): array => [
                'label' => $cat->name,
                'value' => $cat->id,
            ])
            ->all();
    }

    /**
     * Membuka modal tambah pemasukan / tambah uang.
     */
    public function openIncomeModal(): void
    {
        $this->resetValidation();
        $this->income_source_type = IncomeSourceEnum::DIRECT->value;
        $this->income_source_wallet_id = null;
        $this->income_amount = '';
        $this->income_description = '';
        $this->income_date = now()->format('Y-m-d\TH:i');
        unset($this->otherActiveWalletOptions);
        $this->incomeModal = true;
    }

    /**
     * Menyimpan transaksi tambah uang (langsung atau dari kantong lain).
     */
    public function saveIncome(TransactionService $transactionService, TransferService $transferService): void
    {
        if ($this->income_source_type === IncomeSourceEnum::FROM_POCKET->value) {
            $validated = $this->validate([
                'income_wallet_id' => ['required', 'integer'],
                'income_source_wallet_id' => ['required', 'integer', 'different:income_wallet_id'],
                'income_amount' => ['required', 'numeric', 'gt:0'],
                'income_date' => ['required', 'date'],
                'income_description' => ['nullable', 'string', 'max:190'],
            ], [
                'income_wallet_id.required' => 'Pilih kantong penerima uang.',
                'income_source_wallet_id.required' => 'Pilih kantong asal.',
                'income_source_wallet_id.different' => 'Kantong asal harus berbeda dengan kantong tujuan.',
                'income_amount.required' => 'Nominal wajib diisi.',
                'income_amount.gt' => 'Nominal harus lebih besar dari Rp0.',
            ]);

            try {
                $transferService->executeTransfer((int) Auth::id(), [
                    'source_wallet_id' => $validated['income_source_wallet_id'],
                    'destination_wallet_id' => $validated['income_wallet_id'],
                    'amount' => $validated['income_amount'],
                    'transaction_date' => $validated['income_date'],
                    'description' => $validated['income_description'],
                    'idempotency_key' => 'tx-trf-' . Str::uuid()->toString(),
                ]);

                $this->incomeModal = false;
                unset($this->transactions, $this->activeWalletOptions, $this->otherActiveWalletOptions);
                $this->toast()->success('Uang Berhasil Diambil', 'Dana berhasil dipindahkan antar kantong.')->send();
            } catch (\DomainException|ValidationException $e) {
                $msg = $e instanceof ValidationException
                    ? (collect($e->errors())->flatten()->first() ?? 'Gagal memindahkan uang.')
                    : $e->getMessage();
                $this->addError('income_amount', $msg);
            }

            return;
        }

        $validated = $this->validate([
            'income_wallet_id' => ['required', 'integer'],
            'income_category_id' => ['required', 'integer'],
            'income_amount' => ['required', 'numeric', 'gt:0'],
            'income_date' => ['required', 'date'],
            'income_description' => ['required', 'string', 'max:190'],
        ], [
            'income_wallet_id.required' => 'Pilih kantong tujuan pemasukan.',
            'income_category_id.required' => 'Pilih kategori pemasukan.',
            'income_amount.required' => 'Nominal pemasukan wajib diisi.',
            'income_amount.gt' => 'Nominal harus lebih besar dari Rp0.',
            'income_description.required' => 'Keterangan pemasukan wajib diisi.',
        ]);

        try {
            $transactionService->recordIncome((int) Auth::id(), [
                'wallet_id' => $validated['income_wallet_id'],
                'category_id' => $validated['income_category_id'],
                'amount' => $validated['income_amount'],
                'transaction_date' => $validated['income_date'],
                'description' => $validated['income_description'],
                'idempotency_key' => 'tx-inc-' . Str::uuid()->toString(),
            ]);

            $this->incomeModal = false;
            unset($this->transactions, $this->activeWalletOptions, $this->otherActiveWalletOptions);
            $this->toast()->success('Pemasukan Disimpan', 'Transaksi pemasukan berhasil dicatat.')->send();
        } catch (\DomainException|ValidationException $e) {
            $msg = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal menyimpan pemasukan.')
                : $e->getMessage();
            $this->addError('income_amount', $msg);
        }
    }

    /**
     * Membuka modal catat pengeluaran.
     */
    public function openExpenseModal(): void
    {
        $this->resetValidation();
        $this->expense_amount = '';
        $this->expense_description = '';
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
        ], [
            'expense_wallet_id.required' => 'Pilih kantong sumber pengeluaran.',
            'expense_category_id.required' => 'Pilih kategori pengeluaran.',
            'expense_amount.required' => 'Nominal pengeluaran wajib diisi.',
            'expense_amount.gt' => 'Nominal harus lebih besar dari Rp0.',
            'expense_description.required' => 'Keterangan pengeluaran wajib diisi.',
        ]);

        try {
            $transactionService->recordExpense((int) Auth::id(), [
                'wallet_id' => $validated['expense_wallet_id'],
                'category_id' => $validated['expense_category_id'],
                'amount' => $validated['expense_amount'],
                'transaction_date' => $validated['expense_date'],
                'description' => $validated['expense_description'],
                'idempotency_key' => 'tx-exp-' . Str::uuid()->toString(),
            ]);

            $this->expenseModal = false;
            unset($this->transactions, $this->activeWalletOptions);
            $this->toast()->success('Pengeluaran Disimpan', 'Transaksi pengeluaran berhasil dicatat.')->send();
        } catch (\DomainException|ValidationException $e) {
            $msg = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal mencatat pengeluaran.')
                : $e->getMessage();
            $this->addError('expense_amount', $msg);
        }
    }

    /**
     * Membuka modal pindah uang antar kantong.
     */
    public function openTransferModal(): void
    {
        $this->resetValidation();
        $this->transfer_destination_wallet_id = null;
        $this->transfer_amount = '';
        $this->transfer_description = '';
        $this->transfer_date = now()->format('Y-m-d\TH:i');
        $this->transferModal = true;
    }

    /**
     * Menyimpan pindah uang antar kantong.
     */
    public function saveTransfer(TransferService $transferService): void
    {
        $validated = $this->validate([
            'transfer_source_wallet_id' => ['required', 'integer'],
            'transfer_destination_wallet_id' => ['required', 'integer', 'different:transfer_source_wallet_id'],
            'transfer_amount' => ['required', 'numeric', 'gt:0'],
            'transfer_date' => ['required', 'date'],
            'transfer_description' => ['nullable', 'string', 'max:190'],
        ], [
            'transfer_source_wallet_id.required' => 'Pilih kantong pengirim.',
            'transfer_destination_wallet_id.required' => 'Pilih kantong penerima.',
            'transfer_destination_wallet_id.different' => 'Kantong penerima harus berbeda dengan kantong pengirim.',
            'transfer_amount.required' => 'Nominal wajib diisi.',
            'transfer_amount.gt' => 'Nominal harus lebih besar dari Rp0.',
        ]);

        try {
            $transferService->executeTransfer((int) Auth::id(), [
                'source_wallet_id' => $validated['transfer_source_wallet_id'],
                'destination_wallet_id' => $validated['transfer_destination_wallet_id'],
                'amount' => $validated['transfer_amount'],
                'transaction_date' => $validated['transfer_date'],
                'description' => $validated['transfer_description'],
                'idempotency_key' => 'tx-move-' . Str::uuid()->toString(),
            ]);

            $this->transferModal = false;
            unset($this->transactions, $this->activeWalletOptions);
            $this->toast()->success('Pindah Uang Berhasil', 'Dana berhasil dipindahkan antar kantong.')->send();
        } catch (\DomainException|ValidationException $e) {
            $msg = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal memindahkan uang.')
                : $e->getMessage();
            $this->addError('transfer_amount', $msg);
        }
    }

    /**
     * Menampilkan dialog konfirmasi sebelum menghapus transaksi.
     */
    public function askDeleteTransaction(int $transactionId): void
    {
        $tx = Transaction::find($transactionId);
        if ($tx === null) {
            return;
        }

        $isTransfer = in_array(
            $tx->type,
            [TransactionTypeEnum::TRANSFER_IN, TransactionTypeEnum::TRANSFER_OUT],
            true
        ) || !empty($tx->transfer_reference);

        $description = $isTransfer
            ? 'Transaksi pindah/terima uang ini beserta pasangannya di kantong terkait akan dihapus keduanya, dan saldo kedua kantong dikembalikan.'
            : 'Transaksi ini akan dihapus dan saldo kantong akan dikembalikan seperti semula.';

        $this->dialog()
            ->question('Hapus Transaksi?', $description)
            ->confirm('Ya, Hapus', 'deleteTransaction', $transactionId)
            ->cancel('Batal')
            ->send();
    }

    /**
     * Menghapus transaksi dan mengembalikan saldo kantong (termasuk menghapus pasangan pindah/terima antar kantong).
     */
    public function deleteTransaction(int $transactionId, TransactionService $transactionService): void
    {
        try {
            $transactionService->deleteTransaction($transactionId);
            unset($this->transactions, $this->activeWalletOptions, $this->otherActiveWalletOptions);
            $this->toast()->success('Transaksi Dihapus', 'Transaksi berhasil dihapus dan saldo kantong telah diperbarui.')->send();
        } catch (\DomainException $e) {
            $this->toast()->error('Gagal Menghapus', $e->getMessage())->send();
        }
    }
};
