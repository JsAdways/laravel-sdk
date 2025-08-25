<?php

namespace Jsadways\LaravelSDK\Console\Commands\OLD\MakeClass;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeClassEnum extends BaseMakeClassCommand
{
    protected $signature = 'make:sdk-enum {name} {--cases=}';
    protected $description = 'Create sdk enum class';
    protected string $stub_path = 'core/Enum/Enum';
    protected string $target_path = '\Core\\Enums';
    protected string $case_stub_path = 'core/Enum/EnumCase';
    protected array $replace_tags = [];

    /**
     * Override
     * 重新處理name參數
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);
        if ($input->hasArgument('name')) {
            $name = implode('/',array_map('ucfirst',explode('/',$input->getArgument('name'))));
            $input->setArgument('name',$name);
        }
    }

    /**
     * 取得 case stub
     */
    protected function get_case_stub(): string
    {
        return $this->_prepare_stub_file($this->case_stub_path);
    }

    /**
     * Override
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the service'],
            ['cases', InputArgument::OPTIONAL, 'The cases you want to add to the enum (separated by a comma)'],
        ];
    }

    /**
     * Override
     * 轉換 Repository 模板內部動態內容，例 {{ name }}Repository 轉 UserRepository
     *
     * @param string $name
     * @throws FileNotFoundException
     */
    protected function buildClass($name):array|string
    {
        $cases = $this->option('cases') ? $this->_get_case_list() : '';
        $this->replace_tags['cases'] = $cases;

        return parent::buildClass($name);
    }

    /**
     * 取得 case 轉換文字內容
     *
     * @return string
     * @throws FileNotFoundException
     */
    private function _get_case_list(): string
    {
        $case_list = explode(',',$this->option('cases'));
        $methods =[];
        foreach ($case_list as $index => $case) {
            $methods[] = str_replace(['{{ case }}','{{ index }}'],[$case , $index],$this->files->get($this->get_case_stub()));
        }

        return implode(PHP_EOL, $methods);
    }
}
