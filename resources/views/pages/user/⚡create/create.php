<?php

use App\Enums\RolesEnum;
use App\Models\User;
use App\Models\IndonesiaRegion;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use Interactions;

    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $nik;
    public $role;
    public ?int $village_code = null;

    public $roles;
    public $villages;
    public $status = 'active';

    public function mount()
    {
        $this->roles = Role::pluck('name', 'id')->all();
        $this->villages = IndonesiaRegion::where('parent', 352911)
            ->orderBy('name', 'ASC')
            ->get()
            ->map(fn($item) => ['label' => $item->name, 'value' => (int) $item->code])
            ->toArray();
    }

    public function store()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'nik' => 'nullable|string|size:16|unique:users,nik',
            'role' => 'required',
            'status' => 'required',
            'village_code' => 'nullable|integer',
        ], [
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'nik' => $this->nik,
            'status' => $this->status,
            'village_code' => $this->village_code ? (int) $this->village_code : null,
        ]);

        $user->assignRole($this->role);

        $this->toast()->success('User berhasil ditambahkan')->flash()->send();

        return to_route('user');
    }
};
