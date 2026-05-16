<?php

namespace App\Providers;

use App\Contracts\StreamingServiceInterface;
use App\Services\StreamingServiceManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StreamingServiceManager::class);

        $this->app->bind(StreamingServiceInterface::class, function ($app) {
            return $app->make(StreamingServiceManager::class)->default();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
