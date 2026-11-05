<?php

declare(strict_types=1);

use App\Enums\CategoryTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WalletStatusEnum;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Wallet;
use App\Models\WalletActivityLog;
use App\Services\TransactionService;
use App\Services\TransferService;
use App\Services\WalletService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

new
#[Title('Detail Kantong Keuangan')]
class extends Component
{
    use Interactions;
    use WithPagination;

    public Wallet $wallet;

    /* Filter Riwayat Transaksi Kantong */
    public string $search = '';
    public string $typeFilter = '';

    /* Modal State */
    public bool $incomeModal = false;
    public bool $expenseModal = false;
    public bool $transferModal = false;
    public bool $editModal = false;
    public bool $deleteModal = false;
    public bool $detailModal = false;

    /* Form Pemasukan */
    public ?int $income_category_id = null;
    public string|int|float $income_amount = '';
    public string $income_date = '';
    public string $income_description = '';
    public string $income_notes = '';

    /* Form Pengeluaran */
    public ?int $expense_category_id = null;
    public string|int|float $expense_amount = '';
    public string $expense_date = '';
    public string $expense_description = '';
    public string $expense_notes = '';

    /* Form Transfer dari Kantong Ini */
    public ?int $transfer_destination_wallet_id = null;
    public string|int|float $transfer_amount = '';
    public string $transfer_date = '';
    public string $transfer_description = '';
    public string $transfer_notes = '';

    /* Form Ubah Informasi Kantong */
    public string $edit_name = '';
    public string $edit_icon = 'wallet';
    public string $edit_color = 'emerald';
    public string $edit_description = '';

    /* Form Penutupan Kantong */
    public ?int $delete_destination_wallet_id = null;

    /* Detail Transaksi */
    public ?int $selectedTransactionId = null;

