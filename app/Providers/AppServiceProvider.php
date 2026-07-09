<?php

namespace App\Providers;

use App\Services\Encryption\Aes256GcmEncrypter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Aes256GcmEncrypter::class, fn () => Aes256GcmEncrypter::fromConfiguration());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole() && config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }

        if (! $this->app->runningInConsole() && app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
