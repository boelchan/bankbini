# Project StarterKit Livewire versi ramah lingkungan
Dalam project ini sudah tersedia manajemen user, role, dan permission. Anda cukup mengisi kontennya saja. dengan beberapa custom component menyerupai FLUXUI

## Stack
- TALL Stack
- [ts-ui](https://github.com/tallstackui/tallstackui)
- [daisyui](https://github.com/saadeghi/daisyui)

## Requirement
- PHP 8.3
- Node.js 20+

## Cara Instalasi
Jalankan perintah berikut untuk menyiapkan project:

```bash
composer install
npm install
### development
npm run dev
### production
npm run build

cp .env.example .env

### Setting database
php artisan migrate:fresh --seed
php artisan key:generate
php artisan serve
```

### user default
email : admin@app.com
pass : 123