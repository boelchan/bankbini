<?php

use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

new class extends Component
{
    public $permission_id;
    public $name;

    #[On('permission-edit')]
    public function edit($permission_id)
    {
        $permission = Permission::findOrFail($permission_id);
        $this->name = $permission->name;
        $this->permission_id = $permission->id;
    }

    public function store()
    {
        $this->validate([
            'name' => 'required|unique:permissions,name,' . $this->permission_id,
        ]);

        Permission::updateOrCreate(['id' => $this->permission_id], ['name' => Str::kebab($this->name)]);
        $this->reset();
        $this->dispatch('load-permission');
    }

    public function batalEdit()
    {
        $this->reset();
    }
};
