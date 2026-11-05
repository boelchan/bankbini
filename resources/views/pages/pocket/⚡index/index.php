<?php

declare(strict_types=1);

use App\Enums\WalletColorEnum;
use App\Enums\WalletIconEnum;
use App\Enums\WalletStatusEnum;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new
#[Title('Manajemen Kantong Keuangan')]
class extends Component
{
    use Interactions;

    /* Filter & Pencarian */
    public string $search = '';
    public string $statusFilter = '';

    /* State Modal */
    public bool $createModal = false;
    public bool $editModal = false;
    public bool $deleteModal = false;

    /* Form Tambah Kantong */
    public string $name = '';
    public string $icon = 'wallet';
    public string $color = 'emerald';
    public string $description = '';
    public string|int|float $initial_balance = '';

    /* Form Ubah Kantong */
    public ?int $editingWalletId = null;
    public string $edit_name = '';
    public string $edit_icon = 'wallet';
    public string $edit_color = 'emerald';
    public string $edit_description = '';

    /* Form Hapus / Tutup Kantong */
    public ?int $deletingWalletId = null;
    public ?int $delete_destination_wallet_id = null;

    /**
     * Mengambil daftar kantong aktif dan dibekukan milik pengguna sesuai filter.
     *
     * @return Collection<int, Wallet>
     */
    #[Computed]
    public function wallets(): Collection
    {
        return Wallet::query()
            ->where('user_id', Auth::id())
            ->whereIn('status', [WalletStatusEnum::ACTIVE, WalletStatusEnum::FROZEN])
            ->when($this->search !== '', function ($query): void {
                $term = '%' . trim($this->search) . '%';
                $query->where(function ($subQuery) use ($term): void {
                    $subQuery->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($this->statusFilter !== '', function ($query): void {
                $query->where('status', $this->statusFilter);
            })
            ->withCount('transactions')
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();
    }

    /**
     * Menghitung ringkasan total saldo dan jumlah kantong.
     *
     * @return array<string, float|int>
     */
    #[Computed]
    public function pocketSummary(): array
    {
        $allWallets = Wallet::query()
            ->where('user_id', Auth::id())
            ->whereIn('status', [WalletStatusEnum::ACTIVE, WalletStatusEnum::FROZEN])
            ->get();

        $activeWallets = $allWallets->where('status', WalletStatusEnum::ACTIVE);
        $frozenWallets = $allWallets->where('status', WalletStatusEnum::FROZEN);

        return [
            'total_balance' => (float) $allWallets->sum('balance'),
            'active_balance' => (float) $activeWallets->sum('balance'),
            'frozen_balance' => (float) $frozenWallets->sum('balance'),
            'active_count' => $activeWallets->count(),
            'frozen_count' => $frozenWallets->count(),
        ];
    }

    /**
     * Mengambil data kantong yang sedang dipilih untuk dihapus.
     */
    #[Computed]
    public function deletingWallet(): ?Wallet
    {
        if (! $this->deletingWalletId) {
            return null;
        }

        return Wallet::query()
            ->where('user_id', Auth::id())
            ->find($this->deletingWalletId);
    }

    /**
     * Mengambil opsi kantong tujuan pemindahan saldo saat penutupan kantong.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function destinationWalletOptions(): array
    {
        return Wallet::query()
            ->where('user_id', Auth::id())
            ->where('status', WalletStatusEnum::ACTIVE)
            ->when($this->deletingWalletId, fn ($q) => $q->where('id', '!=', $this->deletingWalletId))
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get()
            ->map(fn (Wallet $wallet): array => [
                'label' => $wallet->name . ' (' . $wallet->formattedBalance() . ')',
                'value' => $wallet->id,
            ])
            ->all();
    }

    /**
     * Membuka modal tambah kantong baru.
     */
    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->name = '';
        $this->icon = WalletIconEnum::WALLET->value;
        $this->color = WalletColorEnum::EMERALD->value;
        $this->description = '';
        $this->initial_balance = '';
        $this->createModal = true;
    }

    /**
     * Menyimpan kantong keuangan baru.
     */
    public function createPocket(WalletService $walletService): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'icon' => ['required', 'string'],
            'color' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:255'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
        ], [
            'name.required' => 'Nama kantong wajib diisi.',
            'initial_balance.min' => 'Saldo awal tidak boleh kurang dari Rp0.',
        ]);

        $walletService->createWallet((int) Auth::id(), [
            'name' => $validated['name'],
            'icon' => $validated['icon'],
            'color' => $validated['color'],
            'description' => $validated['description'],
            'initial_balance' => $validated['initial_balance'] !== '' ? $validated['initial_balance'] : 0,
        ]);

        $this->createModal = false;
        unset($this->wallets, $this->pocketSummary);
        $this->toast()->success('Kantong Dibuat', 'Kantong keuangan baru berhasil ditambahkan.')->send();
    }

