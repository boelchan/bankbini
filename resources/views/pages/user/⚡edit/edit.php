<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Title('Ubah Pengguna')] class extends Component
{
    use Interactions;

    #[Locked]
    public int $id;

    public string $name = '';
    public string $email = '';
    public ?string $password = null;
    public ?string $password_confirmation = null;
    public ?string $phone = null;
    public ?string $jabatan = null;
    public string $role = RolesEnum::MEMBER->value;
    public string $status = UserStatusEnum::ACTIVE->value;

    /**
     * Memuat data pengguna yang akan diubah.
     */
    public function mount(User $user): void
    {
        $this->id = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->jabatan = $user->jabatan;
        $this->status = $user->status;
        $this->role = (string) ($user->roles->first()?->name ?? RolesEnum::MEMBER->value);
    }

    /**
     * Memperbarui data pengguna di database.
     */
    public function update(): mixed
    {
        $this->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|max:191|unique:users,email,' . $this->id,
            'password' => 'nullable|string|min:6|confirmed',
            'phone' => 'nullable|string|max:30',
            'jabatan' => 'nullable|string|max:80',
            'role' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        $user = User::findOrFail($this->id);
        $wasActive = $user->status === 'active';

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'jabatan' => $this->jabatan,
            'status' => $this->status,
        ]);

        $passwordChanged = filled($this->password);
        if ($passwordChanged) {
            $user->update([
                'password' => Hash::make((string) $this->password),
            ]);
        }

        $user->syncRoles([$this->role]);

        $deactivated = $wasActive && $this->status !== 'active';
        if (($passwordChanged || $deactivated) && $user->id !== auth()->id()) {
            $user->invalidateSessions();
        }

        $this->toast()->success('Berhasil', 'Data pengguna berhasil diperbarui.')->flash()->send();

        return to_route('user');
    }
};
