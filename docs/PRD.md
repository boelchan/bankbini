# Product Requirements Document (PRD): Bank BINI (Simplified Edition)

## 1. Ringkasan Aplikasi
**Bank BINI** adalah aplikasi manajemen kantong keuangan keluarga (*Pocket*) yang simpel, hangat, dan ramah digunakan bersama oleh **Suami dan Istri**.
- **Kantong Bersama (Shared Household Pockets)**: Seluruh kantong (`wallets`), riwayat transaksi (`transactions`), pemindahan uang (`transfers`), dan kategori transaksi diakses secara bersama oleh seluruh akun keluarga (tidak terisolasi per `user_id`). Setiap transaksi tetap mencatat siapa anggota keluarga yang menginputnya (`Oleh: Suami / Istri`).

## 2. Struktur Menu Utama (3 Menu Utama: Home, Kantong, Rekap)
Aplikasi menggunakan **3 menu utama** yang intuitif, ramah, dan terstruktur untuk kebutuhan harian keluarga. Di ponsel, navigasi memakai **bottom navigation** berisi Home, Kantong, Rekap, dan tombol **Akun** (avatar inisial) yang membuka menu profil serta tombol keluar; tidak ada bilah header di bagian atas.

1. **Home (`/home`)**:
   - Menampilkan **detail lengkap Kantong Utama** keluarga yang menjadi rekening operasional harian.
   - Dilengkapi kartu saldo utama, statistik total uang masuk & keluar **bulan aktif** (ikut berubah saat navigasi bulan digeser), serta tombol aksi cepat (**+ Masuk**, **- Keluar**, **⇄ Pindah**) yang melayang di ponsel.
   - Menampilkan histori mutasi kantong utama **khusus bulan berjalan** dengan navigasi bulan (**‹ Bulan Lalu** dan **Bulan Depan ›**) yang tampil **di atas dan di bawah** daftar transaksi. Tombol bulan depan non-aktif saat riwayat sudah berada di bulan berjalan (tidak bisa melangkah ke bulan masa depan), sedangkan tombol bulan lalu selalu aktif. Label bulan aktif tampil di tengah navigasi dalam Bahasa Indonesia (contoh: *November 2026*). Kartu filter berisi chip cepat aliran dana (**Semua / Masuk / Keluar / Pindah**), pencarian keterangan, filter kategori, serta tombol reset; filter bulan tidak lagi memakai dropdown karena bulan aktif dikendalikan navigasi. Transaksi dikelompokkan per tanggal, seluruh transaksi bulan aktif ditampilkan langsung tanpa paginasi, dan teks daftar kosong menyebutkan bulan aktif. Setiap transaksi dapat **dihapus** (saldo kantong dipulihkan otomatis); transaksi **pemasukan/pengeluaran** juga dapat **diubah** (nominal, tanggal, keterangan, kategori), sedangkan transaksi pindah/terima uang hanya dapat dihapus karena berdampak pada dua kantong.
   - Responsif ganda: kartu daftar mutasi mobile di smartphone (`sm:hidden`) dan tabel multi-kolom di desktop (`hidden sm:block`).

2. **Kantong (`/pockets`)**:
   - Menampilkan **daftar seluruh kantong-kantong keluarga** (*Pocket Grid*).
   - Menampilkan ringkasan metrik saldo: Total Seluruh Saldo, Pemasukan Bulan Ini, dan Pengeluaran Bulan Ini.
   - Kotak-kotak kantong dengan warna pastel manis (*playful candy colors*) tersusun **2 kolom** di semua ukuran layar. Setiap kotak hanya menampilkan nama kantong dan saldo; mengetuk kotak membuka halaman detail kantong.
   - Tombol **Tambah Kantong Baru** di header halaman untuk membuat pos anggaran baru.

