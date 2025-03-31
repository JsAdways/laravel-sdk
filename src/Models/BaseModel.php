<?php

namespace Jsadways\LaravelSDK\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Jsadways\ScopeFilter\ScopeFilterTrait;

abstract class BaseModel extends Model
{
    use HasFactory,ScopeFilterTrait;

    public function __construct(array $attributes = [])
    {
        // 動態建立 Fillable
        $this->fillable = array_keys($this->_schema());
        // Model Class 初始化會檢查 Fillable
        parent::__construct($attributes);
    }

    public function get_table_name(): string
    {
        return $this->table;
    }

    # 返回 Model 的完整驗證內容
    abstract protected function _schema(): array;

    /**
     * 取得模型欄位驗證
     *
     * @param array $select
     * @param array $ignore
     *
     * @return array
     */
    public static function get_schema(array $select=Null, array $ignore=Null): array
    {
        $model_schema = (new static)->_schema();
        $validation = ['id' => 'required|integer', ...$model_schema];
        return static::_pick_schema($validation, $select, $ignore);
    }

    /**
     * 選取模型欄位
     *
     * @param array $all_schema
     * @param array $select
     * @param array $ignore
     *
     * @return array
     */
    private static function _pick_schema(array $all_schema, ?array $select, ?array $ignore): array
    {
        $columns = [];
        if ($select)
        {
            $columns = array_intersect(array_keys($all_schema), $select);
        }
        elseif ($ignore)
        {
            $columns = array_diff(array_keys($all_schema), $ignore);
        }

        $choose = [];
        foreach ($columns as $column)
        {
            $choose[$column] = $all_schema[$column];
        }
        return !empty($choose) ? $choose : $all_schema;
    }
}
