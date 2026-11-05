# DESIGN.md: Bank BINI (Digital Wallet & Pocket Management)

## 1. Identitas & Karakter Produk
- **Nama Produk**: **Bank BINI** (Dompet Cerdas & Kantong Manis Keluarga)
- **Target Pengguna**: Pengguna pribadi dan istri tercinta untuk mengelola keuangan rumah tangga, tabungan keluarga, kebutuhan belanja, hingga dana liburan melalui sistem multi-kantong (*Pocket*) seperti Bank Jago.
- **Karakter Visual**: *Cute*, hangat, ramah, rapi, modern, dan menyenangkan digunakan sehari-hari, namun tetap menjaga akurasi dan kejelasan angka layaknya aplikasi perbankan digital profesional.
- **Dials**: `Dial: ENERGY 2 / RHYTHM 2 / MOTION 1`

## 2. Alasan Keputusan Visual (R-31 One-Line Reasons)
- **Mengapa warna ini (R-01, R-29)**: Menggunakan basis netral terang (`slate-50` & putih bersih) yang dipadukan dengan identitas utama *Emerald* (`#059669` untuk pertumbuhan saldo & aksi positif) dan *Rose/Pink* (`#e11d48` untuk aksen *cute* khas Bank BINI), serta warna kartu kantong pastel lembut (`xxx-50` / `xxx-100` dengan border `xxx-200` dan teks `xxx-950`) agar setiap kantong mudah dikenali sekaligus memenuhi kontras WCAG AA (>= 4.5:1).
- **Mengapa tipografi ini (R-06)**: Menggunakan **Plus Jakarta Sans** karena lekukan hurufnya yang bulat, modern, dan ramah memberi kesan *cute* tetapi memiliki *tabular numerals* (`tabular-nums`) yang presisi untuk membaca nominal Rupiah.
- **Mengapa tata letak ini (R-05)**: Mengadopsi ritme komposisi bervariasi (*RHYTHM 2*): kartu ringkasan utama (*Hero Wallet Banner*) dengan tombol aksi cepat (`+ Pemasukan`, `- Pengeluaran`, `⇄ Transfer`), diikuti grid kartu kantong (*Pocket Cards*) yang visual, lalu bagian analitik grafik dan daftar riwayat transaksi yang terstruktur, serta *Bottom Navigation Bar* ramah jempol di layar ponsel.
- **Mengapa jarak & sudut ini (R-11)**: Memakai kombinasi sudut membulat bertingkat (`rounded-2xl` untuk kartu utama dan kantong agar tampak *cute* dan empuk, `rounded-xl` untuk tombol dan navigasi, `rounded-lg` untuk input dan badge) dengan jarak `p-5` dan `gap-4` agar layar tidak terasa penuh.
- **Mengapa menggunakan kartu (R-12, R-14)**: Kartu digunakan untuk memisahkan setiap Kantong (*Pocket*) sebagai wadah fisik uang tersendiri dengan elevasi tipis (`shadow-xs`), sementara daftar transaksi dan rekap disajikan dalam baris tabel/list yang ringkas.
- **Mengapa memilih ikon ini (R-04)**: Menggunakan ikon `secondnetwork/blade-tabler-icons` yang merepresentasikan fungsi nyata kantong dan kategori rumah tangga (seperti `wallet`, `pig-money`, `shopping-bag`, `heart`, `home`, `plane-tilt`, `tools-kitchen-2`, `gift`, `Coffee`) untuk membantu pemindaian visual cepat tanpa hiasan tanpa makna.
- **Mengapa gerakan ini (R-19)**: Menggunakan *MOTION 1* yang tenang berupa perubahan warna dan sedikit angkatan (`hover:-translate-y-0.5 transition-all`) saat kartu kantong disentuh serta transisi modal lembut yang menghormati `prefers-reduced-motion`.
