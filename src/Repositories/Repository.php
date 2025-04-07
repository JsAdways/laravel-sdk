<?php

namespace Jsadways\LaravelSDK\Repositories;

use Jsadways\LaravelSDK\Core\_Consts;
use ReflectionException;
use Throwable;
use Jsadways\LaravelSDK\Core\Dto;
use Jsadways\LaravelSDK\Core\ReadListParamsDto;
use Jsadways\LaravelSDK\Exceptions\RepositoryException;
use Jsadways\LaravelSDK\Models\BaseModel;
use Jsadways\LaravelSDK\Traits\UseRepository;
use Jsadways\LaravelSDK\Traits\LogMessage;
use Illuminate\Database\Eloquent\{Model, Collection};
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Database\Eloquent\Relations\Relation;
use Closure;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repository
{
    use LogMessage, UseRepository;

    public ?string $__model_class__ = Null;
    protected array $__read_relations__ = [];
    protected array $__create_relations__ = [];
    protected array $__optional_relations__ = [];
    protected array $__delete_relations__ = [];

    protected string $__model_root__ = _Consts::MODEL_ROOT;
    protected ?BaseModel $__model__ = Null;

    public function get_model(): BaseModel
    {
        return $this->__model__;
    }

    /**
     * @throws ReflectionException
     */
    public function __construct($model_class=Null, $read_relations=Null, $optional_relations=Null, $create_relations=Null, $delete_relations=Null)
    {
        $this->__model__ = new ($model_class ?? $this->__model_class__ ?? $this->_default_model());
        $default_relations = $this->_init_relations();

        $this->__read_relations__ = $read_relations ?? $this->__read_relations__;
        $this->__create_relations__ = $create_relations ?? ($this->__create_relations__ === [] ? $default_relations['__create_relations__']:$this->__create_relations__);
        $this->__optional_relations__ = $optional_relations ?? ($this->__optional_relations__ === [] ? $default_relations['__optional_relations__']:$this->__optional_relations__);
        $this->__delete_relations__ = $delete_relations ?? $this->__delete_relations__;
    }

    /**
     * relations 根據 scheme 設定資料進行初始化
     *
     * @return array
     * @throws ReflectionException
     */
    private function _init_relations(): array
    {
        $relations = [];
        foreach (['create', 'optional'] as $option) {
            $relations["__{$option}_relations__"] = $this->_get_available_relations();
        }

        return $relations;
    }

    private function _default_model(): string
    {
        # 獲得 Repository class 名稱
        $repository = class_basename(static::class);
        # 組裝 Model class 路徑
        $default_model = str_replace('Repository', '', $repository);
        return $this->__model_root__ . $default_model;
    }

    public function get_relations(string $value)
    {
        return match ($value)
        {
            'read' => $this->__read_relations__,
            'create' => $this->__create_relations__,
            'update' => $this->__optional_relations__,
            'delete' => $this->__delete_relations__,
        };
    }

    /**
     * 取得 Model 第一層關聯
     *
     * @return array
     * @throws ReflectionException
     */
    protected function _get_available_relations(): array
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
     * Database 上下文執行器
     *
     * @param Closure $func
     * @return mixed
     * @throws RepositoryException
     */
    public function execute(Closure $func): mixed
    {
        try{
            DB::beginTransaction();
            $result = $func();
            DB::commit();
            return $result;
        }
        catch (Throwable $throwable)
        {
            DB::rollBack();
            throw new RepositoryException("資料庫執行錯誤: {$throwable->getMessage()}", payload: ['func' => $func]);
        }
    }

    /**
     * 新增
     *
     * @param Dto $params 參數包
     * @param Model|null $model
     * @param array|null $relation
     * @return Model
     * @throws RepositoryException
     */
    public function create(Dto $params, ?Model $model=Null, ?array $relation=Null): Model
    {
        $payload = $params->to_array();
        $relations = $relation !== Null ? $relation : $this->__create_relations__;
        $model = $model ? new ($model) : $this->__model__;
        try{
            DB::beginTransaction();
            $instance = $model->create($payload);
            $this->_relation_processor($instance, $payload, $relations);
            $instance->load($relations);
            DB::commit();
            return $instance;
        }catch (Throwable $throwable)
        {
            DB::rollBack();
            throw new RepositoryException("{$model}新增錯誤: {$throwable->getMessage()}", payload: $payload);
        }
    }

    public function bulk_create(array $rows, ?Model $model=Null)
    {
        $model = $model ? new ($model) : $this->__model__;
        try{
            DB::beginTransaction();
            $instance = $model->insert($rows);
            DB::commit();
            return $instance;
        }catch (Throwable $throwable)
        {
            DB::rollBack();
            throw new RepositoryException("{$model}批量新增錯誤: {$throwable->getMessage()}", payload: $rows);
        }
    }

    /**
     * 更新
     * @param Dto $params 參數包
     * @param Model|null $model
     * @param array|null $relation
     * @return Model|null
     * @throws RepositoryException
     */
    public function update(Dto $params, ?Model $model=Null, ?array $relation=Null): ?Model
    {
        $payload = $params->to_array();
        $relations = $relation !== Null ? $relation : $this->__optional_relations__;
        $model = $model ? new ($model) : $this->__model__;
        try {
            DB::beginTransaction();
            $instance = $this->read_model(model: $model, filter: ['id_eq' => $payload['id']]);
            if (!$instance){
                throw new RepositoryException('資料不存在。', payload: $payload);
            }
            # 關聯資料處理
            $this->_relation_processor($instance, $payload, $relations);
            $instance->update($payload);
            $instance->load($relations);
            DB::commit();
            return $instance;
        }catch (Throwable $throwable){
            DB::rollBack();
            throw new RepositoryException("{$model}更新錯誤: {$throwable->getMessage()}", payload: $payload);
        }
    }

    /**
     * 查詢
     * @param array $filter 查詢條件
     * @param Model|null $model
     * @param array|null $relation 關聯資料表 預設[]
     * @return Model|null
     * @throws RepositoryException
     */
    public function read_model(array $filter, ?Model $model=Null, ?array $relation=Null): Model|null
    {
        $relations = $relation !== Null ? $relation : $this->__optional_relations__;
        $model = $model ? new ($model) : $this->__model__;
        try {
            return $model->with($relations)->filter($filter)->first();
        } catch (Throwable $throwable) {
            throw new RepositoryException($this->get_error($throwable));
        }
    }

    public function read_or_create(Dto $params, ?Model $model=Null): array
    {
        $model = $model ? new ($model) : $this->__model__;
        $filter = [];
        foreach ($params->to_array() as $key => $value)
        {
            if (is_array($value)){continue;}
            $filter[$key] = $value;
        }
        $instance = $model->firstOrCreate($filter, $params->to_array());
        return [$instance->wasRecentlyCreated, $instance];
    }

    public function has_model(array $filter, ?Model $model=Null): bool
    {
        return ($model ?? $this->__model__)->filter($filter)->exists();
    }

    /**
     * 查詢
     * @param array $filter 查詢條件
     * @param Model|null $model
     * @return int
     * @throws RepositoryException
     */
    public function count(array $filter, ?Model $model=Null): int
    {
        $model = $model ? new ($model) : $this->__model__;
        try {
            return $model->filter($filter)->count();
        } catch (Throwable $throwable) {
            throw new RepositoryException($this->get_error($throwable));
        }
    }

    /**
     * 刪除
     * @param int $id
     * @param Model|null $model
     * @return bool
     * @throws RepositoryException
     */
    public function delete_model(int $id, ?Model $model=Null): bool
    {
        $model = $model ? new ($model) : $this->__model__;
        if (!$this->has_model(['id_eq'=>$id], $model)){
            throw new RepositoryException('該筆資料不存在，無法刪除。');
        }

        try {
            $instance = (new $model)->find($id);
            $relations = $this->__delete_relations__;
            foreach ($relations as $relation)
            {
                $instance->{$relation}()->delete();
            }
            $instance->delete();
            DB::commit();
            return True;
        } catch (Throwable $throwable) {
            DB::rollBack();
            throw new RepositoryException("{$this->__model_class__}刪除錯誤: {$throwable->getMessage()}", payload: ['id' => $id]);
        }
    }

    /**
     * 查詢多筆資料
     *
     * @param ReadListParamsDto $params 查詢參數
     * @param Model|null $model
     * @param array|null $relation 關聯資料表 預設[]
     * @return LengthAwarePaginator|Collection
     * @throws RepositoryException
     */
    public function read_models(ReadListParamsDto $params, ?Model $model=Null, ?array $relation=Null): LengthAwarePaginator|Collection
    {
        $model = $model ? new ($model) : $this->__model__;
        $relations = $relation !== Null ? $relation : $this->__read_relations__;
        try{
            $query = $model
                ->query()
                ->select($params->select)
                ->with($relations)
                ->filter($params->filter)
                ->orderBy($params->sort_by, $params->sort_order);
            return ($params->per_page > 0) ? $query->paginate($params->per_page, $params->select, 'page') : $query->get();
        } catch (Throwable $throwable) {
            throw new RepositoryException("{$this->__model_class__}查詢錯誤: {$throwable->getMessage()}", payload: $params->to_array());
        }
    }

    # 解析關聯、關聯資料處理(增、刪、改)
    protected function _relation_processor(Model $instance, array $payload, ?array $relations=Null): void
    {
        $relations_ = $relations ? $relations : $this->__optional_relations__;
        foreach ($relations_ as $relation_)
        {
            if ($data = Arr::pull($payload, "create_{$relation_}", False))
            {
                $instance->{$relation_}()->createMany($data);
            }
            if ($data = Arr::pull($payload, "update_{$relation_}", False))
            {
                $table = $instance->{$relation_}()->getRelated();
                batch()->update($table, $data, 'id');
            }
            if ($data = Arr::pull($payload, "delete_{$relation_}", False))
            {
                $instance->{$relation_}()->whereIn('id', $data)->delete();
            }
        }
    }
}
