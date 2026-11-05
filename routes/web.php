<?php

use App\Http\Controllers\LogoutController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // return view('welcome');
    return to_route('login');
});

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
| - Registrasi publik DITUTUP: akun hanya dibuat oleh administrator (menu User).
| - Lupa / reset password DITUTUP: password hanya direset oleh administrator.
| - Seluruh route bawaan Fortify dinonaktifkan (lihat FortifyServiceProvider).
*/
Route::livewire('/login', 'pages::auth.login')->name('login')->middleware('guest');

Route::post('/logout', LogoutController::class)->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Area Terautentikasi (wajib login + akun aktif)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('/account', 'pages::account.index')->name('account');

    Route::middleware('role:administrator')->group(function () {
        Route::livewire('/user', 'pages::user.index')->name('user');
        Route::livewire('/user/create', 'pages::user.create')->name('user.create');
        Route::livewire('/user/{user}/edit', 'pages::user.edit')->name('user.edit');
        Route::livewire('/role-permission', 'pages::user.role_permission')->name('user.role_permission');
    });
    Route::livewire('/patient', 'pages::patient.index')->name('patient.index')->middleware('permission:pasien-list');
    Route::livewire('/patient/create', 'pages::patient.create')->name('patient.create')->middleware('permission:pasien-tambah');
    Route::livewire('/patient/{patient}/edit/{uuid}', 'pages::patient.edit')->name('patient.edit')->middleware('permission:pasien-edit');

    Route::livewire('/kantong-kehamilan', 'pages::kantong-kehamilan.index')->name('kantong-kehamilan.index');
    Route::redirect('/peta-sebaran', '/dashboard')->name('peta-sebaran');

    Route::livewire('/post', 'pages::post.index')->name('post.index')->middleware('permission:posting-pamflet');
    Route::livewire('/post/create', 'pages::post.create')->name('post.create')->middleware('permission:posting-pamflet');
    Route::livewire('/post/{post}/edit/{uuid}', 'pages::post.edit')->name('post.edit')->middleware('permission:posting-pamflet');

    /* Laporan-Laporan KIA Puskesmas */
    Route::livewire('/laporan/pws-ibu', 'pages::laporan.pws-ibu')->name('laporan.pws-ibu');
    Route::livewire('/laporan/lb3-maternal', 'pages::laporan.lb3-maternal')->name('laporan.lb3-maternal');
    Route::livewire('/laporan/pws-bayi', 'pages::laporan.pws-bayi')->name('laporan.pws-bayi');
    Route::livewire('/laporan/lb3-neonatal', 'pages::laporan.lb3-neonatal')->name('laporan.lb3-neonatal');
});
