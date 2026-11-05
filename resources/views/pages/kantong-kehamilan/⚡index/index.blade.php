<div>
    <!-- Header Halaman & Breadcrumbs -->
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="flex items-center gap-2.5 text-2xl font-bold tracking-tight text-slate-900">
                <x-ti-heart-handshake class="size-7 text-emerald-600" />
                Kantong Kehamilan
            </h1>
            <nav class="mt-1 flex items-center gap-1.5 text-sm text-slate-800">
                <a class="transition-colors hover:text-emerald-600" href="{{ route('dashboard') }}">
                    <x-ti-home class="size-4" />
                </a>
                <span>/</span>
                <span class="font-medium text-slate-700">Kantong Kehamilan</span>
            </nav>
        </div>

        <div class="flex items-center gap-2">
            <x-button color="emerald" wire:click="$dispatch('open-create-pregnancy-modal')">
                <x-ti-plus class="mr-1.5 inline size-4" /> Catat Kehamilan Baru
            </x-button>
        </div>
    </div>

    <!-- 5 Kotak Ringkasan / Stat Indikator Kantong Kehamilan -->
    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <!-- 1. Total Kehamilan -->
        <x-card class="bg-blue-300">
            <div class="flex items-center justify-between">
                <span class="font-semibold text-slate-800">{{ $this->filter_status === 'aktif' ? 'Bumil' : 'Total Bumil' }}</span>
                <span class="text-2xl font-bold tracking-tight text-slate-900">{{ $this->stats['total_aktif'] }}</span>
            </div>
        </x-card>

        <!-- 2. Kehamilan Risiko Rendah (KRR) -->
        <x-card class="bg-emerald-300">
            <div class="flex items-center justify-between">
                <span class="font-semibold text-slate-800">KRR</span>
                <span class="text-2xl font-bold tracking-tight text-emerald-950">{{ $this->stats['risiko_rendah'] }}</span>
            </div>
        </x-card>

        <!-- 3. Kehamilan Risiko Tinggi (KRT) -->
        <x-card class="bg-amber-300">
            <div class="flex items-center justify-between">
                <span class="font-semibold text-slate-800">KRT</span>
                <span class="text-2xl font-bold tracking-tight text-amber-950">{{ $this->stats['risiko_tinggi'] }}</span>
            </div>
        </x-card>

        <!-- 4. Kehamilan Risiko Sangat Tinggi (KRST) -->
        <x-card class="bg-rose-300">
            <div class="flex items-center justify-between">
                <span class="font-semibold text-slate-800">KRST</span>
                <span class="text-2xl font-bold tracking-tight text-rose-950">{{ $this->stats['risiko_sangat_tinggi'] }}</span>
            </div>
        </x-card>

        <!-- 5. Trimester 3 / Mendekati HPL -->
        <x-card
            wire:click="toggleFilterTm('3')"
            class="bg-sky-300 transition-all cursor-pointer hover:opacity-95 {{ $filter_status === 'aktif' && $filter_trimester === '3' ? 'ring-2 ring-sky-600 ring-offset-2' : '' }}"
            title="Klik untuk memfilter bumil Trimester 3"
        >
            <div class="flex items-center justify-between">
                <span class="font-semibold text-slate-800">TM 3</span>
                <span class="text-2xl font-bold tracking-tight text-sky-950">{{ $this->stats['trimester_3'] }}</span>
            </div>
        </x-card>

        <!-- 6. Mendekati HPL (HPL -3) -->
        <x-card
            wire:click="toggleFilterHplMin3"
            class="bg-purple-300 transition-all cursor-pointer hover:opacity-95 {{ $filter_hpl_min_3 ? 'ring-2 ring-purple-600 ring-offset-2' : '' }}"
            title="Klik untuk memfilter bumil HPL -3"
        >
            <div class="flex items-center justify-between">
                <span class="font-semibold text-slate-800 flex items-center gap-1.5">
                    <span>HPL -3</span>
                    @if(($this->stats['hpl_min_3'] ?? 0) > 0)
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-500 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-purple-700"></span>
                        </span>
                    @endif
                </span>
                <span class="text-2xl font-bold tracking-tight text-purple-950">{{ $this->stats['hpl_min_3'] }}</span>
            </div>
        </x-card>
    </div>

    <!-- Filter & Pencarian Bar -->
    <div class="mt-4">
        <x-card>
            <div class="space-y-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 {{ $filter_status === 'aktif' ? 'md:grid-cols-3 lg:grid-cols-5' : 'md:grid-cols-2 lg:grid-cols-4' }}">
                    <!-- Search -->
                    <div wire:key="filter-search-col">
                        <x-input wire:model.live.debounce.300ms="search" placeholder="Cari nama, suami, no. RM, NIK..." icon="magnifying-glass"
                            clearable />
                    </div>

                    <!-- Filter Desa Pasien -->
                    <div wire:key="filter-desa-col">
                        <x-select.styled wire:model.live="filter_desa" placeholder="Semua Desa" :options="$this->villagesList"
                            select="label:label|value:value" />
                    </div>

                    @if ($filter_status === 'aktif')
                        <!-- Filter Trimester (TM 1, 2, 3) - Hanya Muncul Saat Filter Hamil / Aktif -->
                        <div wire:key="filter-trimester-col">
                            <x-select.styled wire:model.live="filter_trimester" placeholder="Semua Trimester" :options="\App\Enums\TrimesterEnum::choices()"
                                select="label:label|value:value" />
                        </div>
                    @endif

                    <!-- Filter Status Risiko -->
                    <div wire:key="filter-risiko-col">
                        <x-select.styled wire:model.live="filter_risiko" placeholder="Status Risiko" :options="[
                            ['label' => 'Semua Risiko', 'value' => 'semua'],
                            ['label' => 'Risiko Rendah ', 'value' => 'krr'],
                            ['label' => 'Risiko Tinggi ', 'value' => 'krt'],
                            ['label' => 'Risiko Sangat Tinggi', 'value' => 'krst'],
                        ]"
                            select="label:label|value:value" />
                    </div>

                    <!-- Filter Status Kehamilan -->
                    <div wire:key="filter-status-col">
                        <x-select.styled wire:model.live="filter_status" placeholder="Status Kehamilan" :options="[
                            ['label' => 'Semua Status', 'value' => 'semua'],
                            ['label' => 'Hamil', 'value' => 'aktif'],
                            ['label' => 'Melahirkan', 'value' => 'selesai'],
                            ['label' => 'Abortus', 'value' => 'abortus'],
                            ['label' => 'Meninggal', 'value' => 'meninggal'],
                        ]"
                            select="label:label|value:value" />
                    </div>
                </div>

                <!-- Baris Filter Tanggal Kontrol ANC -->
                <div class="flex flex-col justify-between gap-3 border-t border-slate-100 pt-3 md:flex-row md:items-center">
                    <div class="flex flex-1 flex-col gap-2.5 sm:flex-row sm:items-center">
                        <div class="w-full sm:w-60">
                            <x-date format="YYYY-MM-DD" wire:model.live="filter_tanggal_kontrol" placeholder="Pilih Tanggal Kontrol" helpers />
                        </div>
                        <div class="w-full sm:w-64">
                            <x-select.styled wire:model.live="filter_jenis_kontrol" :options="[
                                ['label' => 'Jadwal Kontrol', 'value' => 'selanjutnya'],
                                ['label' => 'Tanggal Periksa', 'value' => 'periksa'],
                                ['label' => 'Semua (Jadwal & Periksa)', 'value' => 'semua'],
                            ]" select="label:label|value:value" />
                        </div>
                        @if ($filter_tanggal_kontrol)
                            <div class="flex items-center gap-2">
                                <x-badge color="emerald" light round>
                                    <x-slot:left>
                                        <x-ti-calendar-event class="size-4 mr-1 text-emerald-600" />
                                    </x-slot:left>
                                    {{ \Illuminate\Support\Carbon::parse($filter_tanggal_kontrol)->translatedFormat('d M Y') }}
                                </x-badge>
                                <x-button title="Hapus filter tanggal kontrol" color="slate" flat sm wire:click="clearFilterTanggalKontrol">
                                    <x-ti-x class="mr-1 size-3.5" /> Reset
                                </x-button>
                            </div>
                        @endif
                        @if ($filter_hpl_min_3)
                            <div class="flex items-center gap-2">
                                <x-badge color="purple" light round>
                                    <x-slot:left>
                                        <x-ti-bell-ringing class="size-4 mr-1 text-purple-600" />
                                    </x-slot:left>
                                    Filter: HPL -3
                                </x-badge>
                                <x-button title="Hapus filter HPL -3" color="slate" flat sm wire:click="toggleFilterHplMin3">
                                    <x-ti-x class="mr-1 size-3.5" /> Reset
                                </x-button>
                            </div>
                        @endif
                        @if ($filter_status === 'aktif' && $filter_trimester && $filter_trimester !== 'semua')
                            <div class="flex items-center gap-2">
                                <x-badge color="sky" light round>
                                    <x-slot:left>
                                        <x-ti-calendar-time class="size-4 mr-1 text-sky-600" />
                                    </x-slot:left>
                                    Filter: TM {{ $filter_trimester }}
                                </x-badge>
                                <x-button title="Hapus filter trimester" color="slate" flat sm wire:click="toggleFilterTm('{{ $filter_trimester }}')">
                                    <x-ti-x class="mr-1 size-3.5" /> Reset
                                </x-button>
                            </div>
                        @endif
                    </div>

                    <div class="text-sm font-medium text-slate-800">
                        Ditemukan: <strong class="text-slate-800">{{ $this->pregnancies->total() }}</strong> kantong kehamilan
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Kotak-kotak / Grid Cards Kantong Kehamilan (3 Kolom Bernapas & Elegan) -->
    <div class="mt-6">
        @if ($this->pregnancies->count() > 0)
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($this->pregnancies as $item)
                    @php
                        $patient = $item->patient;
                        $latestEncounter = $item->latestEncounter;
                        $skorPoedji = $item->latest_skor_puji_rochyati;
                        $isKrst = $skorPoedji > 10;
                        $isKrt = $skorPoedji >= 6 && $skorPoedji <= 10;
                        $isKrr = $skorPoedji <= 5;
                        $riskCode = $isKrst ? 'KRST' : ($isKrt ? 'KRT' : 'KRR');
                        $riskColor = $isKrst ? 'rose' : ($isKrt ? 'amber' : 'emerald');
                        $sisaHariHpl = $item->sisa_hari_hpl;
                        $mingguKe = $item->minggu_ke;

                        // Aksen garis atas lembut sesuai status risiko (Clean & Refined)
                        $accentColor = match ($riskCode) {
                            'KRST' => 'border-rose-500',
                            'KRT' => 'border-amber-500',
                            default => 'border-emerald-500',
                        };
                    @endphp

                    <div class="{{ $accentColor }} shadow-xs hover:shadow-md group relative flex flex-col justify-between rounded-2xl border-4 bg-white transition-all duration-200"
                        wire:key="pregnancy-card-{{ $item->id }}">
                        <!-- Header Pasien & Badge Risiko -->
                        <div class="flex items-start justify-between gap-3 p-4 pb-2 border-b border-slate-400">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <h3 class="truncate text-base font-bold text-slate-900" title="{{ $patient?->full_name }}">
                                        {{ $patient?->full_name }}
                                    </h3>
                                </div>
                                <p class="mt-1 flex items-center gap-2 text-sm text-slate-800">
                                    <span>{{ $patient?->umur_sekarang ? explode(' ', $patient->umur_sekarang)[0] : '-' }}</span>
                                    <span class="text-slate-300">&bull;</span>
                                    <span class="truncate" title="{{ $patient?->village?->name ?? '-' }}">
                                        {{ $patient?->village?->name ?? '-' }}
                                    </span>
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                {{-- <x-badge :text="$riskCode" :color="$riskColor" light round title="Skor Poedji Rochyati: {{ $skorPoedji }}" /> --}}
                                @if ($this->canEditPregnancy($item))
                                    <x-button.circle title="Ubah HPHT, GPA, Catatan" color="blue" flat sm
                                        wire:click="$dispatch('open-edit-pregnancy-modal', { pregnancyId: {{ $item->id }} })">
                                        <x-ti-edit class="size-4 text-slate-800" />
                                    </x-button.circle>

                                    <x-button.circle title="Hapus Kantong Kehamilan" color="red" flat sm
                                        wire:click="promptDeletePregnancy({{ $item->id }})">
                                        <x-ti-trash class="size-4 text-rose-500 hover:text-rose-700" />
                                    </x-button.circle>
                                @endif
                            </div>
                        </div>

                        <!-- Body Card -->
                        <!-- Ringkasan Medis Kehamilan -->
                        <div class="border-b border-slate-400 p-4 py-2.5 text-sm space-y-1">
                            
                            <div class="flex items-center justify-between">
                                <span class="font-bold">{{ $item->gpa_label }}</span>
                                <div>
                                    @if ($item->status === 'selesai')
                                        <x-badge text="Selesai" color="slate" light round md />
                                    @elseif ($item->status === 'abortus')
                                        <x-badge text="Abortus" color="amber" light round md />
                                    @elseif ($item->status === 'meninggal')
                                        <x-badge text="Meninggal" color="rose" light round md />
                                    @else
                                    @endif
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-800">Usia Hamil:</span>
                                <span class="font-bold text-slate-800">
                                    @if ($mingguKe !== null)
                                    @if ($item->trimester === 1)
                                        <x-badge text="TM 1" color="emerald" light round md />
                                    @elseif ($item->trimester === 2)
                                        <x-badge text="TM 2" color="amber" light round md />
                                    @else
                                        <x-badge text="TM 3" color="rose" light round md />
                                    @endif
                                        {{ $item->usia_kehamilan_saat_ini }}
                                    @else
                                        <span class="font-normal text-slate-400">-</span>
                                    @endif
                                </span>
                            </div>

                            <div class="flex items-center justify-between ">
                                <span class="font-medium text-slate-800">HPHT:</span>
                                <span class="font-bold text-slate-700">{{ $item->hpht ? $item->hpht->format('d M Y') : '-' }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-800">HPL:</span>
                                <div class="text-right">
                                    <span class="font-bold text-slate-800">{{ $item->hpl ? $item->hpl->format('d M Y') : '-' }}</span>
                                    @if ($sisaHariHpl !== null && $item->status === 'aktif')
                                        <span class="{{ $sisaHariHpl <= 14 ? 'text-rose-600 font-bold' : 'text-slate-800' }} ml-1 text-sm">
                                            (@if ($sisaHariHpl > 0)
                                                -{{ $sisaHariHpl }} hr
                                            @elseif ($sisaHariHpl === 0)
                                                Hari ini!
                                            @else
                                                +{{ abs($sisaHariHpl) }} hr
                                            @endif)
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-800">Pengampu:</span>
                                <span class="max-w-37.5 truncate font-medium text-slate-700" title="{{ $item->creator?->name ?? '-' }}">
                                    {{ $item->creator?->name ?? '-' }}
                                </span>
                            </div>
                        </div>

                        <!-- Histori Kontrol ANC & Kontrol Selanjutnya -->
                        <div class="space-y-2 border-b border-slate-400 p-4 py-2.5 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class=" text-slate-800">
                                        <span class="font-bold">{{ $item->encounters->count() }}x kontrol</span>
                                        @if ($latestEncounter)
                                            {{-- <span class="text-slate-800"> &nbsp; ({{ $latestEncounter->encounter_date?->format('d M Y') }})</span> --}}
                                            @if ($latestEncounter?->next_encounter_date && $item->status === 'aktif')
                                                <div class="flex items-center text-sm gap-1">
                                                    <span class="font-medium text-slate-500">Berikutnya </span>
                                                    <span class="font-bold text-slate-700">{{ $latestEncounter->next_encounter_date->format('d M Y') }}</span>
                                                </div>
                                            @endif

                                        @else
                                            <span class="text-slate-400"> - belum ada</span>
                                        @endif
                                        
                                    </p>
                                </div>
                                <x-button color="emerald" light sm title="Buka Detail Rekam Medis"
                                    wire:click="$dispatch('open-detail-modal', { pregnancyId: {{ $item->id }} })">
                                    <x-slot:left>
                                        <x-ti-notes class="size-4" />
                                    </x-slot:left>
                                    Detail
                                </x-button>
                            </div>

                        </div>

                        @php
                            $abnormalLabs = $latestEncounter ? $latestEncounter->getAbnormalLabResults() : [];
                            $latestFrList = $latestEncounter && is_array($latestEncounter->faktor_risiko_json) ? $latestEncounter->faktor_risiko_json : [];
                        @endphp

                        @if (!empty($abnormalLabs))
                            <div class="border-b border-slate-400 p-4 py-2.5 text-sm">
                                <span class="block text-xs font-semibold uppercase tracking-wider text-rose-700 mb-1">Hasil Lab Abnormal:</span>
                                <div class="grid grid-cols-2 gap-x-2 gap-y-1">
                                    @foreach ($abnormalLabs as $abLab)
                                        <span class="text-slate-700">{{ $abLab['param'] }}:</span>
                                        <span class="font-bold text-rose-600">{{ $abLab['value'] }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (!empty($latestFrList))
                            <div class="border-b border-slate-400 p-4 py-2.5 text-sm">
                                <span class="block text-xs font-semibold uppercase tracking-wider text-amber-800 mb-1">Faktor Risiko</span>
                                <div class="space-y-1">
                                    @foreach ($latestFrList as $frKey)
                                        <div class="flex items-start gap-1.5">
                                            <span class="text-amber-600 font-bold leading-tight">&bull;</span>
                                            <span class="font-medium text-slate-800 leading-snug">{{ \App\Enums\PoedjiRochyatiEnum::getLabelAlias($frKey) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="flex flex-1 flex-col justify-end p-4 py-2.5">
                            <!-- Tombol Aksi Bawah Card -->
                            <div class="flex items-center gap-2">
                                <x-button class="flex-1" title="Riwayat Persalinan Sebelumnya" color="slate" outline sm
                                    wire:click="$dispatch('open-riwayat-kehamilan', { patientId: {{ $item->patient_id }} })">
                                    Riwayat Obstetri
                                </x-button>

                                @if ($item->status === 'aktif' && $this->canEditPregnancy($item))
                                    <x-button class="flex-1" title="Tandai Sudah Melahirkan / Selesai" color="amber" outline sm
                                        wire:click="$dispatch('open-selesai-modal', { pregnancyId: {{ $item->id }} })">
                                        Selesai
                                    </x-button>
                                @elseif ($item->status === 'selesai')
                                    <x-button class="flex-1" title="Lihat Rekam Kunjungan Nifas & Bayi" color="emerald" outline sm
                                        wire:click="$dispatch('open-detail-modal', { pregnancyId: {{ $item->id }}, tab: 'nifas' })">
                                        Nifas &amp; Bayi ({{ ($item->postnatal_encounters_count ?? 0) + ($item->neonatal_encounters_count ?? 0) }})
                                    </x-button>
                                @elseif ($item->status === 'abortus')
                                    <x-button class="flex-1" title="Lihat Rekam Kejadian Abortus" color="amber" outline sm
                                        wire:click="$dispatch('open-detail-modal', { pregnancyId: {{ $item->id }} })">
                                        Detail Abortus
                                    </x-button>
                                @elseif ($item->status === 'meninggal')
                                    <x-button class="flex-1" title="Lihat Rekam Kematian Pasien" color="rose" outline sm
                                        wire:click="$dispatch('open-detail-modal', { pregnancyId: {{ $item->id }} })">
                                        Detail Kematian
                                    </x-button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $this->pregnancies->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="rounded-3xl border-2 border-dashed border-slate-400 bg-white p-12 text-center">
                <div class="mx-auto mb-3 flex size-14 items-center justify-center rounded-2xl border-2 border-emerald-200 bg-emerald-50 text-emerald-600">
                    <x-ti-heart-handshake class="size-8" />
                </div>
                <h3 class="text-lg font-bold text-slate-800">Belum Ada Data di Kantong Kehamilan</h3>
                <p class="mx-auto mt-1 max-w-md text-sm text-slate-800">
                    Data ibu hamil yang aktif akan otomatis muncul di sini lengkap dengan usia kehamilan minggu ke berapa dan status risiko Poedji Rochyati.
                </p>
                <div class="mt-5 flex justify-center gap-3">
                    <x-button color="emerald" wire:click="$dispatch('open-create-pregnancy-modal')">
                        <x-ti-plus class="mr-1.5 inline size-4" /> Catat Kehamilan Baru
                    </x-button>
                </div>
            </div>
        @endif
    </div>

    {{-- =========================================================================
         KOMPONEN MODAL LIVEWIRE SFC KANTONG KEHAMILAN
         ========================================================================= --}}
    <livewire:kantong-kehamilan.modal.create />
    <livewire:kantong-kehamilan.modal.edit />
    <livewire:kantong-kehamilan.modal.detail />
    <livewire:kantong-kehamilan.modal.selesai />
</div>