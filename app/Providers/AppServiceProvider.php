<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Blaze\Blaze;
use TallStackUi\Facades\TallStackUi;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blaze::optimize()
            ->in(resource_path('views/components'))
            ->in(resource_path('views/pages'));


        TallStackUi::customize()
            ->globals()
            ->colorful(toast: true, dialog: false);
    }
}
