# Pedoman Pengembangan Sistem (Laravel 13 & Livewire 4)

Instruksi ini bersifat **wajib dan mengikat** untuk setiap tugas, prompt, maupun interaksi pengembangan di dalam proyek ini.

---

# BAGIAN I: ATURAN CODING & ARSITEKTUR

## 1. Stack & Spesifikasi Teknis

* **Framework & Versi**: Wajib menggunakan **Laravel 13**, **Livewire 4**, dan **PHP 8.3+** (mengikuti standar **PSR-12**).
* **Asset Bundler**: Menggunakan **Vite 7**.
* **Icon Package**: Wajib menggunakan **`secondnetwork/blade-tabler-icons`** dengan format tag `<x-ti-[nama-icon] class="size-..." />` (contoh: `<x-ti-plus class="size-4" />`, `<x-ti-edit class="size-4" />`, `<x-ti-trash class="size-4" />`).
* **Pengujian Otomatis**: **TIDAK PERLU** membuat file Test/Unit/Feature Test kecuali diminta secara eksplisit.
* **Standar Bahasa**:
  - Seluruh teks yang tampil kepada pengguna (label, placeholder, pesan validasi, pesan error, modal, notifikasi) wajib menggunakan **Bahasa Indonesia**.
  - Seluruh penamaan teknis (class, method, property, variable, database field, route name) wajib menggunakan **Bahasa Inggris**.

---

## 2. Standar Penulisan Kode PHP

* **Strict Typing & Return Types**: Wajib menyertakan type hint pada setiap parameter dan return type declaration pada seluruh method.
* **Dokumentasi Method (DocBlocks)**: Setiap method wajib memiliki deskripsi ringkas dengan DocBlock.
  ```php
  /**
   * Menyimpan data baru ke dalam database.
   */
  public function save(): void
  {
      // ...
  }
  ```
* **Fitur Modern PHP**: Manfaatkan Constructor Property Promotion, Readonly Property, Match Expression, dan Enum jika relevan.
* **Kewajiban Penggunaan Enum (All Choices Must Be Enums)**: Seluruh opsi pilihan statis, select dropdown, status, dan kategori (seperti komplikasi kehamilan, komplikasi persalinan, komplikasi nifas, cara melahirkan, fasilitas persalinan, kondisi bayi, jenis kelamin, dll.) **WAJIB dibuat dan dikelola sebagai PHP Backed Enum di dalam namespace `App\Enums`**. Dilarang keras menuliskan hardcoded array pilihan langsung di dalam template Blade atau komponen Livewire. Setiap Enum wajib menyediakan method `choices()` untuk select TallStackUI dan method pendukung seperti `label()` / `values()`.
* **Prinsip Clean Code & DRY**: Hindari duplikasi logika, jaga kode tetap ringkas, bersih, dan mudah dirawat.

---

## 3. Database & Model

* **Tabel & Model Eksisting**: Semua tabel database sudah memiliki **Model** dan konfigurasi **$fillable** yang lengkap.
* **Aturan Migrasi**: **DILARANG membuat file migration baru**, kecuali ada instruksi eksplisit dari pengguna.
* **Pencegahan N+1 Query**: Wajib menggunakan **Eager Loading** (`with([...])`) saat mengambil relasi data.
* **Database Transactions**: Gunakan `DB::transaction(...)` untuk alur proses yang melibatkan manipulasi lebih dari satu tabel atau operasi database berantai.
* **Pemisahan Business Logic**: Tempatkan business logic yang reusable atau kompleks pada **Model** terkait atau **Service Class**, bukan menumpuk seluruh logika di dalam komponen Livewire.

---

## 4. Arsitektur Livewire 4 & Konvensi Modul

Struktur antarmuka halaman menggunakan arsitektur **Livewire 4 Multi-File Component (MFC)** dengan folder berformat `⚡[action]` (contoh: `⚡index`, `⚡create`, `⚡edit`).

### A. Perintah Pembuatan Halaman (Full CRUD Module)
Perintah *"Buatkan halaman [Nama]"* berarti membuat modul CRUD lengkap dengan Livewire:
1. Generate komponen menggunakan perintah:
   ```bash
   php artisan make:livewire pages::[Nama] --mfc
   ```
2. Pastikan file tersimpan pada:
   ```text
   resources/views/pages/[nama-kebab-case]/
   ```
3. Implementasikan fitur standar:
   - Data Table (menggunakan `<x-table>` dan `<x-table.thead>`)
   - Formulir Tambah Data (Create)
   - Formulir Ubah Data (Edit)
   - Aksi Hapus Data dengan Konfirmasi (Delete)
   - Validasi Form otomatis
   - Paginasi Data
   - Pencarian & Filter Kolom

