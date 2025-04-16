<?php

namespace Jsadways\LaravelSDK\Http\Requests\Server;

use Illuminate\Support\Arr;
use Jsadways\LaravelSDK\Exceptions\BaseException;
use Jsadways\LaravelSDK\Http\Requests\Server\Picker\BasePicker;
use Jsadways\LaravelSDK\Managers\PickerManager;
use Jsadways\LaravelSDK\Http\Validation\ModelSchema;
use Jsadways\LaravelSDK\Http\Validation\Unit\Picker;
use Jsadways\LaravelSDK\Http\Validation\ValidationSchema;
use Illuminate\Foundation\Http\FormRequest;
use JetBrains\PhpStorm\Pure;
use ReflectionException;

class ServerRequest extends FormRequest
{
    protected array $validation_schemas = [];
    protected BasePicker $picker_object;

    /**
     * @throws BaseException
     */
    public function create_validation(string $model_name=Null, string $method_name=Null): static
    {
        if($model_name && $method_name){
            $this->picker_object = (new PickerManager(model_name:$model_name,method_name: $method_name))->build();
            if(method_exists($this->picker_object,'rules')){
                $this->validation_schemas = $this->picker_object->rules();
            }else{
                /**
                 * @see _get_create_relations,_get_update_relations,_get_delete_relations
                 */
                $model_schema = new ModelSchema(
                    model: $this->picker_object->get_model(),
                    model_picks: $this->_get_picker($method_name),
                    relations: $this->{"_get_{$method_name}_relations"}(),
                    option: $method_name
                );

                $this->validation_schemas = (new ValidationSchema(model_schema:$model_schema))->gen_schemas()();
            }
        }

        return $this;
    }

    public function authorize(): bool
    {
        return True;
    }

    # 驗證規則
    public function rules(): array
    {
        return $this->validation_schemas;
    }

    # 驗證picker relation相關
    #[Pure]
    protected function _get_picker(string $option): Picker
    {
        return $this->picker_object->get_picker($option);
    }

    /**
     * @throws ReflectionException
     */
    protected function _get_create_relations(): array
    {
        return $this->picker_object->get_create_relations();
    }

    /**
     * @throws ReflectionException
     */
    protected function _get_update_relations(): array
    {
        return $this->picker_object->get_update_relations();
    }

    /**
     * @throws ReflectionException
     */
    protected function _get_delete_relations(): array
    {
        return $this->picker_object->get_delete_relations();
    }

    /**
     * Get the validated data from the request.
     *
     * @param  array|int|string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function validated($key = null, $default = null): array
    {
        return data_get($this->json()->all(), $key, $default);
    }
}
