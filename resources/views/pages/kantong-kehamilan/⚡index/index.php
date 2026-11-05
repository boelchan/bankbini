<?php

use App\Models\IndonesiaRegion;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Models\PregnancyHistory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

new #[Title('Kantong Persalinan & Kehamilan')] 
class extends Component {
    use WithPagination, Interactions;

    /**
     * Parameter Pencarian & Filter
     */
    public ?string $search = '';
    public ?string $filter_desa = 'semua';
    public ?string $filter_risiko = 'semua';
    public ?string $filter_status = 'aktif';
    public ?string $filter_trimester = 'semua';
    public ?string $filter_tanggal_kontrol = null;
    public string $filter_jenis_kontrol = 'selanjutnya'; // 'selanjutnya', 'periksa', 'semua'
    public bool $filter_hpl_min_3 = false;

    /**
     * Inisialisasi awal komponen.
     * Mengatur default filter desa untuk bidan sesuai desa binaannya.
     */
    public function mount(): void
    {
        $user = Auth::user();
        if ($user && ($user->isBidan() || !$user->hasRole(['administrator', 'koordinator-bidan'])) && $user->village_code) {
            $this->filter_desa = (string) $user->village_code;
        }
    }

    /**
     * Reset pagination saat pencarian berubah.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination saat filter desa berubah.
     */
    public function updatedFilterDesa(): void
    {
        if (empty($this->filter_desa)) {
            $this->filter_desa = 'semua';
        }
        $this->resetPage();
    }

    /**
     * Reset pagination saat filter risiko berubah.
     */
    public function updatedFilterRisiko(): void
    {
        if (empty($this->filter_risiko)) {
            $this->filter_risiko = 'semua';
        }
        $this->resetPage();
    }

    /**
     * Reset pagination saat filter status kehamilan berubah.
     */
    public function updatedFilterStatus(): void
    {
        if (empty($this->filter_status)) {
            $this->filter_status = 'aktif';
        }
        // Filter trimester hanya berlaku saat status hamil (aktif)
        if ($this->filter_status !== 'aktif') {
            $this->filter_trimester = 'semua';
        }
        $this->resetPage();
    }

    /**
     * Reset pagination saat filter trimester berubah.
     */
    public function updatedFilterTrimester(): void
    {
        if (empty($this->filter_trimester) || $this->filter_status !== 'aktif') {
            $this->filter_trimester = 'semua';
        }
        $this->resetPage();
    }

    /**
     * Toggle filter trimester (misal klik kotak stat TM 3).
     */
    public function toggleFilterTm(string $tm): void
    {
        if ($this->filter_status !== 'aktif') {
            $this->filter_status = 'aktif';
            $this->filter_trimester = $tm;
        } else {
            $this->filter_trimester = ($this->filter_trimester === $tm) ? 'semua' : $tm;
        }
        $this->resetPage();
    }

