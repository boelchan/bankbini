<?php

use App\Models\Post;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use TallStackUi\Traits\Interactions;

new class extends Component {
    use WithFileUploads, Interactions;

    public $judul;
    public $slug;
    public $isi;
    public $gambar;
    public $publish = 'ya';

    protected $rules = [
        'judul' => 'required|max:250|unique:posts,judul',
        'gambar' => 'required|image|max:2048', // 2MB Max
        'publish' => 'required|in:ya,tidak',
    ];

    public function updatedJudul($value)
    {
        $this->slug = Str::slug($value);
    }

    public function save()
    {
        $this->validate();

        $path = $this->gambar->store('posts', 'public');

        Post::create([
            'judul' => $this->judul,
            'slug' => $this->slug,
            'isi' => $this->isi,
            'gambar' => $path,
            'publish' => $this->publish,
        ]);

        $this->toast()->success('Pamflet berhasil ditambahkan')->flash()->send();
        return $this->redirect(route('post.index'), navigate: true);
    }
};
?>

<div>
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-medium text-slate-900">Tambah Pamflet</h1>
            <nav class="flex items-center gap-1.5 text-xs text-slate-500 mt-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition-colors"><x-ti-home class="size-4" /></a>
                <span>/</span>
                <a href="{{ route('post.index') }}" class="hover:text-emerald-600 transition-colors">Pamflet</a>
                <span>/</span>
                <span class="text-slate-700 font-medium">Tambah</span>
            </nav>
        </div>
    </div>

    <div class="mt-6 max-w-4xl">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form class="space-y-4" wire:submit="save">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-4">
                        <x-input label="Judul *" placeholder="Masukkan judul pamflet" wire:model="judul" />
                    </div>

                    <div class="space-y-4">
                        <x-upload label="Gambar *" wire:model="gambar" accept="image/*">
                            @if ($gambar)
                                <img class="mt-2 h-32 w-full rounded-xl object-cover shadow-sm border border-slate-100" src="{{ $gambar->temporaryUrl() }}">
                            @endif
                        </x-upload>
                    </div>
                </div>

                <x-textarea label="Isi Pamflet" placeholder="Tuliskan isi pamflet di sini..." wire:model="isi" rows="10" />

                <x-select.native label="Publish *" :options="[['label' => 'Ya', 'value' => 'ya'], ['label' => 'Tidak', 'value' => 'tidak']]" wire:model="publish" />

                <div class="mt-6 flex justify-end gap-2">
                    <x-button color="gray" flat href="{{ route('post.index') }}" wire:navigate>Batal</x-button>
                    <x-button submit color="emerald" loading="save">Simpan</x-button>
                </div>
            </form>
        </div>
    </div>
</div>
