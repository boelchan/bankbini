<?php

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

new class extends Component
{
    use Interactions;

    public bool $showEditModal = false;
    public ?int $edit_pregnancy_id = null;
    public ?Pregnancy $editingPregnancy = null;
    public ?int $edit_bidan_id = null;
    public ?string $edit_hpht = null;
    public ?string $edit_hpl = null;
    public int $edit_gravida = 1;
    public int $edit_partus = 0;
    public int $edit_abortus = 0;
    public string $edit_catatan = '';
    public string $edit_status = 'aktif';

    /**
     * Buka modal edit data kehamilan.
     */
    #[On('open-edit-pregnancy-modal')]
    public function openModal(int $pregnancyId): void
    {
        try {
            $pregnancy = Pregnancy::with(['patient', 'creator'])->findOrFail($pregnancyId);

            if (!$pregnancy->canEdit()) {
                $this->toast()->error('Anda tidak memiliki hak akses untuk mengubah data kantong kehamilan ini. Hanya bidan pengampu atau koordinator bidan yang dapat mengubahnya.')->send();
                return;
            }

            $this->editingPregnancy = $pregnancy;
            $this->edit_pregnancy_id = $pregnancy->id;
            $this->edit_bidan_id = $pregnancy->created_by;
            $this->edit_hpht = $pregnancy->hpht ? $pregnancy->hpht->format('Y-m-d') : null;
            $this->edit_hpl = $pregnancy->hpl ? $pregnancy->hpl->format('Y-m-d') : null;
            $this->edit_gravida = $pregnancy->gravida;
            $this->edit_partus = $pregnancy->partus;
            $this->edit_abortus = $pregnancy->abortus;
            $this->edit_catatan = $pregnancy->catatan ?? '';
            $this->edit_status = $pregnancy->status ?? 'aktif';

            $this->showEditModal = true;
        } catch (\Throwable $e) {
            Log::error('Error in openEditModal: ' . $e->getMessage(), ['id' => $pregnancyId]);
            $this->toast()->error('Data kehamilan tidak ditemukan atau telah dihapus.')->send();
        }
    }

    /**
     * Memeriksa apakah pengguna saat ini adalah Koordinator Bidan.
     */
    #[Computed]
    public function isKoordinator(): bool
    {
        return (bool) Auth::user()?->hasRole('koordinator-bidan');
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
            ->where(function ($q) {
                $q->where('status', 'active');
                if ($this->edit_bidan_id) {
                    $q->orWhere('id', $this->edit_bidan_id);
                }
            })
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
     * Memeriksa apakah user saat ini berhak mengubah data pokok kantong kehamilan.
     * Menggunakan method canEdit() pada model Pregnancy.
     */
    public function canEditPregnancy(Pregnancy $pregnancy): bool
    {
        return $pregnancy->canEdit();
    }

    /**
     * Otomatis hitung HPL saat HPHT diedit.
     */
    public function updatedEditHpht(?string $value): void
    {
        if ($value) {
            try {
                $this->edit_hpl = Carbon::parse($value)->addDays(280)->format('Y-m-d');
            } catch (\Throwable $e) {
                $this->edit_hpl = null;
            }
        } else {
            $this->edit_hpl = null;
        }
    }

    /**
     * Simpan pembaruan data kehamilan.
     */
    public function updatePregnancy(): void
    {
        $rules = [
            'edit_pregnancy_id' => 'required|exists:pregnancies,id',
            'edit_hpht'         => 'required|date|before_or_equal:today',
            'edit_hpl'          => 'nullable|date',
            'edit_gravida'      => 'required|integer|min:1|max:30',
            'edit_partus'       => 'required|integer|min:0|max:30',
            'edit_abortus'      => 'required|integer|min:0|max:30',
            'edit_status'       => 'required|in:aktif,selesai,abortus,meninggal',
            'edit_catatan'      => 'nullable|string|max:1000',
        ];

        $messages = [
            'edit_pregnancy_id.required'  => 'ID kehamilan tidak valid.',
            'edit_hpht.required'          => 'Tanggal HPHT wajib diisi.',
            'edit_hpht.date'              => 'Format tanggal HPHT tidak valid.',
            'edit_hpht.before_or_equal'   => 'Tanggal HPHT tidak boleh di masa mendatang.',
            'edit_gravida.required'       => 'Gravida wajib diisi.',
            'edit_gravida.min'            => 'Gravida wajib minimal 1.',
            'edit_gravida.max'            => 'Nilai Gravida melebihi batas wajar.',
            'edit_partus.min'             => 'Partus minimal 0.',
            'edit_abortus.min'            => 'Abortus minimal 0.',
            'edit_status.required'        => 'Status kehamilan wajib dipilih.',
            'edit_status.in'              => 'Pilihan status kehamilan tidak valid.',
            'edit_catatan.max'            => 'Catatan maksimal 1000 karakter.',
        ];

        if ($this->isKoordinator) {
            $rules['edit_bidan_id'] = 'required|exists:users,id';
            $messages['edit_bidan_id.required'] = 'Bidan Pengampu wajib dipilih.';
            $messages['edit_bidan_id.exists'] = 'Bidan Pengampu yang dipilih tidak valid.';
        }

        $this->validate($rules, $messages);

        if ($this->isKoordinator && $this->edit_bidan_id) {
            $selectedMidwife = User::role('bidan')->where('id', $this->edit_bidan_id)->first();
            if (!$selectedMidwife) {
                throw ValidationException::withMessages([
                    'edit_bidan_id' => 'Bidan pengampu yang dipilih harus memiliki peran Bidan.',
                ]);
            }
        }

        try {
            DB::transaction(function () {
                $pregnancy = Pregnancy::findOrFail($this->edit_pregnancy_id);

                if (!$pregnancy->canEdit()) {
                    throw new \DomainException('Anda tidak memiliki hak akses untuk mengubah data kantong kehamilan ini.');
                }

                $hpl = $this->edit_hpl ?: Carbon::parse($this->edit_hpht)->addDays(280)->format('Y-m-d');

                $updateData = [
                    'updated_by' => Auth::id(),
                    'hpht'       => $this->edit_hpht,
                    'hpl'        => $hpl,
                    'gravida'    => $this->edit_gravida,
                    'partus'     => $this->edit_partus,
                    'abortus'    => $this->edit_abortus,
                    'catatan'    => $this->edit_catatan,
                    'status'     => $this->edit_status,
                ];

                if ($this->isKoordinator && $this->edit_bidan_id) {
                    $updateData['created_by'] = $this->edit_bidan_id;
                }

                $pregnancy->update($updateData);
            });

            $this->showEditModal = false;
            $this->dispatch('pregnancy-updated');
            $this->toast()->success('Data kehamilan berhasil diperbarui!')->send();
        } catch (ValidationException $e) {
            throw $e;
        } catch (\DomainException $e) {
            $this->toast()->error($e->getMessage())->send();
        } catch (\Throwable $e) {
            Log::error('Error in updatePregnancy: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $this->toast()->error('Terjadi kesalahan saat memperbarui data kehamilan.')->send();
        }
    }
};
