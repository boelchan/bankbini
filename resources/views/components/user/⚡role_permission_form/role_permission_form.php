<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use TallStackUi\Traits\Interactions;

new class extends Component {
    use Interactions;

    public $showModal = false;
    public $role_id;
    public $role_name;
    public $selectedPermissions = [];
    public $allPermissions = [];

    #[On('load-permission')]
    public function mount()
    {
        $this->allPermissions = Permission::all()->pluck('name', 'id')->toArray();
    }

    #[On('role-permission-edit')]
    public function edit($role_id)
    {
        $role = Role::findOrFail($role_id);
        $this->role_id = $role->id;
        $this->role_name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('id')->toArray();
        $this->showModal = true;
    }

    public function save()
    {
        $role = Role::findOrFail($this->role_id);

        // Sync permissions by ID
        $permissions = Permission::whereIn('id', $this->selectedPermissions)->get();
        $role->syncPermissions($permissions);

        $this->toast()->success('Permission berhasil diperbarui')->send();
        $this->closeModal();
        $this->dispatch('load-role');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['role_id', 'role_name', 'selectedPermissions']);
    }
};
