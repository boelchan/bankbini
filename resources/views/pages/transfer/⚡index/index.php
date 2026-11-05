<?php

declare(strict_types=1);

use App\Enums\WalletStatusEnum;
use App\Models\Transfer;
use App\Models\Wallet;
use App\Services\TransferService;
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
#[Title('Transfer Antar Kantong')]
class extends Component
{
    use Interactions;
    use WithPagination;

    /* Form Transfer Antar Kantong */
    public ?int $source_wallet_id = null;
    public ?int $destination_wallet_id = null;
    public string|int|float $amount = '';
    public string $transaction_date = '';
    public string $description = '';
    public string $notes = '';

    /* Filter Riwayat Transfer */
    public string $search = '';

    /* Modal Detail Pasangan Transfer */
    public bool $detailModal = false;
    public ?int $selectedTransferId = null;

    /**
     * Inisialisasi nilai default kantong pengirim dan penerima.
     */
    public function mount(): void
    {
        $this->transaction_date = now()->format('Y-m-d\TH:i');

        $activeWallets = $this->activeWallets;
        if ($activeWallets->count() >= 1) {
            $this->source_wallet_id = $activeWallets->first()->id;
        }
        if ($activeWallets->count() >= 2) {
            $this->destination_wallet_id = $activeWallets->skip(1)->first()->id;
        }
    }

    /**
     * Reset halaman paginasi saat pencarian riwayat transfer berubah.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Mengambil koleksi kantong aktif milik pengguna.
     *
     * @return Collection<int, Wallet>
     */
    #[Computed]
    public function activeWallets(): Collection
    {
        return Wallet::query()
            ->where('user_id', Auth::id())
            ->where('status', WalletStatusEnum::ACTIVE)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();
    }

    /**
     * Mengambil opsi dropdown kantong aktif.
     *
     * @return array<int, array{label: string, value: int}>
     */
    #[Computed]
    public function activeWalletOptions(): array
    {
        return $this->activeWallets
            ->map(fn (Wallet $w): array => [
                'label' => $w->name . ' (' . $w->formattedBalance() . ')',
                'value' => $w->id,
            ])
            ->values()
            ->all();
    }

    /**
     * Mengambil model kantong pengirim yang sedang dipilih.
     */
    #[Computed]
    public function selectedSourceWallet(): ?Wallet
    {
        if (! $this->source_wallet_id) {
            return null;
        }

        return $this->activeWallets->firstWhere('id', $this->source_wallet_id);
    }

    /**
     * Mengambil model kantong penerima yang sedang dipilih.
     */
    #[Computed]
    public function selectedDestinationWallet(): ?Wallet
    {
        if (! $this->destination_wallet_id) {
            return null;
        }

        return $this->activeWallets->firstWhere('id', $this->destination_wallet_id);
    }

    /**
     * Menukar posisi kantong pengirim dan kantong penerima.
     */
    public function swapWallets(): void
    {
        $temp = $this->source_wallet_id;
        $this->source_wallet_id = $this->destination_wallet_id;
        $this->destination_wallet_id = $temp;
        unset($this->selectedSourceWallet, $this->selectedDestinationWallet);
    }

    /**
     * Mengeksekusi pemindahan saldo antar kantong secara atomik.
     */
    public function submitTransfer(TransferService $transferService): void
    {
        $validated = $this->validate([
            'source_wallet_id' => ['required', 'integer'],
            'destination_wallet_id' => ['required', 'integer', 'different:source_wallet_id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'source_wallet_id.required' => 'Pilih kantong pengirim terlebih dahulu.',
            'destination_wallet_id.required' => 'Pilih kantong penerima terlebih dahulu.',
            'destination_wallet_id.different' => 'Kantong pengirim dan kantong penerima harus berbeda.',
            'amount.required' => 'Nominal transfer wajib diisi.',
            'amount.gt' => 'Nominal transfer harus lebih besar dari Rp0.',
        ]);

        try {
            $transfer = $transferService->executeTransfer((int) Auth::id(), [
                'source_wallet_id' => $validated['source_wallet_id'],
                'destination_wallet_id' => $validated['destination_wallet_id'],
                'amount' => $validated['amount'],
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'],
                'notes' => $validated['notes'],
                'idempotency_key' => 'page-trf-' . Str::uuid()->toString(),
            ]);

            $this->amount = '';
            $this->description = '';
            $this->notes = '';
            $this->transaction_date = now()->format('Y-m-d\TH:i');

            unset(
                $this->activeWallets,
                $this->activeWalletOptions,
                $this->selectedSourceWallet,
                $this->selectedDestinationWallet,
                $this->transfers,
            );

            $this->toast()->success(
                'Transfer Antar Kantong Berhasil',
                "Referensi {$transfer->transfer_code}: {$transfer->formattedAmount()} berhasil dipindahkan."
            )->send();
        } catch (\DomainException|ValidationException $e) {
            $firstMessage = $e instanceof ValidationException
                ? (collect($e->errors())->flatten()->first() ?? 'Gagal memproses transfer antar kantong.')
                : $e->getMessage();
            $this->addError('amount', $firstMessage);
        }
    }

    /**
     * Mengambil riwayat transfer terpaginasi.
     */
    #[Computed]
    public function transfers(): LengthAwarePaginator
    {
        return Transfer::query()
            ->with([
                'sourceWallet' => fn ($q) => $q->withTrashed(),
                'destinationWallet' => fn ($q) => $q->withTrashed(),
            ])
            ->where('user_id', Auth::id())
            ->when($this->search !== '', function ($query): void {
                $term = '%' . trim($this->search) . '%';
                $query->where(function ($sub) use ($term): void {
                    $sub->where('transfer_code', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('source_wallet_name_snapshot', 'like', $term)
                        ->orWhere('destination_wallet_name_snapshot', 'like', $term);
                });
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(10);
    }

    /**
     * Mengambil detail transfer beserta pasangan transaksi keluar dan masuk.
     */
    #[Computed]
    public function selectedTransfer(): ?Transfer
    {
        if (! $this->selectedTransferId) {
            return null;
        }

        return Transfer::query()
            ->with([
                'sourceWallet' => fn ($q) => $q->withTrashed(),
                'destinationWallet' => fn ($q) => $q->withTrashed(),
                'outTransaction',
                'inTransaction',
            ])
            ->where('user_id', Auth::id())
            ->find($this->selectedTransferId);
    }

    /**
     * Membuka modal detail pasangan transaksi transfer.
     */
    public function showTransferDetail(int $transferId): void
    {
        $this->selectedTransferId = $transferId;
        unset($this->selectedTransfer);
        $this->detailModal = true;
    }
};
