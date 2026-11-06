<?php

declare(strict_types=1);

use App\Http\Controllers\LogoutController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return to_route('login');
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
| Area Terautentikasi (Menu Utama: Dashboard & Transaksi)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function () {
    /* 1. Menu Dashboard (Kotak-Kotak Kantong & Aksi Cepat) */
    Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');

    /* Detail Kantong (Diakses dari Tombol Detail pada Kotak Kantong) */
    Route::livewire('/pockets/{wallet}', 'pages::pocket.show')->name('pockets.show');

    /* 2. Menu Transaksi (Riwayat & Pencatatan) */
    Route::livewire('/transactions', 'pages::transaction.index')->name('transactions.index');

    /* Profil & Akun */
    Route::livewire('/account', 'pages::account.index')->name('account');
});
