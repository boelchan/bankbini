# Product Requirements Document (PRD): Bank BINI (Simplified Edition)

## 1. Ringkasan Aplikasi
**Bank BINI** adalah aplikasi manajemen kantong keuangan keluarga (*Pocket*) yang simpel, hangat, dan ramah digunakan bersama oleh **Suami dan Istri**.
- **Kantong Bersama (Shared Household Pockets)**: Seluruh kantong (`wallets`), riwayat transaksi (`transactions`), pemindahan uang (`transfers`), dan kategori transaksi diakses secara bersama oleh seluruh akun keluarga (tidak terisolasi per `user_id`). Setiap transaksi tetap mencatat siapa anggota keluarga yang menginputnya (`Oleh: Suami / Istri`).

## 2. Struktur Menu Utama (3 Menu Utama: Home, Kantong, Rekap)
Aplikasi menggunakan **3 menu utama** yang intuitif, ramah, dan terstruktur untuk kebutuhan harian keluarga. Di ponsel, navigasi memakai **bottom navigation** berisi Home, Kantong, Rekap, dan tombol **Akun** (avatar inisial) yang membuka menu profil serta tombol keluar; tidak ada bilah header di bagian atas.

1. **Home (`/home`)**:
   - Menampilkan **detail lengkap Kantong Utama** keluarga yang menjadi rekening operasional harian.
   - Dilengkapi kartu saldo utama, statistik total uang masuk & keluar, serta tombol aksi cepat (**+ Masuk**, **- Keluar**, **⇄ Pindah**) yang melayang di ponsel.
   - Menampilkan histori mutasi kantong utama lengkap dengan kartu filter: chip cepat aliran dana (**Semua / Masuk / Keluar / Pindah**), pencarian keterangan, filter bulan, jenis transaksi, kategori, serta tombol reset. Transaksi dikelompokkan per tanggal. Setiap transaksi dapat **dihapus** (saldo kantong dipulihkan otomatis); transaksi **pemasukan/pengeluaran** juga dapat **diubah** (nominal, tanggal, keterangan, kategori), sedangkan transaksi pindah/terima uang hanya dapat dihapus karena berdampak pada dua kantong.
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
   - **Rekap per Kategori & Batas Anggaran (Limit)**: Satu kartu berisi judul, tab **Pengeluaran / Pemasukan**, dan tabel kategori. Setiap baris kategori memiliki tombol pensil untuk mengubah batas atas anggaran (*budget limit*) pada kedua tab. Di bawah setiap baris, bilah pemakaian anggaran membentang penuh dari kiri ke kanan dengan persentase `aktual ÷ batas`: **hijau** di bawah 80%, **merah** 80-100%, dan **hitam** bila melewati batas. Bila pengeluaran melampaui batas, baris kategori juga ditandai **merah peringatan**. Kategori tanpa batas tetap menampilkan persentase porsi terhadap total dengan bilah netral abu-abu agar tidak terbaca sebagai peringatan.
   - **Rekap per Kata Kunci (Keyword) Keterangan**: Satu kartu berisi judul, tombol tambah kata kunci berbentuk ikon di sisi kanan judul, dan tabel kata kunci. Pengguna dapat menyimpan kata kunci belanja khusus (seperti *Beras*, *Skincare*, *Bensin*, *Kopi*, *Listrik*) yang tersimpan permanen di database. Setiap baris hanya memiliki satu tombol pensil untuk mengubah kata kunci dan batas anggaran; tombol hapus tersedia di dalam modal ubah. Baris otomatis ditandai **merah** bila total mutasi dengan kata kunci tersebut melampaui batas. Bilah persentase di bawah tiap baris memakai aturan warna yang sama dengan kategori: persentase `aktual ÷ batas` bila batas diisi, atau porsi terhadap total pengeluaran dengan bilah netral bila batas kosong.

4. **Pengaturan Akun & Kategori (`/account`)**:
   - Pengguna dapat menambah, **mengubah (edit)**, dan **menghapus kategori transaksi**, termasuk menentukan nama, jenis (*income/expense*), ikon, warna, dan **batas atas anggaran (budget limit)** untuk setiap kategori.
   - **Penghapusan kategori berbasis pemakaian**: tombol hapus hanya aktif untuk kategori yang **belum pernah dipakai** pada transaksi. Kategori yang sudah dipakai menampilkan tombol hapus non-aktif beserta keterangan, dan permintaan hapus ditolak di sisi server agar riwayat transaksi tetap utuh.

*(Catatan: Rute URL `/dashboard` dan `/transactions` tetap didukung melalui pengalihan/redirect otomatis ke `/home` dan `/recap` demi kompatibilitas).*

## 3. Halaman Detail Kantong (`/pockets/{wallet}`)
Diakses dengan mengetuk kotak kantong pada halaman **Kantong**:
- Menampilkan informasi kantong, status (`Aktif` / `Dibekukan`), total uang masuk, total uang keluar, dan saldo saat ini.
- **Tata letak mengikuti halaman Home**: kartu kantong (nama, badge, deskripsi, saldo, ringkasan masuk/keluar, serta tombol aksi), lalu kartu filter riwayat transaksi terpisah, dan terakhir kartu riwayat transaksi.
- Menyediakan 3 tombol transaksi utama untuk kantong tersebut (melayang di bagian bawah ponsel):
  1. **↓ Masuk**: Pilih sumber uang dari **Pemasukan Baru (Langsung)** atau **Ambil dari Kantong Lain**.
  2. **↗ Keluar**: Catat pengeluaran dari kantong ini.
  3. **⇄ Pindah**: Pindahkan saldo dari kantong ini ke kantong aktif lain.
- Menyediakan tombol pengelolaan kantong: **Ubah Kantong**, **Bekukan / Aktifkan**, dan **Hapus Kantong** (wajib memindahkan sisa saldo ke kantong lain bila saldo > Rp0).
- Menampilkan **Histori Masuk & Keluar Kantong** tanpa kode TRX, lengkap dengan filter bulan transaksi, filter cepat aliran dana (Semua / Masuk / Keluar / Pindah), filter jenis transaksi, filter kategori, pencarian keterangan, tombol reset filter, serta daftar transaksi yang dikelompokkan per tanggal. Perilaku filter dan daftar transaksi **sama dengan riwayat transaksi di Home**: pencarian mencakup keterangan, catatan, nama kantong lawan, dan nama kategori; filter kategori memakai kategori transaksi; serta 15 transaksi per halaman. Setiap transaksi dapat **dihapus** (saldo dikembalikan) dan transaksi **pemasukan/pengeluaran** dapat **diubah** (nominal, tanggal, keterangan, kategori); transaksi pindah/terima uang hanya dapat dihapus agar pasangan antar kantong tetap konsisten.

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
