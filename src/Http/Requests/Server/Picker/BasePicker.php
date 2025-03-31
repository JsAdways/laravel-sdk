<?php
namespace Jsadways\LaravelSDK\Http\Requests\Server\Picker;

use Jsadways\LaravelSDK\Core\Consts;
use Jsadways\LaravelSDK\Http\Validation\Unit\Operator;
use Jsadways\LaravelSDK\Http\Validation\Unit\Picker;
use Jsadways\LaravelSDK\Http\Validation\Unit\Relation as ValidationRelation;
use Jsadways\LaravelSDK\Managers\ModelManager;
use Jsadways\LaravelSDK\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use JetBrains\PhpStorm\Pure;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class BasePicker
{
    private string $__model_root__ = Consts::MODEL_ROOT;
    protected BaseModel $__model__;
    protected ModelManager $model_manager;
    protected array $_operator_map;

    public function __construct(string $model_name)
    {
        if (class_exists($this->__model_root__.$model_name)) {
            $this->__model__ = new ($this->__model_root__ . $model_name);
        }
        $this->model_manager = new ModelManager;
        $this->_init_default_operator();
    }

    public function get_model(): BaseModel
    {
        return $this->__model__;
    }

    /**
     * Choose Model Picker
     *
     * @param string $option
     * @return Picker
     */
    #[Pure]
    public function get_picker(string $option): Picker
    {
        return match ($option)
        {
            'create' => new Picker(ignore: ['id']),
            'update' => new Picker(ignore: ['creator_id']),
            'delete' => new Picker(select: ['id']),
        };
    }

    /**
     * @throws ReflectionException
     */
    public function get_create_relations(): array
    {
        return $this->_get_validation_relation('create');
    }

    /**
     * @throws ReflectionException
     */
    public function get_update_relations(): array
    {
        return $this->_get_validation_relation('update');
    }

    /**
     * @throws ReflectionException
     */
    public function get_delete_relations(): array
    {
        return $this->_get_validation_relation('delete');
    }

    /**
     * @throws ReflectionException
     */
    private function _get_validation_relation($operator): array
    {
        $relation_list = $this->_get_available_relations();

        $validation_relation = [];
        foreach ($relation_list as $relation)
        {
            $relation_models = $this->model_manager->get_relation_model($this->__model__, $relation);
            $validation_relation[] = new ValidationRelation($relation, end($relation_models), ...$this->_operator_map[$operator]);
        }

        return $validation_relation;
    }

    /**
     * 初始化 operator
     *
     * @return void
     */
    private function _init_default_operator(): void
    {
        $create_operator = new Operator(option: 'create', ignore: ['id'], is_require: false);
        $update_operator = new Operator(option: 'update', ignore: ['creator_id'], is_require: false);
        $delete_operator = new Operator(option: 'delete', select: ['id'], is_require: false);

        $this->_operator_map = [
            'create' => [$create_operator],
            'update' => [$create_operator, $update_operator, $delete_operator],
            'delete' => [$delete_operator],
        ];
    }

    /**
     * 取得 Model 第一層關聯
     *
     * @return array
     * @throws ReflectionException
     */
    private function _get_available_relations(): array
    {
        return array_keys(array_reduce(
            (new ReflectionClass($this->__model__))->getMethods(ReflectionMethod::IS_PUBLIC),
            function ($result, ReflectionMethod $method) {
                // If this function has a return type
                ($returnType = (string) $method->getReturnType()) &&

                // And this function returns a relation
                is_subclass_of($returnType, Relation::class) &&

                // Not Relate to Father
                ($returnType !== BelongsTo::class) &&

                // Add name of this method to the relations array
                ($result = array_merge($result, [$method->getName() => $returnType]));

                return $result;
            }, []
        ));
    }

    /**
     *  建立 validation relation
     *
     * @param string $relation
     * @param array<Operator> $operator_list
     * @return ValidationRelation
     */
    final protected function _make_validation_relation(string $relation, array $operator_list): ValidationRelation
    {
        return new ValidationRelation(
            $relation,
            $this->_get_relation_model($relation),
            ...$operator_list
        );
    }

    private function _get_relation_model(string $relation)
    {
        $models = $this->model_manager->get_relation_model($this->__model__, $relation);
        return end($models);
    }
}