    /**
     * Memastikan kantong milik pengguna yang sedang login.
     */
    public function mount(Wallet $wallet): void
    {
        abort_unless((int) $wallet->user_id === (int) Auth::id(), 403, 'Akses kantong ditolak.');

        $this->wallet = $wallet;
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
     * Reset halaman paginasi saat filter jenis transaksi berubah.
     */
    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Menghitung ringkasan arus dana pada kantong ini.
     *
     * @return array<string, float|int>
     */
    #[Computed]
    public function walletStats(): array
    {
        $baseQuery = Transaction::query()
            ->where('user_id', Auth::id())
            ->where('wallet_id', $this->wallet->id);

        $incomeTotal = (float) (clone $baseQuery)->where('type', TransactionTypeEnum::INCOME)->sum('amount');
        $expenseTotal = (float) (clone $baseQuery)->where('type', TransactionTypeEnum::EXPENSE)->sum('amount');
        $transferInTotal = (float) (clone $baseQuery)->where('type', TransactionTypeEnum::TRANSFER_IN)->sum('amount');
        $transferOutTotal = (float) (clone $baseQuery)->where('type', TransactionTypeEnum::TRANSFER_OUT)->sum('amount');
        $txCount = (int) (clone $baseQuery)->count();

        return [
            'income_total' => $incomeTotal,
            'expense_total' => $expenseTotal,
            'transfer_in_total' => $transferInTotal,
            'transfer_out_total' => $transferOutTotal,
            'tx_count' => $txCount,
        ];
    }

    /**
     * Mengambil riwayat transaksi terpaginasi untuk kantong ini.
     */
    #[Computed]
    public function transactions(): LengthAwarePaginator
    {
        return Transaction::query()
            ->with(['category', 'counterpartWallet' => fn ($q) => $q->withTrashed()])
            ->where('user_id', Auth::id())
            ->where('wallet_id', $this->wallet->id)
            ->when($this->search !== '', function ($query): void {
                $term = '%' . trim($this->search) . '%';
                $query->where(function ($sub) use ($term): void {
                    $sub->where('description', 'like', $term)
                        ->orWhere('transaction_code', 'like', $term)
                        ->orWhere('notes', 'like', $term);
                });
            })
            ->when($this->typeFilter !== '', function ($query): void {
                $query->where('type', $this->typeFilter);
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(10);
    }

    /**
     * Mengambil log aktivitas pengelolaan kantong.
     *
     * @return Collection<int, WalletActivityLog>
     */
    #[Computed]
    public function activityLogs(): Collection
    {
        return WalletActivityLog::query()
            ->where('wallet_id', $this->wallet->id)
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();
    }

    /**
     * Mengambil opsi kantong aktif lain sebagai tujuan transfer atau penutupan.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function otherActiveWalletOptions(): array
    {
        return Wallet::query()
            ->where('user_id', Auth::id())
            ->where('status', WalletStatusEnum::ACTIVE)
            ->where('id', '!=', $this->wallet->id)
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
            ->with(['category', 'wallet' => fn ($q) => $q->withTrashed(), 'counterpartWallet' => fn ($q) => $q->withTrashed()])
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
     * Membuka modal tambah pemasukan ke kantong ini.
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
     * Menyimpan pemasukan ke kantong ini.
     */
    public function saveIncome(TransactionService $transactionService): void
    {
        $validated = $this->validate([
            'income_category_id' => ['required', 'integer'],
            'income_amount' => ['required', 'numeric', 'gt:0'],
            'income_date' => ['required', 'date'],
            'income_description' => ['required', 'string', 'max:190'],
            'income_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'income_category_id.required' => 'Pilih kategori pemasukan.',
            'income_amount.required' => 'Nominal pemasukan wajib diisi.',
            'income_amount.gt' => 'Nominal pemasukan harus lebih besar dari Rp0.',
            'income_description.required' => 'Keterangan pemasukan wajib diisi.',
        ]);

        $transactionService->recordIncome((int) Auth::id(), [
            'wallet_id' => $this->wallet->id,
            'category_id' => $validated['income_category_id'],
            'amount' => $validated['income_amount'],
            'transaction_date' => $validated['income_date'],
            'description' => $validated['income_description'],
            'notes' => $validated['income_notes'],
            'idempotency_key' => 'pock-inc-' . Str::uuid()->toString(),
        ]);

        $this->incomeModal = false;
        $this->refreshPocketState();
        $this->toast()->success('Pemasukan Berhasil', 'Saldo kantong berhasil ditambahkan.')->send();
    }

    /**
     * Membuka modal pengeluaran dari kantong ini.
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
     * Menyimpan pengeluaran dari kantong ini.
     */
    public function saveExpense(TransactionService $transactionService): void
    {
        $validated = $this->validate([
            'expense_category_id' => ['required', 'integer'],
            'expense_amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['required', 'date'],
            'expense_description' => ['required', 'string', 'max:190'],
            'expense_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'expense_category_id.required' => 'Pilih kategori pengeluaran.',
            'expense_amount.required' => 'Nominal pengeluaran wajib diisi.',
            'expense_amount.gt' => 'Nominal pengeluaran harus lebih besar dari Rp0.',
            'expense_description.required' => 'Keterangan pengeluaran wajib diisi.',
        ]);

        try {
            $transactionService->recordExpense((int) Auth::id(), [
                'wallet_id' => $this->wallet->id,
                'category_id' => $validated['expense_category_id'],
                'amount' => $validated['expense_amount'],
                'transaction_date' => $validated['expense_date'],
                'description' => $validated['expense_description'],
                'notes' => $validated['expense_notes'],
                'idempotency_key' => 'pock-exp-' . Str::uuid()->toString(),
            ]);

            $this->expenseModal = false;
            $this->refreshPocketState();
            $this->toast()->success('Pengeluaran Tercatat', 'Saldo kantong telah diperbarui.')->send();
        } catch (\DomainException|ValidationException $e) {
            $firstMessage = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal mencatat pengeluaran.')
                : $e->getMessage();
            $this->addError('expense_amount', $firstMessage);
        }
    }

    /**
     * Membuka modal transfer keluar dari kantong ini.
     */
    public function openTransferModal(): void
    {
        $this->resetValidation();
        $this->transfer_destination_wallet_id = null;
        $this->transfer_amount = '';
        $this->transfer_description = '';
        $this->transfer_notes = '';
        $this->transfer_date = now()->format('Y-m-d\TH:i');
        $this->transferModal = true;
    }

    /**
     * Mengeksekusi transfer keluar dari kantong ini ke kantong lain.
     */
    public function saveTransfer(TransferService $transferService): void
    {
        $validated = $this->validate([
            'transfer_destination_wallet_id' => ['required', 'integer'],
            'transfer_amount' => ['required', 'numeric', 'gt:0'],
            'transfer_date' => ['required', 'date'],
            'transfer_description' => ['nullable', 'string', 'max:190'],
            'transfer_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'transfer_destination_wallet_id.required' => 'Pilih kantong tujuan transfer.',
            'transfer_amount.required' => 'Nominal transfer wajib diisi.',
            'transfer_amount.gt' => 'Nominal transfer harus lebih besar dari Rp0.',
        ]);

        try {
            $transferService->executeTransfer((int) Auth::id(), [
                'source_wallet_id' => $this->wallet->id,
                'destination_wallet_id' => $validated['transfer_destination_wallet_id'],
                'amount' => $validated['transfer_amount'],
                'transaction_date' => $validated['transfer_date'],
                'description' => $validated['transfer_description'],
                'notes' => $validated['transfer_notes'],
                'idempotency_key' => 'pock-trf-' . Str::uuid()->toString(),
            ]);

            $this->transferModal = false;
            $this->refreshPocketState();
            $this->toast()->success('Transfer Berhasil', 'Dana berhasil dipindahkan ke kantong tujuan.')->send();
        } catch (\DomainException|ValidationException $e) {
            $firstMessage = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal memproses transfer.')
                : $e->getMessage();
            $this->addError('transfer_amount', $firstMessage);
        }
    }

    /**
     * Membuka modal ubah informasi kantong.
     */
    public function openEditModal(): void
    {
        $this->resetValidation();
        $this->edit_name = $this->wallet->name;
        $this->edit_icon = $this->wallet->icon;
        $this->edit_color = $this->wallet->color;
        $this->edit_description = (string) ($this->wallet->description ?? '');
        $this->editModal = true;
    }

    /**
     * Menyimpan perubahan informasi kantong.
     */
    public function updatePocket(WalletService $walletService): void
    {
        $validated = $this->validate([
            'edit_name' => ['required', 'string', 'max:100'],
            'edit_icon' => ['required', 'string'],
            'edit_color' => ['required', 'string'],
            'edit_description' => ['nullable', 'string', 'max:255'],
        ], [
            'edit_name.required' => 'Nama kantong wajib diisi.',
        ]);

        $walletService->updateWallet($this->wallet, [
            'name' => $validated['edit_name'],
            'icon' => $validated['edit_icon'],
            'color' => $validated['edit_color'],
            'description' => $validated['edit_description'],
        ]);

        $this->editModal = false;
        $this->refreshPocketState();
        $this->toast()->success('Kantong Diperbarui', 'Informasi kantong berhasil disimpan.')->send();
    }

    /**
     * Membekukan atau mengaktifkan kembali kantong ini.
     */
    public function toggleFreeze(WalletService $walletService): void
    {
        if ($this->wallet->isFrozen()) {
            $walletService->unfreezeWallet($this->wallet);
            $this->toast()->success('Kantong Diaktifkan', 'Kantong dapat digunakan kembali untuk bertransaksi.')->send();
        } else {
            $walletService->freezeWallet($this->wallet);
            $this->toast()->info('Kantong Dibekukan', 'Kantong telah dibekukan dari transaksi baru.')->send();
        }

        $this->refreshPocketState();
    }

    /**
     * Membuka modal konfirmasi penghapusan kantong.
     */
    public function openDeleteModal(): void
    {
        $this->resetValidation();
        $this->delete_destination_wallet_id = null;
        $this->deleteModal = true;
    }

    /**
     * Menutup dan menghapus kantong lalu mengarahkan ke daftar kantong.
     */
    public function confirmDeletePocket(WalletService $walletService): void
    {
        try {
            $walletService->deleteWallet(
                (int) Auth::id(),
                $this->wallet->id,
                $this->delete_destination_wallet_id
            );

            $this->deleteModal = false;
            $this->toast()->success('Kantong Dihapus', 'Kantong berhasil ditutup dan histori transaksi tetap tersimpan.')->send();
            $this->redirectRoute('pockets.index', navigate: true);
        } catch (\DomainException|ValidationException $e) {
            $firstMessage = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal menghapus kantong.')
                : $e->getMessage();
            $this->addError('delete_destination_wallet_id', $firstMessage);
        }
    }

    /**
     * Menyegarkan model kantong dan cache computed setelah perubahan.
     */
    private function refreshPocketState(): void
    {
        $this->wallet->refresh();
        unset(
            $this->walletStats,
            $this->transactions,
            $this->activityLogs,
            $this->otherActiveWalletOptions,
        );
    }
};
