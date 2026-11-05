<?php

use App\Models\Post;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads, Interactions;

    public Post $post;
    public $judul;
    public $slug;
    public $isi;
    public $gambar;
    public $existing_gambar;
    public $publish;

    public function mount(Post $post)
    {
        $this->post = $post;
        $this->judul = $post->judul;
        $this->slug = $post->slug;
        $this->isi = $post->isi;
        $this->existing_gambar = $post->gambar;
        $this->publish = $post->publish;
    }

    protected function rules()
    {
        return [
            'judul' => 'required|max:250|unique:posts,judul,' . $this->post->id,
            'gambar' => 'nullable|image|max:2048',
            'publish' => 'required|in:ya,tidak',
        ];
    }

    public function updatedJudul($value)
    {
        $this->slug = Str::slug($value);
    }

    public function save()
    {
        $this->validate();

        $data = [
            'judul' => $this->judul,
            'slug' => $this->slug,
            'isi' => $this->isi,
            'publish' => $this->publish,
        ];

        if ($this->gambar) {
            // Delete old image
            if ($this->existing_gambar) {
                Storage::disk('public')->delete($this->existing_gambar);
            }
            $data['gambar'] = $this->gambar->store('posts', 'public');
        }

        $this->post->update($data);

        $this->toast()->success('Pamflet berhasil diperbarui')->flash()->send();
        return $this->redirect(route('post.index'), navigate: true);
    }
};
?>

<div>
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-medium text-slate-900">Ubah Pamflet</h1>
            <nav class="flex items-center gap-1.5 text-xs text-slate-500 mt-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition-colors"><x-ti-home class="size-4" /></a>
                <span>/</span>
                <a href="{{ route('post.index') }}" class="hover:text-emerald-600 transition-colors">Pamflet</a>
                <span>/</span>
                <span class="text-slate-700 font-medium">Ubah</span>
            </nav>
        </div>
    </div>

    <div class="mt-6 max-w-4xl">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form class="space-y-4" wire:submit="save">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-4">
                        <x-input label="Judul" placeholder="Masukkan judul pamflet" wire:model="judul" />
                    </div>
                    <div class="space-y-4">
                        <x-upload label="Gambar" wire:model="gambar" accept="image/*" />
                    </div>
                </div>

                <x-textarea label="Isi Pamflet" placeholder="Tuliskan isi pamflet di sini..." wire:model="isi" rows="10" />

                <x-select.native label="Publish" :options="[['label' => 'Ya', 'value' => 'ya'], ['label' => 'Tidak', 'value' => 'tidak']]" wire:model="publish" />

                <div class="mt-6 flex justify-end gap-2">
                    <x-button color="gray" flat href="{{ route('post.index') }}" wire:navigate>Batal</x-button>
                    <x-button submit color="emerald" loading="save">Simpan Perubahan</x-button>
                </div>
            </form>
        </div>
    </div>
</div>
