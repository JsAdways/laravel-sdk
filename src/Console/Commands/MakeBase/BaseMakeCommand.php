<?php

namespace Jsadways\LaravelSDK\Console\Commands\MakeBase;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Jsadways\LaravelSDK\Console\Commands\Traits\stub_files;

class BaseMakeCommand extends Command
{
    use stub_files;
    protected $name = '';
    protected $description = '';
    protected array $files_to_create = [];

    public function handle()
    {
        foreach($this->files_to_create as $file_name => $param){
            $stubPath = $this->_prepare_stub_file($file_name);
            $stub = File::get($stubPath);

            $filePath = $this->_getPath($param['path']);
            $this->_makeDirectory($filePath);

            File::put($filePath, $stub);
            $this->info("File {$file_name} generated successfully.");
        }
    }

    protected function _getPath($fileName): string
    {
        $path = implode("/",array_map("ucfirst",explode('/',$fileName)));
        return app_path("{$path}.php");
    }

    protected function _makeDirectory($path):void
    {
        if (!File::isDirectory(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true, true);
        }
    }
}
