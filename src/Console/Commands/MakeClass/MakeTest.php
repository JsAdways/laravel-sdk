<?php

namespace Jsadways\LaravelSDK\Console\Commands\MakeClass;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Jsadways\LaravelSDK\Console\Commands\Traits\stub_files;
use Jsadways\LaravelSDK\Core\Manager\GetObjectDto;
use Jsadways\LaravelSDK\Managers\ModelManager;
use Jsadways\LaravelSDK\Models\BaseModel;
use Illuminate\Console\Command;
use Faker\Factory as Faker;
use Faker\Generator;

class MakeTest extends Command
{
    use stub_files;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature =
        'app:gen-test
    {--action= : 想要測試的行為，目前提供 [All(預設全部), Create, Update, Delete, Read]。}
    {--model_class= : 想要測試的 Model 名稱。}
    {--group_name= : 測試組別名稱。}
    {--api= : 測試的 API 名稱。}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成測試程式碼模板';
    protected Generator $faker;

    /**
     * 執行指令.
     */
    public function handle()
    {
        $this->faker = Faker::create();
        $action = $this->option('action');
        $actions = $action ? [ucfirst($action)] : ['Create', 'Update', 'Delete', 'Read'];
        foreach ($actions as $action)
        {
            $this->_gen_tests($action);
        }
    }

    protected function _gen_tests(string $action)
    {
        # 初始化參數
        $model_manager = new ModelManager();
        # 指令參數
        $model_class = Str::studly($this->option('model_class'));
        $group_name = $this->option('group_name');
        $api = $this->option('api');

        # 生成路徑
        $directory = base_path("tests/Feature/{$model_class}");
        $this->_gen_directory($directory);

        # 內容參數
        $_model = $this->_get_model($model_manager, strtolower($model_class));
        $namespace = "Tests\Feature"."\\{$model_class}";
        $class_name = "{$action}{$model_class}Test";
        $table_name = $this->_get_table_name($_model);
        $data_count = $action === 'Read' ? 3 : 1;  # read 使用
        $model_root = $model_manager->get_root()."{$model_class}";
        $api = $api ?? "/api/{$table_name}";
        $example_payload = $this->_gen_example_payload($_model, $data_count);

        # 生成常數
        $consts_stub = File::get($this->_prepare_stub_file("test/_consts"));
        //$consts_stub = file_get_contents(base_path("stubs/test/_consts.stub"));
        $consts_fields = [
            'namespace' => $namespace,
            'api' => "{$api}",
            'model_class' => $model_class
        ];
        $this->_gen_file($consts_stub, $directory."/_Consts.php", ...$consts_fields);

        # 生成測試
        $_action = strToLower($action);
        $test_stub = File::get($this->_prepare_stub_file("test/{$_action}"));
        //$test_stub = file_get_contents(base_path("stubs/test/{$_action}.stub"));
        $test_fields = [
            'namespace' => $namespace,
            'group_name' => $group_name ?? $table_name,
            'class_name' => $class_name,
            'model_class' => $model_class,  # 使用模板：delete, update, read
            'model_root' => $model_root,
            'example_payload' => $example_payload,
            'table_name' => $table_name,
            'data_count' => $data_count,
        ];
        $this->_gen_file($test_stub, $directory."/{$class_name}.php", ...$test_fields);
        $this->info("Test File {$class_name} generated successfully.");
    }

    protected function _gen_directory($directory): void
    {
        if (!is_dir($directory))
        {
            mkdir($directory, 0755, true);
        }
    }

    protected function _gen_file($stub, $file_path, ...$columns)
    {
        # 生成替換內容清單 -> ['{{ columnA }}' => 'valueA', ...]
        $content_mapping = [];
        foreach ($columns as $column => $value)
        {
            $content_mapping["{{ {$column} }}"] = $value;
        }

        # 生成替換內容
        $content = str_replace(array_keys($content_mapping), array_values($content_mapping),$stub);

        # 將替換內容放入文件
        File::put($file_path, $content);
        //file_put_contents($file_path, $content);
    }

    protected function _get_model(ModelManager $model_manager, string $model_class): BaseModel
    {
        return $model_manager->get(new GetObjectDto($model_class));
    }

    protected function _gen_example_payload(BaseModel $model, int $count=1): string
    {
        $patterns = [];
        for ($times = $count; $times > 0; $times--)
        {
            $patterns[] = $this->_make_payload($model);
        }
        if ($count === 1)
        {
            $result = $patterns[0];
        }
        else
        {
            $p = implode(", \n", $patterns);
            $result = "[{$p}]";
        }
        return $result;
    }

    protected function _make_payload(BaseModel $model)
    {
        # return "['keyA' => 'valueA', 'keyB' => 'valueB', 'keyC' => 'valueC', ...]"
        $pattern = '';
        foreach ($model->get_table_info() as $info)
        {
            if ($info->name !== 'id'){
                if ($info->required) {
                    $pattern .= "'{$info->name}' => '{$this->_gen_faker_date(type:$info->type,name:$info->name,length: $this->_fit_string_length($info->length))}',  # {$info->comment} \n";
                }else{
                    $pattern .= "'{$info->name}' => Null,  # {$info->comment} \n";
                }
            }
        }
        return "[{$pattern}]";
    }

    protected function _gen_faker_date(string $type, string $name, int|null $length):string | bool
    {
        return match (true){
            str_ends_with($name,'_email') => $this->faker->email,
            str_ends_with($name,'address') => $this->faker->streetAddress,
            $this->_match_column_type($type) === 'string' => $this->faker->text($length),
            $this->_match_column_type($type) === 'int' => $this->faker->numberBetween(1,3),
            default => false
        };
    }

    protected function _match_column_type(string $type): string
    {
        return match ($type){
            'varchar','longtext','text' => 'string',
            'tinyint','smallint','bigint','int' => 'int'
        };
    }

    protected function _fit_string_length(int|null $length): int|null
    {
        return ($length !== null && $length > 30) ? 30:$length;
    }

    protected function _get_table_name(BaseModel $model): string
    {
        return $model->get_table_name();
    }
}
