<?php

use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use Interactions;

    public $roles;
    public $permissions;

    // Modal lihat role (dari permission)
    public $showRolesModal = false;
    public $permissionName = '';
    public $permissionRoles = [];

    // Modal lihat user (dari role)
    public $showUsersModal = false;
    public $roleName = '';
    public $roleUsers = [];

    public function mount()
    {
        $this->loadRole();
        $this->loadPermission();
    }

    #[On('load-role')]
    public function loadRole()
    {
        $this->roles = Role::with('permissions')->get();
    }

    #[On('load-permission')]
    public function loadPermission()
    {
        $this->permissions = Permission::all();
    }

    public function deleteRole($id)
    {
        $role = Role::find($id);
        if ($role->users()->count() > 0) {
            $this->toast()->error('Role tidak bisa dihapus karena masih digunakan')->send();
            return;
        }
        $role->delete();

        $this->toast()->success('Role berhasil dihapus')->send();
        $this->dispatch('load-role');
    }

    public function deletePermission($id)
    {
        $permission = Permission::find($id);
        if ($permission->roles()->count() > 0) {
            $this->toast()->error('Permission tidak bisa dihapus karena masih digunakan')->send();
            return;
        }
        $permission->delete();

        $this->toast()->success('Permission berhasil dihapus')->send();
        $this->dispatch('load-permission');
    }

    public function lihatRole($permissionId)
    {
        $permission = Permission::with('roles')->findOrFail($permissionId);
        $this->permissionName = $permission->name;
        $this->permissionRoles = $permission->roles->map(fn($role) => [
            'id' => $role->id,
            'name' => $role->name,
            'users_count' => $role->users()->count(),
        ])->toArray();
        $this->showRolesModal = true;
    }

    public function closeRolesModal()
    {
        $this->showRolesModal = false;
        $this->reset(['permissionName', 'permissionRoles']);
    }

    public function lihatUser($roleId)
    {
        $role = Role::findOrFail($roleId);
        $this->roleName = $role->name;
        $this->roleUsers = User::role($role->name)->get()->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ])->toArray();
        $this->showUsersModal = true;
    }

    public function closeUsersModal()
    {
        $this->showUsersModal = false;
        $this->reset(['roleName', 'roleUsers']);
    }
};
