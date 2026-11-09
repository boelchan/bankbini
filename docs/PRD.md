# Product Requirements Document (PRD): Bank BINI (Simplified Edition)

## 1. Ringkasan Aplikasi
**Bank BINI** adalah aplikasi manajemen kantong keuangan keluarga (*Pocket*) yang simpel, hangat, dan ramah digunakan bersama oleh **Suami dan Istri**.
- **Kantong Bersama (Shared Household Pockets)**: Seluruh kantong (`wallets`), riwayat transaksi (`transactions`), pemindahan uang (`transfers`), dan kategori transaksi diakses secara bersama oleh seluruh akun keluarga (tidak terisolasi per `user_id`). Setiap transaksi tetap mencatat siapa anggota keluarga yang menginputnya (`Oleh: Suami / Istri`).

## 2. Struktur Menu Utama (3 Menu Utama: Home, Kantong, Rekap)
Aplikasi menggunakan **3 menu utama** yang intuitif, ramah, dan terstruktur untuk kebutuhan harian keluarga:

1. **Home (`/home`)**:
   - Menampilkan **detail lengkap Kantong Utama** keluarga yang menjadi rekening operasional harian.
   - Dilengkapi kartu saldo utama, statistik total uang masuk & keluar, tombol aksi cepat (**+ Masuk**, **- Keluar**, **⇄ Pindah**), dan link cepat menuju daftar kantong lainnya.
   - Menampilkan histori mutasi kantong utama lengkap dengan filter aliran dana (*quick swipe pills*), filter bulan, filter jenis, filter kategori, dan pencarian keterangan.
   - Responsif ganda: kartu daftar mutasi mobile di smartphone (`sm:hidden`) dan tabel multi-kolom di desktop (`hidden sm:block`).

2. **Kantong (`/pockets`)**:
   - Menampilkan **daftar seluruh kantong-kantong keluarga** (*Pocket Grid*).
   - Menampilkan ringkasan metrik saldo: Total Seluruh Saldo, Pemasukan Bulan Ini, dan Pengeluaran Bulan Ini.
   - Kotak-kotak kantong dengan warna pastel manis (*playful candy colors*), status khusus **Kantong Utama** (`is_primary`), saldo kantong, dan 3 transaksi terakhir (bisa di-*expand* hingga 100 baris).
   - Tombol pensil (*Catat Transaksi*) dan tombol titik tiga (*Detail Kantong*).
   - Kotak kartu kosong `+ Tambah Kantong` di posisi terakhir untuk membuat pos anggaran baru.

3. **Rekap (`/recap`)**:
   - Menampilkan **rekapitulasi seluruh transaksi** dari semua kantong keluarga.
   - Ringkasan metrik: Total Pemasukan, Total Pengeluaran, dan Arus Kas Bersih (*Net Cashflow*).
   - Dilengkapi tombol pencatatan cepat (**+ Masuk**, **- Keluar**, **⇄ Pindah**).
   - **Filter Sederhana Berbasis Bulan**: Menggunakan filter bulan transaksi (*tanpa input tanggal yang membingungkan*), pencarian keterangan, dan filter spesifik kantong.
   - **Rekap per Kategori & Batas Anggaran (Limit)**: Menyajikan distribusi alokasi pengeluaran dan pemasukan per kategori. Setiap kategori dapat ditetapkan batas atas anggaran (*budget limit*); bila pengeluaran melampaui batas tersebut, kartu kategori otomatis ditandai **merah peringatan**.
   - **Rekap per Kata Kunci (Keyword) Keterangan**: Pengguna dapat menyimpan kata kunci belanja khusus (seperti *Beras*, *Skincare*, *Bensin*, *Kopi*, *Listrik*) yang tersimpan permanen di database. Setiap kata kunci memiliki batas atas anggaran; bila total mutasi dengan kata kunci tersebut melampaui batas, kartu otomatis ditandai **merah**.
   - **Tampilan Daftar Mutasi Berbasis List (Bukan Tabel)**: Seluruh daftar mutasi disajikan dalam format list kartu transaksi yang bersih, modern, dan mudah dipindai di smartphone maupun komputer tanpa tabel yang kaku.

