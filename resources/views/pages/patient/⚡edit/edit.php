<?php

use App\Models\Patient;
use App\Models\IndonesiaRegion;
use Illuminate\Http\Request;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use Interactions;

    public $patient;

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
    public $gender;

    // Kontak
    public $phone;
    public $mobile_phone;
    public $email;

    // Alamat
    public $address;
    public $province_code;
    public $regency_code;
    public $district_code;
    public $village_code;
    public $postal_code;

    // Data Kependudukan
    public $nationality;
    public $religion;
    public $education;
    public $occupation;
    public $marital_status;

    // Cara Pembayaran
    public $insurance_number;
    public $insurance_name;

    // Penanggung Jawab
    public $emergency_contact_name;
    public $emergency_contact_relation;
    public $emergency_contact_phone;

    public $is_active;

    // Options
    public $provinces = [];
    public $regencies = [];
    public $districts = [];
    public $villages = [];

    // dari encounter
    public $enc_id;
    public $enc_uuid;



    public function mount(Patient $patient, String $uuid, Request $request)
    {
        if ($uuid != $patient->uuid) {
            return to_route('patient.index');
        }

        if ($redirect_to = request()->redirect_to) {
            $split = explode('.', $redirect_to);
            if (count($split) === 2) {
                $this->enc_id = $split[0];
                $this->enc_uuid = $split[1];
            }
        }

        $this->patient = $patient;

        $this->medical_record_number = $patient->medical_record_number;
        $this->nik = $patient->nik;
        $this->ihs_number = $patient->ihs_number;
        $this->passport_kitas = $patient->passport_kitas;
        $this->full_name = $patient->full_name;
        $this->family_name = $patient->family_name;
        $this->birth_date = $patient->birth_date?->format('Y-m-d');
        $this->age = $patient->birth_date?->age;
        $this->birth_place = $patient->birth_place;
        $this->gender = $patient->gender?->value;
        $this->phone = $patient->phone;
        $this->mobile_phone = $patient->mobile_phone;
        $this->email = $patient->email;
        $this->address = $patient->address;
        $this->province_code = $patient->province_code;
        $this->regency_code = $patient->regency_code;
        $this->district_code = $patient->district_code;
        $this->village_code = $patient->village_code;
        $this->postal_code = $patient->postal_code;
        $this->nationality = $patient->nationality?->value ?? 'WNI';
        $this->religion = $patient->religion?->value;
        $this->education = $patient->education?->value;
        $this->occupation = $patient->occupation;
        $this->marital_status = $patient->marital_status?->value;
        $this->insurance_number = $patient->insurance_number;
        $this->insurance_name = $patient->insurance_name;
        $this->emergency_contact_name = $patient->emergency_contact_name;
        $this->emergency_contact_relation = $patient->emergency_contact_relation;
        $this->emergency_contact_phone = $patient->emergency_contact_phone;
        $this->is_active = $patient->is_active;

        $this->provinces = IndonesiaRegion::where('type', 'provinsi')->get()->map(fn($item) => ['label' => $item->name, 'value' => $item->code])->toArray();

        if ($this->province_code) {
            $this->regencies = IndonesiaRegion::where('parent', $this->province_code)->orderBy('name', 'ASC')->get()->map(fn($item) => ['label' => $item->name, 'value' => $item->code])->toArray();
        }
        if ($this->regency_code) {
            $this->districts = IndonesiaRegion::where('parent', $this->regency_code)->orderBy('name', 'ASC')->get()->map(fn($item) => ['label' => $item->name, 'value' => $item->code])->toArray();
        }
        if ($this->district_code) {
            $this->villages = IndonesiaRegion::where('parent', $this->district_code)->orderBy('name', 'ASC')->get()->map(fn($item) => ['label' => $item->name, 'value' => $item->code])->toArray();
        }
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

    public function update()
    {
        $this->validate([
            'medical_record_number' => 'required|min:1|unique:patients,medical_record_number,' . $this->patient->id,
            'full_name' => 'required|string|max:100',
            'birth_date' => 'nullable|date',
            'village_code' => 'required',
            'gender' => 'required',
            'nik' => 'nullable|string|size:16|unique:patients,nik,' . $this->patient->id,
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

        $this->patient->update([
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
        ]);

        $this->toast()->success('Pasien berhasil diupdate')->flash()->send();

        return to_route('patient.index');
    }
};
