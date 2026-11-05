<div>
    <!-- MODAL 1.5: UBAH DATA KEHAMILAN (HPHT, GPA, CATATAN) -->
    <x-modal title="Ubah Data Kehamilan Pasien" wire="showEditModal" z-index="z-50" size="lg">
        @if ($editingPregnancy)
            <form class="space-y-4" wire:submit="updatePregnancy">
                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-sm">
                    <span class="text-slate-500">Pasien:</span>
                    <strong class="ml-1 text-sm text-slate-900">{{ $editingPregnancy->patient?->full_name }}</strong>
                    <span class="ml-2 text-slate-500">(RM: {{ $editingPregnancy->patient?->medical_record_number_label }})</span>
                </div>

                <!-- Bidan Pengampu -->
                <div>
                    @role ('koordinator-bidan')
                        <x-select.styled
                            wire:model="edit_bidan_id"
                            label="Bidan Pengampu *"
                            placeholder="Pilih bidan pengampu..."
                            :options="$this->availableMidwives"
                            select="label:label|value:value"
                            searchable
                        />
                    @else
                        <x-input
                            disabled
                            value="{{ $editingPregnancy->creator?->name ?? '-' }}"
                            label="Bidan Pengampu"
                        />
                    @endrole
                </div>

                <!-- Status GPA -->
                <div class="grid grid-cols-3 gap-3">
                    <x-number wire:model="edit_gravida" label="Gravida (G) *" :min="1" />
                    <x-number wire:model="edit_partus" label="Partus (P) *" :min="0" />
                    <x-number wire:model="edit_abortus" label="Abortus (A) *" :min="0" />
                </div>

                <!-- Tanggal HPHT dan HPL -->
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <x-date format="YYYY-MM-DD" wire:model.live="edit_hpht" label="HPHT*" placeholder="Pilih tanggal HPHT" />

                    <x-date format="YYYY-MM-DD" wire:model="edit_hpl" label="HPL" placeholder="Otomatis dihitung dari HPHT" />
                </div>

                @if ($edit_hpht)
                    @php
                        $previewEditUk = null;
                        try {
                            $parsedEditHpht = Carbon::parse($edit_hpht)->startOfDay();
                            $diffDEdit = (int) $parsedEditHpht->diffInDays(now()->startOfDay());
                            $ukWEdit = intdiv($diffDEdit, 7);
                            $ukDEdit = $diffDEdit % 7;
                            $ukTriEdit = $ukWEdit <= 11 ? 1 : ($ukWEdit <= 24 ? 2 : 3);
                            $previewEditUk = ['weeks' => $ukWEdit, 'days' => $ukDEdit, 'trimester' => $ukTriEdit];
                        } catch (\Throwable $e) {
                        }
                    @endphp
                    @if ($previewEditUk)
                        <div
                            class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-emerald-200 bg-emerald-50/70 p-3 text-sm text-emerald-900">
                            <div class="flex items-center gap-2">
                                <x-ti-calendar-time class="size-4 shrink-0 text-emerald-700" />
                                <span>Usia Kehamilan Saat Ini: <strong class="font-bold text-emerald-950">{{ $previewEditUk['weeks'] }}
                                        Minggu {{ $previewEditUk['days'] }} Hari</strong> (Trimester
                                    {{ $previewEditUk['trimester'] }})</span>
                            </div>
                            @if ($edit_hpl)
                                <span class="font-medium text-slate-600">Perkiraan Lahir: <strong
                                        class="text-slate-800">{{ Carbon::parse($edit_hpl)->format('d M Y') }}</strong></span>
                            @endif
                        </div>
                    @endif
                @endif

                <div class="flex justify-end gap-2 border-t-2 border-slate-100 pt-3">
                    <x-button color="gray" flat x-on:click="$wire.showEditModal = false">Batal</x-button>
                    <x-button submit color="emerald" loading="updatePregnancy">
                        <x-ti-device-floppy class="mr-1 inline size-4" /> Simpan Perubahan
                    </x-button>
                </div>
            </form>
        @endif
    </x-modal>
</div>