    /**
     * Membuka modal ubah informasi kantong.
     */
    public function openEditModal(int $walletId): void
    {
        $this->resetValidation();
        $wallet = Wallet::query()
            ->where('user_id', Auth::id())
            ->findOrFail($walletId);

        $this->editingWalletId = $wallet->id;
        $this->edit_name = $wallet->name;
        $this->edit_icon = $wallet->icon;
        $this->edit_color = $wallet->color;
        $this->edit_description = (string) ($wallet->description ?? '');
        $this->editModal = true;
    }

    /**
     * Menyimpan perubahan informasi kantong tanpa mengubah histori transaksi lama.
     */
    public function updatePocket(WalletService $walletService): void
    {
        if (! $this->editingWalletId) {
            return;
        }

        $validated = $this->validate([
            'edit_name' => ['required', 'string', 'max:100'],
            'edit_icon' => ['required', 'string'],
            'edit_color' => ['required', 'string'],
            'edit_description' => ['nullable', 'string', 'max:255'],
        ], [
            'edit_name.required' => 'Nama kantong wajib diisi.',
        ]);

        $wallet = Wallet::query()
            ->where('user_id', Auth::id())
            ->findOrFail($this->editingWalletId);

        $walletService->updateWallet($wallet, [
            'name' => $validated['edit_name'],
            'icon' => $validated['edit_icon'],
            'color' => $validated['edit_color'],
            'description' => $validated['edit_description'],
        ]);

        $this->editModal = false;
        unset($this->wallets, $this->pocketSummary);
        $this->toast()->success('Kantong Diperbarui', 'Informasi kantong berhasil disimpan.')->send();
    }

    /**
     * Membekukan atau mengaktifkan kembali kantong.
     */
    public function toggleFreeze(int $walletId, WalletService $walletService): void
    {
        $wallet = Wallet::query()
            ->where('user_id', Auth::id())
            ->findOrFail($walletId);

        try {
            if ($wallet->isFrozen()) {
                $walletService->unfreezeWallet($wallet);
                $this->toast()->success('Kantong Diaktifkan', "Kantong \"{$wallet->name}\" dapat digunakan kembali untuk bertransaksi.")->send();
            } else {
                $walletService->freezeWallet($wallet);
                $this->toast()->info('Kantong Dibekukan', "Kantong \"{$wallet->name}\" dikunci sementara dari transaksi keluar/masuk.")->send();
            }

            unset($this->wallets, $this->pocketSummary);
        } catch (\DomainException|ValidationException $e) {
            $firstMessage = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal mengubah status kantong.')
                : $e->getMessage();
            $this->toast()->error('Tidak Dapat Diproses', $firstMessage)->send();
        }
    }

    /**
     * Membuka modal konfirmasi penghapusan / penutupan kantong.
     */
    public function openDeleteModal(int $walletId): void
    {
        $this->resetValidation();
        $this->deletingWalletId = $walletId;
        $this->delete_destination_wallet_id = null;
        unset($this->deletingWallet, $this->destinationWalletOptions);
        $this->deleteModal = true;
    }

    /**
     * Menutup dan menghapus (soft delete) kantong setelah memastikan saldo nol atau dipindahkan.
     */
    public function confirmDeletePocket(WalletService $walletService): void
    {
        if (! $this->deletingWalletId) {
            return;
        }

        try {
            $walletService->deleteWallet(
                (int) Auth::id(),
                $this->deletingWalletId,
                $this->delete_destination_wallet_id
            );

            $this->deleteModal = false;
            $this->deletingWalletId = null;
            $this->delete_destination_wallet_id = null;
            unset($this->wallets, $this->pocketSummary, $this->deletingWallet);

            $this->toast()->success(
                'Kantong Ditutup & Dihapus',
                'Kantong telah dinonaktifkan dan seluruh histori transaksi tetap tersimpan aman.'
            )->send();
        } catch (\DomainException|ValidationException $e) {
            $firstMessage = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal menghapus kantong.')
                : $e->getMessage();
            $this->addError('delete_destination_wallet_id', $firstMessage);
        }
    }
};
