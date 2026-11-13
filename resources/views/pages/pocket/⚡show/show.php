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
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

new
#[Title('Detail Kantong')]
class extends Component
{
    use Interactions;
    use WithPagination;

    public Wallet $wallet;

    /* Filter Riwayat Transaksi Kantong */
    public string $search = '';
    public ?string $flowFilter = '';
    public ?string $monthFilter = '';
    public ?int $categoryFilter = null;

    /* Modal State */
    public bool $incomeModal = false;
    public bool $expenseModal = false;
    public bool $transferModal = false;
    public bool $editModal = false;
    public bool $deleteModal = false;

    /* Form Tambah Uang (Langsung atau Ambil dari Kantong Lain) */
    public string $income_source_type = 'direct';
    public ?int $income_source_wallet_id = null;
    public ?int $income_category_id = null;
    public string|int|float $income_amount = '';
    public string $income_date = '';
    public string $income_description = '';

    /* Form Pengeluaran */
    public ?int $expense_category_id = null;
    public string|int|float $expense_amount = '';
    public string $expense_date = '';
    public string $expense_description = '';

    /* Form Pindah Uang ke Kantong Lain */
    public ?int $transfer_destination_wallet_id = null;
    public string|int|float $transfer_amount = '';
    public string $transfer_date = '';
    public string $transfer_description = '';

    /* Form Ubah Informasi Kantong */
    public string $edit_name = '';
    public string $edit_icon = 'wallet';
    public string $edit_color = 'rose';
    public string $edit_description = '';

    /* Form Hapus Kantong */
    public ?int $delete_destination_wallet_id = null;

    /* Form Ubah Transaksi (pemasukan/pengeluaran) */
    public bool $editTransactionModal = false;
    public ?int $editTransactionId = null;
    public string $edit_tx_type = '';
    public ?int $edit_tx_category_id = null;
    public string|int|float $edit_tx_amount = '';
    public string $edit_tx_date = '';
    public string $edit_tx_description = '';

    /**
     * Inisialisasi halaman detail kantong bersama keluarga.
     */
    public function mount(Wallet $wallet): void
    {
        $this->wallet = $wallet;
        $this->monthFilter = now()->format('Y-m');
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
     * Reset halaman paginasi saat filter aliran dana berubah.
     */
    public function updatedFlowFilter(): void
    {
        $this->flowFilter = $this->flowFilter ?? '';
        $this->resetPage();
    }

    /**
     * Berpindah ke riwayat transaksi bulan sebelumnya.
     */
    public function previousMonth(): void
    {
        $this->monthFilter = Carbon::createFromFormat('Y-m', $this->monthFilter ?: now()->format('Y-m'))
            ->subMonthNoOverflow()
            ->format('Y-m');
        $this->resetPage();
    }

    /**
     * Berpindah ke riwayat transaksi bulan berikutnya (maksimal bulan berjalan).
     */
    public function nextMonth(): void
    {
        $currentMonth = now()->format('Y-m');
        $targetMonth = Carbon::createFromFormat('Y-m', $this->monthFilter ?: $currentMonth)
            ->addMonthNoOverflow()
            ->format('Y-m');

        if ($targetMonth > $currentMonth) {
            return;
        }

        $this->monthFilter = $targetMonth;
        $this->resetPage();
    }

    /**
     * Reset halaman paginasi saat filter kategori berubah.
     */
    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Mengatur filter cepat arah aliran dana (Semua / Pemasukan / Pengeluaran / Pindah Uang).
     */
    public function setFlowFilter(string $flow): void
    {
        $this->flowFilter = $flow;
        $this->resetPage();
    }

    /**
     * Mereset seluruh filter transaksi ke kondisi default (kembali ke bulan berjalan).
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->flowFilter = '';
        $this->monthFilter = now()->format('Y-m');
        $this->categoryFilter = null;
        $this->resetPage();
    }

    /**
     * Label bulan aktif pada riwayat transaksi kantong ini.
     */
    #[Computed]
    public function activeMonthLabel(): string
    {
        return Carbon::createFromFormat('Y-m', $this->monthFilter ?: now()->format('Y-m'))
            ->locale('id')
            ->translatedFormat('F Y');
    }

    /**
     * Menandai apakah riwayat sudah menampilkan bulan berjalan.
     */
    #[Computed]
    public function isLatestMonth(): bool
    {
        return ($this->monthFilter ?: now()->format('Y-m')) >= now()->format('Y-m');
    }

    /**
     * Mengambil daftar pilihan kategori untuk filter transaksi.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function categoryFilterOptions(): array
    {
        return TransactionCategory::query()
            ->orderBy('name')
            ->get()
            ->map(fn (TransactionCategory $c): array => [
                'label' => $c->name . ' (' . ($c->type === CategoryTypeEnum::INCOME ? 'Masuk' : 'Keluar') . ')',
                'value' => $c->id,
            ])
            ->all();
    }

    /**
     * Menghitung ringkasan uang masuk dan keluar kantong ini pada bulan aktif.
     *
     * @return array<string, float>
     */
    #[Computed]
    public function walletStats(): array
    {
        $baseQuery = Transaction::query()
            ->where('wallet_id', $this->wallet->id)
            ->when($this->monthFilter !== '', function ($query): void {
                $query->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$this->monthFilter]);
            });