    /**
     * Reset pagination saat filter tanggal kontrol berubah.
     */
    public function updatedFilterTanggalKontrol(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination saat filter jenis tanggal kontrol berubah.
     */
    public function updatedFilterJenisKontrol(): void
    {
        $this->resetPage();
    }

    /**
     * Kosongkan filter tanggal kontrol.
     */
    public function clearFilterTanggalKontrol(): void
    {
        $this->filter_tanggal_kontrol = null;
        $this->resetPage();
    }

    /**
     * Toggle filter khusus ibu hamil siaga HPL -3.
     */
    public function toggleFilterHplMin3(): void
    {
        if ($this->filter_status !== 'aktif' && !$this->filter_hpl_min_3) {
            $this->filter_status = 'aktif';
        }
        $this->filter_hpl_min_3 = !$this->filter_hpl_min_3;
        $this->resetPage();
    }

    /**
     * Statistik ringkasan kantong kehamilan.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function stats(): array
    {
        $activePregnancies = Pregnancy::with('latestEncounter')
            ->when($this->filter_status && $this->filter_status !== 'semua', function ($q) {
                $q->where('status', $this->filter_status);
            })
            ->when($this->filter_desa && $this->filter_desa !== 'semua', function ($q) {
                $q->whereHas('patient', fn($sub) => $sub->where('village_code', $this->filter_desa));
            })
            ->get();

        $totalAktif = $activePregnancies->count();
        $totalRendah = 0;
        $totalTinggi = 0;
        $totalSangatTinggi = 0;
        $totalMendekatiHpl = 0;
        $totalHMin3 = 0;

        foreach ($activePregnancies as $preg) {
            $skor = $preg->latestEncounter?->skor_puji_rochyati ?? 2;
            if ($skor <= 5) {
                $totalRendah++;
            } elseif ($skor <= 10) {
                $totalTinggi++;
            } else {
                $totalSangatTinggi++;
            }

            if ($preg->status === 'aktif') {
                if ($preg->trimester === 3) {
                    $totalMendekatiHpl++;
                }

                $sisaHari = $preg->sisa_hari_hpl;
                if ($sisaHari !== null && $sisaHari <= 3 && $sisaHari >= -7) {
                    $totalHMin3++;
                }
            }
        }

        return [
            'total_aktif' => $totalAktif,
            'risiko_rendah' => $totalRendah,
            'risiko_tinggi' => $totalTinggi,
            'risiko_sangat_tinggi' => $totalSangatTinggi,
            'trimester_3' => $totalMendekatiHpl,
            'hpl_min_3' => $totalHMin3,
        ];
    }

    /**
     * Query kartu kantong persalinan.
     */
    #[Computed]
    public function pregnancies(): LengthAwarePaginator
    {
        return Pregnancy::query()
            ->with([
                'patient.village',
                'patient.pregnancyHistories',
                'creator',
                'latestEncounter.creator',
                'encounters.creator',
                'pregnancyHistory',
            ])
            ->withCount(['postnatalEncounters', 'neonatalEncounters'])
            ->when($this->search, function ($q) {
                $search = trim($this->search);
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('patient', function ($pQuery) use ($search) {
                        $pQuery
                            ->where('full_name', 'like', "%{$search}%")
                            ->orWhere('nik', 'like', "%{$search}%")
                            ->orWhere('medical_record_number', 'like', "%{$search}%")
                            ->orWhere('family_name', 'like', "%{$search}%");
                    })->orWhereHas('creator', function ($cQuery) use ($search) {
                        $cQuery->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->when($this->filter_desa && $this->filter_desa !== 'semua', function ($q) {
                $q->whereHas('patient', fn($sub) => $sub->where('village_code', $this->filter_desa));
            })
            ->when($this->filter_status && $this->filter_status !== 'semua', function ($q) {
                $q->where('status', $this->filter_status);
            })
            ->when($this->filter_risiko && $this->filter_risiko !== 'semua', function ($q) {
                $risiko = strtolower($this->filter_risiko);
                if (in_array($risiko, ['krr', 'rendah'])) {
                    $q->where(function ($sub) {
                        $sub->whereDoesntHave('latestEncounter')->orWhereHas('latestEncounter', fn($e) => $e->where('skor_puji_rochyati', '<=', 5));
                    });
                } elseif (in_array($risiko, ['krt', 'tinggi'])) {
                    $q->whereHas('latestEncounter', fn($e) => $e->whereBetween('skor_puji_rochyati', [6, 10]));
                } elseif (in_array($risiko, ['krst', 'sangat_tinggi'])) {
                    $q->whereHas('latestEncounter', fn($e) => $e->where('skor_puji_rochyati', '>', 10));
                }
            })
            ->when($this->filter_tanggal_kontrol, function ($q) {
                $date = $this->filter_tanggal_kontrol;
                $jenis = $this->filter_jenis_kontrol;

                $q->whereHas('encounters', function ($eQuery) use ($date, $jenis) {
                    if ($jenis === 'selanjutnya') {
                        $eQuery->whereDate('next_encounter_date', $date);
                    } elseif ($jenis === 'periksa') {
                        $eQuery->whereDate('encounter_date', $date);
                    } else {
                        $eQuery->where(function ($sub) use ($date) {
                            $sub->whereDate('next_encounter_date', $date)->orWhereDate('encounter_date', $date);
                        });
                    }
                });
            })
            ->when($this->filter_hpl_min_3, function ($q) {
                $maxHpl = today()->copy()->addDays(3)->format('Y-m-d');
                $minHpl = today()->copy()->subDays(7)->format('Y-m-d');
                $q->whereNotNull('hpl')->whereBetween('hpl', [$minHpl, $maxHpl]);
            })
            ->when($this->filter_status === 'aktif' && $this->filter_trimester && $this->filter_trimester !== 'semua', function ($q) {
                $today = today();
                if ($this->filter_trimester === '1') {
                    $minHpht = $today->copy()->subDays(83)->format('Y-m-d');
                    $maxHpht = $today->copy()->format('Y-m-d');
                    $q->whereNotNull('hpht')->whereBetween('hpht', [$minHpht, $maxHpht]);
                } elseif ($this->filter_trimester === '2') {
                    $minHpht = $today->copy()->subDays(174)->format('Y-m-d');
                    $maxHpht = $today->copy()->subDays(84)->format('Y-m-d');
                    $q->whereNotNull('hpht')->whereBetween('hpht', [$minHpht, $maxHpht]);
                } elseif ($this->filter_trimester === '3') {
                    $maxHpht = $today->copy()->subDays(175)->format('Y-m-d');
                    $q->whereNotNull('hpht')->where('hpht', '<=', $maxHpht);
                }
            })
            ->orderByRaw("CASE WHEN status = 'aktif' THEN 0 ELSE 1 END")
            ->orderBy('hpl', 'asc')
            ->paginate(12);
    }

    /**
     * Daftar desa untuk filter pencarian.
     *
     * @return array<int, array<string, string>>
     */
    #[Computed]
    public function villagesList(): array
    {
        $options = [['label' => 'Semua Desa', 'value' => 'semua']];

        $villages = IndonesiaRegion::where('parent', 352911)
            ->orWhereIn('code', Patient::whereNotNull('village_code')->select('village_code'))
            ->orderBy('name', 'asc')
            ->get(['code', 'name']);

        foreach ($villages as $v) {
            $options[] = [
                'label' => 'Desa ' . $v->name,
                'value' => (string) $v->code,
            ];
        }

        return $options;
    }

    /**
     * Memeriksa apakah user saat ini berhak mengedit atau menghapus kantong kehamilan (HPHT, GPA, Status, Hapus).
     * Menggunakan method canEdit() pada model Pregnancy (Koordinator Bidan atau Bidan Pengampu).
     */
    public function canEditPregnancy(Pregnancy $pregnancy): bool
    {
        return $pregnancy->canEdit();
    }

    /**
     * Membuka dialog konfirmasi penghapusan kantong kehamilan dengan menyebutkan bidan pengampu.
     */
    public function promptDeletePregnancy(int $pregnancyId): void
    {
        try {
            $pregnancy = Pregnancy::with(['patient', 'creator'])->findOrFail($pregnancyId);

            if (!$pregnancy->canEdit()) {
                $this->toast()->error('Hanya Koordinator Bidan atau Bidan Pengampu yang berhak menghapus kantong kehamilan.')->send();
                return;
            }

            $patientName = $pregnancy->patient?->full_name ?? 'Pasien';
            $midwifeName = $pregnancy->creator?->name ?? 'Belum Ditentukan';

            $this->dialog()
                ->warning(
                    'Konfirmasi Hapus Kantong Kehamilan',
                    "Kantong kehamilan milik Ibu {$patientName} ini diampu oleh Bidan {$midwifeName}. Apakah Anda yakin ingin menghapus kantong ini beserta seluruh riwayat pemeriksaannya?"
                )
                ->confirm('Ya, Hapus Kantong', 'confirmDeletePregnancy', $pregnancy->id)
                ->cancel('Batal')
                ->send();
        } catch (\Throwable $e) {
            Log::error('Error in promptDeletePregnancy: ' . $e->getMessage(), ['id' => $pregnancyId]);
            $this->toast()->error('Data kantong kehamilan tidak ditemukan.')->send();
        }
    }

    /**
     * Eksekusi penghapusan kantong kehamilan beserta relasi periksa di dalamnya.
     */
    public function confirmDeletePregnancy(int $pregnancyId): void
    {
        try {
            $pregnancy = Pregnancy::with(['patient', 'creator'])->findOrFail($pregnancyId);

            if (!$pregnancy->canEdit()) {
                $this->toast()->error('Anda tidak memiliki hak akses untuk menghapus kantong kehamilan ini.')->send();
                return;
            }

            $patientName = $pregnancy->patient?->full_name ?? 'Pasien';

            DB::transaction(function () use ($pregnancy) {
                $pregnancy->postnatalEncounters()->delete();
                $pregnancy->neonatalEncounters()->delete();
                $pregnancy->encounters()->delete();
                $pregnancy->delete();
            });

            $this->resetPage();
            $this->dispatch('pregnancy-updated');
            $this->toast()->success("Kantong kehamilan milik {$patientName} berhasil dihapus.")->send();
        } catch (\Throwable $e) {
            Log::error('Error in confirmDeletePregnancy: ' . $e->getMessage(), ['id' => $pregnancyId]);
            $this->toast()->error('Terjadi kesalahan saat menghapus kantong kehamilan: ' . $e->getMessage())->send();
        }
    }

    /**
     * Membuka dialog konfirmasi pembatalan persalinan.
     */
    public function promptBatalkanPersalinan(int $pregnancyId): void
    {
        try {
            $pregnancy = Pregnancy::with(['patient', 'postnatalEncounters'])->findOrFail($pregnancyId);

            if (!$this->canEditPregnancy($pregnancy)) {
                $this->toast()->error('Anda tidak memiliki hak akses untuk membatalkan persalinan ini.')->send();
                return;
            }

            if ($pregnancy->postnatalEncounters()->exists()) {
                $this->toast()->error('Persalinan tidak dapat dibatalkan karena sudah terdapat data kunjungan nifas (postnatal). Hapus seluruh data kunjungan nifas terlebih dahulu jika ingin membatalkan.')->send();
                return;
            }

            $patientName = $pregnancy->patient?->full_name ?? 'Pasien';

            $this->dialog()
                ->warning(
                    'Konfirmasi Batalkan Persalinan',
                    "Apakah Anda yakin ingin membatalkan status persalinan Ibu {$patientName}? Status kehamilan akan kembali menjadi AKTIF dan data persalinan terkait akan dihapus."
                )
                ->confirm('Ya, Batalkan Persalinan', 'confirmBatalkanPersalinan', $pregnancy->id)
                ->cancel('Batal')
                ->send();
        } catch (\Throwable $e) {
            Log::error('Error in promptBatalkanPersalinan: ' . $e->getMessage(), ['id' => $pregnancyId]);
            $this->toast()->error('Data kantong kehamilan tidak ditemukan.')->send();
        }
    }

    /**
     * Eksekusi pembatalan status persalinan.
     */
    public function confirmBatalkanPersalinan(int $pregnancyId): void
    {
        try {
            $pregnancy = Pregnancy::with(['patient', 'postnatalEncounters'])->findOrFail($pregnancyId);

            if (!$this->canEditPregnancy($pregnancy)) {
                $this->toast()->error('Anda tidak memiliki hak akses untuk membatalkan persalinan ini.')->send();
                return;
            }

            if ($pregnancy->postnatalEncounters()->exists()) {
                $this->toast()->error('Persalinan tidak dapat dibatalkan karena sudah ada kunjungan nifas tercatat.')->send();
                return;
            }

            $patientName = $pregnancy->patient?->full_name ?? 'Pasien';

            DB::transaction(function () use ($pregnancy) {
                // Hapus riwayat persalinan terkait
                PregnancyHistory::where('pregnancy_id', $pregnancy->id)->delete();

                // Kembalikan status pasien jika sebelumnya meninggal
                if ($pregnancy->status === 'meninggal' && $pregnancy->patient) {
                    $pregnancy->patient->update(['is_active' => true]);
                }

                // Kembalikan status kehamilan menjadi aktif
                $pregnancy->update(['status' => 'aktif']);
            });

            $this->resetPage();
            $this->dispatch('pregnancy-updated');
            $this->toast()->success("Status persalinan Ibu {$patientName} berhasil dibatalkan. Kehamilan kembali aktif.")->send();
        } catch (\Throwable $e) {
            Log::error('Error in confirmBatalkanPersalinan: ' . $e->getMessage(), ['id' => $pregnancyId]);
            $this->toast()->error('Terjadi kesalahan saat membatalkan persalinan: ' . $e->getMessage())->send();
        }
    }

    /**
     * Listener saat data kehamilan di tab atau modal diperbarui atau dihapus.
     */
    #[On('pregnancy-updated')]
    public function onPregnancyUpdated(): void
    {
        $this->resetPage();
    }
};