### B. Perintah Pembuatan Komponen Biasa
Perintah *"Buatkan komponen [Nama]"* berarti membuat komponen Livewire mandiri:
1. Generate komponen menggunakan perintah:
   ```bash
   php artisan make:livewire [Nama]
   ```
2. Pastikan file view berada di:
   ```text
   resources/views/components/[nama].blade.php
   ```

### C. Blueprint & Modul Referensi
Gunakan **Modul User** (`resources/views/pages/user/`) sebagai standar acuan utama untuk:
- Struktur folder dan penamaan file
- Penggunaan trait antarmuka (misal: `WithTableX`, `Interactions`)
- Pola query data table berbasis `#[Computed]`
- Penanganan modal form dan dialog konfirmasi
- Mekanisme notifikasi Toast (`$this->toast()->success(...)`)

> Jika terdapat ambiguitas teknis, selalu ikuti pola yang telah terbukti berfungsi di Modul User.

---

# BAGIAN II: STANDAR STYLE, UI & DESAIN

## 1. Stack Tampilan & Styling Murni

* Styling antarmuka dibangun **murni menggunakan TailwindCSS v4** dan komponen resmi **TallStackUI**.
* Tidak menggunakan library atau framework styling pihak ketiga lainnya.

---

## 2. Filosofi Desain: Clean, Refined, Cute & Elegan (Bank BINI Edition)

* **Karakter Visual Khusus Bank BINI**: Tampilan wajib **cute, hangat, ramah, dan menarik** karena aplikasi digunakan oleh istri untuk mengelola kantong keuangan keluarga (inspirasi *Pocket* Bank Jago), namun tetap rapi, profesional, dan memenuhi standar kontras keterbacaan WCAG AA.
* Gunakan sentuhan warna pastel lembut yang manis (`rose`, `pink`, `emerald`, `amber`, `sky`, `violet`), sudut membulat yang bersahabat (`rounded-2xl` untuk kartu kantong, `rounded-xl` untuk tombol), serta sapaan/mikrokopi Bahasa Indonesia yang hangat dan manusiawi.

### 🚨 Larangan Desain "AI Slop":
* **Dilarang** menggunakan gradien pelangi atau kombinasi warna mencolok (`from-purple-... to-pink-...`, background neon, dsb.).
* **Dilarang** menggunakan drop shadow berlebihan atau glowing effect tebal (`shadow-2xl shadow-emerald-500/50`).
* **Dilarang** menggunakan border tebal warna-warni tanpa fungsi semantik yang jelas.
* **Dilarang** nesting card berulang tanpa faedah (card di dalam card di dalam card) yang membuat tampilan kembung (*bloated*).
* **Dilarang** menaruh ikon dekoratif secara acak di setiap sudut tanpa fungsi komunikasi data.
* **Dilarang** membuat elemen melayang (*floating widgets*) atau animasi berlebihan yang mengganggu kenyamanan kerja pengguna.

### ✨ Standar Tampilan Bersih & Berkelas:
* **Subtle Borders & Soft Shadows**: Gunakan garis batas yang halus (`border border-slate-200/80` atau `border-zinc-200`) serta bayangan lembut (`shadow-xs` atau `shadow-sm`).
* **Whitespace Proporsional**: Berikan jarak bernapas yang cukup antar elemen (`p-5`, `p-6`, `gap-4`, `space-y-4`). Tata letak harus rapi dan tidak sesak.
* **Pengelompokan Form Logis**: Susun formulir ke dalam kelompok section yang jelas dan berbatas rapi sesuai konteks datanya.

---

## 3. Standar Tipografi & Skala Teks

### 🚨 Panduan Skala Teks Minimalis:
* **DILARANG menggunakan arbitrary value `text-[...]`** (contoh: `text-[10px]`, `text-[11px]`, `text-[13px]`, dsb.) — selalu gunakan skala resmi Tailwind.
* **Hindari teks raksasa berlebihan**: Teks harus proporsional, rapi, dan tidak memakan ruang (*compact & clean*).

### Panduan Skala Teks:
* **Judul Halaman / Page Title**: `text-lg font-bold text-slate-900 sm:text-xl`.
* **Judul Seksi / Card Header**: `text-sm sm:text-base font-semibold text-slate-900`.
* **Nilai Metrik Utama**: `text-2xl font-bold text-slate-900` (atau `text-emerald-700`, `text-rose-600` sesuai semantik).
* **Data / Nilai Standar / Input Text**: `text-sm text-slate-800`.
* **Label, Header Kolom Tabel, Badge, & Keterangan**: `text-xs font-medium text-slate-500` atau `text-xs font-semibold uppercase tracking-wider text-slate-500`.

---

## 4. Palet Warna & Status Semantik