4. **Pengaturan Akun & Kategori (`/account`)**:
   - Pengguna dapat menambah, **mengubah (edit)**, dan **menghapus kategori transaksi**, termasuk menentukan nama, jenis (*income/expense*), ikon, warna, dan **batas atas anggaran (budget limit)** untuk setiap kategori.

*(Catatan: Rute URL `/dashboard` dan `/transactions` tetap didukung melalui pengalihan/redirect otomatis ke `/home` dan `/recap` demi kompatibilitas).*

## 3. Halaman Detail Kantong (`/pockets/{wallet}`)
Diakses melalui tombol **Detail** pada kotak kantong di Dashboard:
- Menampilkan informasi kantong, status (`Aktif` / `Dibekukan`), total uang masuk, total uang keluar, dan saldo saat ini.
- Menyediakan 3 tombol transaksi utama untuk kantong tersebut:
  1. **+ Masuk**: Pilih sumber uang dari **Pemasukan Baru (Langsung)** atau **Ambil dari Kantong Lain**.
  2. **- Keluar**: Catat pengeluaran dari kantong ini.
  3. **⇄ Pindah**: Pindahkan saldo dari kantong ini ke kantong aktif lain.
- Menyediakan tombol pengelolaan kantong: **Ubah Kantong**, **Bekukan / Aktifkan**, dan **Hapus Kantong** (wajib memindahkan sisa saldo ke kantong lain bila saldo > Rp0).
- Menampilkan tabel **Histori Masuk & Keluar Kantong** beserta saldo sebelum dan sesudah transaksi tanpa kode TRX, lengkap dengan filter bulan transaksi, filter cepat aliran dana (Semua / Pemasukan / Pengeluaran / Pindah Uang), filter jenis transaksi, filter kategori, pencarian keterangan, serta tombol reset filter.

## 4. Integritas Keuangan & Database
- Seluruh perubahan saldo diproses melalui backend service (`WalletService`, `TransactionService`, `TransferService`) di dalam `DB::transaction` dengan penguncian baris `SELECT ... FOR UPDATE` (`lockForUpdate()`).
- Saldo kantong tidak boleh negatif dan nominal transaksi wajib positif (`> 0`).
- Kantong yang dibekukan tidak dapat menerima atau mengeluarkan uang, namun saldonya tetap dihitung dalam Total Saldo.
- Kantong yang dihapus menggunakan *Soft Delete* setelah saldonya menjadi `Rp0`, dan riwayat transaksinya tetap tersimpan permanen.

## 5. Pengoptimalan Tampilan Mobile First (Smartphone Utama)
Karena aplikasi utamanya digunakan langsung dari smartphone (HP) oleh istri:
- **Riwayat Transaksi Responsif**:
  - Di layar ponsel (`sm:hidden`): Riwayat transaksi disajikan dalam bentuk daftar kartu transaksi vertikal yang padat, rapi, dan mudah dibaca tanpa perlu geser layar ke kanan (*no horizontal scroll fatigue*), menampilkan tanggal & waktu, nominal berwarna tebal (`+Rp...` / `-Rp...`), nama kantong dengan swatch warna, badge jenis/kategori, dan sisa saldo kantong.
  - Di tablet dan desktop (`hidden sm:block`): Tetap menyajikan data table lengkap multi-kolom.
- **Toolbar & Filter Ramah Sentuhan**:
  - Tombol filter cepat (*quick pills*) pada riwayat transaksi dan detail kantong mendukung *horizontal swipe* yang mulus di ponsel tanpa memakan ruang vertikal.
- **Ukuran Sentuh & Jarak Ergonomis**:
  - Tombol-tombol aksi utama, pemilihan kategori radio 3 kolom, dan menu tab di profil dirancang dengan ukuran sentuh nyaman (touch target >= 40-44px) yang bersahabat untuk sentuhan jempol di smartphone.
  - Kotak kartu kosong `+ Tambah Kantong` memiliki tinggi proporsional (`min-h-36 sm:min-h-52`) agar efisien dan tidak menghabiskan ruang vertikal layar HP.
