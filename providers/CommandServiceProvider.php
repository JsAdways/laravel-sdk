<?php

namespace Jsadways\LaravelSDK\Providers;

use Illuminate\Support\ServiceProvider;
use Jsadways\LaravelSDK\Console\Commands\GenerateApiDocs;
use Jsadways\LaravelSDK\Console\Commands\OLD\CodeInit;
use Jsadways\LaravelSDK\Console\Commands\OLD\InstallSDK;
use Jsadways\LaravelSDK\Console\Commands\OLD\MakeBase\InstallBaseClasses;
use Jsadways\LaravelSDK\Console\Commands\OLD\MakeBase\InstallExampleClasses;
use Jsadways\LaravelSDK\Console\Commands\OLD\MakeBase\InstallRoute;
use Jsadways\LaravelSDK\Console\Commands\OLD\MakeClass\MakeClassController;
use Jsadways\LaravelSDK\Console\Commands\OLD\MakeClass\MakeClassControllerContract;
use Jsadways\LaravelSDK\Console\Commands\OLD\MakeClass\MakeClassEnum;
use Jsadways\LaravelSDK\Console\Commands\OLD\MakeClass\MakeClassModel;
use Jsadways\LaravelSDK\Console\Commands\OLD\MakeClass\MakeClassRepository;
use Jsadways\LaravelSDK\Console\Commands\OLD\MakeClass\MakeClassRepositoryDto;
use Jsadways\LaravelSDK\Console\Commands\OLD\MakeClass\MakeRoute;
use Jsadways\LaravelSDK\Console\Commands\OLD\MakeClass\MakeTest;
use Jsadways\LaravelSDK\Console\Commands\RemoveSDK;

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
                MakeTest::class,
                InstallSDK::class,
                RemoveSDK::class,
                CodeInit::class,
                GenerateApiDocs::class
            ]);
        }
    }
}
