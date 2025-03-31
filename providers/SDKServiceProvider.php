<?php

namespace Jsadways\LaravelSDK\Providers;

use Illuminate\Support\ServiceProvider;

class SDKServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../src/resources/view','laravel-sdk');
    }
}
