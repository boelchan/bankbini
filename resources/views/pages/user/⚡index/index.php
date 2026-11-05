<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Livewire\Traits\WithTableX;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Title('Kelola Pengguna')] class extends Component
{
    use Interactions, WithTableX;

    public string $sortFieldDefault = 'name';
    public string $sortDirectionDefault = 'asc';

    #[Url]
    public string $search_name = '';

    #[Url]
    public string $search_email = '';

    #[Url]
    public ?string $search_role = null;

    #[Url]
    public ?string $search_status = null;

    /**
     * Mengambil daftar pengguna dengan filter dan paginasi.
     */
    #[Computed]
    public function dataTable(): LengthAwarePaginator
    {
        return User::with('roles')
            ->withCount('wallets')
            ->when($this->search_name !== '', fn ($q) => $q->where('name', 'like', '%' . $this->search_name . '%'))
            ->when($this->search_email !== '', fn ($q) => $q->where('email', 'like', '%' . $this->search_email . '%'))
            ->when($this->search_role, fn ($q) => $q->whereHas('roles', fn ($rq) => $rq->where('name', $this->search_role)))
            ->when($this->search_status, fn ($q) => $q->where('status', $this->search_status))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    /**
     * Mengambil opsi peran pengguna dari Enum.
     *
     * @return array<int, array{label: string, value: string}>
     */
    #[Computed]
    public function roleOptions(): array
    {
        return RolesEnum::choices();
    }

    /**
     * Mengambil opsi status pengguna dari Enum.
     *
     * @return array<int, array{label: string, value: string}>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return UserStatusEnum::choices();
    }

    /**
     * Menghapus pengguna setelah konfirmasi.
     */
    public function delete(int $id): void
    {
        if ($id === 1 || $id === (int) auth()->id()) {
            $this->toast()->error('Gagal', 'Akun utama atau akun yang sedang digunakan tidak dapat dihapus.')->send();
            return;
        }

        $user = User::findOrFail($id);
        $user->delete();
        $this->toast()->success('Berhasil', 'Pengguna berhasil dihapus.')->send();
    }
};
