<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Spatie\Permission\Models\Role;

new class extends Component {
    public $role_id;
    public $name;

    #[On('role-edit')]
    public function edit($role_id)
    {
        $role = Role::findOrFail($role_id);
        $this->name = $role->name;
        $this->role_id = $role->id;
    }

    public function batalEdit()
    {
        $this->reset();
    }

    public function store()
    {
        $this->validate([
            'name' => 'required|unique:roles,name,' . $this->role_id,
        ]);

        Role::updateOrCreate(['id' => $this->role_id], ['name' => $this->name]);
        $this->reset();
        $this->dispatch('load-role');
    }
};
?>

<div>
    <form wire:submit.prevent="store">
        <input type="hidden" wire:model="role_id">

        <x-input placeholder="Nama role baru" wire:model="name">
            <x-slot:suffix>
                <button class="flex items-center justify-center h-8 w-8 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors" type="submit"><x-ti-check class="size-4" /></button>
                <button class="flex items-center justify-center h-8 w-8 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 transition-colors ml-1" type="button" wire:click="batalEdit" wire:show="role_id">
                    <x-ti-x class="size-4" />
                </button>
            </x-slot:suffix>
        </x-input>
    </form>
</div>
