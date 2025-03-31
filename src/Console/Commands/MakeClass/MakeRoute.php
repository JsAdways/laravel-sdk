<?php

namespace Jsadways\LaravelSDK\Console\Commands\MakeClass;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Jsadways\LaravelSDK\Console\Commands\Traits\stub_files;
use Jsadways\LaravelSDK\Console\Commands\Traits\route_method;

class MakeRoute extends Command
{
    use stub_files,route_method;
    protected $signature = 'make:sdk-route {name} {comment}';
    protected $description = 'Create new router file';

    protected string $table_name = '';
    protected string $table_comment = '';
    protected string $controller_name = '';
    protected string $use_string_insert_target = 'use Illuminate\Support\Facades\Route;';
    protected string $route_target = 'web';

    public function handle()
    {
        [$route_target,$table_name] = $this->_separate_route_target($this->argument('name'));
        $this->route_target = $route_target;
        $this->table_name = $table_name;
        $this->table_comment = $this->argument('comment');
        $this->controller_name = Str::studly($this->table_name);
        $target_rout_path = base_path('routes')."/{$this->route_target}.php";

        $stub_element_path = $this->_prepare_stub_file("route/routeElement");
        $stub_element_content = $this->_replace_element_stub_file($stub_element_path);

        $router_file_content = File::get($target_rout_path);
        $router_file_content = $this->_insert_use_controller($router_file_content);

        $router_file_content = $router_file_content.$stub_element_content;

        File::put($target_rout_path, $router_file_content);
        $this->info("Route File generated successfully.");
    }

    protected function _replace_element_stub_file(string $stubPath): array|string
    {
        $replace_data = [
            "{{ comment }}" => $this->table_comment,
            "{{ class }}" => $this->table_name,
            "{{ controller }}" => $this->controller_name,
        ];
        $stub_content = File::get($stubPath);
        foreach ($replace_data as $replace_key => $replace_data_string){
            $stub_content = str_replace($replace_key, $replace_data_string, $stub_content);
        }

        return $stub_content;
    }

    protected function _insert_use_controller(string $file_content):string
    {
        $position = $this->_check_insert_position($file_content);
        if($position !== false){
            $insert_position = $this->_insert_position($position);
            $controller_name = $this->controller_name;
            if($this->route_target !== 'web'){
                $controller_name = strtoupper($this->route_target).'\\'.$controller_name;
            }
            $file_content = substr($file_content,0,$insert_position) . "\nuse App\\Http\\Controllers\\{$controller_name}Controller;" . substr($file_content,$insert_position);
        }
        return $file_content;
    }

    protected function _separate_route_target(string $name):array
    {
        #api/name or web/name or name
        $result = [];
        $parts = explode('/',$name);
        if(count($parts) === 2){
            $result[] = strtolower($parts[0]);
            $result[] = $parts[1];
        }else{
            $result[] = 'web';
            $result[] = $name;
        }

        return $result;
    }
}
