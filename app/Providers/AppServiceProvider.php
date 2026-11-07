<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Livewire\Blaze\Blaze;
use TallStackUi\Facades\TallStackUi;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Mendaftarkan layanan aplikasi.
     */
    public function register(): void
    {
        //
    }

    /**
     * Menjalankan konfigurasi awal layanan aplikasi.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Blaze::optimize()
            ->in(resource_path('views/components'))
            ->in(resource_path('views/pages'));

        TallStackUi::customize()
            ->globals()
            ->colorful(toast: true, dialog: false);

        TallStackUi::customize()
            ->form('radio.group')
            ->block('columns.3', 'grid-cols-3');
    }
}
