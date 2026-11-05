<?php

use App\Models\Patient;
use App\Models\IndonesiaRegion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use Interactions;

    // Identitas Utama
    public $medical_record_number;
    public $nik;
    public $ihs_number;
    public $passport_kitas;

    // Data Personal
    public $full_name;
    public $family_name;
    public $birth_date;
    public $age;
    public $birth_place;
    public $gender = 2;

    // Kontak
    public $phone;
    public $mobile_phone;
    public $email;

    // Alamat
    public $address;
    public $province_code = 35;
    public $regency_code = 3529;
    public $district_code = 352911;
    public $village_code;
    public $postal_code;

    // Data Kependudukan
    public $nationality = 'WNI';
    public $religion;
    public $education;
    public $occupation;
    public $marital_status = 'Kawin';

    // Cara Pembayaran
    public $insurance_number;
    public $insurance_name;

    // Penanggung Jawab
    public $emergency_contact_name;
    public $emergency_contact_relation;
    public $emergency_contact_phone;

    public $is_active = true;

    // Options
    public $provinces = [];
    public $regencies = [];
    public $districts = [];
    public $villages = [];

    public function mount()
    {
        $this->medical_record_number = Patient::generateMedicalRecordNumber();

        $this->nik = request()->nik;
        $this->full_name = request()->full_name;
        $this->age = request()->age;
        $this->family_name = request()->family_name;
        $this->mobile_phone = request()->mobile_phone;

        // Otomatis hitung birth_date dari umur jika tersedia
        if ($this->age && !$this->birth_date) {
            $this->birth_date = now()
                ->subYears((int) $this->age)
                ->startOfYear()
                ->format('Y-m-d');
        }

        $this->provinces = IndonesiaRegion::where('type', 'provinsi')->get()->map(fn($item) => ['label' => $item->name, 'value' => $item->code])->toArray();
        $this->regencies = IndonesiaRegion::where('parent', $this->province_code)->orderBy('name', 'ASC')->get()->map(fn($item) => ['label' => $item->name, 'value' => $item->code])->toArray();
        $this->districts = IndonesiaRegion::where('parent', $this->regency_code)->orderBy('name', 'ASC')->get()->map(fn($item) => ['label' => $item->name, 'value' => $item->code])->toArray();
        $this->villages = IndonesiaRegion::where('parent', $this->district_code)->orderBy('name', 'ASC')->get()->map(fn($item) => ['label' => $item->name, 'value' => $item->code])->toArray();
    }

    public function updatedProvinceCode($value)
    {
        $this->regency_code = null;
        $this->district_code = null;
        $this->village_code = null;
        $this->regencies = $value
            ? IndonesiaRegion::where('parent', $value)->orderBy('name', 'ASC')->get()->map(fn($item) => ['label' => $item->name, 'value' => $item->code])->toArray()
            : [];
        $this->districts = [];
        $this->villages = [];
    }

    public function updatedRegencyCode($value)
    {
        $this->district_code = null;
        $this->village_code = null;
        $this->districts = $value
            ? IndonesiaRegion::where('parent', $value)->orderBy('name', 'ASC')->get()->map(fn($item) => ['label' => $item->name, 'value' => $item->code])->toArray()
            : [];
        $this->villages = [];
    }

    public function updatedDistrictCode($value)
    {
        $this->village_code = null;
        $this->villages = $value
            ? IndonesiaRegion::where('parent', $value)->orderBy('name', 'ASC')->get()->map(fn($item) => ['label' => $item->name, 'value' => $item->code])->toArray()
            : [];
    }

    public function store()
    {
        $this->validate([
            'medical_record_number' => 'required|min:1|unique:patients,medical_record_number',
            'full_name' => 'required|string|max:100',
            'birth_date' => 'nullable|date',
            'village_code' => 'required',
            'gender' => 'required',
            'nik' => 'nullable|string|size:16|unique:patients,nik',
            'mobile_phone' => 'nullable|string|max:20',
        ], [
            'medical_record_number.required' => 'No. Rekam Medis wajib diisi.',
            'medical_record_number.min' => 'No. Rekam Medis minimal bernilai 1.',
            'medical_record_number.unique' => 'No. Rekam Medis ini sudah digunakan oleh pasien lain.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'village_code.required' => 'Desa wajib dipilih.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'nik.size' => 'NIK harus berjumlah 16 digit.',
            'nik.unique' => 'NIK sudah terdaftar.',
        ]);

        $user = Patient::create([
            'uuid' => Str::uuid(),
            'medical_record_number' => (int) $this->medical_record_number,
            'nik' => $this->nik,
            'ihs_number' => $this->ihs_number,
            'passport_kitas' => $this->passport_kitas,
            'full_name' => $this->full_name,
            'family_name' => $this->family_name,
            'birth_date' => $this->birth_date,
            'birth_place' => $this->birth_place,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'mobile_phone' => $this->mobile_phone,
            'email' => $this->email,
            'address' => $this->address,
            'province_code' => $this->province_code,
            'regency_code' => $this->regency_code,
            'district_code' => $this->district_code,
            'village_code' => $this->village_code,
            'postal_code' => $this->postal_code,
            'nationality' => $this->nationality,
            'religion' => $this->religion,
            'education' => $this->education,
            'occupation' => $this->occupation,
            'marital_status' => $this->marital_status,
            'insurance_number' => $this->insurance_number,
            'insurance_name' => $this->insurance_name,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_relation' => $this->emergency_contact_relation,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'is_active' => $this->is_active,
            'created_by' => Auth::id(),
        ]);

        $this->toast()->success('Pasien berhasil ditambahkan')->send();

        return to_route('patient.index');
    }
};
