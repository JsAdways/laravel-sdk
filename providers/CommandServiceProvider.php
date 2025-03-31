<?php

namespace Jsadways\LaravelSDK\Providers;

use Illuminate\Support\ServiceProvider;
use Jsadways\LaravelSDK\Console\Commands\CodeInit;
use Jsadways\LaravelSDK\Console\Commands\InstallSDK;
use Jsadways\LaravelSDK\Console\Commands\MakeBase\InstallBaseClasses;
use Jsadways\LaravelSDK\Console\Commands\MakeBase\InstallExampleClasses;
use Jsadways\LaravelSDK\Console\Commands\MakeBase\InstallRoute;


use Jsadways\LaravelSDK\Console\Commands\MakeClass\MakeClassController;
use Jsadways\LaravelSDK\Console\Commands\MakeClass\MakeClassControllerContract;
use Jsadways\LaravelSDK\Console\Commands\MakeClass\MakeClassRepository;
use Jsadways\LaravelSDK\Console\Commands\MakeClass\MakeClassRepositoryDto;
use Jsadways\LaravelSDK\Console\Commands\MakeClass\MakeClassModel;
use Jsadways\LaravelSDK\Console\Commands\MakeClass\MakeClassEnum;
use Jsadways\LaravelSDK\Console\Commands\MakeClass\MakeRoute;
use Jsadways\LaravelSDK\Console\Commands\GenerateApiDocs;

class CommandServiceProvider extends ServiceProvider
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
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallBaseClasses::class,
                InstallExampleClasses::class,
                InstallRoute::class,

                MakeClassController::class,
                MakeClassControllerContract::class,
                MakeClassRepository::class,
                MakeClassRepositoryDto::class,
                MakeClassModel::class,
                MakeClassEnum::class,
                MakeRoute::class,
                InstallSDK::class,
                CodeInit::class,
                GenerateApiDocs::class
            ]);
        }
    }
}