3. **Rekap (`/recap`)**:
   - Halaman khusus rekapitulasi — **tanpa pencatatan transaksi** dan **tanpa daftar mutasi**; fokus hanya pada ringkasan dan analisis.
   - Ringkasan metrik: Total Pemasukan, Total Pengeluaran, dan Arus Kas Bersih (*Net Cashflow*).
   - **Filter Rekap Berbasis Bulan & Kantong**: Seluruh rekap mengikuti filter bulan dan kantong yang dipilih.
   - **Rekap per Kategori & Batas Anggaran (Limit)**: Satu kartu berisi judul, tab **Pengeluaran / Pemasukan**, dan tabel kategori. Setiap baris kategori memiliki tombol pensil untuk mengubah batas atas anggaran (*budget limit*) pada kedua tab. Nilai **Aktual** diberi warna langsung sesuai pemakaian anggaran `aktual ÷ batas`: **merah** bila melampaui batas (> 100%), **hijau** bila tepat 100%, dan **netral (warna teks default)** bila di bawah batas, dengan persentase pemakaian ditampilkan di bawah nilai aktual memakai warna yang sama. Bila pengeluaran melampaui batas, baris kategori juga ditandai **merah peringatan** beserta keterangan nominal kelebihan. Kategori tanpa batas tetap menampilkan persentase porsi terhadap total dengan warna netral abu-abu agar tidak terbaca sebagai peringatan.
   - **Rekap per Kata Kunci (Keyword) Keterangan**: Satu kartu berisi judul, tombol tambah kata kunci berbentuk ikon di sisi kanan judul, dan tabel kata kunci. Pengguna dapat menyimpan kata kunci belanja khusus (seperti *Beras*, *Skincare*, *Bensin*, *Kopi*, *Listrik*) yang tersimpan permanen di database. Setiap baris hanya memiliki satu tombol pensil untuk mengubah kata kunci dan batas anggaran; tombol hapus tersedia di dalam modal ubah. Baris otomatis ditandai **merah** bila total mutasi dengan kata kunci tersebut melampaui batas. Nilai **Aktual** pada tiap baris memakai aturan warna yang sama dengan kategori: **merah** bila melampaui batas, **hijau** bila tepat 100%, **netral (default)** bila di bawah batas; persentase `aktual ÷ batas` ditampilkan di bawah nilai aktual, atau porsi terhadap total pengeluaran dengan warna netral bila batas kosong.

4. **Pengaturan Akun & Kategori (`/account`)**:
   - Pengguna dapat menambah, **mengubah (edit)**, dan **menghapus kategori transaksi**, termasuk menentukan nama, jenis (*income/expense*), ikon, warna, dan **batas atas anggaran (budget limit)** untuk setiap kategori.
   - **Penghapusan kategori berbasis pemakaian**: tombol hapus hanya aktif untuk kategori yang **belum pernah dipakai** pada transaksi. Kategori yang sudah dipakai menampilkan tombol hapus non-aktif beserta keterangan, dan permintaan hapus ditolak di sisi server agar riwayat transaksi tetap utuh.

*(Catatan: Rute URL `/dashboard` dan `/transactions` tetap didukung melalui pengalihan/redirect otomatis ke `/home` dan `/recap` demi kompatibilitas).*

## 3. Halaman Detail Kantong (`/pockets/{wallet}`)
Diakses dengan mengetuk kotak kantong pada halaman **Kantong**:
- Menampilkan informasi kantong, status (`Aktif` / `Dibekukan`), total uang masuk, total uang keluar, dan saldo saat ini. Total masuk & keluar mengikuti **bulan aktif** riwayat transaksi di bawahnya.
- **Tata letak mengikuti halaman Home**: kartu kantong (nama, badge, deskripsi, saldo, ringkasan masuk/keluar, serta tombol aksi), lalu kartu filter riwayat transaksi terpisah, dan terakhir kartu riwayat transaksi.
- Menyediakan 3 tombol transaksi utama untuk kantong tersebut (melayang di bagian bawah ponsel):
  1. **↓ Masuk**: Pilih sumber uang dari **Pemasukan Baru (Langsung)** atau **Ambil dari Kantong Lain**.
  2. **↗ Keluar**: Catat pengeluaran dari kantong ini.
  3. **⇄ Pindah**: Pindahkan saldo dari kantong ini ke kantong aktif lain.
