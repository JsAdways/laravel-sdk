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
        foreach ($this->files_to_create as $base_path =>$files){
            foreach ($files as $file_name => $param){
                $stubPath = $this->_prepare_stub_file($file_name);
                $stub = File::get($stubPath);

                $filePath = $this->_getPath($base_path,$param['path_method'],$param['path']);
                $this->_makeDirectory($filePath);

                File::put($filePath, $stub);
                $this->info("File {$file_name} generated successfully.");
            }
        }
    }

    protected function _getPath(string $base_path,string $path_method,string $fileName): string
    {
        $path = implode("/",array_map($path_method,explode('/',$fileName)));
        return $base_path("{$path}.php");
    }

    protected function _makeDirectory(string $path):void
    {
        if (!File::isDirectory(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true, true);
        }
    }
}
