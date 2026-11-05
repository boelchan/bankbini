<?php

use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component 
{
    public $showModal = false;
    public $filePath = null;

    #[On('open_preview')]
    public function openPreview($path)
    {
        $this->filePath = asset('storage/' . $path);
        $this->showModal = true;
    }

    public function close()
    {
        $this->showModal = false;
        $this->filePath = null;
    }

};
?>

<div>
    <x-modal
        title="Preview Dokumen"
        wire="showModal"
        z-index="z-81"
        size="7xl"
    >
        <div class="flex justify-center">
            @if ($filePath)
                @php
                    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                @endphp

                @if (strtolower($ext) == 'pdf')
                    <iframe
                        class="h-[80vh] w-full rounded-xl"
                        src="{{ $filePath }}"
                    ></iframe>
                @else
                    <img
                        class="rounded-xl max-h-[80vh] object-contain"
                        src="{{ $filePath }}"
                        alt="Preview"
                    >
                @endif
            @endif
        </div>
        <div class="flex justify-end pt-6">
            <x-button color="gray" flat type="button" wire:click="close">Tutup</x-button>
        </div>
    </x-modal>
</div>
