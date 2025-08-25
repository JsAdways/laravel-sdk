<?php

namespace Jsadways\LaravelSDK\Console\Commands\OLD\MakeClass;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Jsadways\LaravelSDK\Console\Commands\OLD\Traits\stub_files;

class BaseMakeClassCommand extends GeneratorCommand
{
    use stub_files;
    protected $name = '';
    protected $description = '';
    protected string $target_path = '';
    protected string $stub_path = '';
    protected array $replace_tags = [];

    protected function getStub(): string
    {
        return $this->_prepare_stub_file($this->stub_path);
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . $this->target_path;
    }

    protected function getNameInput(): string
    {
        return Str::studly(trim($this->argument('name')));
    }

    protected function buildClass($name): array|string
    {
        $stub = $this->files->get($this->getStub());

        $namespace = $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
        $replacedStub = $namespace;
        foreach ($this->replace_tags as $tag => $text){
            $replacedStub = str_replace("{{ {$tag} }}", $text, $replacedStub);
        }

        return $replacedStub;
    }
}
