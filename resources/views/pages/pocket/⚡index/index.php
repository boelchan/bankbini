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
#[Title('Daftar Kantong')]
class extends Component
{
    use Interactions;

    /* State Modal Tambah Kantong */
    public bool $pocketModal = false;

    /* Form Tambah Kantong Baru */
    public string $pocket_name = '';
    public string $pocket_icon = '👛';
    public string $pocket_color = 'rose';
    public string $pocket_description = '';
    public string|int|float $pocket_initial_balance = '';

    /**
     * Mengambil daftar seluruh kantong aktif dan dibekukan keluarga.
     *
     * @return Collection<int, Wallet>
     */
    #[Computed]
    public function wallets(): Collection
    {
        return Wallet::query()
            ->whereIn('status', [WalletStatusEnum::ACTIVE, WalletStatusEnum::FROZEN])
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();
    }

    /**
     * Membuka modal pembuatan kantong baru.
     */
    public function openPocketModal(): void
    {
        $this->pocket_name = '';
        $this->pocket_icon = WalletIconEnum::WALLET->emoji();
        $this->pocket_color = WalletColorEnum::ROSE->value;
        $this->pocket_description = '';
        $this->pocket_initial_balance = '';
        $this->pocketModal = true;
    }

    /**
     * Membuat kantong keuangan baru.
     */
    public function savePocket(WalletService $walletService): void
    {
        $this->validate([
            'pocket_name' => ['required', 'string', 'max:100'],
            'pocket_icon' => ['required', 'string', 'max:32'],
            'pocket_color' => ['required', 'string'],
            'pocket_initial_balance' => ['nullable', 'numeric', 'gte:0'],
            'pocket_description' => ['nullable', 'string', 'max:255'],
        ], [
            'pocket_name.required' => 'Nama kantong wajib diisi.',
            'pocket_icon.required' => 'Ikon kantong wajib diisi.',
            'pocket_color.required' => 'Warna kantong wajib dipilih.',
            'pocket_initial_balance.gte' => 'Saldo awal tidak boleh negatif.',
        ]);

        try {
            $walletService->createWallet([
                'user_id' => (int) Auth::id(),
                'name' => $this->pocket_name,
                'icon' => $this->pocket_icon,
                'color' => $this->pocket_color,
                'description' => $this->pocket_description,
                'initial_balance' => $this->pocket_initial_balance ? (float) $this->pocket_initial_balance : 0,
            ]);

            $this->pocketModal = false;
            unset($this->wallets);
            unset($this->summary);

            $this->toast()->success('Berhasil!', 'Kantong baru berhasil dibuat.');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'pocket_name' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Ringkasan total saldo seluruh kantong.
     *
     * @return array{total_balance: float}
     */
    #[Computed]
    public function summary(): array
    {
        return [
            'total_balance' => round((float) Wallet::query()->visible()->sum('balance'), 2),
        ];
    }
};