* **Warna Utama (Brand & Aksi)**: Menggunakan tema **Emerald** (`emerald-600`, `emerald-700`, `emerald-50`, ring fokus `focus:ring-emerald-500`) untuk navigasi aktif, tombol aksi utama, dan identitas visual aplikasi.
* **Warna Semantik Status**:
  - **Sukses / Aktif / Normal**: Emerald / Green (`bg-emerald-50 text-emerald-700 border-emerald-200`)
  - **Bahaya / Kritis / Non-Aktif**: Rose / Red (`bg-rose-50 text-rose-700 border-rose-200`)
  - **Peringatan / Perhatian**: Amber (`bg-amber-50 text-amber-700 border-amber-200`)
  - **Informasi / Netral**: Sky / Slate (`bg-sky-50 text-sky-700 border-sky-200` / `bg-slate-50 text-slate-600 border-slate-200`)

---

## 5. Standar Komponen TallStackUI

### 🚨 Kewajiban Form Controls TallStackUI:
**WAJIB menggunakan komponen form bawaan TallStackUI.** Dilarang membuat tag form HTML native manual (seperti `<input>`, `<textarea>`, `<select>` mentah) saat TallStackUI menyediakan komponennya:

* **Teks & Input Standar**: `<x-input />`
* **Kata Sandi**: `<x-password />` (fitur show/hide terintegrasi)
* **Teks Panjang / Catatan**: `<x-textarea />`
* **Angka & Kuantitas**: `<x-number />`
* **Nilai Finansial / Mata Uang**: `<x-currency />`
* **Pemilih Tanggal**: `<x-date />`
* **Pemilih Waktu**: `<x-time />`
* **Select Dropdown Modern**: `<x-select.styled />` (searchable, multi-select, model/API request)
* **Select Native Cepat**: `<x-select.native />`
* **Kombinasi Input & Select**: `<x-input-select />`
* **Autocomplete**: `<x-autocomplete />`
* **Kotak Centang (Checkbox)**: `<x-checkbox />` & `<x-checkbox.group />`
* **Pilihan Radio**: `<x-radio />` & `<x-radio.group />`
* **Sakelar Toggle**: `<x-toggle />`
* **Tag / Multi-Label**: `<x-tag />`
* **Upload Berkas**: `<x-upload />`
* **PIN / Kode Verifikasi**: `<x-pin />`
* **Slider Rentang Nilai**: `<x-range />`
* **Badge**: `<x-badge />`

> **Ketentuan Form Controls**:
> - Jangan gunakan prefix `ts-` (gunakan `<x-input>`, bukan `<x-ts-input>`).
> - Maksimalkan props bawaan (`label="..."`, `hint="..."`) dan integrasi error binding Livewire (`wire:model="..."`).
> - Dilarang mencampuradukkan input HTML biasa dengan form TallStackUI dalam satu tampilan.
> - Seluruh opsi pilihan dropdown / radio (`<x-select.styled />`, `<x-select.native />`, dll.) wajib bersumber dari Enum (`:options="\App\Enums\[Nama]Enum::choices()"`), dilarang menggunakan hardcoded array opsi di Blade.

---

## 6. Standar Card & Kontainer (`<x-card>`)

* **DILARANG membuat card manual** menggunakan `<div>` kustom (`<div class="rounded-... border ... bg-white ...">`). Selalu gunakan `<x-card>`.
* **Prop Pembantu**: Boleh menggunakan `paddingless` (misal saat membungkus tabel) dan `shadowless` untuk tampilan flat.
* **Desain Minimalis pada Card & Widget**:
  - **Card Konten Standar**: Gunakan latar netral standar (`bg-white border border-slate-200/80 shadow-xs`).
  - **Widget Statistik / Ringkasan / Metrik**: Menggunakan warna-warna soft `xxx-300` yang ramah di mata (contoh: `bg-emerald-300 border-emerald-400`, `bg-amber-300 border-amber-400`, `bg-sky-300 border-sky-400`, `bg-rose-300 border-rose-400`) dengan teks kontras tinggi (`text-...-950` dan aksen `text-...-900`), atau latar putih bersih beraksen lembut. Hindari warna neon tajam.
* **Sidebar Layout**: Menggunakan latar **panel slate netral** (`bg-slate-100 border-r border-slate-200`) untuk memberikan batas dan pembeda visual yang tegas dari area konten (`bg-slate-50`). Item navigasi bersih (`text-slate-600 hover:text-slate-900 hover:bg-slate-200/70`) dengan active state kartu putih elegan (`bg-white text-emerald-800 font-semibold border border-slate-200 shadow-xs`) beraksen indikator emerald. Dilarang menggunakan sidebar hitam pekat.

---


## 7. Standar Button & Tombol Aksi

1. **Form Submit**: Selalu gunakan prop `submit` (bukan `type="submit"`):
   ```blade
   <x-button submit color="emerald" loading="save">Simpan</x-button>
   ```
