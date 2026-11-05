<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Mendaftarkan service aplikasi.
     *
     * Seluruh route bawaan Fortify (POST /login, register, forgot/reset password,
     * verifikasi email, two-factor, update profil/password) DINONAKTIFKAN.
     * Login hanya melalui komponen Livewire `pages::auth.login`, sedangkan
     * pembuatan akun dan reset password hanya dilakukan oleh administrator.
     */
    public function register(): void
    {
        Fortify::ignoreRoutes();
    }

    /**
     * Bootstrap service aplikasi.
     */
    public function boot(): void
    {
        //
    }
}