- Menyediakan tombol pengelolaan kantong: **Ubah Kantong**, **Bekukan / Aktifkan**, dan **Hapus Kantong** (wajib memindahkan sisa saldo ke kantong lain bila saldo > Rp0).
- Menampilkan **Histori Masuk & Keluar Kantong** tanpa kode TRX, dengan **navigasi bulan** (**‹ Bulan Lalu** / **Bulan Depan ›**) di atas dan di bawah daftar seperti di Home (default bulan berjalan, tidak bisa melangkah ke bulan masa depan), lengkap dengan filter cepat aliran dana (Semua / Masuk / Keluar / Pindah), filter kategori, pencarian keterangan, tombol reset filter, serta daftar transaksi yang dikelompokkan per tanggal. Perilaku filter dan daftar transaksi **sama dengan riwayat transaksi di Home**: pencarian mencakup keterangan, catatan, nama kantong lawan, dan nama kategori; filter kategori memakai kategori transaksi; serta seluruh transaksi bulan aktif ditampilkan langsung tanpa paginasi. Setiap transaksi dapat **dihapus** (saldo dikembalikan) dan transaksi **pemasukan/pengeluaran** dapat **diubah** (nominal, tanggal, keterangan, kategori); transaksi pindah/terima uang hanya dapat dihapus agar pasangan antar kantong tetap konsisten.

## 4. Integritas Keuangan & Database
- Seluruh perubahan saldo diproses melalui backend service (`WalletService`, `TransactionService`, `TransferService`) di dalam `DB::transaction` dengan penguncian baris `SELECT ... FOR UPDATE` (`lockForUpdate()`).
- Saldo kantong tidak boleh negatif dan nominal transaksi wajib positif (`> 0`).
- Kantong yang dibekukan tidak dapat menerima atau mengeluarkan uang, namun saldonya tetap dihitung dalam Total Saldo.
- Kantong yang dihapus menggunakan *Soft Delete* setelah saldonya menjadi `Rp0`, dan riwayat transaksinya tetap tersimpan permanen.

## 5. Pengoptimalan Tampilan Mobile First (Smartphone Utama)
Karena aplikasi utamanya digunakan langsung dari smartphone (HP) oleh istri:
- **Riwayat Transaksi Responsif**:
  - Di layar ponsel (`sm:hidden`): Riwayat transaksi disajikan dalam bentuk daftar kartu transaksi vertikal yang padat, rapi, dan mudah dibaca tanpa perlu geser layar ke kanan (*no horizontal scroll fatigue*), menampilkan tanggal & waktu, nominal berwarna tebal (`+Rp...` / `-Rp...`), nama kantong dengan swatch warna, badge jenis/kategori, serta aksi ubah/hapus transaksi.
  - Di tablet dan desktop (`hidden sm:block`): Tetap menyajikan data table lengkap multi-kolom.
- **Toolbar & Filter Ramah Sentuhan**:
  - Tombol filter cepat (*quick pills*) pada riwayat transaksi dan detail kantong mendukung *horizontal swipe* yang mulus di ponsel tanpa memakan ruang vertikal.
- **Navigasi Ponsel Tanpa Header**:
  - Tidak ada bilah header di atas layar; identitas brand dan profil pengguna berada di dalam sidebar.
  - Bottom navigation berisi 4 item: Home, Kantong, Rekap, dan Menu. Item aktif memakai aksen pink tua, sedangkan tombol Menu membuka sidebar dan menandai dirinya aktif selama sidebar terbuka.
  - Sidebar dapat ditutup lewat tombol X, mengetuk area gelap di luar panel, atau tombol Esc.
- **Ukuran Sentuh & Jarak Ergonomis**:
  - Tombol-tombol aksi utama, pemilihan kategori radio 3 kolom, dan menu tab di profil dirancang dengan ukuran sentuh nyaman (touch target >= 40-44px) yang bersahabat untuk sentuhan jempol di smartphone.
  - Pembuatan kantong baru hanya melalui tombol **Tambah Kantong Baru** di header halaman Kantong, tanpa kartu kosong tambahan di dalam daftar.

