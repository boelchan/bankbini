<div>
    <h1 class="text-2xl font-medium text-slate-900">Pasien</h1>
    <nav class="flex items-center gap-1.5 text-xs text-slate-500 mt-1">
        <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition-colors"><x-ti-home class="size-4" /></a>
        <span>/</span>
        <a href="{{ route('patient.index') }}" class="hover:text-emerald-600 transition-colors">Pasien</a>
        <span>/</span>
        <span class="text-slate-700 font-medium">Edit data</span>
    </nav>

    <div class="mt-6">
        <form wire:submit="update">
            @csrf
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="space-y-4">
                        <h2 class="text-lg font-bold text-slate-800">Identitas Utama</h2>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex-1">
                                <x-input wire:model="medical_record_number" label="No. Rekam Medis *" placeholder="Masukkan No. Rekam Medis" />
                            </div>
                            <div class="sm:pt-6">
                                <x-toggle wire:model="is_active" label="Status Aktif" />
                            </div>
                        </div>
                        <x-input wire:model.live.debounce.500ms="nik" label="NIK" placeholder="16 digit NIK" maxlength="16" />

                        <x-input wire:model.live.debounce.500ms="full_name" label="Nama Lengkap *" />
                        <x-select.styled wire:model="gender" label="Jenis Kelamin *" :options="\App\Enums\GenderEnum::choices()" select="label:label|value:value" />
                        
                        <div class="grid grid-cols-3 gap-2" x-data="{
                            setBirthDate() {
                                if (this.$wire.age) {
                                    let year = new Date().getFullYear() - this.$wire.age;
                                    this.$wire.set('birth_date', year + '-01-01');
                                }
                            },
                            setAge() {
                                if (this.$wire.birth_date) {
                                    let birth = new Date(this.$wire.birth_date);
                                    let today = new Date();
                                    let age = today.getFullYear() - birth.getFullYear();
                                    let m = today.getMonth() - birth.getMonth();
                                    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                                        age--;
                                    }
                                    this.$wire.set('age', age);
                                }
                            }
                        }" x-init="
                            $watch('$wire.birth_date', value => setAge());
                        ">
                            <x-input wire:model.live.debounce.500ms="age" label="Umur" suffix="tahun" type="number" x-on:input="setBirthDate()" />
                            <div class="col-span-2">
                                <x-date wire:model.live="birth_date" format="DD MMM YYYY" label="Tanggal Lahir" />
                            </div>
                        </div>
                        
                        <x-input wire:model.live.debounce.500ms="family_name" label="Nama Suami" />
                        <x-input wire:model="mobile_phone" label="No. Handphone / WA" />
                    </div>

                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="space-y-4">
                        <h2 class="text-lg font-bold text-slate-800">Alamat</h2>

                        {{-- PROVINSI --}}
                        <x-select.styled
                            id="province-select"
                            wire:model.live="province_code"
                            label="Provinsi"
                            placeholder="Pilih Provinsi"
                            :options="$provinces"
                            searchable
                        />

                        {{-- KOTA / KABUPATEN --}}
                        <div wire:key="wrapper-regency-{{ $province_code }}-{{ count($regencies) }}">
                            @if ($province_code && count($regencies) > 0)
                                <x-select.styled
                                    id="regency-select-{{ $province_code }}"
                                    wire:model.live="regency_code"
                                    label="Kota / Kabupaten"
                                    placeholder="Pilih Kota/Kabupaten"
                                    :options="$regencies"
                                    searchable
                                />
                            @else
                                <x-select.styled label="Kota / Kabupaten" placeholder="Pilih Provinsi terlebih dahulu" disabled />
                            @endif
                        </div>

                        {{-- KECAMATAN --}}
                        <div wire:key="wrapper-district-{{ $regency_code }}-{{ count($districts) }}">
                            @if ($regency_code && count($districts) > 0)
                                <x-select.styled
                                    id="district-select-{{ $regency_code }}"
                                    wire:model.live="district_code"
                                    label="Kecamatan"
                                    placeholder="Pilih Kecamatan"
                                    :options="$districts"
                                    searchable
                                />
                            @else
                                <x-select.styled label="Kecamatan" placeholder="Pilih Kota/Kabupaten terlebih dahulu" disabled />
                            @endif
                        </div>

                        {{-- KELURAHAN / DESA --}}
                        <div wire:key="wrapper-village-{{ $district_code }}-{{ count($villages) }}">
                            @if ($district_code && count($villages) > 0)
                                <x-select.styled
                                    id="village-select-{{ $district_code }}"
                                    wire:model="village_code"
                                    label="Desa *"
                                    placeholder="Pilih Desa"
                                    :options="$villages"
                                    searchable
                                />
                            @else
                                <x-select.styled label="Desa *" placeholder="Pilih Kecamatan terlebih dahulu" disabled />
                            @endif
                        </div>
                        <x-input wire:model="address" label="Alamat" />

                    </div>
                </div>

            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-button color="gray" flat href="{{ route('patient.index') }}" wire:navigate>Batal</x-button>
                <x-button submit color="emerald" loading="update">
                    Update Data Pasien
                </x-button>
            </div>
        </form>
    </div>
</div>
