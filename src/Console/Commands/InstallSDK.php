<?php

namespace Jsadways\LaravelSDK\Console\Commands;

use Illuminate\Console\Command;
use Throwable;

class InstallSDK extends Command
{
    protected $name = 'laravel-sdk:install';
    protected $description = 'install sdk files';

    public function handle()
    {
        try{
            $this->call('vendor:publish',[
                '--provider' => 'Js\Authenticator\Providers\AuthServiceProvider'
            ]);
            $this->call('install:base-classes');
            $this->call('install:example-classes');
            $this->call('install:route');
        }catch (Throwable $e){
            dump("安裝失敗 - {$e->getMessage()}");
        }
    }
}
