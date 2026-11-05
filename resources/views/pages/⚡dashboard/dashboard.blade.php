<div class="space-y-5 pb-10">

    {{-- Minimalist Header / Ringkasan Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-1">
        <div>
            <h1 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 mt-1">
                <span x-data="dashboardGreeting" x-text="greeting">Selamat Datang</span>
                {{ auth()->user()->name}}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Sistem Pemantauan Kantong Kehamilan, Peta Sebaran Geospasial, dan Skrining Risiko Pasien.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 self-start sm:self-auto">
            {{-- Kontrol Pengumuman Suara Jumlah KRST & Siaga H-3 HPL (Default Tidak Menyala) --}}
            <div
                id="dashboard-voice-announcer"
                data-announcement="{{ $this->voiceAnnouncementText }}"
                class="inline-flex items-center rounded-xl border border-slate-200/80 bg-white shadow-2xs overflow-hidden"
                x-data="dashboardVoiceAnnouncer"
                x-show="supported"
                x-cloak
            >
                <button
                    type="button"
                    @click="togglePlayPause()"
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 text-xs font-semibold transition-colors focus:outline-none cursor-pointer"
                    :class="isRunning ? 'bg-amber-50 text-amber-800 hover:bg-amber-100/80' : 'text-slate-700 hover:bg-slate-50'"
                    x-bind:x-tooltip="isRunning ? 'Jeda pembacaan suara berulang' : 'Putar / lanjutkan pengumuman suara berulang (jeda 5 detik)'"
                    data-position="bottom"
                >
                    <span x-show="isRunning" x-cloak class="inline-flex">
                        <x-ti-player-pause class="size-4 text-amber-600 shrink-0" />
                    </span>
                    <span x-show="!isRunning" class="inline-flex">
                        <x-ti-player-play class="size-4 text-emerald-600 shrink-0" />
                    </span>
                    <span x-text="buttonLabel">Putar Suara</span>
                </button>
            </div>

            <div
                class="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-white px-3.5 py-2 shadow-2xs text-xs font-medium text-slate-600"
                x-data="dashboardClock"
            >
                <div class="flex items-center gap-1.5 text-xl text-slate-500">
                    <x-ti-calendar class="size-4 text-slate-400" />
                    <span x-text="dateStr"></span>
                </div>
                <span class="text-slate-200">|</span>
                <div class="flex items-center gap-1.5 font-mono text-xl font-semibold text-slate-700">
                    <x-ti-clock class="size-4 text-emerald-600" />
                    <span x-text="timeStr"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Ringkasan Statistik Metrik Interaktif (6 Stat Cards Terintegrasi ke Filter Peta) --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <!-- 1. Total Bumil -->
        <x-card
            wire:click="resetFilter"
            class="bg-blue-300 border-blue-400 transition-all cursor-pointer hover:opacity-95 {{ empty($filter_risiko) && !$filter_h_min_3 ? 'ring-2 ring-blue-600 ring-offset-2' : '' }}"
            title="Klik untuk menampilkan seluruh penanda (KRR, KRT, KRST, H-3)"
        >
            <div class="flex items-center justify-between">
                <span class="font-semibold text-slate-800 text-xs sm:text-sm">Total Bumil</span>
                <span class="text-2xl font-bold tracking-tight text-slate-900">{{ $this->totalBumil }}</span>
            </div>
        </x-card>

        <!-- 2. KRR (Risiko Rendah) -->
        <x-card
            wire:click="setFilterRisiko('krr')"
            class="bg-emerald-300 border-emerald-400 transition-all cursor-pointer hover:opacity-95 {{ $filter_risiko === 'krr' ? 'ring-2 ring-emerald-600 ring-offset-2' : '' }}"
            title="Klik untuk memfilter penanda KRR pada peta"
        >
            <div class="flex items-center justify-between">
                <span class="font-semibold text-slate-800 text-xs sm:text-sm">KRR</span>
                <span class="text-2xl font-bold tracking-tight text-emerald-950">{{ $this->totalKrr }}</span>
            </div>
        </x-card>

        <!-- 3. KRT (Risiko Tinggi) -->
        <x-card
            wire:click="setFilterRisiko('krt')"
            class="bg-amber-300 border-amber-400 transition-all cursor-pointer hover:opacity-95 {{ $filter_risiko === 'krt' ? 'ring-2 ring-amber-600 ring-offset-2' : '' }}"
            title="Klik untuk memfilter penanda KRT pada peta"
        >
            <div class="flex items-center justify-between">
                <span class="font-semibold text-slate-800 text-xs sm:text-sm">KRT</span>
                <span class="text-2xl font-bold tracking-tight text-amber-950">{{ $this->totalKrt }}</span>
            </div>
        </x-card>

        <!-- 4. KRST (Risiko Sangat Tinggi) -->
        <x-card
            wire:click="setFilterRisiko('krst')"
            class="bg-rose-300 border-rose-400 transition-all cursor-pointer hover:opacity-95 {{ $filter_risiko === 'krst' ? 'ring-2 ring-rose-600 ring-offset-2' : '' }}"
            title="Klik untuk memfilter penanda KRST pada peta"
        >
            <div class="flex items-center justify-between">
                <span class="font-semibold text-slate-800 text-xs sm:text-sm">KRST</span>
                <span class="text-2xl font-bold tracking-tight text-rose-950">{{ $this->totalKrst }}</span>
            </div>
        </x-card>

        <!-- 5. Siaga H-3 HPL (Dapat Diklik untuk Filter Peta) -->
        <x-card
            wire:click="toggleFilterHMin3"
            class="bg-purple-300 border-purple-400 transition-all cursor-pointer hover:opacity-95 {{ $filter_h_min_3 ? 'ring-2 ring-purple-600 ring-offset-2' : '' }}"
            title="Klik untuk memfilter penanda Siaga H-3 HPL pada peta"
        >
            <div class="flex items-center justify-between">
                <span class="font-semibold text-slate-800 text-xs sm:text-sm flex items-center gap-1.5">
                    <span>Siaga H-3</span>
                    @if($this->totalHMin3 > 0)
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-500 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-purple-700"></span>
                        </span>
                    @endif
                </span>
                <span class="text-2xl font-bold tracking-tight text-purple-950">{{ $this->totalHMin3 }}</span>
            </div>
        </x-card>

        <!-- 6. Lab Abnormal -->
        <x-card
            onclick="document.getElementById('tabel-lab-abnormal')?.scrollIntoView({ behavior: 'smooth' })"
            class="bg-rose-200/90 border-rose-300 transition-all cursor-pointer hover:opacity-95"
            title="Klik untuk melihat daftar pasien hasil lab abnormal"
        >
            <div class="flex items-center justify-between">
                <span class="font-semibold text-rose-950 text-xs sm:text-sm flex items-center gap-1">
                    <x-ti-flask-2 class="size-3.5 text-rose-700" /> Lab Abnormal
                </span>
                <span class="text-2xl font-bold tracking-tight text-rose-950">{{ $this->pregnancyStats['abnormal_lab_patients_count'] }}</span>
            </div>
        </x-card>
    </div>

    {{-- Peta Spasial MapLibre dengan GeoJSON Layer Batas 14 Desa Kecamatan Pragaan --}}
    <x-card paddingless class="border border-slate-200/80 shadow-xs relative z-20 overflow-hidden">
        <style>
            .maplibregl-popup {
                z-index: 9999 !important;
            }
            .maplibregl-popup-content {
                z-index: 9999 !important;
                border-radius: 0.75rem !important;
                padding: 0 !important;
                box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.15), 0 8px 10px -6px rgb(0 0 0 / 0.1) !important;
                border: 1px solid rgba(226, 232, 240, 0.95) !important;
            }
            .maplibregl-popup-close-button {
                z-index: 10000 !important;
                top: 8px !important;
                right: 8px !important;
                padding: 2px 6px !important;
                font-size: 16px !important;
                color: #64748b !important;
                border-radius: 0.375rem !important;
            }
            .maplibregl-popup-close-button:hover {
                color: #0f172a !important;
                background-color: #f1f5f9 !important;
            }
            .lm-popup-container {
                z-index: 9999 !important;
            }
            .lm-marker-dot {
                display: none !important;
            }

            /* Animasi Denyut / Kedip-kedip (Beacon & Pulse) untuk Desa KRST & H-3 */
            @keyframes marker-beacon-rose {
                0%, 100% {
                    box-shadow: 0 0 0 2px rgba(225, 29, 72, 0.9), 0 0 16px rgba(225, 29, 72, 0.5);
                    border-color: rgba(225, 29, 72, 0.95);
                }
                50% {
                    box-shadow: 0 0 0 6px rgba(225, 29, 72, 0.2), 0 0 4px rgba(225, 29, 72, 0.1);
                    border-color: rgba(225, 29, 72, 0.4);
                }
            }
            @keyframes marker-beacon-purple {
                0%, 100% {
                    box-shadow: 0 0 0 2px rgba(147, 51, 234, 0.9), 0 0 16px rgba(147, 51, 234, 0.5);
                    border-color: rgba(147, 51, 234, 0.95);
                }
                50% {
                    box-shadow: 0 0 0 6px rgba(147, 51, 234, 0.2), 0 0 4px rgba(147, 51, 234, 0.1);
                    border-color: rgba(147, 51, 234, 0.4);
                }
            }
            .marker-pulse-rose {
                animation: marker-beacon-rose 1.3s infinite ease-in-out;
            }
            .marker-pulse-purple {
                animation: marker-beacon-purple 1.3s infinite ease-in-out;
            }

            @keyframes chip-beacon-rose {
                0%, 100% {
                    transform: scale(1);
                    box-shadow: 0 0 0 0 rgba(225, 29, 72, 0.8), 0 0 8px rgba(225, 29, 72, 0.6);
                }
                50% {
                    transform: scale(1.14);
                    box-shadow: 0 0 0 5px rgba(225, 29, 72, 0), 0 0 0 rgba(225, 29, 72, 0);
                }
            }
            @keyframes chip-beacon-purple {
                0%, 100% {
                    transform: scale(1);
                    box-shadow: 0 0 0 0 rgba(147, 51, 234, 0.8), 0 0 8px rgba(147, 51, 234, 0.6);
                }
                50% {
                    transform: scale(1.14);
                    box-shadow: 0 0 0 5px rgba(147, 51, 234, 0), 0 0 0 rgba(147, 51, 234, 0);
                }
            }
            .chip-pulse-rose {
                animation: chip-beacon-rose 1.1s infinite ease-in-out;
            }
            .chip-pulse-purple {
                animation: chip-beacon-purple 1.1s infinite ease-in-out;
            }

            /* Animasi Border Kedip-Kedip Ungu untuk Pasien H-3 di Popup */
            @keyframes patient-h3-border-blink {
                0%, 100% {
                    border-color: #9333ea !important;
                    box-shadow: 0 0 0 2px rgba(147, 51, 234, 0.6), 0 0 8px rgba(147, 51, 234, 0.35) !important;
                }
                50% {
                    border-color: rgba(147, 51, 234, 0.35) !important;
                    box-shadow: 0 0 0 0 rgba(147, 51, 234, 0) !important;
                }
            }
            .patient-h3-pulse {
                border-width: 2px !important;
                border-style: solid !important;
                border-color: #9333ea !important;
                animation: patient-h3-border-blink 1.2s infinite ease-in-out !important;
                background-color: #faf5ff !important;
            }
        </style>

        {{-- Bar Kontrol & Filter Peta --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between p-4 border-b border-slate-100 bg-white">
            <div class="flex items-center gap-2.5">
                <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-100">
                    <x-ti-map-2 class="size-4.5" />
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Peta Sebaran Geospasial Kehamilan</h2>
                    <p class="text-xs text-slate-500">Visualisasi sebaran ibu hamil, klaster risiko, dan batas wilayah 14 desa Kecamatan Pragaan</p>
                </div>
            </div>

            <!-- Filter Kontrol Peta -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="w-40">
                    <x-select.styled
                        wire:model.live="filter_risiko"
                        placeholder="Semua Risiko"
                        :options="[
                            ['label' => 'KRR', 'value' => 'krr'],
                            ['label' => 'KRT', 'value' => 'krt'],
                            ['label' => 'KRST', 'value' => 'krst'],
                        ]"
                        select="label:label|value:value"
                    />
                </div>

                <div class="w-40">
                    <x-select.styled
                        wire:model.live="filter_tm"
                        placeholder="Semua Trimester"
                        :options="[
                            ['label' => 'TM 1', 'value' => '1'],
                            ['label' => 'TM 2', 'value' => '2'],
                            ['label' => 'TM 3', 'value' => '3'],
                        ]"
                        select="label:label|value:value"
                    />
                </div>

                <x-button
                    sm
                    :color="$filter_h_min_3 ? 'purple' : 'white'"
                    wire:click="toggleFilterHMin3"
                    title="Filter hanya desa dengan ibu hamil siaga H-3 HPL"
                    class="{{ $filter_h_min_3 ? '' : 'border border-slate-200 text-slate-700 hover:bg-slate-50' }}"
                >
                    <x-ti-bell-ringing class="size-4 mr-1.5 {{ $filter_h_min_3 ? 'text-white' : 'text-purple-600' }}" />
                    <span>Siaga H-3</span>
                    @if($this->totalHMin3 > 0)
                        <x-badge :color="$filter_h_min_3 ? 'white' : 'purple'" :light="!$filter_h_min_3" round class="ml-1.5 text-xs font-bold">
                            {{ $this->totalHMin3 }}
                        </x-badge>
                    @endif
                </x-button>

                @if($filter_risiko || $filter_tm || $filter_h_min_3)
                    <x-button.circle
                        color="slate"
                        flat
                        sm
                        wire:click="resetFilter"
                        title="Reset Filter"
                    >
                        <x-ti-x class="size-4" />
                    </x-button.circle>
                @endif
            </div>
        </div>

        {{-- Wadah MapLibre --}}
        <div wire:key="dashboard-map-{{ $filter_risiko }}-{{ $filter_tm }}-{{ $filter_h_min_3 ? 'h3' : 'all' }}" class="relative w-full" style="height: 600px;">
            <x-map
                :center="[113.6550, -7.0980]"
                :zoom="12"
                :scroll-zoom="false"
                height="100%"
                class="w-full h-full"
            >
                <x-map-controls :locate="true" :fullscreen="true" three-d position="top-right" />

                <!-- Layer Batas Desa (Per Desa) GeoJSON Kecamatan Pragaan -->
                <x-map-geojson
                    id="pragaan-villages"
                    :data="asset('pragaan_desa.json')"
                    interactive
                    :fill-paint="['fill-color' => '#10b981', 'fill-opacity' => 0.10]"
                    :fill-hover-paint="['fill-color' => '#10b981', 'fill-opacity' => 0.28]"
                    :line-paint="['line-color' => '#059669', 'line-width' => 1.5]"
                    :circle-paint="false"
                >
                    <x-slot:tooltip>Desa {name}</x-slot:tooltip>
                    <x-slot:popup>
                        <div class="p-2 space-y-1 text-xs">
                            <div class="font-bold text-emerald-800 text-sm">Desa {name}</div>
                            <div class="text-slate-600">Kecamatan {sub_district}, Kabupaten {district}</div>
                            <div class="text-slate-400">Kode Wilayah BPS: {village_code}</div>
                        </div>
                    </x-slot:popup>
                </x-map-geojson>

                <!-- Layer Garis Batas Terluar Kecamatan Pragaan -->
                <x-map-geojson
                    id="pragaan-outer-boundary"
                    :data="asset('pragaan.json')"
                    :fill-paint="false"
                    :line-paint="['line-color' => '#047857', 'line-width' => 2.5, 'line-dasharray' => [3, 2]]"
                    :circle-paint="false"
                />

                <!-- 1 Marker Dinamis Terpadu per Desa -->
                @foreach($this->villageMarkers as $village)
                    @php
                        $markerPulseClass = '';
                        if ($village['has_h3']) {
                            $markerPulseClass = 'marker-pulse-purple';
                        } elseif ($village['has_krst']) {
                            $markerPulseClass = 'marker-pulse-rose';
                        }
                    @endphp
                    <x-map-marker :lat="$village['lat']" :lng="$village['lng']" anchor="center" :color="$village['primary_color']">
                        <x-marker-content>
                            <div class="group relative flex flex-col items-center rounded-xl bg-white px-2.5 py-1.5 shadow-md border border-slate-200/90 backdrop-blur-xs transition-all hover:scale-105 hover:shadow-xl hover:z-30 cursor-pointer select-none {{ $markerPulseClass }}" title="Desa {{ $village['name'] }} (Klik untuk lihat detail kasus)">
                                {{-- Gelombang Radar Ping Eksternal --}}
                                @if($village['has_h3'])
                                    <span class="pointer-events-none absolute -inset-1 rounded-xl bg-purple-500 opacity-50 animate-ping"></span>
                                @elseif($village['has_krst'])
                                    <span class="pointer-events-none absolute -inset-1 rounded-xl bg-rose-500 opacity-50 animate-ping"></span>
                                @endif

                                {{-- Baris 1: Nama Desa --}}
                                <span class="relative z-10 text-xs font-bold text-slate-800 whitespace-nowrap leading-tight mb-1">{{ $village['name'] }}</span>

                                {{-- Baris 2: Chips Kategori Terpadu --}}
                                <div class="relative z-10 flex items-center justify-center gap-1">
                                    {{-- Chip KRR (Hijau) --}}
                                    @if($village['show_krr'])
                                        <span class="inline-flex h-4.5 min-w-[18px] items-center justify-center rounded-full bg-emerald-600 px-1 text-xs font-bold text-white shadow-2xs" title="{{ $village['krr_count'] }} KRR (Risiko Rendah)">
                                            {{ $village['krr_count'] }}
                                        </span>
                                    @endif

                                    {{-- Chip KRT (Kuning) --}}
                                    @if($village['show_krt'])
                                        <span class="inline-flex h-4.5 min-w-[18px] items-center justify-center rounded-full bg-amber-500 px-1 text-xs font-bold text-white shadow-2xs" title="{{ $village['krt_count'] }} KRT (Risiko Tinggi)">
                                            {{ $village['krt_count'] }}
                                        </span>
                                    @endif

                                    {{-- Chip KRST (Merah) dengan Animasi Kedip-Kedip --}}
                                    @if($village['show_krst'])
                                        <span class="relative inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-rose-600 px-1 text-xs font-bold text-white shadow-xs {{ !$village['has_h3'] ? 'chip-pulse-rose' : '' }}" title="{{ $village['krst_count'] }} KRST (Risiko Sangat Tinggi)">
                                            <span class="relative z-10">{{ $village['krst_count'] }}</span>
                                        </span>
                                    @endif

                                    {{-- Chip H-3 (Ungu) dengan Animasi Kedip-Kedip --}}
                                    @if($village['show_h3'])
                                        <span class="relative inline-flex h-5 min-w-[22px] items-center gap-0.5 rounded-full bg-purple-600 px-1.5 text-xs font-bold text-white shadow-xs chip-pulse-purple" title="{{ $village['h3_count'] }} Siaga H-3 HPL">
                                            <x-ti-bell-filled class="relative z-10 size-2.5 text-white" />
                                            <span class="relative z-10">{{ $village['h3_count'] }}</span>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </x-marker-content>

                        <!-- Satu Popup Komprehensif Lengkap per Desa (3 Kolom: KRR, KRT, KRST - Pasien H-3 Berkedip Ungu) -->
                        <x-marker-popup max-width="740px" class="w-[300px] sm:w-[560px] md:w-[700px]">
                            <div class="p-3 space-y-2.5 text-xs">
                                <!-- Header Popup -->
                                <div class="border-b border-slate-100 pb-2 flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <div class="flex size-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <x-ti-map-pin class="size-4" />
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 text-sm">Desa {{ $village['name'] }}</div>
                                            <div class="text-xs text-slate-500">Puskesmas Pragaan</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        @if($village['h3_count'] > 0)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200" title="{{ $village['h3_count'] }} Pasien Siaga H-3 HPL (Border Ungu Berkedip)">
                                                <x-ti-bell-filled class="size-3 text-purple-600" />
                                                {{ $village['h3_count'] }} Siaga H-3
                                            </span>
                                        @endif
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                            Total: {{ $village['total_bumil'] }} Bumil Aktif
                                        </span>
                                    </div>
                                </div>

                                <!-- Grid 3 Kolom: KRR, KRT, KRST -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                    {{-- Kolom 1: KRR (Risiko Rendah) --}}
                                    <div class="flex flex-col">
                                        <div class="flex items-center justify-between px-2 py-1.5 rounded-t-lg bg-emerald-50 border border-emerald-200 text-emerald-900 font-bold">
                                            <span class="flex items-center gap-1">
                                                <x-ti-shield-check class="size-3.5 text-emerald-600" /> KRR
                                            </span>
                                            <span class="px-1.5 py-0.5 rounded-full bg-emerald-200/80 text-emerald-900 font-bold text-xs">{{ $village['krr_count'] }}</span>
                                        </div>
                                        <div class="bg-slate-50/50 border-x border-b border-emerald-200 rounded-b-lg p-1.5 max-h-60 overflow-y-auto space-y-1.5 flex-1">
                                            @forelse($village['krr_patients'] as $p)
                                                <div class="p-2 rounded-md shadow-2xs space-y-1 transition-all {{ $p['is_h3'] ? 'patient-h3-pulse' : 'bg-white border border-slate-200' }}">
                                                    <div class="flex items-center justify-between gap-1">
                                                        <span class="font-semibold text-slate-900 truncate" title="{{ $p['name'] }}">{{ $p['name'] }}</span>
                                                        @if($p['is_h3'])
                                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-purple-600 text-white font-bold text-xs shrink-0 shadow-2xs" title="Mendekati HPL">
                                                                <x-ti-bell-filled class="size-2.5 text-white" />
                                                                <span>{{ $p['sisa_label'] }}</span>
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="text-slate-600 text-xs flex items-center justify-between">
                                                        <span>TM {{ $p['trimester'] }}</span>
                                                        <span class="text-slate-400">{{ $p['minggu_ke'] }} mg</span>
                                                    </div>
                                                    <div class="text-slate-500 text-xs flex items-center justify-between">
                                                        <span>HPL: <span class="{{ $p['is_h3'] ? 'text-purple-700 font-bold' : '' }}">{{ $p['hpl'] }}</span></span>
                                                    </div>
                                                    <div class="text-slate-400 text-xs truncate" title="{{ $p['bidan_pengampu'] }}">{{ $p['bidan_pengampu'] }}</div>
                                                </div>
                                            @empty
                                                <div class="text-center py-6 text-slate-400 italic text-xs">Nihil</div>
                                            @endforelse
                                        </div>
                                    </div>

                                    {{-- Kolom 2: KRT (Risiko Tinggi) --}}
                                    <div class="flex flex-col">
                                        <div class="flex items-center justify-between px-2 py-1.5 rounded-t-lg bg-amber-50 border border-amber-200 text-amber-900 font-bold">
                                            <span class="flex items-center gap-1">
                                                <x-ti-alert-circle class="size-3.5 text-amber-600" /> KRT
                                            </span>
                                            <span class="px-1.5 py-0.5 rounded-full bg-amber-200/80 text-amber-900 font-bold text-xs">{{ $village['krt_count'] }}</span>
                                        </div>
                                        <div class="bg-slate-50/50 border-x border-b border-amber-200 rounded-b-lg p-1.5 max-h-60 overflow-y-auto space-y-1.5 flex-1">
                                            @forelse($village['krt_patients'] as $p)
                                                <div class="p-2 rounded-md shadow-2xs space-y-1 transition-all {{ $p['is_h3'] ? 'patient-h3-pulse' : 'bg-white border border-amber-100' }}">
                                                    <div class="flex items-center justify-between gap-1">
                                                        <span class="font-semibold text-slate-900 truncate" title="{{ $p['name'] }}">{{ $p['name'] }}</span>
                                                        <div class="flex items-center gap-1 shrink-0">
                                                            @if($p['is_h3'])
                                                                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-purple-600 text-white font-bold text-xs shadow-2xs" title="Mendekati HPL">
                                                                    <x-ti-bell-filled class="size-2.5 text-white" />
                                                                    <span>{{ $p['sisa_label'] }}</span>
                                                                </span>
                                                            @endif
                                                            <span class="font-bold text-amber-800 text-xs px-1 rounded bg-amber-50">Skor {{ $p['skor'] }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="text-slate-600 text-xs flex items-center justify-between">
                                                        <span>TM {{ $p['trimester'] }}</span>
                                                        <span class="text-slate-400">{{ $p['minggu_ke'] }} mg</span>
                                                    </div>
                                                    <div class="text-slate-500 text-xs flex items-center justify-between">
                                                        <span>HPL: <span class="{{ $p['is_h3'] ? 'text-purple-700 font-bold' : '' }}">{{ $p['hpl'] }}</span></span>
                                                    </div>
                                                    <div class="text-slate-400 text-xs truncate" title="{{ $p['bidan_pengampu'] }}">{{ $p['bidan_pengampu'] }}</div>
                                                </div>
                                            @empty
                                                <div class="text-center py-6 text-slate-400 italic text-xs">Nihil</div>
                                            @endforelse
                                        </div>
                                    </div>

                                    {{-- Kolom 3: KRST (Risiko Sangat Tinggi) --}}
                                    <div class="flex flex-col">
                                        <div class="flex items-center justify-between px-2 py-1.5 rounded-t-lg bg-rose-50 border border-rose-200 text-rose-900 font-bold">
                                            <span class="flex items-center gap-1">
                                                <x-ti-alert-triangle class="size-3.5 text-rose-600" /> KRST
                                            </span>
                                            <span class="px-1.5 py-0.5 rounded-full bg-rose-200/80 text-rose-900 font-bold text-xs">{{ $village['krst_count'] }}</span>
                                        </div>
                                        <div class="bg-slate-50/50 border-x border-b border-rose-200 rounded-b-lg p-1.5 max-h-60 overflow-y-auto space-y-1.5 flex-1">
                                            @forelse($village['krst_patients'] as $p)
                                                <div class="p-2 rounded-md shadow-2xs space-y-1 transition-all {{ $p['is_h3'] ? 'patient-h3-pulse' : 'bg-white border border-rose-100' }}">
                                                    <div class="flex items-center justify-between gap-1">
                                                        <span class="font-semibold text-slate-900 truncate" title="{{ $p['name'] }}">{{ $p['name'] }}</span>
                                                        <div class="flex items-center gap-1 shrink-0">
                                                            @if($p['is_h3'])
                                                                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-purple-600 text-white font-bold text-xs shadow-2xs" title="Mendekati HPL">
                                                                    <x-ti-bell-filled class="size-2.5 text-white" />
                                                                    <span>{{ $p['sisa_label'] }}</span>
                                                                </span>
                                                            @endif
                                                            <span class="font-bold text-rose-800 text-xs px-1 rounded bg-rose-50">Skor {{ $p['skor'] }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="text-slate-600 text-xs flex items-center justify-between">
                                                        <span>TM {{ $p['trimester'] }}</span>
                                                        <span class="text-slate-400">{{ $p['minggu_ke'] }} mg</span>
                                                    </div>
                                                    <div class="text-slate-500 text-xs flex items-center justify-between">
                                                        <span>HPL: <span class="{{ $p['is_h3'] ? 'text-purple-700 font-bold' : '' }}">{{ $p['hpl'] }}</span></span>
                                                    </div>
                                                    <div class="text-slate-400 text-xs truncate" title="{{ $p['bidan_pengampu'] }}">{{ $p['bidan_pengampu'] }}</div>
                                                </div>
                                            @empty
                                                <div class="text-center py-6 text-slate-400 italic text-xs">Nihil</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </x-marker-popup>
                    </x-map-marker>
                @endforeach
            </x-map>
        </div>

        <!-- Legenda Peta (Footer Overlay Bar) -->
        <div class="border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur-xs flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600">
            <div class="flex flex-wrap items-center gap-4">
                <span class="font-semibold text-slate-800 flex items-center gap-1.5">
                    <x-ti-info-circle class="size-4 text-emerald-600" /> Legenda Penanda:
                </span>
                <div class="flex items-center gap-1.5">
                    <span class="inline-flex h-4 min-w-[18px] items-center justify-center rounded-full bg-emerald-600 px-1 text-xs font-bold text-white shadow-2xs">KRR</span>
                    <span>Risiko Rendah</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-flex h-4 min-w-[18px] items-center justify-center rounded-full bg-amber-500 px-1 text-xs font-bold text-white shadow-2xs">KRT</span>
                    <span>Risiko Tinggi</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="relative inline-flex h-4 min-w-[18px] items-center justify-center rounded-full bg-rose-600 px-1 text-xs font-bold text-white shadow-2xs">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                        <span class="relative">KRST</span>
                    </span>
                    <span class="font-semibold text-rose-700">Risiko Sangat Tinggi (Pulse)</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="relative inline-flex h-4 min-w-[18px] items-center gap-0.5 rounded-full bg-purple-600 px-1 text-xs font-bold text-white shadow-2xs">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"></span>
                        <x-ti-bell-filled class="relative size-2 text-white" />
                        <span class="relative">H-3</span>
                    </span>
                    <span class="font-semibold text-purple-700">Siaga H-3 HPL (Pulse Ungu)</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block h-2.5 w-5 border border-emerald-600 bg-emerald-500/20 rounded-xs"></span>
                    <span>Batas Poligon 14 Desa</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block h-2.5 w-5 border-2 border-dashed border-emerald-700 bg-transparent rounded-xs"></span>
                    <span>Batas Kecamatan</span>
                </div>
            </div>

            <div class="text-slate-400">
                Puskesmas Pragaan &bull; Sumenep
            </div>
        </div>
    </x-card>

    {{-- Tabel 1: Rekapitulasi Berdasarkan Alamat Desa --}}
    <x-card paddingless class="bg-white border border-slate-200/80 shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 border-b border-slate-100 bg-white">
            <div class="flex items-center gap-2.5">
                <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-100">
                    <x-ti-building-community class="size-4.5" />
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Rekapitulasi Kehamilan Berdasarkan Alamat Desa</h2>
                    <p class="text-xs text-slate-500">Distribusi wilayah tempat tinggal ibu hamil aktif di Kecamatan Pragaan</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200 self-start sm:self-auto">
                <x-ti-map-pin class="size-3.5 text-slate-500" />
                <span>{{ count($this->pregnancyVillageSummary) }} Desa / Wilayah</span>
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/70 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-2.5 px-3 w-12 text-center font-semibold">No</th>
                        <th class="py-2.5 px-3 font-semibold">Desa</th>
                        <th class="py-2.5 px-3 text-center font-semibold">Total Bumil</th>
                        <th class="py-2.5 px-3 text-center font-semibold">KRR / KRT / KRST</th>
                        <th class="py-2.5 px-3 text-center font-semibold">TM1 / TM2 / TM3</th>
                        <th class="py-2.5 px-3 text-center font-semibold">Siaga H-3</th>
                        <th class="py-2.5 px-3 text-center font-semibold">Lab Abnormal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @php
                        $totBumil = $this->pregnancyStats['total_active'];
                        $idx = 1;
                        $totKrr = 0; $totKrt = 0; $totKrst = 0;
                        $totTm1 = 0; $totTm2 = 0; $totTm3 = 0;
                        $totH3 = 0;
                        $totAbnormal = 0;
                    @endphp
                    @forelse ($this->pregnancyVillageSummary as $v)
                        @php
                            $totKrr += $v['krr'];
                            $totKrt += $v['krt'];
                            $totKrst += $v['krst'];
                            $totTm1 += $v['tm1'];
                            $totTm2 += $v['tm2'];
                            $totTm3 += $v['tm3'];
                            $totH3 += $v['h3_count'];
                            $totAbnormal += $v['abnormal_lab_count'];
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-2.5 px-3 text-center text-xs text-slate-400">{{ $idx++ }}</td>
                            <td class="py-2.5 px-3 text-slate-800 font-medium text-sm">
                                {{ $v['village_name'] }}
                            </td>
                            <td class="py-2.5 px-3 text-center font-semibold text-slate-900 text-sm">
                                {{ $v['total'] }}
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <div class="inline-flex items-center gap-1">
                                    <x-badge :text="$v['krr']" color="emerald" light round sm title="KRR (Risiko Rendah)" />
                                    <x-badge :text="$v['krt']" color="amber" light round sm title="KRT (Risiko Tinggi)" />
                                    <x-badge :text="$v['krst']" color="rose" light round sm title="KRST (Sangat Tinggi)" />
                                </div>
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <div class="inline-flex items-center gap-1">
                                    <x-badge :text="$v['tm1']" color="sky" light round sm title="Trimester 1" />
                                    <x-badge :text="$v['tm2']" color="teal" light round sm title="Trimester 2" />
                                    <x-badge :text="$v['tm3']" color="amber" light round sm title="Trimester 3" />
                                </div>
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                @if ($v['h3_count'] > 0)
                                    <x-badge color="purple" light round sm>
                                        {{ $v['h3_count'] }}
                                    </x-badge>
                                @else
                                    <span class="inline-flex items-center text-xs text-slate-400 font-medium">
                                        -
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                @if ($v['abnormal_lab_count'] > 0)
                                    <x-badge color="rose" light round sm>
                                        <x-slot:left>
                                            <x-ti-alert-circle class="size-3.5 mr-1 text-rose-600" />
                                        </x-slot:left>
                                        {{ $v['abnormal_lab_count'] }} Kasus
                                    </x-badge>
                                @else
                                    <span class="inline-flex items-center text-xs text-slate-400 font-medium">
                                        -
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-xs text-slate-400 italic">
                                Belum ada data kehamilan aktif yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if (count($this->pregnancyVillageSummary) > 0)
                    <tfoot>
                        <tr class="border-t border-slate-200 bg-slate-50/70 text-slate-800 text-xs">
                            <td colspan="2" class="py-2.5 px-3 text-right uppercase tracking-wider text-slate-500 font-semibold">
                                Total Keseluruhan:
                            </td>
                            <td class="py-2.5 px-3 text-center font-bold text-slate-900">
                                {{ $totBumil }} Bumil
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <div class="inline-flex items-center gap-1 font-semibold">
                                    <x-badge :text="$totKrr" color="emerald" light round sm />
                                    <x-badge :text="$totKrt" color="amber" light round sm />
                                    <x-badge :text="$totKrst" color="rose" light round sm />
                                </div>
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <div class="inline-flex items-center gap-1 font-semibold">
                                    <x-badge :text="$totTm1" color="sky" light round sm />
                                    <x-badge :text="$totTm2" color="teal" light round sm />
                                    <x-badge :text="$totTm3" color="amber" light round sm />
                                </div>
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                @if ($totH3 > 0)
                                    <x-badge :text="$totH3 . ' Pasien'" color="purple" round sm />
                                @else
                                    <span class="text-xs text-slate-400">0</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                @if ($totAbnormal > 0)
                                    <x-badge :text="$totAbnormal . ' Kasus'" color="rose" round sm />
                                @else
                                    <span class="text-xs text-slate-400">0</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-card>

    {{-- Tabel 2: Daftar Pasien Siaga H-3 HPL (Pemantauan Persalinan Dekat) --}}
    <x-card paddingless id="tabel-siaga-h3" class="border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between bg-slate-50/60">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-100 text-purple-700">
                    <x-ti-bell-ringing class="size-5" />
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-semibold text-slate-900 flex items-center gap-2">
                        <span>Daftar Ibu Hamil Siaga H-3 HPL</span>
                        <x-badge color="purple" light round class="font-bold">
                            {{ count($this->hMin3PatientsList) }} Pasien
                        </x-badge>
                    </h2>
                    <p class="text-xs text-slate-500">Ibu hamil aktif dengan sisa hari menuju perkiraan persalinan ≤ 3 hari atau telah melewati HPL di wilayah Kecamatan Pragaan</p>
                </div>
            </div>

            @if(count($this->hMin3PatientsList) > 0)
                <div class="flex items-center gap-2">
                    <x-button
                        sm
                        color="emerald"
                        outline
                        href="{{ route('kantong-kehamilan.index') }}"
                        title="Buka Kantong Kehamilan"
                    >
                        <x-ti-folder-heart class="size-4 mr-1.5" /> Buka Kantong Kehamilan
                    </x-button>
                </div>
            @endif
        </div>

        @if(count($this->hMin3PatientsList) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Nama Pasien & Rekam Medis</th>
                            <th class="px-4 py-3">Desa</th>
                            <th class="px-4 py-3">Tanggal HPL</th>
                            <th class="px-4 py-3">Status Sisa Hari</th>
                            <th class="px-4 py-3">Tingkat Risiko</th>
                            <th class="px-4 py-3">Bidan Pengampu & Kontak</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($this->hMin3PatientsList as $patient)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $patient['patient_name'] }}</div>
                                    <div class="text-xs text-slate-500">No. RM: {{ $patient['mrn'] }} &bull; NIK: {{ $patient['nik'] }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 font-medium text-slate-700">
                                        <x-ti-map-pin class="size-3.5 text-slate-400" /> Desa {{ $patient['village_name'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-800">
                                    {{ $patient['hpl'] }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $patient['sisa_hari'] <= 0 ? 'bg-rose-100 text-rose-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ $patient['sisa_label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $patient['risk_badge_class'] }}">
                                        {{ $patient['risk_code'] }} (Skor {{ $patient['skor'] }})
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    <div class="font-medium text-slate-800">{{ $patient['bidan_pengampu'] }}</div>
                                    @if($patient['phone'] !== '-')
                                        <div class="text-slate-500 flex items-center gap-1 mt-0.5">
                                            <x-ti-phone class="size-3 text-emerald-600" /> {{ $patient['phone'] }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <x-button.circle
                                        color="emerald"
                                        sm
                                        flat
                                        href="{{ route('kantong-kehamilan.index') }}"
                                        title="Buka Kantong Kehamilan"
                                    >
                                        <x-ti-arrow-up-right class="size-4" />
                                    </x-button.circle>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-8 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 mb-3">
                    <x-ti-circle-check class="size-6" />
                </div>
                <h3 class="text-sm font-semibold text-slate-800">Tidak Ada Ibu Hamil Siaga H-3 HPL</h3>
                <p class="mt-1 text-xs text-slate-500 max-w-md mx-auto">Saat ini seluruh ibu hamil aktif berada di luar rentang siaga mendekati hari perkiraan persalinan (HPL).</p>
            </div>
        @endif
    </x-card>

    {{-- Tabel 3: Pasien dengan Hasil Laboratorium Abnormal --}}
    <x-card paddingless id="tabel-lab-abnormal" class="bg-white border border-slate-200/80 shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 border-b border-slate-100 bg-white">
            <div class="flex items-center gap-2.5">
                <div class="flex size-8 items-center justify-center rounded-lg bg-rose-50 text-rose-700 border border-rose-100">
                    <x-ti-flask-2 class="size-4.5" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-semibold text-slate-900">Daftar Pasien dengan Hasil Laboratorium Abnormal</h2>
                        @if (count($this->abnormalLabPatients) > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                {{ count($this->abnormalLabPatients) }} Pasien
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                0 Pasien
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500">Ibu hamil aktif yang memerlukan pemantauan medis intensif dan rujukan</p>
                </div>
            </div>
            <a href="{{ route('kantong-kehamilan.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 hover:text-emerald-800 transition-colors self-start sm:self-auto">
                <span>Buka di Kantong Kehamilan</span>
                <x-ti-arrow-right class="size-3.5" />
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/70 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-2.5 px-3 w-12 text-center font-semibold">No</th>
                        <th class="py-2.5 px-3 font-semibold">Desa</th>
                        <th class="py-2.5 px-3 font-semibold">Pasien</th>
                        <th class="py-2.5 px-3 text-center font-semibold">Usia Hamil</th>
                        <th class="py-2.5 px-3 text-center font-semibold">Risiko PR</th>
                        <th class="py-2.5 px-3 font-semibold">Parameter Lab Abnormal</th>
                        <th class="py-2.5 px-3 font-semibold">Bidan Pengampu</th>
                        <th class="py-2.5 px-3 font-semibold">Pemeriksa Terakhir</th>
                        <th class="py-2.5 px-3 text-center w-14 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($this->abnormalLabPatients as $index => $patient)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-2.5 px-3 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                            <td class="py-2.5 px-3 text-slate-700 text-sm">
                                {{ $patient['village_name'] }}
                            </td>
                            <td class="py-2.5 px-3 font-medium text-slate-900 text-sm">
                                {{ $patient['patient_name'] }}
                            </td>
                            <td class="py-2.5 px-3 text-center text-xs">
                                <span class="font-bold text-slate-800">{{ $patient['usia_kehamilan'] }}</span>
                                <span class="block text-teal-700 font-medium">TM {{ $patient['trimester'] }}</span>
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                @php
                                    $skor = $patient['skor_puji_rochyati'];
                                    $riskColor = $skor > 10 ? 'rose' : ($skor >= 6 ? 'amber' : 'emerald');
                                @endphp
                                <x-badge :text="'Skor ' . $skor" :color="$riskColor" light round sm />
                            </td>
                            <td class="py-2.5 px-3">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @foreach ($patient['abnormal_labs'] as $lab)
                                        @php
                                            $labColor = ($lab['alert_type'] ?? '') === 'danger' ? 'rose' : 'amber';
                                        @endphp
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $labColor === 'rose' ? 'bg-rose-50 text-rose-700 border border-rose-200/60' : 'bg-amber-50 text-amber-700 border border-amber-200/60' }}">
                                            <span class="font-semibold mr-1">{{ $lab['param'] ?? $lab['label'] }}:</span>
                                            <span>{{ $lab['value'] }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-2.5 px-3 text-xs text-slate-700">
                                {{ $patient['bidan_pengampu'] }}
                            </td>
                            <td class="py-2.5 px-3 text-xs">
                                <div class="text-slate-800 font-medium">{{ $patient['latest_midwife_name'] }}</div>
                                <div class="text-slate-400 mt-0.5">{{ $patient['latest_encounter_date'] }}</div>
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <x-button.circle color="emerald" sm href="{{ route('kantong-kehamilan.index') }}" title="Lihat di Kantong Kehamilan">
                                    <x-ti-arrow-up-right class="size-4" />
                                </x-button.circle>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center">
                                <div class="flex flex-col items-center justify-center gap-1.5 text-emerald-700">
                                    <x-ti-shield-check class="size-7 text-emerald-600" />
                                    <p class="font-semibold text-sm text-slate-800">Hasil Laboratorium Terpantau Normal</p>
                                    <p class="text-xs text-slate-500">Tidak ada pasien kehamilan aktif dengan parameter laboratorium abnormal saat ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
