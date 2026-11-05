<?php

use App\Models\Post;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;
use Illuminate\Support\Facades\Storage;
use App\Livewire\Traits\WithTableX;

new class extends Component
{
    use WithTableX, Interactions;

    public $sortFieldDefault = 'created_at';
    public $sortDirectionDefault = 'desc';
    public $search_judul;
    
    #[Computed]
    public function dataTable()
    {
        return Post::query()
            ->when($this->search_judul, fn($q) => $q->where('judul', 'like', '%' . $this->search_judul . '%'))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function delete($id)
    {
        $post = Post::findOrFail($id);
        
        if ($post->gambar) {
            Storage::disk('public')->delete($post->gambar);
        }

        $post->delete();
        $this->toast()->success('Pamflet berhasil dihapus')->send();
    }
};
?>

<div>
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-medium text-slate-900">Pamflet</h1>
            <nav class="flex items-center gap-1.5 text-xs text-slate-500 mt-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition-colors"><x-ti-home class="size-4" /></a>
                <span>/</span>
                <span>Pamflet</span>
                <span>/</span>
                <span class="text-slate-700 font-medium">Daftar</span>
            </nav>
        </div>
        <x-button color="emerald" sm href="{{ route('post.create') }}" wire:navigate>
            <x-ti-plus class="size-4 mr-1 inline" /> Tambah
        </x-button>
    </div>

    <div class="mt-6">
        {{-- filter --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <span class="font-semibold text-slate-800 text-sm">Filter Pencarian</span>
                <x-button.circle color="red" flat sm type="button" wire:click="resetFilters" title="Reset filter">
                    <x-ti-filter-x class="size-4" />
                </x-button.circle>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <x-input label="Judul Pamflet" placeholder="Cari judul..." wire:model.live.debounce.500ms="search_judul" clearable />
            </div>
        </div>
        {{-- end filter --}}

        <div class="mt-6">
            <x-table :paginate="$this->dataTable">
                <x-table.thead class="bg-slate-50" :sortDirection="$sortDirection" :sortField="$sortField">
                    <x-table.th width="5%" label="No" />
                    <x-table.th label="Gambar" width="15%" />
                    <x-table.th label="Judul" />
                    <x-table.th label="Tanggal" sort="created_at" />
                    <x-table.th label="Publish" sort="publish" width="10%" />
                    <x-table.th width="10%" label="Aksi" />
                </x-table.thead>

                <tbody>
                    @forelse ($this->dataTable as $index => $post)
                        <tr class="bg-white hover:bg-slate-50 transition-colors" wire:key="post-{{ $post->id }}">
                            <td class="p-2 text-slate-600 text-sm"> {{ $perPage * ($this->dataTable->currentPage() - 1) + $index + 1 }} </td>
                            <td class="p-2">
                                @if($post->gambar)
                                    <img src="{{ Storage::url($post->gambar) }}" class="h-12 w-20 rounded-lg object-cover shadow-sm border border-slate-100">
                                @else
                                    <div class="flex h-12 w-20 items-center justify-center rounded-lg bg-slate-50 text-slate-300 border border-dashed border-slate-200">
                                        <x-ti-photo-off class="size-6 text-slate-300" />
                                    </div>
                                @endif
                            </td>
                            <td class="p-2 text-sm font-medium text-slate-800"> {{ $post->judul }} </td>
                            <td class="p-2 text-sm text-slate-600"> {{ $post->created_at->format('d-m-Y H:i') }} </td>
                            <td class="p-2">
                                @if ($post->publish === 'ya')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 px-2.5 py-0.5 text-xs font-semibold">
                                        <x-ti-check class="size-3.5" /> Ya
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 text-rose-800 border border-rose-200 px-2.5 py-0.5 text-xs font-semibold">
                                        <x-ti-x class="size-3.5" /> Tidak
                                    </span>
                                @endif
                            </td>
                            <td class="p-2">
                                <div class="flex items-center justify-center gap-1">
                                    <x-button.circle color="emerald" sm href="{{ route('post.edit', [$post->id, Illuminate\Support\Str::uuid()]) }}"
                                        title="Ubah" wire:navigate
                                    >
                                        <x-ti-edit class="size-4" />
                                    </x-button.circle>

                                    <x-button.circle color="red" flat sm title="Hapus"
                                        wire:click="$js.confirmDelete({{ $post->id }}, '{{ addslashes($post->judul) }}')"
                                    >
                                        <x-ti-trash class="size-4" />
                                    </x-button.circle>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="p-8 text-center text-slate-400" colspan="6">
                                <div class="flex flex-col items-center">
                                    <x-ti-notes-off class="size-10 mb-2 opacity-20" />
                                    <p>Tidak ada data pamflet</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-table>
        </div>
    </div>
</div>

<script>
    this.$js.confirmDelete = (id, nama) => {
        $tsui.interaction('dialog')
            .wireable()
            .question('Hapus Pamflet?', 'Apakah Anda yakin ingin menghapus pamflet "' + nama + '"?')
            .cancel('Kembali')
            .confirm('Ya, Hapus', 'delete', id)
            .send();
    }
</script>