<?php

declare(strict_types=1);

use App\Http\Controllers\LogoutController;
use App\Http\Controllers\ReportExportController;
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
| Area Terautentikasi (Wajib Login + Akun Aktif)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');

    /* Manajemen Kantong (Pocket) */
    Route::livewire('/pockets', 'pages::pocket.index')->name('pockets.index');
    Route::livewire('/pockets/{wallet}', 'pages::pocket.show')->name('pockets.show');

    /* Transaksi Pemasukan, Pengeluaran & Riwayat Global */
    Route::livewire('/transactions', 'pages::transaction.index')->name('transactions.index');

    /* Transfer Antar Kantong */
    Route::livewire('/transfers', 'pages::transfer.index')->name('transfers.index');

    /* Laporan Keuangan & Export */
    Route::livewire('/reports', 'pages::report.index')->name('reports.index');
    Route::get('/reports/export/excel', [ReportExportController::class, 'exportExcel'])->name('reports.export.excel');
    Route::get('/reports/export/pdf', [ReportExportController::class, 'exportPdf'])->name('reports.export.pdf');

    /* Pengaturan Akun & Kategori */
    Route::livewire('/account', 'pages::account.index')->name('account');

    /* Manajemen Pengguna (Khusus Administrator) */
    Route::middleware('role:administrator')->group(function () {
        Route::livewire('/user', 'pages::user.index')->name('user');
        Route::livewire('/user/create', 'pages::user.create')->name('user.create');
        Route::livewire('/user/{user}/edit', 'pages::user.edit')->name('user.edit');
    });
});
