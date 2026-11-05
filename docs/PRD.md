# PRD: Bank BINI (Digital Wallet & Pocket Management)

## 1. Gambaran Umum Produk
**Bank BINI** adalah aplikasi manajemen keuangan pribadi dan keluarga dengan konsep **Pocket Management** (Kantong Keuangan) seperti Bank Jago. Aplikasi ini dirancang dengan tampilan yang *cute*, hangat, modern, dan ramah digunakan sehari-hari oleh pengguna dan istri untuk mengatur pos keuangan rumah tangga, tabungan, belanja harian, serta rencana liburan secara akurat dan aman.

## 2. Arsitektur & Teknologi
- **Framework**: Laravel 13, Livewire 4 (Multi-File Component `⚡`), PHP 8.3+
- **UI & Styling**: Tailwind CSS v4 & TallStackUI v4
- **Ikon**: `secondnetwork/blade-tabler-icons` (`<x-ti-... />`)
- **Database**: MySQL 5.7+ / MariaDB kompatibel
- **Export**: `spatie/simple-excel` (Excel/CSV) & tampilan cetak PDF ramah browser

## 3. Desain Database Modular (MySQL 5.7 Compatible)
1. **`users`**: Data pengguna (id, name, email, password, status, avatar_color, timestamps).
2. **`wallets`** (Kantong / Pocket):
   - `id`, `user_id`, `name`, `description`, `icon`, `color`, `initial_balance` (`decimal(18,2)`), `balance` (`decimal(18,2)`), `status` (`active`, `frozen`, `deleted`), `is_primary` (`boolean`), `deleted_at` (SoftDeletes), `created_at`, `updated_at`.
3. **`transaction_categories`**:
   - `id`, `user_id` (nullable untuk kategori bawaan sistem), `name`, `type` (`income`, `expense`), `icon`, `color`, `created_at`, `updated_at`.
4. **`transfers`**:
   - `id`, `transfer_code` (unik), `idempotency_key` (unik, nullable), `user_id`, `source_wallet_id`, `source_wallet_name` (snapshot), `destination_wallet_id`, `destination_wallet_name` (snapshot), `amount` (`decimal(18,2)`), `description`, `transaction_date`, `created_at`, `updated_at`.
5. **`transactions`** (Immutable Ledger):
   - `id`, `transaction_code` (unik), `idempotency_key` (unik, nullable), `user_id`, `wallet_id`, `wallet_name_snapshot`, `counterpart_wallet_id` (nullable), `counterpart_wallet_name_snapshot` (nullable), `transfer_reference` (nullable, mengacu ke `transfers.transfer_code`), `type` (`income`, `expense`, `transfer_in`, `transfer_out`), `category_id` (nullable), `category_name_snapshot` (nullable), `amount` (`decimal(18,2)`), `balance_before` (`decimal(18,2)`), `balance_after` (`decimal(18,2)`), `description`, `notes` (nullable), `transaction_date`, `created_at`, `updated_at`.
6. **`wallet_activity_logs`**:
   - `id`, `wallet_id`, `wallet_name_snapshot`, `user_id`, `activity`, `description`, `metadata` (`text`), `created_at`.

## 4. Alur & Fitur Utama
### 4.1 Dashboard
- **Ringkasan Saldo**: Total seluruh saldo (kantong aktif + dibekukan), total pemasukan bulan ini, total pengeluaran bulan ini, dan jumlah kantong aktif.
- **Tombol Aksi Cepat**: `+ Pemasukan`, `- Pengeluaran`, `⇄ Transfer Antar Kantong`, dan `+ Kantong Baru`.
- **Daftar Kantong (Pocket Cards)**: Menampilkan nama, ikon, warna pastel kustom, saldo saat ini, status (`Aktif` / `Dibekukan`), serta tombol detail.
- **Grafik Pemasukan vs Pengeluaran**: Grafik arus kas 6 bulan terakhir (tanpa menghitung transfer internal).
- **Transaksi Terbaru**: 8 transaksi terakhir beserta tombol Lihat Semua Transaksi.

### 4.2 Manajemen Kantong (Pocket)
- **Tambah Kantong**: Nama, ikon, warna pastel, deskripsi, dan saldo awal (opsional; jika > 0 otomatis mencatat transaksi saldo awal).
- **Ubah Kantong**: Mengubah nama, ikon, warna, dan deskripsi tanpa mengubah snapshot histori transaksi masa lalu.
- **Bekukan / Aktifkan Kantong**: Kantong yang dibekukan (`frozen`) tidak dapat menerima pemasukan, pengeluaran, maupun menjadi pengirim/penerima transfer, tetapi saldonya tetap dihitung dalam Total Saldo dan riwayatnya tetap dapat dilihat.
- **Hapus Kantong (Penutupan Aman)**:
  - Wajib melalui konfirmasi.
  - Jika saldo kantong > 0, pengguna wajib memilih kantong aktif tujuan untuk memindahkan seluruh sisa saldo secara otomatis melalui proses transfer penutupan sebelum kantong ditutup/dihapus (`soft delete` + status `deleted`).
  - Seluruh riwayat transaksi lama dan snapshot nama kantong tetap tersimpan utuh di database dan tampil pada histori global maupun kantong pasangan transfer.

### 4.3 Transaksi Pemasukan & Pengeluaran
- **Pemasukan (`income`)**: Memilih kantong aktif tujuan, nominal (> 0), kategori pemasukan (Gaji, Bonus, Bisnis, Investasi, Hadiah, Uang Belanja Suami, Lainnya), tanggal transaksi, keterangan, dan catatan tambahan.
- **Pengeluaran (`expense`)**: Memilih kantong aktif sumber, nominal (> 0 dan <= saldo kantong), kategori pengeluaran (Makanan, Transportasi, Belanja, Tagihan, Kesehatan, Hiburan, Pendidikan, Rumah Tangga, Skincare & Cantik, Lainnya), tanggal transaksi, keterangan, dan catatan tambahan.

### 4.4 Transfer Antar Kantong
- Memindahkan dana dari kantong pengirim ke kantong penerima dalam satu `DB::transaction` dengan penguncian baris `lockForUpdate()`.
- Menghasilkan 1 record `transfers` dan 2 record `transactions` (`transfer_out` pada pengirim dan `transfer_in` pada penerima) yang terhubung melalui `transfer_reference`.
- Total saldo keseluruhan tetap konsisten (tidak berubah).

### 4.5 Riwayat Transaksi (Immutable Ledger)
- Filter lengkap berdasarkan rentang tanggal, kantong (termasuk kantong yang telah dihapus), kategori, jenis transaksi (`income`, `expense`, `transfer_in`, `transfer_out`), dan pencarian keterangan/kode transaksi.
- Menampilkan modal detail transaksi lengkap dengan `balance_before`, `balance_after`, kode referensi transfer, dan snapshot kantong.

### 4.6 Laporan Keuangan & Export
- Ringkasan total pemasukan, total pengeluaran, selisih bersih (*net cashflow*), dan rasio tabungan pada rentang tanggal terpilih.
- Transfer internal antar kantong dikecualikan dari perhitungan pemasukan/pengeluaran agregat.
- Grafik arus kas bulanan, grafik komposisi pengeluaran per kategori, dan tabel rekapitulasi mutasi per kantong.
- Fitur Export ke **Excel (.xlsx/.csv)** dan **Cetak / Simpan PDF**.
