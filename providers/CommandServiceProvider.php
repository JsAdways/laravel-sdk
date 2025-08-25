<?php

namespace Jsadways\LaravelSDK\Providers;

use Illuminate\Support\ServiceProvider;
use Jsadways\LaravelSDK\Console\Commands\GenerateApiDocs;
use Jsadways\LaravelSDK\Console\Commands\GenerateArchitectureCommand;
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
                GenerateArchitectureCommand::class,
                RemoveSDK::class,
                GenerateApiDocs::class
            ]);
        }
    }
}