## 6. Kalkulator Mini pada Field Nominal
Field **Nominal** pada form **Masuk** dan **Keluar** (di Home maupun detail kantong) dilengkapi **popup kalkulator mini** untuk menghitung nominal langsung tanpa keluar dari form:
- Tombol ikon kalkulator di sisi kanan field membuka popup berisi keypad angka (0-9) dan empat operasi aritmetika: **+ − × ÷**.
- Display popup menampilkan ekspresi yang sedang disusun (contoh: `12 × 5`), dengan tombol `C` (bersihkan) dan `⌫` (hapus satu karakter).
- Perhitungan berlangsung **dua langkah**: tombol **= Hitung** menghitung ekspresi dan menampilkan hasilnya di display (belum mengisi field), lalu tombol **Masukkan** yang memasukkan hasil ke field nominal (popup menutup otomatis). Tombol `Masukkan` non-aktif selama belum ada hasil perhitungan. Hasil dibulatkan ke rupiah penuh dan ditampilkan dengan pemisah ribuan gaya Indonesia (contoh: `60.000`).
- **Penyimpanan hasil (M+)**: tombol **M+** (non-aktif selama belum ada hasil) menyimpan hasil perhitungan sebagai angka yang nanti **dijumlahkan/dikurangkan** dengan hitungan berikutnya — berguna untuk total berupa penjumlahan beberapa perkalian, contoh `2 × 4 + 4 × 4`: hitung `2 × 4` → `= Hitung` → `M+` → ketik `4 × 4` → `= Hitung` (hasil `24`). Sebelum disimpan kembali, operator dapat diganti dengan menekan `−` (bawaan `+`); display menampilkan bentuk `8 + 4 × 4` dan muncul chip **"Tersimpan: 8"** dengan tombol buang (×) di bawah display. Memory ikut ter-reset saat popup dibuka ulang.
- Hasil perhitungan ditulis ke field dengan simulasi input yang sama seperti ketikan pengguna, sehingga sinkronisasi Livewire (`wire:model` pada `x-currency`) tetap bekerja normal.
- Implementasi memakai komponen Blade reusable `resources/views/components/amount-calculator.blade.php` yang membungkus field: `<x-amount-calculator model="...">...</x-amount-calculator>`; ekspresi divalidasi whitelist (`0-9 + - * / .`) sebelum dievaluasi. Diterapkan pada seluruh field nominal transaksi: form **Masuk**, **Keluar**, **Pindah**, dan modal **Ubah Transaksi** (di Home maupun detail kantong). Tombol kalkulator tampil sebagai *addon* di **dalam field** (sisi kanan, vertikal tengah) dan popup muncul **di atas** field.

## 7. Rekonsiliasi Saldo Akhir (Rekap)
Di bagian **paling bawah halaman Rekap (`/recap`)** terdapat kartu **"Saldo Akhir & Rekonsiliasi"** untuk membandingkan saldo akhir kantong hasil hitungan sistem dengan **saldo yang dihitung manual** oleh keluarga (contoh: Tunai, Bank A, Bank B):
- **Pemilihan kantong**: dropdown di kanan judul kartu untuk memilih kantong yang direkonsiliasi; **default = kantong utama (primary)**. Opsi kantong hanya memuat kantong aktif/dibekukan; kantong utama ditandai akhiran "(Utama)". Setiap kantong memiliki daftar saldo manualnya sendiri (berdasarkan `wallet_id`).
- **Kartu ringkasan 3 metrik**: **Saldo Akhir Kantong** (saldo kantong saat ini dari kolom `balance`), **Total Hitungan Manual** (penjumlahan seluruh entri saldo manual), dan **Selisih** (total manual − saldo akhir). Selisih tampil **"Cocok"** berwarna emerald bila nilainya 0, dan **`+/-` nominal** berwarna rose bila berbeda; Saldo Akhir netral slate, Total Manual aksen sky.
- **Kelola saldo manual**: tombol tambah (ikon +) membuka modal input berisi nama sumber saldo (mis. `Tunai`, `Bank A`, `Bank B`) dan nominal (`x-currency` rupiah). Setiap baris entri memiliki tombol pensil untuk mengubah; tombol hapus berada di dalam modal ubah dengan dialog konfirmasi Interactions. Semua aksi ditemani toast sukses Bahasa Indonesia.
- **Penyimpanan database**: entri tersimpan permanen pada tabel `wallet_reconciliations` (`wallet_id` FK *cascade on delete*, `user_id` pencatat nullable, `label` max 100 karakter, `amount` decimal 18,2). Model `App\Models\WalletReconciliation` dengan relasi `Wallet::reconciliations()` (HasMany); data kantong diambil dengan eager loading sederhana dan `#[Computed]` cache di komponen Rekap.
- **Kartu kosong**: bila kantong belum memiliki entri, kartu menampilkan ajakan menambahkan sumber saldo; bila kantong yang dipilih tidak tersedia (mis. dihapus), kartu menampilkan instruksi memilih kantong aktif.
