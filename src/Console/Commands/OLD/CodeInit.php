<?php

namespace Jsadways\LaravelSDK\Console\Commands\OLD;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CodeInit extends Command
{
    protected $signature = 'laravel-sdk:code-init {dir?}';
    protected $description = 'init controllers,models,repositories from migration file';
    protected array $ignore_tables = ['migrations','personal_access_tokens'];
    protected array $allow_dir = ['api'];
    protected string $create_dir = '';

    public function handle()
    {
        if($this->_fit_dir($this->argument('dir'))){
            $db = $this->_get_all_tables();
            foreach ($db as $table){
                $table_name = ucfirst($table['name']);
                $this->call("make:sdk-controller",[
                    'name' => strtoupper($this->create_dir).$table_name."Controller"
                ]);
                $this->call("make:sdk-model",[
                    'name' => $table_name
                ]);
                $this->call("make:sdk-repository",[
                    'name' => $table_name."Repository"
                ]);
                $this->call("make:sdk-route",[
                    'name' => $this->create_dir.$table['name'],
                    'comment' => $table['comment']
                ]);
                $this->call("app:gen-test",[
                    '--model_class' => $table_name
                ]);
            }
        }else{
            $this->error('Dir argument not allow!!');
        }



    }

    protected function _get_all_tables(): array
    {
        $result = [];
        $databaseName = DB::connection()->getDatabaseName();
        $tables = DB::select("SELECT TABLE_NAME, TABLE_COMMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?", [$databaseName]);
        foreach ($tables as $table){
            if(!$this->_is_ignore_table($table->TABLE_NAME)){
                $result[] = [
                    'name' => $table->TABLE_NAME,
                    'comment' => $table->TABLE_COMMENT
                ];
            }
        }

        return $result;
    }

    protected function _is_ignore_table(string $table_name): bool
    {
        return in_array($table_name,$this->ignore_tables);
    }

    protected function _fit_dir(string|null $dir): bool
    {
        $dir = strtolower(trim($dir));
        if($dir === '' || in_array($dir,$this->allow_dir)){
            $this->create_dir = $dir;
            if($dir !== ''){
                $this->create_dir = $this->create_dir.'/';
            }
            return true;
        }

        return false;
    }
}
