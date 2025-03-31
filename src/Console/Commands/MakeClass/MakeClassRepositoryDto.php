<?php

namespace Jsadways\LaravelSDK\Console\Commands\MakeClass;

use Illuminate\Support\Str;
use Jsadways\LaravelSDK\Console\Commands\Traits\table_method;

class MakeClassRepositoryDto extends BaseMakeClassCommand
{
    use table_method;

    protected $name = 'make:sdk-repository-dto';
    protected $description = 'Create sdk repository dto class';
    protected string $stub_path = 'repositories/ClassRepositoryDto';
    protected string $base_target_path = '\Core\repositories';
    protected string $target_path = '';
    protected array $replace_tags = [];

    public function handle(): ?bool
    {
        [$method,$class_name] = $this->_filter_method($this->argument('name'));
        $this->target_path = $this->base_target_path."\\".str::studly($class_name)."\\Dtos";

        //get columns
        $columns = $this->_get_table_columns($class_name);
        if($columns){
            //handle columns
            $column_string = "";
            foreach ($columns as $column){
                $pass_check = false;
                $validate_type = $this->_column_type_match($column['Type']);
                if($method === 'Create'){
                    if($validate_type && !$this->_column_ignored($column['Field'])){
                        $pass_check = true;
                    }
                }else{
                    if($validate_type){
                        $pass_check = true;
                    }
                }

                if($pass_check){
                    $required = $this->_column_need_require($column['Null']);
                    $column_string = $column_string . "public readonly ".$required.$validate_type." $".$column['Field'].",\n        ";
                }
            }

            $this->replace_tags['columns'] = $column_string;
        }

        return parent::handle(); // TODO: Change the autogenerated stub
    }

    protected function _column_type_match($type): bool|string
    {
        return match (true){
            str_starts_with($type,"bigint"),str_starts_with($type,"smallint"),str_starts_with($type,"int"),str_starts_with($type,"tinyint") => 'int',
            str_starts_with($type,"varchar") ,str_starts_with($type,"longtext")=> 'string',
            default => false
        };
    }

    protected function _filter_method($name): bool|array
    {
        $name = preg_replace("/Dto$/", "", $name);
        return match (true){
            str_starts_with($name,"Create") => ["Create",ucfirst(preg_replace("/^Create/", "", $name))],
            str_starts_with($name,"Update") => ["Update",ucfirst(preg_replace("/^Update/", "", $name))],
            default => false
        };
    }

    protected function _column_need_require($column_null): string
    {
        return ($column_null === 'NO') ?'' : '?';
    }

    protected function _column_ignored($column_name):bool
    {
        return match($column_name){
            'id' => true,
            default => false
        };
    }
}
