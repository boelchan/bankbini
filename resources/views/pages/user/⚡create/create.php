<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Title('Tambah Pengguna')] class extends Component
{
    use Interactions;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public ?string $phone = null;
    public ?string $jabatan = 'Pemilik Dompet';
    public string $role = RolesEnum::MEMBER->value;
    public string $status = UserStatusEnum::ACTIVE->value;

    /**
     * Menyimpan pengguna baru ke dalam database.
     */
    public function store(): mixed
    {
        $this->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|max:191|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:30',
            'jabatan' => 'nullable|string|max:80',
            'role' => 'required|string',
            'status' => 'required|in:active,inactive',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Alamat email sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'phone' => $this->phone,
            'jabatan' => $this->jabatan,
            'status' => $this->status,
        ]);

        $user->assignRole($this->role);

        $this->toast()->success('Berhasil', 'Pengguna baru berhasil ditambahkan.')->flash()->send();

        return to_route('user');
    }
};
