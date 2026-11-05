<?php

use Livewire\Attributes\Locked;
use Livewire\Component;
use TallStackUi\Traits\Interactions;
use App\Models\User;
use App\Models\IndonesiaRegion;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

new class extends Component
{
    use Interactions;

    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $nik;
    public $role;
    #[Locked]
    public $id;
    public $status;
    public ?int $village_code = null;

    public $roles;
    public $villages;

    public function mount(User $user)
    {
        $this->id = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->nik = $user->nik;
        $this->status = $user->status;
        $this->village_code = $user->village_code ? (int) $user->village_code : null;

        $this->role = $user->roles->pluck('name')->toArray();

        $this->roles = Role::pluck('name', 'id')->all();
        $this->villages = IndonesiaRegion::where('parent', 352911)
            ->orderBy('name', 'ASC')
            ->get()
            ->map(fn($item) => ['label' => $item->name, 'value' => (int) $item->code])
            ->toArray();
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->id,
            'password' => 'nullable|string|min:8|confirmed',
            'nik' => 'nullable|string|size:16|unique:users,nik,' . $this->id,
            'role' => 'required',
            'status' => 'required|in:active,inactive',
            'village_code' => 'nullable|integer',
        ], [
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'status.in' => 'Status tidak valid.',
        ]);

        $user = User::findOrFail($this->id);
        $wasActive = $user->status === 'active';

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'nik' => $this->nik,
            'status' => $this->status,
            'village_code' => $this->village_code ? (int) $this->village_code : null,
        ]);

        $passwordChanged = filled($this->password);
        if ($passwordChanged) {
            $user->update([
                'password' => Hash::make($this->password),
            ]);
        }

        $user->syncRoles($this->role);

        // Putus semua sesi lama bila password direset admin atau akun dinonaktifkan.
        $deactivated = $wasActive && $this->status !== 'active';
        if (($passwordChanged || $deactivated) && $user->id !== auth()->id()) {
            $user->invalidateSessions();
        }

        $this->toast()->success('User berhasil diubah')->flash()->send();

        return to_route('user');
    }
};