2. **Tombol Ikon / Aksi Tabel**: Gunakan `<x-button.circle>`:
   ```blade
   <x-button.circle color="emerald" sm href="{{ route('...') }}" title="Ubah">
       <x-ti-edit class="size-4" />
   </x-button.circle>
   <x-button.circle color="red" flat sm wire:click="delete({{ $id }})" title="Hapus">
       <x-ti-trash class="size-4" />
   </x-button.circle>
   ```

---

## 8. Breadcrumbs & Navigasi

Gunakan navigasi flex TailwindCSS yang bersih dan konsisten:
```blade
<nav class="flex items-center gap-1.5 text-sm text-slate-500 mt-1">
    <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition-colors">
        <x-ti-home class="size-4" />
    </a>
    <span>/</span>
    <span>Menu</span>
    <span>/</span>
    <span class="text-slate-700 font-medium">Halaman Aktif</span>
</nav>
```

---

## 9. Referensi Vendor TallStackUI (The Golden Rule)

**DILARANG mereka-reka (invent) nama prop, slot, atau event!**
Seluruh dokumentasi resmi TallStackUI sudah terpasang lengkap di dalam folder `vendor`. **Sebelum menulis atau mengubah komponen TallStackUI, WAJIB membaca file dokumentasi terkait di direktori vendor:**

1. **Index Utama & Panduan Dasar**:
   [`vendor/tallstackui/tallstackui/.ai/index.md`](file:///f:/bul/kantong-kehamilan/vendor/tallstackui/tallstackui/.ai/index.md)
2. **Koleksi Komponen Detail**:
   - Form Controls: [`vendor/tallstackui/tallstackui/.ai/components/form/`](file:///f:/bul/kantong-kehamilan/vendor/tallstackui/tallstackui/.ai/components/form/)
   - Layout & Display: [`vendor/tallstackui/tallstackui/.ai/components/`](file:///f:/bul/kantong-kehamilan/vendor/tallstackui/tallstackui/.ai/components/)
3. **Soft Customization & Internal Scopes**:
   [`vendor/tallstackui/tallstackui/.ai/soft-customization-internal-scopes.md`](file:///f:/bul/kantong-kehamilan/vendor/tallstackui/tallstackui/.ai/soft-customization-internal-scopes.md)
4. **Skill Reference**:
   [`vendor/tallstackui/tallstackui/resources/boost/skills/tallstackui-development/SKILL.md`](file:///f:/bul/kantong-kehamilan/vendor/tallstackui/tallstackui/resources/boost/skills/tallstackui-development/SKILL.md)


Setiap perubahan alur harus mengupdate file PRD yang ada

<!-- antislop:start -->
## antislop
For UI, copy, people, mobile layout, or code comments work, read these installed skill files directly (use these paths even if a same-named global skill exists):
- Core filter, always on: `antislop`: `.agents/skills/antislop/SKILL.md`
- UI / visual: `antislop-ui`: `.agents/skills/antislop-ui/SKILL.md`
- Copy & text: `antislop-copywriting`: `.agents/skills/antislop-copywriting/SKILL.md`
- People: `antislop-human`: `.agents/skills/antislop-human/SKILL.md`
- Mobile / responsive: `antislop-layoutmobile`: `.agents/skills/antislop-layoutmobile/SKILL.md`
- Code comments: `antislop-code`: `.agents/skills/antislop-code/SKILL.md`
Before starting, follow the core's "Two Usage Modes" section in strict order: explicit session instruction first, then global preference, then ask. A session instruction always wins. For a resolved mode, say `antislop active: <mode> (session override).` or `antislop active: <mode> (global preference).` once before presenting findings or making edits, using the actual mode and source. Acknowledging the user's request without naming the source does not replace this notice.
Only an explicit choice of antislop during or after selects a session mode. A request to review, audit, or avoid file edits does not select a mode; read the global preference in that case. Another skill's mode does not select antislop's mode.
If the mode is unresolved, ask during/after and end the response; wait for the answer before any UI review, planning, or concept. For read-only tasks, put the active-mode notice only at the start of the final answer, never in progress messages. For editing tasks, announce before the first edit and omit it from the final answer.
To update antislop later: `npx antislop-ai --update`, or run `npx antislop-ai` and pick Overwrite them.
<!-- antislop:end -->

<!-- karpathy-guidelines:start -->
## Karpathy Guidelines (Coding & Engineering Guardrails)
For code implementation, refactoring, and bug fixes, reference `.agents/skills/karpathy-guidelines/SKILL.md`:
1. **Think Before Coding**: Explicit assumptions, surface tradeoffs, ask when unclear.
2. **Simplicity First**: Minimum code that solves the problem. Nothing speculative or overengineered.
3. **Surgical Changes**: Touch only what you must. No unprompted refactoring of adjacent code.
4. **Goal-Driven Execution**: Define success criteria and verify with tests/checks.
<!-- karpathy-guidelines:end -->
