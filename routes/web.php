<?php

declare(strict_types=1);

use App\Http\Controllers\LogoutController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? to_route('home') : to_route('login');
});

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
Route::livewire('/login', 'pages::auth.login')->name('login')->middleware('guest');

Route::post('/logout', LogoutController::class)->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Area Terautentikasi (3 Menu Utama: Home, Kantong, Rekap)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function () {
    /* 1. Menu Home (Detail Kantong Utama) */
    Route::livewire('/home', 'pages::home')->name('home');

    /* 2. Menu Kantong (List Seluruh Kantong Keluarga) */
    Route::livewire('/pockets', 'pages::pocket.index')->name('pockets.index');

    /* Detail Kantong Tertentu (Diakses dari tombol titik tiga pada kartu kantong) */
    Route::livewire('/pockets/{wallet}', 'pages::pocket.show')->name('pockets.show');

    /* 3. Menu Rekap (Rekap Seluruh Mutasi Transaksi) */
    Route::livewire('/recap', 'pages::recap.index')->name('recap.index');

    /* Alias Redirect untuk Kompatibilitas URL Lama */
    Route::get('/dashboard', fn () => redirect()->route('home'))->name('dashboard');
    Route::get('/transactions', fn () => redirect()->route('recap.index'))->name('transactions.index');

    /* Profil & Akun */
    Route::livewire('/account', 'pages::account.index')->name('account');
});
