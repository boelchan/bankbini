<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use Interactions;

    // Profile Properties
    public $name = '';
    public $email = '';
    public $nik = '';

    // Password Properties
    public $current_password = '';
    public $password = '';
    public $password_confirmation = '';
    public $password_for_email = '';

    public $tab = 'summary'; // summary, edit_name, edit_email, edit_password

    public function mount()
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->nik = $user->nik;
    }

    public function setTab($tab)
    {
        $this->tab = $tab;
        $this->reset(['current_password', 'password', 'password_confirmation', 'password_for_email']);
        $this->resetValidation();

        // Sync name/email from DB in case they were changed but not saved in other tabs
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->nik = $user->nik;
    }

    public function updateName()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'nik' => 'nullable|string|size:16|unique:users,nik,' . Auth::id(),
        ]);

        Auth::user()->update([
            'name' => $this->name,
            'nik' => $this->nik,
        ]);

        $this->toast()->success('Berhasil', 'Profil berhasil diubah.')->send();
    }

    public function updateEmail()
    {
        $user = Auth::user();

        $this->validate([
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password_for_email' => 'required|current_password',
        ], [
            'password_for_email.required' => 'Konfirmasi password diperlukan untuk merubah email.',
            'password_for_email.current_password' => 'Password yang Anda masukkan salah.',
        ]);

        // Check if email actually changed
        if ($user->email === $this->email) {
            $this->toast()->info('Info', 'Email yang dimasukkan sama dengan email yang terdaftar.')->send();
            return;
        }

        $user->email = $this->email;
        $user->save();

        // Reset password field
        $this->password_for_email = '';

        $this->toast()->success('Email Diubah', 'Email Anda berhasil diubah.')->send();
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        $user->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->toast()->success('Berhasil', 'Password berhasil diubah.')->send();
    }
};
