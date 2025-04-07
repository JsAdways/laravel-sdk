<?php
namespace Jsadways\LaravelSDK\Managers;

use Jsadways\LaravelSDK\Core\_Consts;
use Jsadways\LaravelSDK\Core\Manager\GetObjectDto;
use Jsadways\LaravelSDK\Exceptions\BaseException;

class PickerManager extends Manager
{
    protected string $__root__ = _Consts::PICKER_ROOT;
    protected GetObjectDto $process_data;
    public function __construct(string $model_name,string $method_name)
    {
        $name = 'BasePicker';
        $UcFirst_method_name = collect(explode('_',$method_name))->reduce(function ($result,$item){
            return $result.ucfirst($item);
        },"");
        if (class_exists($this->__root__.$model_name.ucfirst($method_name).'Picker') || class_exists($this->__root__.$model_name.$UcFirst_method_name.'Picker')){
            $name = $model_name.ucfirst($method_name).'Picker';
        }

        $this->process_data = new GetObjectDto(name:$name,params: ['model_name'=>$model_name]);
    }

    /**
     * @throws BaseException
     */
    public function build():mixed
    {
        return $this->get($this->process_data);
    }
}
