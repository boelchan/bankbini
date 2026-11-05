<?php

use App\Models\Patient;
use App\Models\Pregnancy;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new class extends Component {
    use Interactions;

    public bool $showCreateModal = false;
    public ?int $create_patient_id = null;
    public ?int $create_bidan_id = null;
    public ?string $create_hpht = null;
    public ?string $create_hpl = null;
    public int $create_gravida = 1;
    public int $create_partus = 0;
    public int $create_abortus = 0;
    public string $create_catatan = '';

    /**
     * Memeriksa apakah user login saat ini adalah Koordinator Bidan.
     * Hanya role koordinator-bidan yang dapat memilih Bidan Pengampu (role bidan).
     */
    #[Computed]
    public function isKoordinator(): bool
    {
        return (bool) Auth::user()?->hasRole('koordinator-bidan');
    }

    /**
     * Buka modal pencatatan kehamilan baru dari event.
     */
    #[On('open-create-pregnancy-modal')]
    public function openModal(): void
    {
        $this->reset(['create_patient_id', 'create_bidan_id', 'create_hpht', 'create_hpl', 'create_catatan']);
        $this->create_gravida = 1;
        $this->create_partus = 0;
        $this->create_abortus = 0;
        $this->create_catatan = '';
        $this->create_bidan_id = $this->isKoordinator ? null : Auth::id();

        $this->showCreateModal = true;
    }

    /**
     * Daftar pasien yang tersedia untuk kehamilan baru (belum punya kehamilan aktif).
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function availablePatients(): array
    {
        return Patient::with('village')
            ->where('is_active', true)
            ->whereDoesntHave('activePregnancy')
            ->orderBy('full_name')
            ->get(['id', 'medical_record_number', 'full_name', 'village_code'])
            ->map(
                fn($item) => [
                    'label' => sprintf('%04d - %s%s', $item->medical_record_number, $item->full_name, $item->village ? " ({$item->village->name})" : ''),
                    'value' => $item->id,
                ],
            )
            ->toArray();
    }

    /**
     * Daftar bidan pengampu (user dengan role bidan) untuk dipilih oleh Koordinator Bidan.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function availableMidwives(): array
    {
        return User::with('village')
            ->role('bidan')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'village_code'])
            ->map(
                fn(User $user): array => [
                    'label' => $user->village?->name ? "{$user->name} (Desa {$user->village->name})" : $user->name,
                    'value' => $user->id,
                ],
            )
            ->toArray();
    }

    /**
     * Otomatis hitung HPL saat HPHT diisi di modal kehamilan baru.
     */
    public function updatedCreateHpht(?string $value): void
    {
        if ($value) {
            try {
                $this->create_hpl = Carbon::parse($value)->addDays(280)->format('Y-m-d');
            } catch (\Throwable $e) {
                $this->create_hpl = null;
            }
        } else {
            $this->create_hpl = null;
        }
    }

    /**
     * Simpan pendaftaran kehamilan baru.
     */
    public function saveNewPregnancy(): void
    {
        $rules = [
            'create_patient_id' => 'required|exists:patients,id',
            'create_hpht' => 'required|date|before_or_equal:today',
            'create_hpl' => 'nullable|date',
            'create_gravida' => 'required|integer|min:1|max:30',
            'create_partus' => 'required|integer|min:0|max:30',
            'create_abortus' => 'required|integer|min:0|max:30',
            'create_catatan' => 'nullable|string|max:1000',
        ];

        $messages = [
            'create_patient_id.required' => 'Silakan pilih pasien.',
            'create_patient_id.exists' => 'Data pasien yang dipilih tidak valid.',
            'create_bidan_id.required' => 'Silakan pilih bidan pengampu.',
            'create_bidan_id.exists' => 'Bidan pengampu yang dipilih tidak valid.',
            'create_hpht.required' => 'Tanggal HPHT wajib diisi.',
            'create_hpht.date' => 'Format tanggal HPHT tidak valid.',
            'create_hpht.before_or_equal' => 'Tanggal HPHT tidak boleh di masa mendatang.',
            'create_gravida.required' => 'Gravida wajib diisi.',
            'create_gravida.min' => 'Gravida wajib minimal 1.',
            'create_gravida.max' => 'Nilai Gravida melebihi batas wajar.',
            'create_partus.min' => 'Partus minimal 0.',
            'create_abortus.min' => 'Abortus minimal 0.',
            'create_catatan.max' => 'Catatan maksimal 1000 karakter.',
        ];

        if ($this->isKoordinator) {
            $rules['create_bidan_id'] = 'required|exists:users,id';
        }

        $this->validate($rules, $messages);

        $midwifeId = Auth::id();
        if ($this->isKoordinator) {
            $selectedMidwife = User::role('bidan')->where('id', $this->create_bidan_id)->first();
            if (!$selectedMidwife) {
                throw ValidationException::withMessages([
                    'create_bidan_id' => 'Bidan pengampu yang dipilih harus memiliki peran Bidan.',
                ]);
            }
            $midwifeId = $selectedMidwife->id;
        }

        $existsActive = Pregnancy::where('patient_id', $this->create_patient_id)->where('status', 'aktif')->exists();

        if ($existsActive) {
            $this->toast()->warning('Pasien ini sudah memiliki kehamilan aktif!')->send();
            return;
        }

        try {
            DB::transaction(function () use ($midwifeId) {
                $hpl = $this->create_hpl ?: Carbon::parse($this->create_hpht)->addDays(280)->format('Y-m-d');

                Pregnancy::create([
                    'patient_id' => $this->create_patient_id,
                    'created_by' => $midwifeId,
                    'hpht' => $this->create_hpht,
                    'hpl' => $hpl,
                    'gravida' => $this->create_gravida,
                    'partus' => $this->create_partus,
                    'abortus' => $this->create_abortus,
                    'catatan' => $this->create_catatan,
                    'status' => 'aktif',
                ]);
            });

            $this->showCreateModal = false;
            $this->dispatch('pregnancy-updated');
            $this->toast()->success('Kehamilan baru berhasil didaftarkan ke Kantong Persalinan!')->send();
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error in saveNewPregnancy: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->toast()
                ->error('Terjadi kesalahan saat mendaftarkan kehamilan: ' . $e->getMessage())
                ->send();
        }
    }
};
