<?php

use App\Models\Pregnancy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use Interactions;

    public bool $showDetailModal = false;
    public ?int $pregnancyId = null;
    public string $detail_active_tab = 'kehamilan';

    /**
     * Memeriksa apakah user saat ini berhak mengedit status kehamilan.
     * Menggunakan method canEdit() pada model Pregnancy.
     */
    public function canEditPregnancy(Pregnancy $pregnancy): bool
    {
        return $pregnancy->canEdit();
    }

    /**
     * Buka modal Detail Kantong Kehamilan dari event.
     */
    #[On('open-detail-modal')]
    public function openDetail(int $pregnancyId, ?string $tab = null): void
    {
        try {
            $this->pregnancyId = $pregnancyId;
            unset($this->selectedPregnancy);

            $pregnancy = $this->selectedPregnancy;
            if (!$pregnancy) {
                $this->toast()->error('Data detail kehamilan tidak ditemukan.')->send();
                return;
            }

            if ($pregnancy->status === 'selesai') {
                $this->detail_active_tab = $tab ?? 'nifas';
            } else {
                $this->detail_active_tab = 'kehamilan';
            }

            $this->showDetailModal = true;
            $this->dispatch('refresh-tabs');
        } catch (\Throwable $e) {
            Log::error('Error in openDetail: ' . $e->getMessage(), ['id' => $pregnancyId]);
            $this->toast()->error('Data detail kehamilan tidak ditemukan.')->send();
        }
    }

    /**
     * Membuka dialog konfirmasi pembatalan persalinan dari detail modal.
     */
    public function promptBatalkanPersalinan(int $pregnancyId): void
    {
        try {
            $pregnancy = Pregnancy::with(['patient', 'postnatalEncounters', 'neonatalEncounters'])->findOrFail($pregnancyId);

            if (!$this->canEditPregnancy($pregnancy)) {
                $this->toast()->error('Anda tidak memiliki hak akses untuk membatalkan persalinan ini.')->send();
                return;
            }

            if ($pregnancy->postnatalEncounters()->exists() || $pregnancy->neonatalEncounters()->exists()) {
                $this->toast()->error('Tidak dapat membatalkan persalinan karena sudah terdapat data kunjungan nifas atau neonatal.')->send();
                return;
            }

            $patientName = $pregnancy->patient?->full_name ?? 'Pasien';
            $statusLabel = match ($pregnancy->status) {
                'abortus'   => 'status kejadian abortus',
                'meninggal' => 'status kematian pasien',
                default     => 'status persalinan',
            };

            $this->dialog()
                ->warning(
                    'Konfirmasi Batalkan ' . ucfirst($pregnancy->status),
                    "Apakah Anda yakin ingin membatalkan {$statusLabel} Ibu {$patientName}? Status kehamilan akan kembali menjadi AKTIF dan riwayat resolusi kehamilan ini akan dihapus."
                )
                ->confirm('Ya, Batalkan', 'confirmBatalkanPersalinan', $pregnancy->id)
                ->cancel('Tutup')
                ->send();
        } catch (\Throwable $e) {
            Log::error('Error in promptBatalkanPersalinan: ' . $e->getMessage(), ['id' => $pregnancyId]);
            $this->toast()->error('Data kehamilan tidak ditemukan.')->send();
        }
    }

    /**
     * Eksekusi pembatalan persalinan (mengembalikan status kehamilan menjadi aktif).
     */
    public function confirmBatalkanPersalinan(int $pregnancyId): void
    {
        try {
            $pregnancy = Pregnancy::with(['patient', 'postnatalEncounters', 'neonatalEncounters', 'pregnancyHistories'])->findOrFail($pregnancyId);

            if (!$this->canEditPregnancy($pregnancy)) {
                $this->toast()->error('Anda tidak memiliki hak akses untuk membatalkan persalinan ini.')->send();
                return;
            }

            if ($pregnancy->postnatalEncounters()->exists() || $pregnancy->neonatalEncounters()->exists()) {
                $this->toast()->error('Tidak dapat membatalkan persalinan karena sudah terdapat data kunjungan nifas atau neonatal.')->send();
                return;
            }

            // Bersihkan catatan penanda abortus/meninggal dari catatan kehamilan
            $cleanedCatatan = preg_replace('/(\r?\n)?\[(Abortus|Pasien Meninggal): [^\n]*\]/m', '', $pregnancy->catatan ?? '');

            DB::transaction(function () use ($pregnancy, $cleanedCatatan) {
                // Hapus riwayat persalinan terkait kehamilan ini
                $pregnancy->pregnancyHistories()->delete();

                // Jika status sebelumnya meninggal, kembalikan pasien menjadi aktif
                if ($pregnancy->status === 'meninggal') {
                    $pregnancy->patient?->update(['is_active' => true]);
                }

                // Kembalikan status kehamilan menjadi aktif
                $pregnancy->update([
                    'status'  => 'aktif',
                    'catatan' => trim($cleanedCatatan) ?: null,
                ]);
            });

            $this->showDetailModal = false;
            $this->dispatch('pregnancy-updated');
            $this->toast()->success('Status kehamilan berhasil dibatalkan dan kembali aktif.')->send();
        } catch (\Throwable $e) {
            Log::error('Error in confirmBatalkanPersalinan: ' . $e->getMessage(), ['id' => $pregnancyId]);
            $this->toast()->error('Gagal membatalkan persalinan: ' . $e->getMessage())->send();
        }
    }

    /**
     * Listener saat data di tab di-refresh.
     */
    #[On('refresh-tabs')]
    #[On('pregnancy-updated')]
    public function refreshDetail(): void
    {
        unset($this->selectedPregnancy);
    }

    /**
     * Mengambil model data kehamilan yang dipilih beserta seluruh relasinya.
     */
    #[Computed]
    public function selectedPregnancy(): ?Pregnancy
    {
        if (!$this->pregnancyId) {
            return null;
        }

        return Pregnancy::with([
            'patient.village',
            'patient.district',
            'patient.pregnancyHistories',
            'pregnancyHistory',
            'creator',
            'encounters.creator',
            'encounters.pregnancy',
            'postnatalEncounters.creator',
            'neonatalEncounters.creator',
            'neonatalComplication.creator',
        ])->find($this->pregnancyId);
    }
};
