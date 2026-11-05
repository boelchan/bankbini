<?php

use App\Livewire\Traits\WithTableX;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Spatie\Permission\Models\Role;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use WithTableX, Interactions;

    public $sortFieldDefault = 'name';
    public $sortDirectionDefault = 'asc';

    #[Url]
    public $search_name = '';
    #[Url]
    public $search_email = '';
    #[Url]
    public $search_role = '';
    #[Url]
    public $search_status = '';

    public $roles;
    public $status;

    public function mount()
    {
        $this->roles = Role::pluck('name', 'id')->toArray();
        $this->status = ['active' => 'active', 'inactive' => 'inactive'];
    }

    #[Computed]
    public function dataTable()
    {
        return User::with('roles')->with('village')
            ->when($this->search_name, fn($q) => $q->where('name', 'like', '%' . $this->search_name . '%'))
            ->when($this->search_email, fn($q) => $q->where('email', 'like', '%' . $this->search_email . '%'))
            ->when($this->search_role, fn($q) => $q->whereHas('roles', fn($q) => $q->where('name', $this->search_role)))
            ->when($this->search_status, fn($q) => $q->where('status', $this->search_status))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }


    public function delete($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        $this->toast()->success('User berhasil dihapus')->send();
    }
};
