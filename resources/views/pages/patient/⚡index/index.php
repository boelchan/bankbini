<?php

use App\Livewire\Traits\WithTableX;
use App\Models\Patient;
use Livewire\Component;
use Livewire\Attributes\Computed;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use WithTableX, Interactions;

    public $sortFieldDefault = 'medical_record_number';
    public $sortDirectionDefault = 'desc';

    public $search_full_name = '';
    public $search_nik = '';
    public $search_family_name = '';
    public $search_medical_record_number = '';
    public $search_village = '';
    public $search_status = '';

    #[Computed]
    public function dataTable()
    {
        return Patient::query()
            ->with('village')
            ->when($this->search_full_name, fn($q) => $q->where('full_name', 'like', '%' . $this->search_full_name . '%'))
            ->when($this->search_nik, fn($q) => $q->where('nik', 'like', '%' . $this->search_nik . '%'))
            ->when($this->search_family_name, fn($q) => $q->where('family_name', 'like', '%' . $this->search_family_name . '%'))
            ->when($this->search_medical_record_number, fn($q) => $q->where('medical_record_number', (int) $this->search_medical_record_number))
            ->when($this->search_village, function ($q) {
                $q->whereHas('village', function ($q2) {
                    $q2->where('name', 'like', $this->search_village . '%');
                });
            })
            ->when($this->search_status, function ($q) {
                if ($this->search_status == 'aktif') {
                    $q->where('is_active', true);
                } else {
                    $q->where('is_active', false);
                }
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    /**
     * Menghapus data pasien.
     */
    public function delete(int $id): void
    {
        $patient = Patient::with(['encounters', 'pregnancies'])->findOrFail($id);

        if ($patient->encounters()->count() > 0 || $patient->pregnancies()->count() > 0) {
            $this->toast()->error('Pasien tidak dapat dihapus! Sudah ada data kunjungan/kehamilan')->send();
            return;
        }

        $patient->delete();
        $this->toast()->success('Pasien berhasil dihapus')->send();
    }
};