        $totalIn = (float) (clone $baseQuery)
            ->whereIn('type', [TransactionTypeEnum::INCOME, TransactionTypeEnum::TRANSFER_IN])
            ->sum('amount');

        $totalOut = (float) (clone $baseQuery)
            ->whereIn('type', [TransactionTypeEnum::EXPENSE, TransactionTypeEnum::TRANSFER_OUT])
            ->sum('amount');

        return [
            'total_in' => $totalIn,
            'total_out' => $totalOut,
        ];
    }

    /**
     * Mengambil riwayat transaksi terpaginasi untuk kantong ini.
     */
    #[Computed]
    public function transactions(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return Transaction::query()
            ->with(['user', 'category', 'counterpartWallet' => fn ($q) => $q->withTrashed()])
            ->where('wallet_id', $this->wallet->id)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($sub) use ($search): void {
                    $sub->where('description', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhere('counterpart_wallet_name_snapshot', 'like', "%{$search}%")
                        ->orWhere('category_name_snapshot', 'like', "%{$search}%");
                });
            })
            ->when($this->flowFilter !== '', function ($query): void {
                match ($this->flowFilter) {
                    'income' => $query->whereIn('type', [TransactionTypeEnum::INCOME, TransactionTypeEnum::TRANSFER_IN]),
                    'expense' => $query->whereIn('type', [TransactionTypeEnum::EXPENSE, TransactionTypeEnum::TRANSFER_OUT]),
                    'transfer' => $query->whereIn('type', [TransactionTypeEnum::TRANSFER_IN, TransactionTypeEnum::TRANSFER_OUT]),
                    default => null,
                };
            })
            ->when($this->monthFilter !== '', function ($query): void {
                $query->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$this->monthFilter]);
            })
            ->when($this->categoryFilter !== null, function ($query): void {
                $query->where('category_id', $this->categoryFilter);
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(15);
    }

    /**
     * Mengambil opsi kantong aktif lain sebagai sumber/tujuan pindah uang.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function otherActiveWalletOptions(): array
    {
        return Wallet::query()
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
     * Membuka modal tambah uang ke kantong ini (langsung atau dari kantong lain).
     */
    public function openIncomeModal(string $sourceType = 'direct'): void
    {
        $this->resetValidation();
        $this->income_source_type = $sourceType;
        $this->income_source_wallet_id = null;
        $this->income_category_id = null;
        $this->income_amount = '';
        $this->income_description = '';
        $this->income_date = now()->format('Y-m-d\TH:i');
        $this->incomeModal = true;
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
     * Menyimpan tambah/pindah uang pada kantong ini (langsung, ambil dari kantong lain, atau pindah ke kantong lain).
     */
    public function saveIncome(TransactionService $transactionService, TransferService $transferService): void
    {
        if ($this->income_source_type === IncomeSourceEnum::FROM_POCKET->value) {
            $validated = $this->validate([
                'income_source_wallet_id' => ['required', 'integer'],
                'income_amount' => ['required', 'numeric', 'gt:0'],
                'income_date' => ['required', 'date'],
                'income_description' => ['nullable', 'string', 'max:190'],
            ], [
                'income_source_wallet_id.required' => 'Pilih kantong asal tempat mengambil uang.',
                'income_amount.required' => 'Nominal wajib diisi.',
                'income_amount.gt' => 'Nominal harus lebih besar dari Rp0.',
            ]);

            try {
                $transferService->executeTransfer((int) Auth::id(), [
                    'source_wallet_id' => $validated['income_source_wallet_id'],
                    'destination_wallet_id' => $this->wallet->id,
                    'amount' => $validated['income_amount'],
                    'transaction_date' => $validated['income_date'],
                    'description' => $validated['income_description'],
                    'idempotency_key' => 'pock-in-trf-' . Str::uuid()->toString(),
                ]);

                $this->incomeModal = false;
                $this->refreshPocketState();
                $this->toast()->success('Uang Berhasil Diambil', 'Dana dari kantong lain berhasil ditambahkan ke kantong ini.')->send();
            } catch (\DomainException|ValidationException $e) {
                $msg = $e instanceof ValidationException
                    ? (collect($e->errors())->flatten()->first() ?? 'Gagal memindahkan uang.')
                    : $e->getMessage();
                $this->addError('income_amount', $msg);
            }

            return;
        }

        $validated = $this->validate([
            'income_category_id' => ['required', 'integer'],
            'income_amount' => ['required', 'numeric', 'gt:0'],
            'income_date' => ['required', 'date'],
            'income_description' => ['required', 'string', 'max:190'],
        ], [
            'income_category_id.required' => 'Pilih kategori pemasukan.',
            'income_amount.required' => 'Nominal pemasukan wajib diisi.',
            'income_amount.gt' => 'Nominal harus lebih besar dari Rp0.',
            'income_description.required' => 'Keterangan pemasukan wajib diisi.',
        ]);

        try {
            $transactionService->recordIncome((int) Auth::id(), [
                'wallet_id' => $this->wallet->id,
                'category_id' => $validated['income_category_id'],
                'amount' => $validated['income_amount'],
                'transaction_date' => $validated['income_date'],
                'description' => $validated['income_description'],
                'idempotency_key' => 'pock-inc-' . Str::uuid()->toString(),
            ]);

            $this->incomeModal = false;
            $this->refreshPocketState();
            $this->toast()->success('Pemasukan Berhasil', 'Saldo kantong berhasil ditambahkan.')->send();
        } catch (\DomainException|ValidationException $e) {
            $msg = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal menyimpan pemasukan.')
                : $e->getMessage();
            $this->addError('income_amount', $msg);
        }
    }

    /**
     * Membuka modal pengeluaran dari kantong ini.
     */
    public function openExpenseModal(): void
    {
        $this->resetValidation();
        $defaultExpenseCat = TransactionCategory::query()
            ->where('type', CategoryTypeEnum::EXPENSE->value)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->first();

        $this->expense_category_id = $defaultExpenseCat?->id;
        $this->expense_amount = '';
        $this->expense_description = '';
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
        ], [
            'expense_category_id.required' => 'Pilih kategori pengeluaran.',
            'expense_amount.required' => 'Nominal pengeluaran wajib diisi.',
            'expense_amount.gt' => 'Nominal harus lebih besar dari Rp0.',
            'expense_description.required' => 'Keterangan pengeluaran wajib diisi.',
        ]);

        try {
            $transactionService->recordExpense((int) Auth::id(), [
                'wallet_id' => $this->wallet->id,
                'category_id' => $validated['expense_category_id'],
                'amount' => $validated['expense_amount'],
                'transaction_date' => $validated['expense_date'],
                'description' => $validated['expense_description'],
                'idempotency_key' => 'pock-exp-' . Str::uuid()->toString(),
            ]);

            $this->expenseModal = false;
            $this->refreshPocketState();
            $this->toast()->success('Pengeluaran Tercatat', 'Saldo kantong telah diperbarui.')->send();
        } catch (\DomainException|ValidationException $e) {
            $msg = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal mencatat pengeluaran.')
                : $e->getMessage();
            $this->addError('expense_amount', $msg);
        }
    }

    /**
     * Membuka modal pindah uang dari kantong ini ke kantong lain.
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
     * Mengeksekusi pindah uang dari kantong ini ke kantong lain.
     */
    public function saveTransfer(TransferService $transferService): void
    {
        $validated = $this->validate([
            'transfer_destination_wallet_id' => ['required', 'integer'],
            'transfer_amount' => ['required', 'numeric', 'gt:0'],
            'transfer_date' => ['required', 'date'],
            'transfer_description' => ['nullable', 'string', 'max:190'],
        ], [
            'transfer_destination_wallet_id.required' => 'Pilih kantong tujuan pindah uang.',
            'transfer_amount.required' => 'Nominal wajib diisi.',
            'transfer_amount.gt' => 'Nominal harus lebih besar dari Rp0.',
        ]);

        try {
            $transferService->executeTransfer((int) Auth::id(), [
                'source_wallet_id' => $this->wallet->id,
                'destination_wallet_id' => $validated['transfer_destination_wallet_id'],
                'amount' => $validated['transfer_amount'],
                'transaction_date' => $validated['transfer_date'],
                'description' => $validated['transfer_description'],
                'idempotency_key' => 'pock-trf-' . Str::uuid()->toString(),
            ]);

            $this->transferModal = false;
            $this->refreshPocketState();
            $this->toast()->success('Pindah Uang Berhasil', 'Dana berhasil dipindahkan ke kantong tujuan.')->send();
        } catch (\DomainException|ValidationException $e) {
            $msg = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal memindahkan uang.')
                : $e->getMessage();
            $this->addError('transfer_amount', $msg);
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
            $this->toast()->info('Kantong Dibekukan', 'Kantong telah dibekukan sementara.')->send();
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
     * Menjadikan kantong ini sebagai kantong utama keluarga.
     */
    public function makePrimary(WalletService $walletService): void
    {
        $walletService->setPrimaryWallet($this->wallet);
        $this->refreshPocketState();
        $this->toast()->success('Kantong Utama Diperbarui', sprintf('Kantong "%s" kini menjadi kantong utama keluarga.', $this->wallet->name))->send();
    }

    /**
     * Menutup dan menghapus kantong lalu mengarahkan ke daftar Kantong.
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
            $this->toast()->success('Kantong Dihapus', 'Kantong berhasil ditutup dan histori tetap tersimpan.')->send();
            $this->redirectRoute('pockets.index', navigate: true);
        } catch (\DomainException|ValidationException $e) {
            $msg = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal menghapus kantong.')
                : $e->getMessage();
            $this->addError('delete_destination_wallet_id', $msg);
        }
    }

    /**
     * Opsi kategori sesuai jenis transaksi yang sedang diubah.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function editTxCategoryOptions(): array
    {
        $type = $this->edit_tx_type === TransactionTypeEnum::EXPENSE->value
            ? CategoryTypeEnum::EXPENSE
            : CategoryTypeEnum::INCOME;

        return TransactionCategory::query()
            ->where('type', $type)
            ->orderBy('name')
            ->get()
            ->map(fn (TransactionCategory $c): array => [
                'label' => $c->name,
                'value' => $c->id,
            ])
            ->all();
    }

    /**
     * Membuka modal ubah transaksi pemasukan/pengeluaran.
     */
    public function openEditTransaction(int $transactionId): void
    {
        $tx = Transaction::query()
            ->where('wallet_id', $this->wallet->id)
            ->find($transactionId);

        if ($tx === null) {
            return;
        }

        if (in_array($tx->type, [TransactionTypeEnum::TRANSFER_IN, TransactionTypeEnum::TRANSFER_OUT], true)
            || !empty($tx->transfer_reference)) {
            $this->toast()->warning('Tidak Bisa Diubah', 'Transaksi pindah uang tidak dapat diubah. Hapus lalu catat ulang jika perlu.')->send();
            return;
        }

        $this->resetValidation();
        $this->editTransactionId = $tx->id;
        $this->edit_tx_type = $tx->type instanceof TransactionTypeEnum ? $tx->type->value : (string) $tx->type;
        $this->edit_tx_category_id = $tx->category_id;
        $this->edit_tx_amount = (float) $tx->amount;
        $this->edit_tx_date = $tx->transaction_date?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->edit_tx_description = (string) $tx->description;
        unset($this->editTxCategoryOptions);
        $this->editTransactionModal = true;
    }

    /**
     * Menyimpan perubahan transaksi pemasukan/pengeluaran.
     */
    public function saveEditTransaction(TransactionService $transactionService): void
    {
        $validated = $this->validate([
            'editTransactionId' => ['required', 'integer'],
            'edit_tx_category_id' => ['nullable', 'integer'],
            'edit_tx_amount' => ['required', 'numeric', 'gt:0'],
            'edit_tx_date' => ['required', 'date'],
            'edit_tx_description' => ['required', 'string', 'max:190'],
        ], [
            'edit_tx_amount.required' => 'Nominal transaksi wajib diisi.',
            'edit_tx_amount.gt' => 'Nominal harus lebih besar dari Rp0.',
            'edit_tx_description.required' => 'Keterangan transaksi wajib diisi.',
            'edit_tx_date.required' => 'Tanggal transaksi wajib diisi.',
        ]);

        try {
            $transactionService->updateTransaction((int) $validated['editTransactionId'], [
                'category_id' => $validated['edit_tx_category_id'] ? (int) $validated['edit_tx_category_id'] : null,
                'amount' => $validated['edit_tx_amount'],
                'transaction_date' => $validated['edit_tx_date'],
                'description' => $validated['edit_tx_description'],
            ]);

            $this->editTransactionModal = false;
            $this->refreshPocketState();
            $this->toast()->success('Transaksi Diperbarui', 'Perubahan transaksi berhasil disimpan.')->send();
        } catch (\DomainException $e) {
            $this->addError('edit_tx_amount', $e->getMessage());
        }
    }

    /**
     * Menampilkan dialog konfirmasi sebelum menghapus transaksi.
     */
    public function askDeleteTransaction(int $transactionId): void
    {
        $tx = Transaction::query()->where('wallet_id', $this->wallet->id)->find($transactionId);
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
            $transaction = Transaction::query()
                ->where('wallet_id', $this->wallet->id)
                ->findOrFail($transactionId);

            $transactionService->deleteTransaction($transaction->id);
            $this->refreshPocketState();
            $this->toast()->success('Transaksi Dihapus', 'Transaksi berhasil dihapus dan saldo kantong telah diperbarui.')->send();
        } catch (\Throwable $e) {
            $this->toast()->error('Gagal Menghapus', $e->getMessage())->send();
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
            $this->otherActiveWalletOptions,
        );
    }
};
