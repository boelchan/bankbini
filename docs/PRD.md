# Product Requirements Document (PRD): Bank BINI (Simplified Edition)

## 1. Ringkasan Aplikasi
**Bank BINI** adalah aplikasi manajemen kantong keuangan keluarga (*Pocket*) yang simpel, hangat, dan ramah digunakan bersama oleh **Suami dan Istri**.
- **Kantong Bersama (Shared Household Pockets)**: Seluruh kantong (`wallets`), riwayat transaksi (`transactions`), pemindahan uang (`transfers`), dan kategori transaksi diakses secara bersama oleh seluruh akun keluarga (tidak terisolasi per `user_id`). Setiap transaksi tetap mencatat siapa anggota keluarga yang menginputnya (`Oleh: Suami / Istri`).

## 2. Struktur Menu Utama (Sederhana & Fokus)
Aplikasi menggunakan **2 menu utama** agar ringkas dan mudah digunakan sehari-hari:
1. **Dashboard (`/dashboard`)**:
   - Menampilkan ringkasan Total Saldo Seluruh Kantong, Pemasukan Bulan Ini, dan Pengeluaran Bulan Ini.
   - Menampilkan **Kotak-Kotak Kantong** (*Pocket Grid*) dengan tampilan warna ceria yang manis, ramah anak & keluarga (*candy / playful pastels* seperti Permen Stroberi, Permen Karet, Madu Karamel, Permen Lemon, Melon Segar, Awan Biru, Lilac Gemas, dsb.) dengan kartu membulat `rounded-2xl`, badge ikon stiker manis (`bg-white shadow-2xs`), serta kontras teks yang nyaman (WCAG AA).
   - Status kantong **tidak ditampilkan jika berstatus Aktif** (hanya tampil badge jika kantong sedang Dibekukan). Tidak menampilkan teks tipe kantong generik.
   - Tombol aksi kantong menggunakan **tombol ikon bulat (`<x-button.circle>`)** yang ditempatkan rapi di samping nama kantong:
     - **Ikon `±` (`plus-minus`)**: Membuka **1 formulir transaksi terpadu** berbasis pilihan radio tanpa label (`<x-radio.group>`) dengan pilihan **Pengeluaran** (Pengeluaran Baru / Pindah ke Kantong Lain) atau **Pemasukan** (Pemasukan Baru / Ambil dari Kantong Lain) di bagian paling atas. Keterangan transaksi keluar antar kantong tercatat konsisten sebagai `"Pindah ke [Nama Kantong]"` dan transaksi masuk antar kantong tercatat sebagai `"Terima dari [Nama Kantong]"`.
     - **Ikon `...`**: Membuka halaman rincian & pengaturan kantong (`/pockets/{wallet}`).
   - Di bawah **Saldo Kantong**, menampilkan **3 transaksi terakhir** pada kantong tersebut (dapat di-*expand* hingga **100 transaksi terakhir**) lengkap dengan tombol **Hapus Transaksi** (`<x-ti-trash />`). Jika yang dihapus adalah transaksi `Pindah ke ...` atau `Terima dari ...`, maka sistem otomatis menghapus **kedua** transaksi pasangannya sekaligus dan mengembalikan saldo kedua kantong.
   - Pada urutan paling akhir daftar kotak kantong terdapat **Kotak Kosong (`+ Tambah Kantong`)** untuk membuat kantong baru dengan cepat.
2. **Transaksi (`/transactions`)**:
   - Menampilkan riwayat lengkap seluruh uang masuk, uang keluar, dan pindah uang antar kantong.
   - Dilengkapi tombol cepat **+ Pemasukan**, **- Pengeluaran**, dan **⇄ Pindah Uang**.
   - Dilengkapi filter sederhana berdasarkan jenis transaksi, kantong, tanggal, dan pencarian keterangan (tanpa menampilkan kode TRX).

## 3. Halaman Detail Kantong (`/pockets/{wallet}`)
Diakses melalui tombol **Detail** pada kotak kantong di Dashboard:
- Menampilkan informasi kantong, status (`Aktif` / `Dibekukan`), total uang masuk, total uang keluar, dan saldo saat ini.
- Menyediakan 3 tombol transaksi utama untuk kantong tersebut:
  1. **+ Tambah Uang**: Pilih sumber uang dari **Pemasukan Baru (Langsung)** atau **Ambil dari Kantong Lain**.
  2. **- Pengeluaran**: Catat pengeluaran dari kantong ini.
  3. **⇄ Pindah Uang**: Pindahkan saldo dari kantong ini ke kantong aktif lain.
- Menyediakan tombol pengelolaan kantong: **Ubah Kantong**, **Bekukan / Aktifkan**, dan **Hapus Kantong** (wajib memindahkan sisa saldo ke kantong lain bila saldo > Rp0).
- Menampilkan tabel **Histori Masuk & Keluar Kantong** beserta saldo sebelum dan sesudah transaksi tanpa kode TRX.

## 4. Integritas Keuangan & Database
- Seluruh perubahan saldo diproses melalui backend service (`WalletService`, `TransactionService`, `TransferService`) di dalam `DB::transaction` dengan penguncian baris `SELECT ... FOR UPDATE` (`lockForUpdate()`).
- Saldo kantong tidak boleh negatif dan nominal transaksi wajib positif (`> 0`).
- Kantong yang dibekukan tidak dapat menerima atau mengeluarkan uang, namun saldonya tetap dihitung dalam Total Saldo.
- Kantong yang dihapus menggunakan *Soft Delete* setelah saldonya menjadi `Rp0`, dan riwayat transaksinya tetap tersimpan permanen.
