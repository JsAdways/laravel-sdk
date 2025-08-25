<?php

namespace Jsadways\LaravelSDK\Console\Commands\OLD\MakeBase;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Jsadways\LaravelSDK\Console\Commands\OLD\Traits\route_method;
use Jsadways\LaravelSDK\Console\Commands\OLD\Traits\stub_files;

class InstallRoute extends Command
{
    use stub_files,route_method;
    protected $signature = 'install:route';
    protected $description = 'install router file';
    protected string $use_string_insert_target = 'use Illuminate\Support\Facades\Route;';

    public function handle()
    {
        $target_rout_path = base_path('routes')."/api.php";
        $router_file_content = File::get($target_rout_path);

        $stub_base_path = $this->_prepare_stub_file("route/routeBase");
        $stub_base_use_path = $this->_prepare_stub_file("route/routeBaseUse");
        $base_content = File::get($stub_base_path);
        $base_use_content = File::get($stub_base_use_path);
        $router_file_content = $this->_insert_use_string(file_content:$router_file_content,base_use_content:$base_use_content);

        $router_file_content = $router_file_content.$base_content;
        File::put($target_rout_path, $router_file_content);
        $this->info("File routes/api.php generated successfully.");
    }

    protected  function _insert_use_string(string $file_content, string $base_use_content):string
    {
        $position = $this->_check_insert_position($file_content);
        if($position !== false){
            $insert_position = $this->_insert_position($position);
            $file_content = substr($file_content,0,$insert_position) ."\n". $base_use_content. substr($file_content,$insert_position);
        }
        return $file_content;
    }
}
