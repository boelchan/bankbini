<?php

declare(strict_types=1);

use App\Enums\CategoryTypeEnum;
use App\Enums\WalletColorEnum;
use App\Enums\WalletIconEnum;
use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new #[Title('Pengaturan & Profil')] class extends Component
{
    use Interactions;

    public string $name = '';
    public string $email = '';
    public ?string $phone = null;
    public ?string $jabatan = null;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $password_for_email = '';

    public string $tab = 'summary';

    // Form Kategori Transaksi Kustom
    public string $cat_name = '';
    public string $cat_type = CategoryTypeEnum::EXPENSE->value;
    public string $cat_icon = WalletIconEnum::SHOPPING_BAG->value;
    public string $cat_color = WalletColorEnum::ROSE->value;
    public string|int|float $cat_budget_limit = '';

    // State Modal Ubah Kategori
    public bool $editCategoryModal = false;
    public ?int $editingCategoryId = null;
    public string $edit_cat_name = '';
    public string $edit_cat_type = CategoryTypeEnum::EXPENSE->value;
    public string $edit_cat_icon = WalletIconEnum::SHOPPING_BAG->value;
    public string $edit_cat_color = WalletColorEnum::ROSE->value;
    public string|int|float $edit_cat_budget_limit = '';

    /**
     * Inisialisasi data profil pengguna yang sedang masuk.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->phone = $user->phone;
        $this->jabatan = $user->jabatan;
    }

    /**
     * Mengganti tab aktif pada halaman pengaturan.
     */
    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->reset(['current_password', 'password', 'password_confirmation', 'password_for_email']);
        $this->resetValidation();

        $user = Auth::user();
        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->phone = $user->phone;
        $this->jabatan = $user->jabatan;
    }

    /**
     * Memperbarui nama, nomor telepon, dan peran keluarga pengguna.
     */
    public function updateName(): void
    {
        $this->validate([
            'name' => 'required|string|max:191',
            'phone' => 'nullable|string|max:30',
            'jabatan' => 'nullable|string|max:80',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
        ]);

        Auth::user()->update([
            'name' => $this->name,
            'phone' => $this->phone,
            'jabatan' => $this->jabatan,
        ]);

        $this->toast()->success('Berhasil', 'Informasi profil berhasil diperbarui.')->send();
    }

    /**
     * Memperbarui alamat email pengguna dengan verifikasi kata sandi.
     */
    public function updateEmail(): void
    {
        $user = Auth::user();

        $this->validate([
            'email' => 'required|email|max:191|unique:users,email,' . $user->id,
            'password_for_email' => 'required|current_password',
        ], [
            'password_for_email.required' => 'Konfirmasi kata sandi diperlukan untuk mengubah email.',
            'password_for_email.current_password' => 'Kata sandi yang Anda masukkan salah.',
        ]);

        if ($user->email === $this->email) {
            $this->toast()->info('Informasi', 'Email yang dimasukkan sama dengan email saat ini.')->send();
            return;
        }

        $user->email = $this->email;
        $user->save();

        $this->password_for_email = '';

        $this->toast()->success('Email Diperbarui', 'Alamat email Anda berhasil diubah.')->send();
    }

    /**
     * Memperbarui kata sandi keamanan akun pengguna.
     */
    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|min:6|confirmed',
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $user = Auth::user();
        $currentSessionId = session()->getId();

        $user->update([
            'password' => Hash::make($this->password),
        ]);

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->where('id', '!=', $currentSessionId)
                ->delete();
        }

        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
        session()->regenerate();

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->toast()->success('Berhasil', 'Kata sandi keamanan berhasil diperbarui.')->send();
    }

    /**
     * Mengambil daftar seluruh kategori transaksi (bawaan & kustom pengguna).
     *
     * @return Collection<int, TransactionCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return TransactionCategory::query()
            ->availableFor((int) Auth::id())
            ->withCount('transactions')
            ->orderBy('type')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Menambahkan kategori transaksi kustom milik pengguna.
     */
    public function addCategory(): void
    {
        $this->validate([
            'cat_name' => 'required|string|max:60',
            'cat_type' => 'required|in:income,expense',
            'cat_icon' => 'required|string|max:60',
            'cat_color' => 'required|string|max:30',
            'cat_budget_limit' => 'nullable|numeric|gte:0',
        ], [
            'cat_name.required' => 'Nama kategori wajib diisi.',
            'cat_budget_limit.gte' => 'Batas anggaran tidak boleh negatif.',
        ]);

        TransactionCategory::create([
            'user_id' => Auth::id(),
            'name' => trim($this->cat_name),
            'type' => $this->cat_type,
            'icon' => $this->cat_icon,
            'color' => $this->cat_color,
            'budget_limit' => $this->cat_budget_limit ? (float) $this->cat_budget_limit : 0,
            'is_default' => false,
        ]);

        $this->cat_name = '';
        $this->cat_budget_limit = '';
        unset($this->categories);

        $this->toast()->success('Kategori Ditambahkan', 'Kategori transaksi baru siap digunakan.')->send();
    }

    /**
     * Membuka modal ubah kategori transaksi.
     */
    public function openEditCategoryModal(int $categoryId): void
    {
        $cat = TransactionCategory::findOrFail($categoryId);
        $this->editingCategoryId = $cat->id;
        $this->edit_cat_name = $cat->name;
        $this->edit_cat_type = $cat->type->value;
        $this->edit_cat_icon = $cat->icon;
        $this->edit_cat_color = $cat->color;
        $this->edit_cat_budget_limit = (float) $cat->budget_limit > 0 ? (float) $cat->budget_limit : '';
        $this->editCategoryModal = true;
    }

    /**
     * Menyimpan perubahan informasi kategori transaksi.
     */
    public function updateCategory(): void
    {
        $this->validate([
            'edit_cat_name' => 'required|string|max:60',
            'edit_cat_type' => 'required|in:income,expense',
            'edit_cat_icon' => 'required|string|max:60',
            'edit_cat_color' => 'required|string|max:30',
            'edit_cat_budget_limit' => 'nullable|numeric|gte:0',
        ], [
            'edit_cat_name.required' => 'Nama kategori wajib diisi.',
            'edit_cat_budget_limit.gte' => 'Batas anggaran tidak boleh negatif.',
        ]);

        $cat = TransactionCategory::findOrFail($this->editingCategoryId);
        $cat->update([
            'name' => trim($this->edit_cat_name),
            'type' => $this->edit_cat_type,
            'icon' => $this->edit_cat_icon,
            'color' => $this->edit_cat_color,
            'budget_limit' => $this->edit_cat_budget_limit ? (float) $this->edit_cat_budget_limit : 0,
        ]);

        $this->editCategoryModal = false;
        unset($this->categories);

        $this->toast()->success('Berhasil', 'Kategori transaksi berhasil diperbarui.')->send();
    }

    /**
     * Menampilkan dialog konfirmasi hapus kategori.
     */
    public function askDeleteCategory(int $categoryId): void
    {
        $cat = TransactionCategory::find($categoryId);
        if (!$cat) {
            return;
        }

        if ($cat->transactions()->exists()) {
            $this->toast()->error('Tidak Bisa Dihapus', 'Kategori ini sudah dipakai pada transaksi sehingga tidak dapat dihapus.')->send();

            return;
        }

        $this->dialog()
            ->question('Hapus Kategori Ini?', sprintf('Kategori "%s" belum pernah dipakai, jadi aman dihapus permanen.', $cat->name))
            ->confirm('Ya, Hapus', 'deleteCategory', $categoryId)
            ->cancel('Batal')
            ->send();
    }

    /**
     * Menghapus kategori transaksi.
     */
    public function deleteCategory(int $categoryId): void
    {
        $cat = TransactionCategory::find($categoryId);

        if (!$cat) {
            $this->toast()->error('Gagal', 'Kategori tidak ditemukan.')->send();
            return;
        }

        if ($cat->transactions()->exists()) {
            $this->toast()->error('Tidak Bisa Dihapus', 'Kategori ini sudah dipakai pada transaksi sehingga tidak dapat dihapus.')->send();
            return;
        }

        $cat->delete();
        unset($this->categories);
        $this->toast()->success('Dihapus', 'Kategori berhasil dihapus.')->send();
    }
};
