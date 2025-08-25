<?php

namespace Jsadways\LaravelSDK\Console\Commands\OLD\Traits;

trait stub_files
{
    protected function _prepare_stub_file($path): string
    {
        $loader = $this->_getClassLoader();
        $psr4 = $loader->getPrefixesPsr4();

        $namespace = 'Jsadways\\LaravelSDK\\';
        $stubFilename = "Stubs/{$path}.stub"; // Stub 檔案名稱
        $stub_path_location = '';

        foreach ($psr4 as $prefix => $paths) {
            if (str_starts_with($namespace, $prefix)) {
                foreach ($paths as $path) { // 處理多個路徑
                    $relativePath = str_replace($prefix, '', $namespace);
                    $relativePath = str_replace('\\', '/', $relativePath);
                    $stubPath = $path . '/' . $relativePath . $stubFilename;

                    if (file_exists($stubPath)) {
                        $stub_path_location =  $stubPath;
                    }
                }
            }
        }
        return $stub_path_location;
    }

    protected function _getClassLoader()
    {
        return require base_path('vendor/autoload.php');
    }
}
