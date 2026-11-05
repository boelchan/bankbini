<div>
    <!-- MODAL 1: CATAT KEHAMILAN BARU -->
    <x-modal title="Catat Kehamilan Baru Pasien" wire="showCreateModal" z-index="z-50" size="lg">
        <form class="space-y-4" wire:submit="saveNewPregnancy">
            <p class="text-sm text-slate-500">
                Gunakan form ini untuk mencatat kehamilan baru seorang pasien. Pasien harus sudah terdaftar pada Master Data Pasien.
            </p>

            <!-- Pilih Pasien & Info Bidan Pengampu -->
                <x-select.styled wire:model="create_patient_id" label="Pilih Pasien Terdaftar *" placeholder="Pilih nama pasien..."
                    :options="$this->availablePatients" select="label:label|value:value" searchable />

                @if ($this->isKoordinator)
                    <x-select.styled
                        wire:model="create_bidan_id"
                        label="Bidan Pengampu *"
                        placeholder="Pilih bidan pengampu..."
                        :options="$this->availableMidwives"
                        select="label:label|value:value"
                        searchable
                    />
                @else
                    <x-input disabled value="{{ auth()->user()->name ?? '-' }}" label="Bidan Pengampu" />
                @endif

            <!-- Status GPA -->
            <div class="grid grid-cols-3 gap-3">
                <x-number wire:model="create_gravida" label="Gravida (G) *" :min="1" />
                <x-number wire:model="create_partus" label="Partus (P)" :min="0" />
                <x-number wire:model="create_abortus" label="Abortus (A)" :min="0" />
            </div>

            <!-- Tanggal HPHT dan HPL -->
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <x-date format="YYYY-MM-DD" wire:model.live="create_hpht" label="HPHT" placeholder="Pilih tanggal HPHT" />

                <x-date format="YYYY-MM-DD" wire:model="create_hpl" label="HPL" placeholder="Otomatis dihitung dari HPHT" />
            </div>

            @if ($create_hpht)
                @php
                    $previewUk = null;
                    try {
                        $parsedHpht = Carbon::parse($create_hpht)->startOfDay();
                        $diffD = (int) $parsedHpht->diffInDays(now()->startOfDay());
                        $ukW = intdiv($diffD, 7);
                        $ukD = $diffD % 7;
                        $ukTri = $ukW <= 11 ? 1 : ($ukW <= 24 ? 2 : 3);
                        $previewUk = ['weeks' => $ukW, 'days' => $ukD, 'trimester' => $ukTri];
                    } catch (\Throwable $e) {
                    }
                @endphp
                @if ($previewUk)
                    <div
                        class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-emerald-200 bg-emerald-50/70 p-3 text-sm text-emerald-900">
                        <div class="flex items-center gap-2">
                            <x-ti-calendar-time class="size-4 shrink-0 text-emerald-700" />
                            <span>Usia Kehamilan Saat Ini: <strong class="font-bold text-emerald-950">{{ $previewUk['weeks'] }} Minggu
                                    {{ $previewUk['days'] }} Hari</strong> (Trimester {{ $previewUk['trimester'] }})</span>
                        </div>
                        @if ($create_hpl)
                            <span class="font-medium text-slate-600">Perkiraan Lahir: <strong
                                    class="text-slate-800">{{ Carbon::parse($create_hpl)->format('d M Y') }}</strong></span>
                        @endif
                    </div>
                @endif
            @endif

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
                <x-button color="gray" flat x-on:click="$wire.showCreateModal = false">Batal</x-button>
                <x-button submit color="emerald" loading="saveNewPregnancy">
                    Simpan ke Kantong Kehamilan
                </x-button>
            </div>
        </form>
    </x-modal>
</div>