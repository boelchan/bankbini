# Panduan Menyiapkan Notifikasi Transaksi FCM

Notifikasi dikirim lewat Firebase Cloud Messaging (FCM) HTTP v1. Yang menerima notifikasi adalah perangkat HP yang sudah mengaktifkan notifikasi, bukan browser desktop. Saat transaksi dibuat, notifikasi dikirim ke akun aktif lain, bukan ke akun yang menginput.

## 1. Buat project Firebase

1. Buka https://console.firebase.google.com/ lalu buat project baru, misalnya `bank-bini`.
2. Matikan Google Analytics bila tidak dipakai.
3. Buka **Project settings > Cloud Messaging**, lalu aktifkan **Cloud Messaging API (V1)**.
4. Di halaman yang sama, salin **Sender ID** dan **Project ID**.

## 2. Tambahkan Web App

1. Di **Project settings > Your apps**, pilih ikon web dan daftarkan aplikasi, misalnya `Bank BINI Web`.
2. Salin nilai konfigurasi web: `apiKey`, `authDomain`, `projectId`, `messagingSenderId`, dan `appId`.
3. Buka tab **Cloud Messaging** pada aplikasi web tersebut, lalu di bagian **Web Push certificates** pilih **Generate key pair**. Salin **public key** yang muncul. Nilai inilah yang dipakai sebagai `FIREBASE_VAPID_KEY`.

## 3. Buat service account untuk server Laravel

1. Buka **Project settings > Service accounts**.
2. Klik **Generate new private key**, lalu unduh file JSON.
3. Simpan file tersebut di `storage/app/private/firebase-service-account.json`.
4. Jangan commit file JSON ini. File sudah masuk `.gitignore`.

## 4. Isi file `.env`

Tambahkan atau isi nilai berikut pada `.env`:

```dotenv
FCM_ENABLED=true
FCM_SERVICE_ACCOUNT_PATH=firebase-service-account.json
FCM_PROJECT_ID=proyek-firebase-anda
FCM_TTL_SECONDS=10800

FIREBASE_API_KEY=api-key-web-app
FIREBASE_AUTH_DOMAIN=proyek-firebase-anda.firebaseapp.com
FIREBASE_PROJECT_ID=proyek-firebase-anda
FIREBASE_MESSAGING_SENDER_ID=sender-id
FIREBASE_APP_ID=app-id-web-app
FIREBASE_VAPID_KEY=public-key-web-push
```

`FCM_PROJECT_ID` dan `FIREBASE_PROJECT_ID` biasanya berisi nilai yang sama. `FIREBASE_VAPID_KEY` adalah public key dari Web Push certificates, bukan private key service account.

## 5. Jalankan migrasi dan build aset

```bash
php artisan migrate
npm run build
php artisan config:clear
php artisan view:clear
```

Jika memakai `config:cache` di produksi, jalankan `php artisan config:cache` setelah `.env` final.

## 6. Aktifkan notifikasi di HP

1. Buka aplikasi di **Chrome Android** menggunakan alamat HTTPS. Web push tidak bekerja lewat `http://` biasa, termasuk alamat LAN seperti `http://192.168.1.5:8000`.
2. Login sebagai akun yang ingin menerima notifikasi.
3. Buka menu **Akun**, lalu pilih **Aktifkan Notifikasi HP**.
4. Izinkan notifikasi saat browser meminta izin.
5. Ulangi pada HP akun lain bila ingin kedua akun menerima notifikasi.

Status akan berubah menjadi **Notifikasi HP Aktif**. Pilih menu tersebut lagi untuk mematikan notifikasi pada HP itu.

## 7. Uji alur notifikasi

1. Pastikan akun penerima sudah aktif dan token HP-nya tersimpan.
2. Login sebagai akun lain, lalu buat transaksi masuk, keluar, atau pindah uang pada kantong mana pun.
3. HP penerima seharusnya menampilkan notifikasi berisi nama penginput, jenis transaksi, nominal, dan nama kantong.
4. Mengetuk notifikasi membuka halaman detail kantong terkait.

Notifikasi tidak dikirim ke akun yang sedang menginput. Transaksi pindah uang mencatat dua baris transaksi, sehingga FCM dapat mengirim dua notifikasi untuk satu aksi pindah.

## 8. Catatan operasional

- Token disimpan di tabel `fcm_tokens` dengan relasi ke `users`. Token yang sudah tidak terdaftar di FCM dihapus otomatis saat FCM merespons `UNREGISTERED`.
- Hanya perangkat yang terdeteksi sebagai HP Android atau iPhone yang menampilkan tombol aktivasi. Desktop sengaja dilewati.
- Jika tombol tidak muncul, periksa user agent browser atau buka lewat menu Akun pada HP.
- Jika notifikasi tidak sampai, periksa `storage/logs/laravel.log` untuk pesan konfigurasi FCM, izin notifikasi browser, dan koneksi HTTPS.
- Aktifkan HTTPS produksi atau gunakan tunnel HTTPS saat menguji dari HP. Tanpa itu, service worker dan subscription tidak dapat didaftarkan.
